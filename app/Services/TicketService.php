<?php
namespace App\Services;

use App\Config\Database;
use App\Utils\FileUploader;
use PDO;
use Exception;

class TicketService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Penomoran tiket otomatis per bulan: TKT-YYYYMM-XXX
     */
    public function generateTicketCode(): string {
        $yearMonth = date('Ym');
        $prefix = 'TKT-' . $yearMonth . '-';

        // Hitung tiket yang dibuat pada bulan ini
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tickets 
            WHERE ticket_code LIKE ?
        ");
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn();

        return $prefix . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung Angka Counter untuk Sidebar Badge
     */
    public function getBadgeCounts(int $userId, string $role): array {
        $counts = [
            'all'         => 0,
            'my_tickets'  => 0,
            'in_progress' => 0,
            'pending'     => 0,
            'resolved'    => 0,
            'urgent'      => 0
        ];

        // Total semua tiket
        $counts['all'] = (int)$this->db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

        // Tiket Saya (Staf)
        $myStmt = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
        $myStmt->execute([$userId]);
        $counts['my_tickets'] = (int)$myStmt->fetchColumn();

        // Status Berjalan
        $counts['in_progress'] = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();
        $counts['pending']     = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status = 'pending'")->fetchColumn();
        $counts['resolved']    = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed')")->fetchColumn();
        $counts['urgent']      = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE priority = 'urgent' AND status != 'closed'")->fetchColumn();

        return $counts;
    }

    /**
     * Filter, Pencarian Full-Text, dan Pagination
     */
    public function getFilteredTickets(array $filters, array $currentUser, int $page = 1, int $perPage = 15): array {
        $offset = max(0, ($page - 1) * $perPage);
        $conditions = ["1=1"];
        $params = [];

        // Filter Navigasi Folder
        $filterType = $filters['filter'] ?? 'all';
        if ($filterType === 'my_tickets') {
            $conditions[] = "t.user_id = ?";
            $params[] = $currentUser['id'];
        } elseif ($filterType === 'in_progress') {
            $conditions[] = "t.status = 'in_progress'";
        } elseif ($filterType === 'pending') {
            $conditions[] = "t.status = 'pending'";
        } elseif ($filterType === 'resolved') {
            $conditions[] = "t.status IN ('resolved', 'closed')";
        } elseif ($filterType === 'urgent') {
            $conditions[] = "t.priority = 'urgent' AND t.status != 'closed'";
        }

        // Filter Kategori
        if (!empty($filters['category'])) {
            $conditions[] = "t.category_id = ?";
            $params[] = (int)$filters['category'];
        }

        // Filter Prioritas
        if (!empty($filters['priority'])) {
            $conditions[] = "t.priority = ?";
            $params[] = $filters['priority'];
        }

        // Filter Status Spesifik
        if (!empty($filters['status'])) {
            $conditions[] = "t.status = ?";
            $params[] = $filters['status'];
        }

        // Pencarian Kata Kunci (Judul, Lokasi/Ruangan, Kode Tiket, atau Nama Pelapor)
        if (!empty($filters['q'])) {
            $keyword = '%' . trim($filters['q']) . '%';
            $conditions[] = "(t.title LIKE ? OR t.location LIKE ? OR t.reporter_name LIKE ? OR t.ticket_code LIKE ?)";
            array_push($params, $keyword, $keyword, $keyword, $keyword);
        }

        $whereClause = implode(" AND ", $conditions);

        // 1. Hitung total records untuk pagination
        $countSql = "SELECT COUNT(*) FROM tickets t WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        // Tentukan Urutan Sorting
        $sort = $filters['sort'] ?? 'newest';
        $orderBy = "CASE WHEN t.priority = 'urgent' AND t.status != 'closed' THEN 0 ELSE 1 END, t.created_at DESC";

        if ($sort === 'oldest') {
            $orderBy = "t.created_at ASC";
        } elseif ($sort === 'priority_desc') {
            $orderBy = "CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END ASC, t.created_at DESC";
        } elseif ($sort === 'priority_asc') {
            $orderBy = "CASE t.priority WHEN 'low' THEN 1 WHEN 'medium' THEN 2 WHEN 'high' THEN 3 WHEN 'urgent' THEN 4 ELSE 5 END ASC, t.created_at DESC";
        } elseif ($sort === 'updated') {
            $orderBy = "t.updated_at DESC";
        }

        // 2. Ambil data tiket dengan urutan prioritas & sorting
        $dataSql = "
            SELECT t.*, 
                   c.name as category_name, c.icon as category_icon, c.color as category_color,
                   (SELECT COUNT(*) FROM ticket_replies r WHERE r.ticket_id = t.id) as reply_count
            FROM tickets t
            JOIN categories c ON t.category_id = c.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($dataSql);
        $allParams = array_merge($params, [$perPage, $offset]);
        foreach ($allParams as $idx => $val) {
            $stmt->bindValue($idx + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $tickets = $stmt->fetchAll();

        return [
            'data'         => $tickets,
            'total'        => $totalRecords,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total_pages'  => (int)ceil($totalRecords / max(1, $perPage))
        ];
    }

    /**
     * Pembuatan Tiket Baru (Staf / Reporter)
     */
    public function createTicket(array $data, ?array $file, array $currentUser): int {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 1);
        $location = trim($data['location'] ?? '');
        $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $data['priority'] : 'medium';

        $reporterName = trim($data['reporter_name'] ?? '');
        $reporterDept = trim($data['reporter_dept'] ?? '');

        if (empty($reporterName)) {
            $reporterName = $currentUser['name'] ?? 'Staf Kantor';
        }
        if (empty($reporterDept)) {
            $reporterDept = $currentUser['department'] ?? 'Kantor Advent';
        }
        if (empty($location)) {
            $location = $reporterDept;
        }

        if (empty($title) || empty($description)) {
            throw new Exception('Judul dan penjelasan kendala wajib diisi.');
        }

        // Upload lampiran dengan validasi 5MB dan MIME type
        $attachmentFilename = FileUploader::upload($file);

        // Generate nomor tiket otomatis bulanan
        $ticketCode = $this->generateTicketCode();

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tickets (ticket_code, user_id, reporter_name, reporter_dept, category_id, title, description, location, priority, status, attachment, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, datetime('now', 'localtime'), datetime('now', 'localtime'))
            ");
            $stmt->execute([
                $ticketCode,
                $currentUser['id'],
                $reporterName,
                $reporterDept,
                $categoryId,
                $title,
                $description,
                $location,
                $priority,
                $attachmentFilename ?: ''
            ]);

            $ticketId = (int)$this->db->lastInsertId();

            // Catat ke ticket_logs
            $logStmt = $this->db->prepare("
                INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at)
                VALUES (?, ?, 'Tiket Diajukan', ?, datetime('now', 'localtime'))
            ");
            $logStmt->execute([$ticketId, $currentUser['id'], "Tiket {$ticketCode} diajukan oleh {$reporterName} ({$reporterDept})"]);

            $this->db->commit();
            return $ticketId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ubah Status & Solusi Tiket (Khusus Tim IT)
     */
    public function updateStatus(int $ticketId, string $status, ?string $solution, array $currentUser): void {
        if ($currentUser['role'] !== 'it') {
            throw new Exception('Hanya Tim IT yang memiliki izin mengubah status tiket.');
        }

        $resolvedAt = in_array($status, ['resolved', 'closed']) ? date('Y-m-d H:i:s') : null;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE tickets 
                SET status = ?, solution = ?, resolved_at = COALESCE(?, resolved_at), updated_at = datetime('now', 'localtime')
                WHERE id = ?
            ");
            $stmt->execute([$status, trim($solution ?? ''), $resolvedAt, $ticketId]);

            // Status label
            $statusLabels = [
                'open' => 'Menunggu Respon (Open)',
                'in_progress' => 'Sedang Dikerjakan (In Progress)',
                'pending' => 'Menunggu Konfirmasi (Pending)',
                'resolved' => 'Selesai Ditangani (Resolved)',
                'closed' => 'Ditutup (Closed)'
            ];
            $statusText = $statusLabels[$status] ?? ucfirst($status);

            // Audit Log
            $logStmt = $this->db->prepare("
                INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at)
                VALUES (?, ?, 'Status Diubah', ?, datetime('now', 'localtime'))
            ");
            $logStmt->execute([$ticketId, $currentUser['id'], "Status diubah menjadi: {$statusText} oleh Tim IT"]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Submit Rating Kepuasan Pelapor
     */
    public function rateTicket(int $ticketId, int $rating, ?string $feedback, array $currentUser): void {
        $stmt = $this->db->prepare("SELECT user_id, status FROM tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            throw new Exception('Tiket tidak ditemukan.');
        }

        $rating = max(1, min(5, $rating));

        $this->db->beginTransaction();
        try {
            $updateStmt = $this->db->prepare("
                UPDATE tickets 
                SET rating = ?, rating_feedback = ?, updated_at = datetime('now', 'localtime') 
                WHERE id = ?
            ");
            $updateStmt->execute([$rating, trim($feedback ?? ''), $ticketId]);

            $logStmt = $this->db->prepare("
                INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at)
                VALUES (?, ?, 'Rating Diberikan', ?, datetime('now', 'localtime'))
            ");
            $logStmt->execute([$ticketId, $currentUser['id'], "Pelapor memberikan penilaian kepuasan: {$rating}/5 Bintang"]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
