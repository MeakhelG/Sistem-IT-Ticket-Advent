<?php
/**
 * Sistem IT Ticket Advent - Dashboard & Statistik Ringkasan
 */
$pageTitle = 'Dashboard & Laporan Tiket IT';
require_once __DIR__ . '/layouts/header.php';

$db = getDB();
$counts = getTicketCounts();

// Statistik Kategori
$catStats = $db->query("
    SELECT c.name, c.icon, c.color, COUNT(t.id) as total
    FROM categories c
    LEFT JOIN tickets t ON c.id = t.category_id
    GROUP BY c.id
    ORDER BY total DESC
")->fetchAll();

// 5 Tiket Terbaru
$latestTickets = $db->query("
    SELECT t.*, c.name as category_name, c.color as category_color, c.icon as category_icon
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll();

// Rating Rata-rata
$avgRating = $db->query("SELECT AVG(rating) as avg_r, COUNT(rating) as cnt_r FROM tickets WHERE rating IS NOT NULL")->fetch();
$ratingVal = round($avgRating['avg_r'] ?: 0, 1);
?>

<div class="container-fluid p-0">
    <!-- Header Dashboard -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="fs-5 fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i> Dashboard & Laporan Layanan Tim IT
            </h2>
            <p class="text-muted small m-0">
                Statistik penanganan kendala IT dan evaluasi kepuasan layanan staf kantor.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php?page=create_ticket" class="btn btn-primary btn-sm px-3 py-2 rounded-3 shadow-sm fw-semibold">
                <i class="bi bi-plus-lg me-1"></i> Buat Tiket Baru
            </a>
        </div>
    </div>

    <!-- 4 Kartu Statistik Utama -->
    <div class="row g-3 mb-3">
        <!-- 1. Total Tiket Masuk -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Tiket Masuk</div>
                    <div class="stat-number"><?= $counts['all'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-folder2-open text-primary"></i> Keseluruhan Laporan</div>
                </div>
                <div class="stat-icon-wrapper" style="background-color: #e0e7ff; color: #4338ca;">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
            </div>
        </div>

        <!-- 2. Sedang Dikerjakan -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Sedang Diproses</div>
                    <div class="stat-number text-warning"><?= $counts['in_progress'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-arrow-repeat text-warning"></i> Ditangani Tim IT</div>
                </div>
                <div class="stat-icon-wrapper" style="background-color: #fef3c7; color: #d97706;">
                    <i class="bi bi-tools"></i>
                </div>
            </div>
        </div>

        <!-- 3. Tiket Selesai -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Selesai Ditangani</div>
                    <div class="stat-number text-success"><?= $counts['resolved'] + $counts['closed'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-check2-circle text-success"></i> Berhasil Diperbaiki</div>
                </div>
                <div class="stat-icon-wrapper" style="background-color: #d1fae5; color: #059669;">
                    <i class="bi bi-check-all"></i>
                </div>
            </div>
        </div>

        <!-- 4. Tiket Urgent / Darurat -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Tiket Darurat</div>
                    <div class="stat-number text-danger"><?= $counts['urgent'] ?></div>
                    <div class="text-muted small mt-1"><i class="bi bi-fire text-danger"></i> Prioritas Utama</div>
                </div>
                <div class="stat-icon-wrapper" style="background-color: #fee2e2; color: #dc2626;">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Grafik Kategori & Status -->
    <div class="row g-3 mb-3">
        <!-- Kolom Kiri: Statistik Kategori Kendala -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2 small">
                        <i class="bi bi-pie-chart-fill text-primary"></i> Sebaran Kategori Masalah IT
                    </h6>
                    <span class="badge bg-light text-muted border"><?= count($catStats) ?> Kategori</span>
                </div>
                <div class="card-body p-3">
                    <?php 
                    $totalAll = max(1, $counts['all']);
                    foreach ($catStats as $cs): 
                        $percentage = round(($cs['total'] / $totalAll) * 100);
                    ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-dark d-flex align-items-center gap-2">
                                    <i class="bi <?= $cs['icon'] ?>" style="color: <?= $cs['color'] ?>;"></i>
                                    <?= htmlspecialchars($cs['name']) ?>
                                </span>
                                <span>
                                    <strong><?= $cs['total'] ?></strong> Tiket (<?= $percentage ?>%)
                                </span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 4px; background-color: #f1f5f9;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%; background-color: <?= $cs['color'] ?>;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Tingkat Kepuasan & Status Ringkasan -->
        <div class="col-lg-5">
            <!-- Kepuasan Layanan Staf -->
            <div class="card border-0 shadow-sm rounded-4 mb-3 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                <div class="card-body p-3 text-center">
                    <div class="small text-white-50 text-uppercase fw-bold mb-1" style="font-size: 0.72rem;">Indeks Kepuasan Pengguna</div>
                    <div class="fs-2 fw-bold mb-1"><?= $ratingVal ?> <span class="fs-6 fw-normal opacity-75">/ 5.0</span></div>
                    <div class="text-warning fs-5 mb-1">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="bi <?= $s <= round($ratingVal) ? 'bi-star-fill text-warning' : 'bi-star text-white-50' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="small opacity-75" style="font-size: 0.75rem;">Berdasarkan <?= $avgRating['cnt_r'] ?> ulasan staf yang telah dilayani</div>
                </div>
            </div>

            <!-- Status Pelayanan -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2 small">
                        <i class="bi bi-activity text-primary"></i> Status Tiket Aktif
                    </h6>
                </div>
                <div class="card-body p-2 px-3">
                    <div class="d-flex justify-content-between align-items-center p-1 border-bottom">
                        <span class="small"><i class="bi bi-envelope-open text-info me-2"></i> Menunggu Respon</span>
                        <span class="badge bg-info-subtle text-info fw-bold"><?= $counts['open'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-1 border-bottom">
                        <span class="small"><i class="bi bi-arrow-repeat text-warning me-2"></i> Sedang Dikerjakan</span>
                        <span class="badge bg-warning-subtle text-warning fw-bold"><?= $counts['in_progress'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-1 border-bottom">
                        <span class="small"><i class="bi bi-pause-circle text-secondary me-2"></i> Menunggu Konfirmasi</span>
                        <span class="badge bg-secondary-subtle text-secondary fw-bold"><?= $counts['pending'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-1">
                        <span class="small"><i class="bi bi-check2-circle text-success me-2"></i> Selesai Ditangani</span>
                        <span class="badge bg-success-subtle text-success fw-bold"><?= $counts['resolved'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel 5 Tiket Terbaru -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2 small">
                <i class="bi bi-clock-history text-primary"></i> 5 Tiket Kendala Terkini
            </h6>
            <a href="index.php?page=tickets" class="btn btn-sm btn-link text-decoration-none small">Lihat Semua &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-muted text-uppercase" style="font-size: 0.75rem;">
                    <tr>
                        <th class="ps-3">No. Tiket</th>
                        <th>Pelapor & Ruangan</th>
                        <th>Kendala / Subjek</th>
                        <th>Kategori</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestTickets as $lt): ?>
                        <tr>
                            <td class="ps-3 font-monospace fw-bold text-primary"><?= htmlspecialchars($lt['ticket_code']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($lt['reporter_name']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($lt['location']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;"><?= htmlspecialchars($lt['title']) ?></div>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= timeAgo($lt['created_at']) ?></div>
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background-color: <?= $lt['category_color'] ?>20; color: <?= $lt['category_color'] ?>; border: 1px solid <?= $lt['category_color'] ?>40; font-size: 0.7rem; padding: 2px 8px;">
                                    <?= htmlspecialchars($lt['category_name']) ?>
                                </span>
                            </td>
                            <td><?= getPriorityBadge($lt['priority']) ?></td>
                            <td><?= getStatusBadge($lt['status']) ?></td>
                            <td class="pe-3 text-end">
                                <a href="index.php?page=show_ticket&id=<?= $lt['id'] ?>" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1" style="font-size: 0.75rem;">
                                    Buka Tiket
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
