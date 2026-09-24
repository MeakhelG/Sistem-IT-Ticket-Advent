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
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$currentUser = getCurrentUser();
$db = getDB();

// Bangun query SQL dinamis
$whereSql = "";
$params = [];

if ($filter === 'my_tickets') {
    $whereSql .= " AND t.user_id = ?";
    $params[] = $currentUser['id'];
} elseif ($filter === 'in_progress') {
    $whereSql .= " AND t.status = 'in_progress'";
} elseif ($filter === 'pending') {
    $whereSql .= " AND t.status = 'pending'";
} elseif ($filter === 'resolved') {
    $whereSql .= " AND (t.status = 'resolved' OR t.status = 'closed')";
} elseif ($filter === 'urgent') {
    $whereSql .= " AND t.priority = 'urgent' AND t.status != 'closed'";
}

if (!empty($categoryFilter)) {
    $whereSql .= " AND t.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($priorityFilter)) {
    $whereSql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}

if (!empty($statusFilter)) {
    $whereSql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

// 1. Hitung Total Data untuk Pagination Bar
$countSql = "SELECT COUNT(*) FROM tickets t WHERE 1=1" . $whereSql;
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalTickets = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalTickets / $perPage));

// 2. Tentukan Urutan Sorting
$orderBy = "CASE WHEN t.priority = 'urgent' AND t.status != 'closed' THEN 0 ELSE 1 END, t.created_at DESC";
if ($sort === 'oldest') {
    $orderBy = "t.created_at ASC";
} elseif ($sort === 'priority_desc') {
    $orderBy = "CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END ASC, t.created_at DESC";
} elseif ($sort === 'priority_asc') {
    $orderBy = "CASE t.priority WHEN 'low' THEN 1 WHEN 'medium' THEN 2 WHEN 'high' THEN 3 WHEN 'urgent' THEN 4 ELSE 5 END ASC, t.created_at DESC";
} elseif ($sort === 'updated') {
    $orderBy = "t.updated_at DESC";
}

$sql = "
    SELECT t.*, 
           c.name as category_name, c.icon as category_icon, c.color as category_color,
           (SELECT COUNT(*) FROM ticket_replies r WHERE r.ticket_id = t.id) as reply_count
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    WHERE 1=1 {$whereSql}
    ORDER BY {$orderBy}
    LIMIT {$perPage} OFFSET {$offset}
";

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

// Helper untuk membuat link URL pagination & filter dengan mempertahankan parameter aktif
function buildFilterUrl($newParams = []) {
    $params = array_merge($_GET, $newParams);
    return 'index.php?' . http_build_query($params);
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

                <!-- Quick Sort Dropdown -->
                <?php
                $sortLabels = [
                    'newest'        => 'Terbaru',
                    'oldest'        => 'Paling Lama',
                    'priority_desc' => 'Prioritas Tinggi',
                    'priority_asc'  => 'Prioritas Rendah',
                    'updated'       => 'Baru Dibalas'
                ];
                ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle px-3 py-1 rounded-3 small" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-sort-down text-primary me-1"></i> 
                        Urutkan: <strong><?= $sortLabels[$sort] ?? 'Terbaru' ?></strong>
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item <?= $sort === 'newest' ? 'active' : '' ?>" href="<?= buildFilterUrl(['sort' => 'newest', 'p' => 1]) ?>"><i class="bi bi-calendar-event me-2"></i> Terbaru Dibuat (Default)</a></li>
                        <li><a class="dropdown-item <?= $sort === 'oldest' ? 'active' : '' ?>" href="<?= buildFilterUrl(['sort' => 'oldest', 'p' => 1]) ?>"><i class="bi bi-hourglass-bottom me-2"></i> Paling Lama Menunggu</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= $sort === 'priority_desc' ? 'active' : '' ?>" href="<?= buildFilterUrl(['sort' => 'priority_desc', 'p' => 1]) ?>"><i class="bi bi-fire text-danger me-2"></i> Prioritas Tertinggi (Urgent &rarr; Low)</a></li>
                        <li><a class="dropdown-item <?= $sort === 'priority_asc' ? 'active' : '' ?>" href="<?= buildFilterUrl(['sort' => 'priority_asc', 'p' => 1]) ?>"><i class="bi bi-arrow-up text-secondary me-2"></i> Prioritas Terendah (Low &rarr; Urgent)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= $sort === 'updated' ? 'active' : '' ?>" href="<?= buildFilterUrl(['sort' => 'updated', 'p' => 1]) ?>"><i class="bi bi-arrow-repeat text-success me-2"></i> Baru Dibalas / Diperbarui</a></li>
                    </ul>
                </div>

                <!-- Filter Status Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle px-3 py-1 rounded-3 small" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel text-muted me-1"></i> 
                        Status: <?= !empty($statusFilter) ? htmlspecialchars(getStatusText($statusFilter)) : 'Semua' ?>
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item <?= empty($statusFilter) ? 'active' : '' ?>" href="<?= buildFilterUrl(['status' => '', 'p' => 1]) ?>">Semua Status</a></li>
                        <li><a class="dropdown-item <?= $statusFilter === 'open' ? 'active' : '' ?>" href="<?= buildFilterUrl(['status' => 'open', 'p' => 1]) ?>">Menunggu Respon (Open)</a></li>
                        <li><a class="dropdown-item <?= $statusFilter === 'in_progress' ? 'active' : '' ?>" href="<?= buildFilterUrl(['status' => 'in_progress', 'p' => 1]) ?>">Sedang Dikerjakan</a></li>
                        <li><a class="dropdown-item <?= $statusFilter === 'pending' ? 'active' : '' ?>" href="<?= buildFilterUrl(['status' => 'pending', 'p' => 1]) ?>">Menunggu Konfirmasi</a></li>
                        <li><a class="dropdown-item <?= $statusFilter === 'resolved' ? 'active' : '' ?>" href="<?= buildFilterUrl(['status' => 'resolved', 'p' => 1]) ?>">Selesai Ditangani</a></li>
                        <li><a class="dropdown-item <?= $statusFilter === 'closed' ? 'active' : '' ?>" href="<?= buildFilterUrl(['status' => 'closed', 'p' => 1]) ?>">Ditutup</a></li>
                    </ul>
                </div>

                <!-- Filter Prioritas Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle px-3 py-1 rounded-3 small" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-flag text-muted me-1"></i> 
                        Prioritas: <?= !empty($priorityFilter) ? htmlspecialchars(ucfirst($priorityFilter)) : 'Semua' ?>
                    </button>
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item <?= empty($priorityFilter) ? 'active' : '' ?>" href="<?= buildFilterUrl(['priority' => '', 'p' => 1]) ?>">Semua Prioritas</a></li>
                        <li><a class="dropdown-item text-danger fw-bold <?= $priorityFilter === 'urgent' ? 'active' : '' ?>" href="<?= buildFilterUrl(['priority' => 'urgent', 'p' => 1]) ?>"><i class="bi bi-fire"></i> Darurat</a></li>
                        <li><a class="dropdown-item text-warning <?= $priorityFilter === 'high' ? 'active' : '' ?>" href="<?= buildFilterUrl(['priority' => 'high', 'p' => 1]) ?>"><i class="bi bi-exclamation-triangle"></i> Tinggi</a></li>
                        <li><a class="dropdown-item <?= $priorityFilter === 'medium' ? 'active' : '' ?>" href="<?= buildFilterUrl(['priority' => 'medium', 'p' => 1]) ?>"><i class="bi bi-dash-circle"></i> Normal</a></li>
                        <li><a class="dropdown-item <?= $priorityFilter === 'low' ? 'active' : '' ?>" href="<?= buildFilterUrl(['priority' => 'low', 'p' => 1]) ?>"><i class="bi bi-arrow-down-circle"></i> Rendah</a></li>
                    </ul>
                </div>

                <?php if (!empty($categoryFilter) || !empty($statusFilter) || !empty($priorityFilter) || $filter !== 'all' || $sort !== 'newest'): ?>
                    <a href="index.php?page=tickets" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-3 small" title="Reset Semua Filter">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                <?php endif; ?>
            </div>

            <div class="text-muted small">
                Total: <strong><?= $totalTickets ?></strong> Tiket
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

        <!-- Pagination Bar -->
        <?php if ($totalTickets > 0): ?>
            <div class="mailbox-footer p-3 border-top bg-light-subtle d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="text-muted small">
                    Menampilkan <strong><?= $totalTickets > 0 ? ($offset + 1) : 0 ?></strong> - <strong><?= min($offset + $perPage, $totalTickets) ?></strong> dari <strong><?= $totalTickets ?></strong> tiket
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Navigasi Halaman Tiket">
                        <ul class="pagination pagination-sm m-0 gap-1">
                            <!-- Tombol Sebelumnya -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link rounded-2" href="<?= buildFilterUrl(['p' => max(1, $page - 1)]) ?>" aria-label="Sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <!-- Nomor Halaman -->
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <?php if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)): ?>
                                    <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                                        <a class="page-link rounded-2 <?= $p == $page ? 'fw-bold' : '' ?>" href="<?= buildFilterUrl(['p' => $p]) ?>"><?= $p ?></a>
                                    </li>
                                <?php elseif ($p == $page - 3 || $p == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link rounded-2 border-0 bg-transparent text-muted">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Tombol Berikutnya -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link rounded-2" href="<?= buildFilterUrl(['p' => min($totalPages, $page + 1)]) ?>" aria-label="Berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
