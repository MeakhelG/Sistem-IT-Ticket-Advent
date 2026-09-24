<?php
namespace App\Services;

use App\Config\Database;
use App\Utils\FileUploader;
use PDO;
use Exception;

class ReplyService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Mengambil balasan percakapan (Menyembunyikan is_internal jika bukan Tim IT)
     */
    public function getReplies(int $ticketId, array $currentUser): array {
        $sql = "
            SELECT r.*, u.name as user_name, u.role as user_role, u.department as user_department
            FROM ticket_replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.ticket_id = ?
        ";

        if ($currentUser['role'] !== 'it') {
            $sql .= " AND r.is_internal = 0";
        }

        $sql .= " ORDER BY r.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }

    /**
     * Menambahkan balasan pesan atau catatan internal
     */
    public function createReply(int $ticketId, string $message, bool $isInternal, ?array $file, array $currentUser): int {
        $message = trim($message);
        if (empty($message)) {
            throw new Exception('Isi pesan tidak boleh kosong.');
        }

        // Catatan internal hanya boleh dibuat oleh Tim IT
        if ($isInternal && $currentUser['role'] !== 'it') {
            $isInternal = false;
        }

        $senderName = $currentUser['name'] ?? (($currentUser['role'] === 'it') ? 'Tim IT Advent' : 'Staf Kantor');

        // Handle upload lampiran jika ada
        $attachmentFilename = FileUploader::upload($file);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ticket_replies (ticket_id, user_id, sender_name, message, attachment, is_internal, created_at)
                VALUES (?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
            ");
            $stmt->execute([
                $ticketId,
                $currentUser['id'],
                $senderName,
                $message,
                $attachmentFilename ?: '',
                $isInternal ? 1 : 0
            ]);
            $replyId = (int)$this->db->lastInsertId();

            // Update waktu updated_at tiket
            $this->db->prepare("UPDATE tickets SET updated_at = datetime('now', 'localtime') WHERE id = ?")->execute([$ticketId]);

            // Audit Log
            $actionName = $isInternal ? 'Catatan Internal Ditambahkan' : 'Balasan Pesan Dikirim';
            $logStmt = $this->db->prepare("
                INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at)
                VALUES (?, ?, ?, ?, datetime('now', 'localtime'))
            ");
            $logStmt->execute([$ticketId, $currentUser['id'], $actionName, "Tanggapan oleh {$senderName}"]);

            $this->db->commit();
            return $replyId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
