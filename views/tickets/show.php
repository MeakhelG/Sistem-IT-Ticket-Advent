<?php
/**
 * Sistem IT Ticket Advent - Halaman Detail Tiket & Thread Balasan
 * Disesuaikan untuk 2 Role: Tim IT dan Staf Kantor
 */
$ticketId = $_GET['id'] ?? 0;
$db = getDB();
$currentUser = getCurrentUser();
$isTimIT = ($currentUser['role'] === 'it');

// Ambil data tiket lengkap
$stmt = $db->prepare("
    SELECT t.*, 
           c.name as category_name, c.icon as category_icon, c.color as category_color
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('danger', 'Tiket tidak ditemukan.');
    header('Location: index.php?page=tickets');
    exit;
}

$pageTitle = 'Tiket ' . $ticket['ticket_code'] . ' - ' . $ticket['title'];
require_once __DIR__ . '/../layouts/header.php';

// Ambil semua balasan (replies)
$replySql = "
    SELECT r.*, u.name as user_name, u.role as user_role
    FROM ticket_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.ticket_id = ?
";
if (!$isTimIT) {
    // Staf biasa tidak melihat catatan internal IT
    $replySql .= " AND r.is_internal = 0";
}
$replySql .= " ORDER BY r.created_at ASC";
$replyStmt = $db->prepare($replySql);
$replyStmt->execute([$ticketId]);
$replies = $replyStmt->fetchAll();

// Ambil log aktivitas riwayat tiket
$logs = $db->prepare("
    SELECT l.*, u.name as user_name 
    FROM ticket_logs l 
    JOIN users u ON l.user_id = u.id 
    WHERE l.ticket_id = ? 
    ORDER BY l.created_at DESC
");
$logs->execute([$ticketId]);
$ticketLogs = $logs->fetchAll();
?>

<div class="container-fluid p-0">
    <!-- Top Action Bar -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="index.php?page=tickets" class="btn btn-light btn-sm border px-3 py-1 rounded-3 shadow-sm text-dark fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Kotak Tiket
            </a>
            <span class="badge bg-dark-subtle text-dark font-monospace px-2 py-1 rounded-3 small">
                <?= htmlspecialchars($ticket['ticket_code']) ?>
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="index.php?page=print_ticket&id=<?= $ticket['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm px-3 py-1 rounded-3 btn-no-print">
                <i class="bi bi-printer me-1"></i> Cetak Lembar Kerja
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Kolom Kiri: Thread Percakapan & Detail Kendala -->
        <div class="<?= $isTimIT ? 'col-lg-8' : 'col-lg-9' ?>">
            <!-- Header Kartu Tiket -->
            <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                <div class="card-header bg-white p-3 px-4 border-bottom">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge rounded-pill px-2 py-1 small" style="background-color: <?= $ticket['category_color'] ?>20; color: <?= $ticket['category_color'] ?>; border: 1px solid <?= $ticket['category_color'] ?>40;">
                                <i class="bi <?= $ticket['category_icon'] ?> me-1"></i> <?= htmlspecialchars($ticket['category_name']) ?>
                            </span>
                            <?= getPriorityBadge($ticket['priority']) ?>
                            <?= getStatusBadge($ticket['status']) ?>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock me-1"></i> <?= timeAgo($ticket['created_at']) ?>
                        </div>
                    </div>
                    <h3 class="fs-5 fw-bold text-dark mb-2 mt-1"><?= htmlspecialchars($ticket['title']) ?></h3>
                    
                    <!-- Metadata Bar Pelapor & Ruangan -->
                    <div class="d-flex align-items-center gap-3 flex-wrap p-2 px-3 bg-light rounded-3 small text-muted">
                        <div>
                            Pelapor: <strong class="text-dark"><?= htmlspecialchars($ticket['reporter_name']) ?></strong> (<?= htmlspecialchars($ticket['reporter_dept']) ?>)
                        </div>
                        <div>•</div>
                        <div>
                            Lokasi / Ruangan: <strong class="text-dark"><?= htmlspecialchars($ticket['location']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Pesan Pertama (Laporan Kendala Awal) -->
                <div class="card-body p-4 bg-white">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="ticket-user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                            <?= strtoupper(substr($ticket['reporter_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark small"><?= htmlspecialchars($ticket['reporter_name']) ?></div>
                            <div class="text-muted" style="font-size: 0.72rem;"><?= formatDateIndo($ticket['created_at']) ?></div>
                        </div>
                    </div>

                    <div class="p-3 bg-light-subtle rounded-3 border mb-2" style="font-size: 0.92rem; line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></div>

                    <!-- Lampiran Foto Laporan Awal -->
                    <?php if (!empty($ticket['attachment']) && file_exists(__DIR__ . '/../../uploads/' . $ticket['attachment'])): ?>
                        <div class="mt-2 p-2 bg-light rounded-3 border d-inline-block">
                            <div class="small fw-bold text-muted mb-1"><i class="bi bi-paperclip"></i> Foto Kendala:</div>
                            <img src="uploads/<?= htmlspecialchars($ticket['attachment']) ?>" class="img-thumbnail zoomable-image shadow-sm" style="max-height: 200px; max-width: 300px; object-fit: cover;" alt="Lampiran Foto" title="Klik untuk memperbesar">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thread Balasan Percakapan (Email Style Thread) -->
            <?php if (count($replies) > 0): ?>
                <div class="mb-3">
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-chat-dots-fill text-primary"></i> Riwayat Balasan & Komunikasi (<?= count($replies) ?>)
                    </h6>

                    <?php foreach ($replies as $rep): ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-2 <?= $rep['is_internal'] ? 'border-start border-4 border-warning bg-warning-subtle' : 'bg-white' ?>">
                            <div class="card-body p-3 px-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="ticket-user-avatar <?= $rep['user_role'] === 'it' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' ?>" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                            <i class="bi <?= $rep['user_role'] === 'it' ? 'bi-tools' : 'bi-person' ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small d-flex align-items-center gap-2">
                                                <?= htmlspecialchars($rep['sender_name'] ?: $rep['user_name']) ?>
                                                <?= getRoleBadge($rep['user_role']) ?>
                                                <?php if ($rep['is_internal']): ?>
                                                    <span class="badge bg-warning text-dark small"><i class="bi bi-shield-lock"></i> Catatan Internal IT</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.72rem;"><?= timeAgo($rep['created_at']) ?> • <?= formatDateIndo($rep['created_at']) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div style="font-size: 0.9rem; line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($rep['message']) ?></div>

                                <?php if (!empty($rep['attachment']) && file_exists(__DIR__ . '/../../uploads/' . $rep['attachment'])): ?>
                                    <div class="mt-2">
                                        <img src="uploads/<?= htmlspecialchars($rep['attachment']) ?>" class="img-thumbnail zoomable-image shadow-sm" style="max-height: 160px; max-width: 240px; object-fit: cover;" alt="Lampiran Balasan" title="Klik untuk memperbesar">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Solusi Akhir & Rating Jika Selesai -->
            <?php if ($ticket['status'] === 'resolved' || $ticket['status'] === 'closed'): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-success-subtle border-start border-4 border-success">
                    <div class="card-body p-3 px-4">
                        <h6 class="fw-bold text-success mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Solusi / Tindakan Perbaikan Tim IT
                        </h6>
                        <p class="mb-2 text-dark small" style="white-space: pre-wrap;"><?= !empty($ticket['solution']) ? htmlspecialchars($ticket['solution']) : 'Kendala telah berhasil diperbaiki dan dikonfirmasi selesai oleh Tim IT.' ?></p>

                        <!-- Rating Kepuasan Pelapor -->
                        <?php if ($ticket['rating']): ?>
                            <div class="p-2 px-3 bg-white rounded-3 border mt-2">
                                <div class="small fw-bold text-muted">Penilaian Kepuasan dari Pelapor:</div>
                                <div class="text-warning fs-6">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star-fill <?= $i <= $ticket['rating'] ? 'text-warning' : 'text-muted opacity-25' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-dark small fw-bold ms-2">(<?= $ticket['rating'] ?>/5 Bintang)</span>
                                </div>
                                <?php if (!empty($ticket['rating_feedback'])): ?>
                                    <p class="small text-muted mb-0 fst-italic">"<?= htmlspecialchars($ticket['rating_feedback']) ?>"</p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($currentUser['role'] === 'staff'): ?>
                            <!-- Form Berikan Rating untuk Pelapor -->
                            <div class="p-3 bg-white rounded-3 border mt-2">
                                <h6 class="fw-bold text-dark small mb-1">Beri Nilai Pelayanan Tim IT:</h6>
                                <form action="index.php?action=rate_ticket" method="POST">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                    <div class="mb-2">
                                        <div class="rating-stars-input">
                                            <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                                            <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                                            <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                                            <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                                            <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <input type="text" class="form-control form-control-sm" name="rating_feedback" placeholder="Tuliskan ulasan singkat atau ucapan terima kasih...">
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-success px-3 fw-semibold">
                                        Kirim Penilaian
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form Balas Cepat (Quick Reply) -->
            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 reply-form-box">
                    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold m-0 text-dark small d-flex align-items-center gap-2">
                            <i class="bi bi-reply-fill text-primary"></i> Tulis Tanggapan / Balasan
                        </h6>
                        <span class="small text-muted">Sebagai: <strong><?= $isTimIT ? 'Tim IT Advent' : 'Staf Kantor' ?></strong></span>
                    </div>
                    <div class="card-body p-3 px-4 bg-white">
                        <form action="index.php?action=reply_ticket" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            
                            <div class="mb-2">
                                <textarea class="form-control rounded-3" name="message" rows="3" placeholder="Tulis balasan pesan di sini (konfirmasi kedatangan teknisi, petunjuk, atau info tambahan)..." required></textarea>
                            </div>

                            <div class="row align-items-center g-2">
                                <div class="col-md-6">
                                    <input type="file" class="form-control form-control-sm" name="attachment" accept="image/*,application/pdf" title="Lampirkan foto">
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <?php if ($isTimIT): ?>
                                        <div class="form-check form-check-inline me-2">
                                            <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="internalCheck">
                                            <label class="form-check-label small fw-semibold text-warning-emphasis" for="internalCheck">
                                                <i class="bi bi-shield-lock"></i> Catatan Internal IT
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary btn-sm px-3 py-2 rounded-3 fw-bold shadow-sm">
                                        <i class="bi bi-send-fill me-1"></i> Kirim Balasan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kolom Kanan: Panel Aksi Khusus Tim IT -->
        <?php if ($isTimIT): ?>
            <div class="col-lg-4 action-sidebar-col">
                <!-- Panel Kontrol Tim IT -->
                <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden border-top border-4 border-primary">
                    <div class="card-header bg-white p-3 border-bottom">
                        <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                            <i class="bi bi-sliders text-primary"></i> Tindakan Tim IT
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Form Ubah Status Tiket -->
                        <form action="index.php?action=update_status" method="POST">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <label class="form-label fw-bold small text-muted text-uppercase">Ubah Status Tiket</label>
                            <select name="status" class="form-select form-select-sm mb-2 rounded-3 fw-semibold" onchange="toggleSolutionField(this.value)">
                                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Menunggu Respon (Open)</option>
                                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>Sedang Dikerjakan (In Progress)</option>
                                <option value="pending" <?= $ticket['status'] === 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi (Pending)</option>
                                <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Selesai Ditangani (Resolved)</option>
                                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Tutup Tiket (Closed)</option>
                            </select>

                            <!-- Field Solusi Muncul Jika Memilih Resolved -->
                            <div id="solutionBox" class="mb-2" style="display: <?= ($ticket['status'] === 'resolved' || $ticket['status'] === 'closed') ? 'block' : 'none' ?>;">
                                <label class="form-label small fw-bold text-success">Keterangan Solusi / Perbaikan:</label>
                                <textarea name="solution" class="form-control form-control-sm rounded-3" rows="3" placeholder="Tuliskan tindakan perbaikan yang dilakukan..."><?= htmlspecialchars($ticket['solution']) ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2 rounded-3">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Status Baru
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Riwayat Status Tiket -->
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-header bg-white p-3 border-bottom">
                        <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2 small">
                            <i class="bi bi-journal-text text-primary"></i> Riwayat Audit Log
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline-logs small">
                            <?php foreach ($ticketLogs as $log): ?>
                                <div class="mb-2 pb-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-dark" style="font-size: 0.8rem;"><?= htmlspecialchars($log['action']) ?></strong>
                                        <span class="text-muted" style="font-size: 0.7rem;"><?= timeAgo($log['created_at']) ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($log['notes']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Kolom Kanan untuk Staf: Info Singkat & Bantuan -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 bg-light">
                    <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-info-circle text-primary"></i> Status Tiket Anda</h6>
                    <div class="mb-2">
                        <?= getStatusBadge($ticket['status']) ?>
                    </div>
                    <p class="small text-muted mb-0">
                        Tim IT akan datang ke ruangan Anda atau membalas pesan di kolom tanggapan.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSolutionField(val) {
    const box = document.getElementById('solutionBox');
    if (box) {
        box.style.display = (val === 'resolved' || val === 'closed') ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
