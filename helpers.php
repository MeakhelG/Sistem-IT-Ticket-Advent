<?php
/**
 * Sistem IT Ticket Advent - Helper Functions & Utilities
 * Disederhanakan untuk 2 Role: Tim IT dan Staf Kantor
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
initDatabase();

// Inisialisasi default user session jika belum login (Default: Tim IT)
function getCurrentUser() {
    $db = getDB();
    if (!isset($_SESSION['user_id'])) {
        $stmt = $db->query("SELECT * FROM users WHERE role = 'it' LIMIT 1");
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_department'] = $user['department'];
        }
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 1]);
    $user = $stmt->fetch();

    return $user ?: [
        'id' => 1,
        'name' => 'Tim IT Advent',
        'email' => 'it@advent.local',
        'role' => 'it',
        'department' => 'Divisi IT & Multimedia',
        'phone' => '081234567890'
    ];
}

// Format Tanggal Relatif (Waktu Lalu dalam Bahasa Indonesia)
function timeAgo($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' menit lalu';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' jam lalu';
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' hari lalu';
    } else {
        return date('d M Y, H:i', $time);
    }
}

// Format Tanggal Lengkap Indonesia
function formatDateIndo($datetime) {
    if (!$datetime) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $time = strtotime($datetime);
    $d = date('j', $time);
    $m = $bulan[(int)date('n', $time)];
    $y = date('Y', $time);
    $h = date('H:i', $time);
    return "$d $m $y, pukul $h WIB";
}

// Badge Status Tiket
function getStatusBadge($status) {
    switch ($status) {
        case 'open':
            return '<span class="badge-ticket badge-open"><i class="bi bi-envelope-open"></i> Menunggu Respon</span>';
        case 'in_progress':
            return '<span class="badge-ticket badge-in-progress"><i class="bi bi-arrow-repeat spin-icon"></i> Sedang Dikerjakan</span>';
        case 'pending':
            return '<span class="badge-ticket badge-pending"><i class="bi bi-pause-circle"></i> Menunggu Konfirmasi</span>';
        case 'resolved':
            return '<span class="badge-ticket badge-resolved"><i class="bi bi-check2-circle"></i> Selesai Ditangani</span>';
        case 'closed':
            return '<span class="badge-ticket badge-closed"><i class="bi bi-lock"></i> Ditutup</span>';
        default:
            return '<span class="badge-ticket badge-secondary">' . htmlspecialchars(ucfirst($status)) . '</span>';
    }
}

// Text Status
function getStatusText($status) {
    $labels = [
        'open' => 'Menunggu Respon (Open)',
        'in_progress' => 'Sedang Dikerjakan (In Progress)',
        'pending' => 'Menunggu Konfirmasi (Pending)',
        'resolved' => 'Selesai Ditangani (Resolved)',
        'closed' => 'Ditutup (Closed)'
    ];
    return $labels[$status] ?? ucfirst($status);
}

// Badge Prioritas
function getPriorityBadge($priority) {
    switch ($priority) {
        case 'urgent':
            return '<span class="badge-priority badge-urgent"><i class="bi bi-fire"></i> Darurat</span>';
        case 'high':
            return '<span class="badge-priority badge-high"><i class="bi bi-exclamation-triangle"></i> Tinggi</span>';
        case 'medium':
            return '<span class="badge-priority badge-medium"><i class="bi bi-dash-circle"></i> Normal</span>';
        case 'low':
            return '<span class="badge-priority badge-low"><i class="bi bi-arrow-down-circle"></i> Rendah</span>';
        default:
            return '<span class="badge-priority badge-medium">' . htmlspecialchars(ucfirst($priority)) . '</span>';
    }
}

// Role Badge (Hanya 2 Role: Tim IT dan Staf Kantor)
function getRoleBadge($role) {
    if ($role === 'it') {
        return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-tools me-1"></i> Tim IT</span>';
    } else {
        return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-person me-1"></i> Staf Kantor</span>';
    }
}

// Flash Message Helper
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Menghitung Jumlah Tiket
function getTicketCounts() {
    $db = getDB();
    $user = getCurrentUser();

    $counts = [
        'all' => $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
        'my_tickets' => $db->query("SELECT COUNT(*) FROM tickets WHERE user_id = {$user['id']}")->fetchColumn(),
        'open' => $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn(),
        'in_progress' => $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn(),
        'pending' => $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'pending'")->fetchColumn(),
        'resolved' => $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'resolved'")->fetchColumn(),
        'closed' => $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'closed'")->fetchColumn(),
        'urgent' => $db->query("SELECT COUNT(*) FROM tickets WHERE priority = 'urgent' AND status != 'closed'")->fetchColumn(),
    ];

    return $counts;
}

// Sanitasi Input Form
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
