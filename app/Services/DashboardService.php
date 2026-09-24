<?php
namespace App\Services;

use App\Config\Database;
use PDO;

class DashboardService {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Dapatkan Statistik Agregasi Dashboard
     */
    public function getStats(): array {
        $stats = [];

        // 1. Total Semua Tiket
        $stats['total_tickets'] = (int)$this->db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

        // 2. Tiket Sedang Dikerjakan
        $stats['in_progress'] = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();

        // 3. Tiket Selesai
        $stats['resolved'] = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed')")->fetchColumn();

        // 4. Tiket Urgent Masih Terbuka
        $stats['urgent_open'] = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE priority = 'urgent' AND status != 'closed'")->fetchColumn();

        // 5. CSAT (Kepuasan Pengguna)
        $csatStmt = $this->db->query("SELECT AVG(rating) as avg_rating, COUNT(rating) as rating_count FROM tickets WHERE rating IS NOT NULL");
        $csatRow = $csatStmt->fetch();
        $stats['csat_score'] = $csatRow['avg_rating'] ? round((float)$csatRow['avg_rating'], 1) : 5.0;
        $stats['rating_count'] = (int)$csatRow['rating_count'];

        return $stats;
    }

    /**
     * Sebaran Masalah Berdasarkan Kategori
     */
    public function getCategoryDistribution(): array {
        $stmt = $this->db->query("
            SELECT c.name as category_name, c.color as category_color, COUNT(t.id) as ticket_count
            FROM categories c
            LEFT JOIN tickets t ON c.id = t.category_id
            GROUP BY c.id, c.name, c.color
            ORDER BY ticket_count DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * 5 Tiket Terkini
     */
    public function getRecentTickets(int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT t.*, c.name as category_name, c.color as category_color
            FROM tickets t
            JOIN categories c ON t.category_id = c.id
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
