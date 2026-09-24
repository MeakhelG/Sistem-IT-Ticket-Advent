<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';

            try {
                if ($driver === 'sqlite') {
                    $dbPath = defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : __DIR__ . '/../../data/it_ticket.db';
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    self::$instance = new PDO('sqlite:' . $dbPath);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec('PRAGMA foreign_keys = ON;');
                } else {
                    $host = defined('DB_HOST') ? DB_HOST : 'localhost';
                    $dbname = defined('DB_NAME') ? DB_NAME : 'it_ticket_advent';
                    $user = defined('DB_USER') ? DB_USER : 'root';
                    $pass = defined('DB_PASS') ? DB_PASS : '';
                    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                    
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                }
            } catch (PDOException $e) {
                die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;'>
                    <h3>⚠️ Gagal Terhubung ke Database</h3>
                    <p>" . htmlspecialchars($e->getMessage()) . "</p>
                </div>");
            }
        }
        return self::$instance;
    }
}
