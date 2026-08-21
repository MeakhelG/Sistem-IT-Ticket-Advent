<?php
/**
 * Sistem IT Ticket Advent - Inisialisasi Database & Seeder
 * Disederhanakan menjadi 2 Role: Tim IT dan Staf Kantor
 */

require_once __DIR__ . '/config.php';

function initDatabase() {
    $db = getDB();

    // Cek apakah kolom reporter_name sudah ada di tabel tickets
    $needsReset = false;
    try {
        $check = $db->query("PRAGMA table_info(tickets)")->fetchAll();
        $hasReporterName = false;
        foreach ($check as $col) {
            if ($col['name'] === 'reporter_name') {
                $hasReporterName = true;
                break;
            }
        }
        if (count($check) > 0 && !$hasReporterName) {
            $needsReset = true;
        }
    } catch (Exception $e) {
        $needsReset = true;
    }

    if ($needsReset) {
        $db->exec("DROP TABLE IF EXISTS ticket_logs");
        $db->exec("DROP TABLE IF EXISTS ticket_replies");
        $db->exec("DROP TABLE IF EXISTS tickets");
        $db->exec("DROP TABLE IF EXISTS categories");
        $db->exec("DROP TABLE IF EXISTS users");
    }

    // 1. Tabel Pengguna (2 Akun: Tim IT dan Staf Kantor)
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'staff', -- 'it' atau 'staff'
            department VARCHAR(100) DEFAULT 'Kantor Advent',
            phone VARCHAR(30) DEFAULT '',
            avatar VARCHAR(255) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 2. Tabel Kategori Kendala IT
    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) DEFAULT 'bi-gear',
            color VARCHAR(30) DEFAULT '#4f46e5',
            description TEXT
        );
    ");

    // 3. Tabel Tiket
    $db->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_code VARCHAR(30) UNIQUE NOT NULL,
            user_id INTEGER NOT NULL,
            reporter_name VARCHAR(100) NOT NULL,
            reporter_dept VARCHAR(100) NOT NULL,
            category_id INTEGER NOT NULL,
            assigned_to INTEGER DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100) DEFAULT '',
            priority VARCHAR(20) NOT NULL DEFAULT 'medium',
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            attachment VARCHAR(255) DEFAULT '',
            solution TEXT DEFAULT '',
            rating INTEGER DEFAULT NULL,
            rating_feedback TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        );
    ");

    // 4. Tabel Balasan Percakapan Tiket
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_replies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            sender_name VARCHAR(100) DEFAULT '',
            message TEXT NOT NULL,
            attachment VARCHAR(255) DEFAULT '',
            is_internal INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // 5. Tabel Riwayat Status (Audit Log)
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            action VARCHAR(50) NOT NULL,
            notes TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Cek apakah data awal sudah ada
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM users");
    $userCount = $stmt->fetch()['cnt'];

    if ($userCount == 0) {
        seedInitialData($db);
    }
}

function seedInitialData($db) {
    // 1. Data Kategori Kendala IT
    $categories = [
        ['Jaringan & Internet', 'bi-wifi', '#0284c7', 'Kendala Wi-Fi, kabel LAN, internet lambat, atau akses web portal terputus'],
        ['Printer & Mesin Cetak', 'bi-printer', '#7c3aed', 'Kertas macet (paper jam), tinta habis, scanner rusak, atau printer offline'],
        ['Komputer & Laptop', 'bi-pc-display', '#2563eb', 'PC lambat, Blue Screen (BSOD), monitor mati, keyboard/mouse rusak'],
        ['Software & Aplikasi', 'bi-window-stack', '#059669', 'Aplikasi error, install program baru, aktivasi Office, kendala meeting'],
        ['Akun, Email & Sandi', 'bi-person-badge', '#d97706', 'Lupa password email, reset akun SIAK/Sistem, registrasi akun staf baru'],
        ['Proyektor & Sound System', 'bi-projector', '#dc2626', 'Koneksi HDMI proyektor ruang ibadah/aula, audio berdengung, mic wireless'],
        ['Lain-lain / Sarana IT', 'bi-wrench-adjustable', '#4b5563', 'Permintaan backup data, instalasi kabel, konsultasi perangkat IT']
    ];

    $catStmt = $db->prepare("INSERT INTO categories (name, icon, color, description) VALUES (?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }

    // 2. Data Pengguna Default (HANYA 2 ROLE: Tim IT dan Staf Kantor)
    $users = [
        [
            'name' => 'Tim IT Advent',
            'email' => 'it@advent.local',
            'password' => password_hash('it123', PASSWORD_DEFAULT),
            'role' => 'it',
            'department' => 'Divisi IT & Multimedia',
            'phone' => '081234567890'
        ],
        [
            'name' => 'Staf Kantor',
            'email' => 'staf@advent.local',
            'password' => password_hash('staf123', PASSWORD_DEFAULT),
            'role' => 'staff',
            'department' => 'Staf & Pengguna Kantor',
            'phone' => '081398765432'
        ]
    ];

    $userStmt = $db->prepare("INSERT INTO users (name, email, password, role, department, phone) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $userStmt->execute([$u['name'], $u['email'], $u['password'], $u['role'], $u['department'], $u['phone']]);
    }

    // 3. Data Tiket Contoh Awal
    $sampleTickets = [
        [
            'ticket_code' => 'TKT-' . date('Ym') . '-001',
            'user_id' => 2,
            'reporter_name' => 'Ibu Maria Hutapea',
            'reporter_dept' => 'Bagian Keuangan & Kasir',
            'category_id' => 2,
            'assigned_to' => 1,
            'title' => 'Printer Epson di Ruang Keuangan Lampu Merah Berkedip & Kertas Macet',
            'description' => "Selamat siang tim IT, printer Epson L3110 di meja kasir keuangan tidak mau mencetak kuitansi. Lampu indikator tinta dan kertas berkedip merah bersamaan. Mohon bantuannya segera karena antrian pembayaran sedang banyak. Terima kasih.",
            'location' => 'Gedung A, Lantai 1 - Ruang Keuangan',
            'priority' => 'high',
            'status' => 'in_progress',
            'solution' => '',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            'replies' => [
                [
                    'user_id' => 1,
                    'sender_name' => 'Tim IT Advent',
                    'message' => "Halo Bu Maria, tiket sudah kami terima. Kami sedang menuju ke ruang keuangan untuk membersihkan roller penarik kertas.",
                    'is_internal' => 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours 30 mins'))
                ],
                [
                    'user_id' => 1,
                    'sender_name' => 'Tim IT Advent',
                    'message' => "Catatan Internal: Roller penarik kertas aus, perlu diganti karetnya atau dibersihkan dengan alkohol.",
                    'is_internal' => 1,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                ]
            ]
        ],
        [
            'ticket_code' => 'TKT-' . date('Ym') . '-002',
            'user_id' => 2,
            'reporter_name' => 'Bpk. Daniel Tumanggor',
            'reporter_dept' => 'Bagian Tata Usaha & SDM',
            'category_id' => 1,
            'assigned_to' => 1,
            'title' => 'Koneksi Wi-Fi "ADVENT-OFFICE" di Ruang TU Sering Terputus (RTO)',
            'description' => "Koneksi internet di laptop staf TU sering disconnected setiap 15 menit sekali saat menginput data absensi. Sinyal terlihat 3 bar tapi status No Internet Access. Mohon dicek router access point di lorong TU.",
            'location' => 'Gedung Utama, Lantai 2 - Ruang Tata Usaha',
            'priority' => 'urgent',
            'status' => 'open',
            'solution' => '',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'replies' => []
        ],
        [
            'ticket_code' => 'TKT-' . date('Ym') . '-003',
            'user_id' => 2,
            'reporter_name' => 'Pdt. Johanes Manurung',
            'reporter_dept' => 'Sekretariat Kantor',
            'category_id' => 6,
            'assigned_to' => 1,
            'title' => 'Proyektor Utama Aula Muncul Notifikasi "Lamp Replace" & Warna Agak Kuning',
            'description' => "Proyektor di aula utama yang dipakai untuk ibadah sabat dan pertemuan panitia lampunya sudah mulai redup dan berwarna kekuningan. Mohon dijadwalkan pengecekan sebelum acara pertemuan sabat ini.",
            'location' => 'Aula Utama & Ruang Ibadah',
            'priority' => 'medium',
            'status' => 'resolved',
            'solution' => 'Lampu proyektor telah dibersihkan filternya dan diganti unit lampu cadangan 240W. Tampilan sudah kembali jernih dan tajam 1080p.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'resolved_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'rating' => 5,
            'rating_feedback' => 'Pelayanan sangat cepat dan proyektor sudah sangat jernih kembali. Terima kasih tim IT!',
            'replies' => [
                [
                    'user_id' => 1,
                    'sender_name' => 'Tim IT Advent',
                    'message' => "Selamat siang Pendeta, kami sudah lakukan penggantian lampu cadangan dan pengetesan display. Hasilnya sudah normal kembali.",
                    'is_internal' => 0,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
                ]
            ]
        ]
    ];

    $tktStmt = $db->prepare("
        INSERT INTO tickets (ticket_code, user_id, reporter_name, reporter_dept, category_id, assigned_to, title, description, location, priority, status, solution, rating, rating_feedback, created_at, resolved_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $replyStmt = $db->prepare("
        INSERT INTO ticket_replies (ticket_id, user_id, sender_name, message, is_internal, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $logStmt = $db->prepare("
        INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($sampleTickets as $st) {
        $tktStmt->execute([
            $st['ticket_code'],
            $st['user_id'],
            $st['reporter_name'],
            $st['reporter_dept'],
            $st['category_id'],
            $st['assigned_to'],
            $st['title'],
            $st['description'],
            $st['location'],
            $st['priority'],
            $st['status'],
            $st['solution'],
            $st['rating'] ?? null,
            $st['rating_feedback'] ?? '',
            $st['created_at'],
            $st['resolved_at'] ?? null
        ]);
        $ticketId = $db->lastInsertId();

        $logStmt->execute([$ticketId, $st['user_id'], 'Tiket Dibuat', 'Tiket diajukan oleh ' . $st['reporter_name'], $st['created_at']]);

        if (!empty($st['replies'])) {
            foreach ($st['replies'] as $rep) {
                $replyStmt->execute([$ticketId, $rep['user_id'], $rep['sender_name'], $rep['message'], $rep['is_internal'], $rep['created_at']]);
            }
        }
    }
}
