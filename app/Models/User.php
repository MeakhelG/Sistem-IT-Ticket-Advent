<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAllTechnicians(): array {
        $stmt = $this->db->query("SELECT * FROM users WHERE role = 'it' ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }

        return null;
    }
}
