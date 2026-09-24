<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Reply {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getByTicketId(int $ticketId, bool $isIT = false): array {
        $sql = "
            SELECT r.*, u.name as user_name, u.role as user_role
            FROM ticket_replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.ticket_id = ?
        ";

        if (!$isIT) {
            $sql .= " AND r.is_internal = 0";
        }

        $sql .= " ORDER BY r.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }

    public function create(int $ticketId, int $userId, string $senderName, string $message, ?string $attachment, bool $isInternal): int {
        $stmt = $this->db->prepare("
            INSERT INTO ticket_replies (ticket_id, user_id, sender_name, message, attachment, is_internal, created_at)
            VALUES (?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
        ");
        $stmt->execute([$ticketId, $userId, $senderName, $message, $attachment ?: '', $isInternal ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }
}
