<?php
namespace App\Controllers;

abstract class BaseController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Dapatkan informasi user yang sedang aktif
     */
    protected function getCurrentUser(): array {
        if (function_exists('getCurrentUser')) {
            return getCurrentUser();
        }

        return [
            'id' => $_SESSION['user_id'] ?? 1,
            'name' => $_SESSION['user_name'] ?? 'Tim IT Advent',
            'email' => $_SESSION['user_email'] ?? 'it@advent.local',
            'role' => $_SESSION['user_role'] ?? 'it',
            'department' => $_SESSION['user_department'] ?? 'Divisi IT & Multimedia'
        ];
    }

    /**
     * Set notifikasi flash
     */
    protected function setFlash(string $type, string $message): void {
        if (function_exists('setFlash')) {
            setFlash($type, $message);
        } else {
            $_SESSION['flash'] = [
                'type' => $type,
                'message' => $message
            ];
        }
    }

    /**
     * Redirect ke URL tujuan
     */
    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }

    /**
     * Render view file
     */
    protected function render(string $viewPath, array $data = []): void {
        extract($data);
        $fullPath = __DIR__ . '/../../views/' . $viewPath . '.php';

        if (!file_exists($fullPath)) {
            die("File view tidak ditemukan: " . htmlspecialchars($viewPath));
        }

        require $fullPath;
    }
}
