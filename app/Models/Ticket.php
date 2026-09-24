<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Ticket {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, 
                   c.name as category_name, c.icon as category_icon, c.color as category_color,
                   u.name as user_name, u.email as user_email, u.department as user_department, u.phone as user_phone
            FROM tickets t
            JOIN categories c ON t.category_id = c.id
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tickets WHERE ticket_code = ?");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
