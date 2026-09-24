<?php
namespace App\Middlewares;

class AuthMiddleware {
    /**
     * Memastikan user sudah login
     */
    public static function check(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=tickets');
            exit;
        }

        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'] ?? 'staff',
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'department' => $_SESSION['user_department'] ?? ''
        ];
    }

    /**
     * Membatasi akses khusus Tim IT
     */
    public static function requireIT(): array {
        $user = self::check();
        if ($user['role'] !== 'it') {
            http_response_code(403);
            die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:500px;margin:50px auto;'>
                <h3>⛔ Akses Ditolak (403 Forbidden)</h3>
                <p>Hanya Tim IT yang memiliki izin untuk melakukan tindakan ini.</p>
                <a href='index.php?page=tickets'>&larr; Kembali ke Daftar Tiket</a>
            </div>");
        }
        return $user;
    }
}
