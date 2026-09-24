<?php
/**
 * Sistem IT Ticket Advent - Router & Controller Utama
 * Menerapkan Arsitektur MVC + Service Layer & RBAC
 */

// 1. Daftarkan PSR-4 Autoloader Sederhana untuk Namespace App\
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// Inisialisasi Database jika belum ada
initDatabase();

$db = getDB();
$currentUser = getCurrentUser();

$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? 'tickets';

// ==========================================
// PROSES AKSI BACKEND (POST / ACTION HANDLERS)
// ==========================================


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {

    // 1. AKSI: BUAT TIKET BARU
    if ($action === 'create_ticket') {
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $_POST['priority'] : 'medium';
        
        $reporterName = trim($_POST['reporter_name'] ?? '');
        $reporterDept = trim($_POST['reporter_dept'] ?? '');

        if (empty($reporterName)) {
            $reporterName = ($currentUser['role'] === 'it') ? 'Tim IT Advent' : 'Staf Kantor';
        }
        if (empty($reporterDept)) {
            $reporterDept = ($currentUser['role'] === 'it') ? 'Divisi IT' : 'Kantor';
        }
        if (empty($location)) {
            $location = $reporterDept;
        }

        if (empty($title) || empty($description)) {
            setFlash('danger', 'Judul dan penjelasan kendala wajib diisi.');
            header('Location: index.php?page=create_ticket');
            exit;
        }

        // Handle Upload Lampiran Foto
        $attachmentFilename = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['attachment']['tmp_name'];
            $origName = $_FILES['attachment']['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];

            if (in_array($ext, $allowedExts)) {
                $attachmentFilename = 'tkt_' . time() . '_' . rand(100, 999) . '.' . $ext;
                move_uploaded_file($tmpPath, UPLOAD_DIR . $attachmentFilename);
            }
        }

        // Generate Kode Tiket Unik (Format: TKT-YYYYMM-XXX)
        $countToday = $db->query("SELECT COUNT(*) FROM tickets WHERE strftime('%Y-%m', created_at) = '" . date('Y-m') . "'")->fetchColumn();
        $ticketCode = 'TKT-' . date('Ym') . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        // Simpan Tiket ke Database
        $stmt = $db->prepare("
            INSERT INTO tickets (ticket_code, user_id, reporter_name, reporter_dept, category_id, title, description, location, priority, status, attachment, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, datetime('now', 'localtime'), datetime('now', 'localtime'))
        ");
        $stmt->execute([$ticketCode, $currentUser['id'], $reporterName, $reporterDept, $categoryId, $title, $description, $location, $priority, $attachmentFilename]);
        $ticketId = $db->lastInsertId();

        // Catat Riwayat Log
        $logStmt = $db->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at) VALUES (?, ?, ?, ?, datetime('now', 'localtime'))");
        $logStmt->execute([$ticketId, $currentUser['id'], 'Tiket Diajukan', "Tiket baru diajukan oleh $reporterName ($reporterDept)"]);

        setFlash('success', "Tiket <strong>$ticketCode</strong> berhasil diajukan! Tim IT akan segera memproses laporan Anda.");
        header("Location: index.php?page=show_ticket&id=$ticketId");
        exit;
    }

    // 2. AKSI: KIRIM BALASAN TIKET
    if ($action === 'reply_ticket') {
        $replyCtrl = new \App\Controllers\ReplyController();
        $replyCtrl->store();
        exit;
    }

    // 3. AKSI: UBAH STATUS TIKET (KHUSUS TIM IT)
    if ($action === 'update_status') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $status = $_POST['status'] ?? 'open';
        $solution = trim($_POST['solution'] ?? '');

        if ($currentUser['role'] !== 'it') {
            setFlash('danger', 'Hanya Tim IT yang dapat mengubah status tiket.');
            header("Location: index.php?page=show_ticket&id=$ticketId");
            exit;
        }

        $resolvedAt = in_array($status, ['resolved', 'closed']) ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare("
            UPDATE tickets 
            SET status = ?, solution = ?, resolved_at = COALESCE(?, resolved_at), updated_at = datetime('now', 'localtime') 
            WHERE id = ?
        ");
        $stmt->execute([$status, $solution, $resolvedAt, $ticketId]);

        // Catat Audit Log
        $statusText = getStatusText($status);
        $db->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at) VALUES (?, ?, ?, ?, datetime('now', 'localtime'))")
           ->execute([$ticketId, $currentUser['id'], 'Status Diubah', "Status diubah menjadi: $statusText oleh Tim IT"]);

        setFlash('success', "Status tiket berhasil diperbarui menjadi <strong>$statusText</strong>.");
        header("Location: index.php?page=show_ticket&id=$ticketId");
        exit;
    }

    // 4. AKSI: BERIKAN RATING & ULASAN KEPUASAN (CUSTOMER RATING)
    if ($action === 'rate_ticket') {
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 5);
        $feedback = trim($_POST['rating_feedback'] ?? '');

        $stmt = $db->prepare("UPDATE tickets SET rating = ?, rating_feedback = ? WHERE id = ?");
        $stmt->execute([$rating, $feedback, $ticketId]);

        $db->prepare("INSERT INTO ticket_logs (ticket_id, user_id, action, notes, created_at) VALUES (?, ?, ?, ?, datetime('now', 'localtime'))")
           ->execute([$ticketId, $currentUser['id'], 'Rating Diberikan', "Pelapor memberikan rating kepuasan: $rating/5 Bintang"]);

        setFlash('success', 'Terima kasih atas penilaian kepuasan yang Anda berikan!');
        header("Location: index.php?page=show_ticket&id=$ticketId");
        exit;
    }
}

// ==========================================
// SWITCH USER HANDLER (SIMULASI 2 ROLE LOCALHOST)
// ==========================================
if ($page === 'switch_user') {
    $targetUserId = (int)($_GET['user_id'] ?? 1);
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$targetUserId]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_department'] = $user['department'];
        setFlash('info', "Sekarang Anda masuk sebagai <strong>" . htmlspecialchars($user['name']) . "</strong>.");
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php?page=tickets';
    if (strpos($referer, 'switch_user') !== false) {
        $referer = 'index.php?page=tickets';
    }
    header("Location: $referer");
    exit;
}

// ==========================================
// ROUTER HALAMAN VIEW (GET REQUESTS)
// ==========================================

switch ($page) {
    case 'create_ticket':
        require_once __DIR__ . '/views/tickets/create.php';
        break;
    case 'show_ticket':
        require_once __DIR__ . '/views/tickets/show.php';
        break;
    case 'print_ticket':
        require_once __DIR__ . '/views/tickets/print.php';
        break;
    case 'dashboard':
        require_once __DIR__ . '/views/dashboard.php';
        break;
    case 'about':
        require_once __DIR__ . '/views/about.php';
        break;
    case 'api':
        $apiCtrl = new \App\Controllers\ApiController();
        $endpoint = $_GET['endpoint'] ?? '';
        if ($endpoint === 'search') {
            $apiCtrl->search();
        } elseif ($endpoint === 'badge_counts') {
            $apiCtrl->badgeCounts();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint API tidak ditemukan']);
        }
        exit;
    case 'tickets':
    default:
        require_once __DIR__ . '/views/tickets/index.php';
        break;
}

