<?php
/**
 * Sistem IT Ticket Advent - Daftar Staf & Teknisi
 */
$pageTitle = 'Daftar Pengguna & Teknisi IT';
require_once __DIR__ . '/../layouts/header.php';

$db = getDB();
$users = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM tickets t WHERE t.user_id = u.id) as ticket_count,
           (SELECT COUNT(*) FROM tickets t WHERE t.assigned_to = u.id) as assigned_count
    FROM users u
    ORDER BY CASE u.role WHEN 'admin' THEN 1 WHEN 'technician' THEN 2 ELSE 3 END, u.name ASC
")->fetchAll();
?>

<div class="container-fluid p-0">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fs-4 fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-primary"></i> Daftar Staf & Tim IT
            </h2>
            <p class="text-muted small m-0 mt-1">
                Data pengguna terdaftar dalam sistem. Anda dapat berpindah akun secara instan untuk mencoba sistem dari berbagai hak akses.
            </p>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($users as $u): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 <?= $u['id'] == $currentUser['id'] ? 'border border-2 border-primary bg-primary-subtle' : 'bg-white' ?>">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="user-avatar-circle" style="width: 48px; height: 48px; font-size: 1.1rem;">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($u['name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($u['department']) ?></div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3 small">
                            <span class="text-muted">Peran Akun:</span>
                            <?= getRoleBadge($u['role']) ?>
                        </div>

                        <div class="p-3 bg-light rounded-3 small mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="bi bi-envelope"></i> Email:</span>
                                <span class="fw-semibold text-dark text-truncate" style="max-width: 170px;"><?= htmlspecialchars($u['email']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="bi bi-telephone"></i> Telepon:</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($u['phone'] ?: '-') ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="bi bi-ticket-perforated"></i> Tiket Dibuat:</span>
                                <span class="badge bg-secondary"><?= $u['ticket_count'] ?> Tiket</span>
                            </div>
                            <?php if ($u['role'] === 'admin' || $u['role'] === 'technician'): ?>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-muted"><i class="bi bi-tools"></i> Tugas Ditangani:</span>
                                <span class="badge bg-primary"><?= $u['assigned_count'] ?> Tugas</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <a href="index.php?page=switch_user&user_id=<?= $u['id'] ?>" class="btn <?= $u['id'] == $currentUser['id'] ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm w-100 rounded-3 py-2 fw-semibold">
                            <?php if ($u['id'] == $currentUser['id']): ?>
                                <i class="bi bi-check-circle-fill me-1"></i> Sedang Digunakan (Akun Aktif)
                            <?php else: ?>
                                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sebagai Pengguna Ini
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
