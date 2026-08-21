<?php
/**
 * Sistem IT Ticket Advent - Konfigurasi Sistem & Database
 * Mendukung SQLite (Otomatis & Tanpa Konfigurasi) dan MySQL (XAMPP)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Dasar Aplikasi
define('APP_NAME', 'Sistem IT Ticket Advent');
define('APP_VERSION', '1.0.0');
define('APP_ORGANIZATION', 'Gereja / Institusi Advent');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// Pengaturan Database
// Pilihan Driver: 'sqlite' (Default & Langsung Jalan) atau 'mysql' (XAMPP)
define('DB_DRIVER', 'sqlite'); 

// Konfigurasi MySQL (jika DB_DRIVER diubah menjadi 'mysql')
define('DB_HOST', 'localhost');
define('DB_NAME', 'it_ticket_advent');
define('DB_USER', 'root');
define('DB_PASS', '');

// Lokasi File Database SQLite & Uploads
define('DB_SQLITE_PATH', __DIR__ . '/data/it_ticket.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Pastikan folder data dan uploads tersedia
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Inisialisasi Koneksi PDO
function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON;');
        } else {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $pdo;
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;'>
            <h3>⚠️ Gagal Terhubung ke Database</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Jika menggunakan SQLite, pastikan folder <code>data/</code> memiliki izin tulis (write permission).</p>
        </div>");
    }
}
