<?php
/**
 * Sistem IT Ticket Advent - Halaman Mailbox / Daftar Tiket (Email Style)
 */
$pageTitle = 'Kotak Tiket Masuk';
require_once __DIR__ . '/../layouts/header.php';

$filter = $_GET['filter'] ?? 'all';
$categoryFilter = $_GET['category'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$currentUser = getCurrentUser();

$db = getDB();

// Bangun query SQL dinamis
$sql = "
    SELECT t.*, 
           c.name as category_name, c.icon as category_icon, c.color as category_color,
           (SELECT COUNT(*) FROM ticket_replies r WHERE r.ticket_id = t.id) as reply_count
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($filter === 'my_tickets') {
    $sql .= " AND t.user_id = ?";
    $params[] = $currentUser['id'];
} elseif ($filter === 'in_progress') {
    $sql .= " AND t.status = 'in_progress'";
} elseif ($filter === 'pending') {
    $sql .= " AND t.status = 'pending'";
} elseif ($filter === 'resolved') {
    $sql .= " AND (t.status = 'resolved' OR t.status = 'closed')";
} elseif ($filter === 'urgent') {
    $sql .= " AND t.priority = 'urgent' AND t.status != 'closed'";
}

if (!empty($categoryFilter)) {
    $sql .= " AND t.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($priorityFilter)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}

if (!empty($statusFilter)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY CASE WHEN t.priority = 'urgent' AND t.status != 'closed' THEN 0 ELSE 1 END, t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Dapatkan nama kategori jika sedang di-filter kategori
$selectedCatName = '';
if (!empty($categoryFilter)) {
    $catStmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $catStmt->execute([$categoryFilter]);
    $selectedCatName = $catStmt->fetchColumn() ?: '';
}
?>

<div class="container-fluid p-0">
    <!-- Header Banner & Quick Action -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="fs-5 fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <?php if ($filter === 'my_tickets'): ?>
                    <i class="bi bi-person-workspace text-primary"></i> Tiket Yang Saya Ajukan
                <?php elseif ($filter === 'in_progress'): ?>
                    <i class="bi bi-arrow-repeat text-warning"></i> Tiket Sedang Dikerjakan
                <?php elseif ($filter === 'pending'): ?>
                    <i class="bi bi-hourglass-split text-secondary"></i> Tiket Menunggu Konfirmasi
                <?php elseif ($filter === 'resolved'): ?>
                    <i class="bi bi-check-circle-fill text-success"></i> Tiket Selesai
                <?php elseif ($filter === 'urgent'): ?>
                    <i class="bi bi-fire text-danger"></i> Tiket Darurat / Urgent
                <?php elseif (!empty($selectedCatName)): ?>
                    <i class="bi bi-tag-fill text-primary"></i> Kategori: <?= htmlspecialchars($selectedCatName) ?>
                <?php else: ?>
                    <i class="bi bi-inbox-fill text-primary"></i> Semua Kotak Tiket (Inbox)
                <?php endif; ?>
            </h2>
            <p class="text-muted small m-0">
                <?= $currentUser['role'] === 'it' ? 'Panel pemantauan dan pengelolaan kendala IT staf kantor.' : 'Daftar laporan kendala sarana IT yang diajukan oleh staf.' ?>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="index.php?page=create_ticket" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 rounded-3 shadow-sm fw-semibold">
                <i class="bi bi-plus-lg"></i> + Buat Tiket Baru
            </a>
        </div>
    </div>

    <!-- Mailbox Main Card -->
    <div class="mailbox-card">
        <!-- Mailbox Search & Filters Toolbar -->
        <div class="mailbox-toolbar">
            <div class="mailbox-toolbar-left">
                <!-- Search Input Realtime -->
                <div class="header-search">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="ticketSearchInput" placeholder="Cari judul, nama pelapor, ruangan...">
                </div>

                <!-- Filter Status Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle px-3 py-1 rounded-3 small" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel text-muted me-1"></i> 
                        Status: <?= !empty($statusFilter) ? htmlspecialchars(getStatusText($statusFilter)) : 'Semua' ?>
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>">Semua Status</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&status=open">Menunggu Respon (Open)</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&status=in_progress">Sedang Dikerjakan</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&status=pending">Menunggu Konfirmasi</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&status=resolved">Selesai Ditangani</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&status=closed">Ditutup</a></li>
                    </ul>
                </div>

                <!-- Filter Prioritas Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle px-3 py-1 rounded-3 small" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-flag text-muted me-1"></i> 
                        Prioritas: <?= !empty($priorityFilter) ? htmlspecialchars(ucfirst($priorityFilter)) : 'Semua' ?>
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>">Semua Prioritas</a></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="index.php?page=tickets&filter=<?= $filter ?>&priority=urgent"><i class="bi bi-fire"></i> Darurat</a></li>
                        <li><a class="dropdown-item text-warning" href="index.php?page=tickets&filter=<?= $filter ?>&priority=high"><i class="bi bi-exclamation-triangle"></i> Tinggi</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&priority=medium"><i class="bi bi-dash-circle"></i> Normal</a></li>
                        <li><a class="dropdown-item" href="index.php?page=tickets&filter=<?= $filter ?>&priority=low"><i class="bi bi-arrow-down-circle"></i> Rendah</a></li>
                    </ul>
                </div>

                <?php if (!empty($categoryFilter) || !empty($statusFilter) || !empty($priorityFilter) || $filter !== 'all'): ?>
                    <a href="index.php?page=tickets" class="btn btn-sm btn-outline-secondary px-2 py-1 rounded-3 small" title="Reset Semua Filter">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                <?php endif; ?>
            </div>

            <div class="text-muted small">
                Total: <strong><?= count($tickets) ?></strong> Tiket
            </div>
        </div>

        <!-- Ticket Rows List (Email Style) -->
        <?php if (count($tickets) > 0): ?>
            <div class="mailbox-list">
                <?php foreach ($tickets as $t): ?>
                    <a href="index.php?page=show_ticket&id=<?= $t['id'] ?>" class="ticket-row <?= ($t['priority'] === 'urgent' && $t['status'] !== 'closed') ? 'is-urgent' : '' ?>">
                        <!-- Info Pelapor -->
                        <div class="ticket-row-user">
                            <div class="ticket-user-avatar">
                                <?= strtoupper(substr($t['reporter_name'] ?: 'S', 0, 1)) ?>
                            </div>
                            <div class="ticket-user-info">
                                <div class="ticket-user-name"><?= htmlspecialchars($t['reporter_name'] ?: 'Staf') ?></div>
                                <div class="ticket-user-dept"><?= htmlspecialchars($t['reporter_dept'] ?: 'Kantor') ?></div>
                            </div>
                        </div>

                        <!-- Info Judul & Snippet Deskripsi Kendala -->
                        <div class="ticket-row-content">
                            <div class="ticket-row-title">
                                <span class="ticket-code-tag"><?= htmlspecialchars($t['ticket_code']) ?></span>
                                <span class="text-truncate"><?= htmlspecialchars($t['title']) ?></span>
                                <?php if (!empty($t['attachment'])): ?>
                                    <i class="bi bi-paperclip text-muted small ms-1" title="Ada Lampiran Bukti"></i>
                                <?php endif; ?>
                                <?php if ($t['reply_count'] > 0): ?>
                                    <span class="badge bg-light text-muted border small px-2 py-0 ms-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-chat-left-text"></i> <?= $t['reply_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="ticket-row-snippet">
                                <span class="badge rounded-pill me-1" style="background-color: <?= $t['category_color'] ?>18; color: <?= $t['category_color'] ?>; border: 1px solid <?= $t['category_color'] ?>40; font-size: 0.7rem; padding: 2px 8px;">
                                    <i class="bi <?= $t['category_icon'] ?>"></i> <?= htmlspecialchars($t['category_name']) ?>
                                </span>
                                <?= htmlspecialchars(mb_substr($t['description'], 0, 100)) ?>...
                            </div>
                        </div>

                        <!-- Meta Info: Prioritas, Status, Waktu -->
                        <div class="ticket-row-meta">
                            <?= getPriorityBadge($t['priority']) ?>
                            <?= getStatusBadge($t['status']) ?>
                            <div class="text-muted small text-end" style="min-width: 90px;" title="<?= formatDateIndo($t['created_at']) ?>">
                                <i class="bi bi-clock me-1"></i><?= timeAgo($t['created_at']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Tampilan Kosong Jika Tidak Ada Tiket -->
            <div class="text-center py-5 px-3">
                <div class="mb-3">
                    <i class="bi bi-inbox text-muted" style="font-size: 3.5rem;"></i>
                </div>
                <h5 class="fw-bold text-dark">Tidak Ada Tiket Ditemukan</h5>
                <p class="text-muted small mx-auto" style="max-width: 420px;">
                    Belum ada tiket pada kategori atau filter ini. Staf dapat membuat tiket baru jika mengalami kendala sarana IT.
                </p>
                <a href="index.php?page=create_ticket" class="btn btn-primary btn-sm px-4 py-2 rounded-3 mt-2">
                    <i class="bi bi-plus-lg me-1"></i> Buat Tiket Sekarang
                </a>
            </div>
        <?php endif; ?>

        <!-- Search Empty Notice for JS -->
        <div id="searchEmptyNotice" class="text-center py-5 px-3" style="display: none;">
            <i class="bi bi-search text-muted fs-2 mb-2"></i>
            <h6 class="fw-bold text-dark">Tidak ada tiket yang cocok dengan pencarian</h6>
            <p class="text-muted small">Coba gunakan kata kunci yang lebih umum.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
