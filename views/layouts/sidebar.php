<?php
/**
 * Sistem IT Ticket Advent - Layout Sidebar
 * Tampilan Putih Bersih Persis Seperti Sistem Absensi Pegawai Advent
 */
$currentUser = getCurrentUser();
$counts = getTicketCounts();
$currentFilter = $_GET['filter'] ?? 'all';
$currentCategory = $_GET['category'] ?? '';
$currentPage = $_GET['page'] ?? 'tickets';

$db = getDB();
$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Target switch role ID
$otherUserId = ($currentUser['role'] === 'it') ? 2 : 1;
$otherRoleName = ($currentUser['role'] === 'it') ? 'Staf Kantor' : 'Tim IT';
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Header Logo (Hanya Logo & Teks, Tanpa Duplikat Tombol Toggle) -->
    <div class="sidebar-header">
        <a href="index.php?page=tickets" class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="bi bi-ticket-perforated-fill"></i>
            </div>
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title">Sistem IT Ticket</span>
                <span class="sidebar-logo-sub">Advent Support</span>
            </div>
        </a>
    </div>

    <!-- Body Sidebar -->
    <div class="sidebar-body">
        
        <!-- 1. MENU UTAMA -->
        <div class="sidebar-section-title">MENU UTAMA</div>
        <ul class="sidebar-menu">
            <li class="sidebar-menu-item">
                <a href="index.php?page=tickets&filter=all" class="sidebar-menu-link <?= ($currentPage === 'tickets' && $currentFilter === 'all' && empty($currentCategory)) ? 'active' : '' ?>" title="Dashboard Tiket">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-grid-fill menu-icon"></i>
                        <span class="menu-text">Dashboard Tiket</span>
                    </div>
                    <span class="badge-count"><?= $counts['all'] ?></span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a href="index.php?page=create_ticket" class="sidebar-menu-link <?= ($currentPage === 'create_ticket') ? 'active' : '' ?>" title="Buat Tiket Baru">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-plus-circle-fill menu-icon text-success"></i>
                        <span class="menu-text">+ Buat Tiket Baru</span>
                    </div>
                </a>
            </li>
        </ul>

        <!-- 2. STATUS & ALUR TIKET -->
        <div class="sidebar-section-title">STATUS & ALUR TIKET</div>
        <ul class="sidebar-menu">
            <?php if ($currentUser['role'] === 'staff'): ?>
                <li class="sidebar-menu-item">
                    <a href="index.php?page=tickets&filter=my_tickets" class="sidebar-menu-link <?= ($currentFilter === 'my_tickets') ? 'active' : '' ?>" title="Tiket Saya">
                        <div class="sidebar-menu-link-inner">
                            <i class="bi bi-person-workspace menu-icon"></i>
                            <span class="menu-text">Tiket Saya</span>
                        </div>
                        <span class="badge-count"><?= $counts['my_tickets'] ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="sidebar-menu-item">
                <a href="index.php?page=tickets&filter=in_progress" class="sidebar-menu-link <?= ($currentFilter === 'in_progress') ? 'active' : '' ?>" title="Sedang Diproses">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-arrow-repeat menu-icon text-warning"></i>
                        <span class="menu-text">Sedang Diproses</span>
                    </div>
                    <span class="badge-count"><?= $counts['in_progress'] ?></span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="index.php?page=tickets&filter=pending" class="sidebar-menu-link <?= ($currentFilter === 'pending') ? 'active' : '' ?>" title="Menunggu Konfirmasi">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-hourglass-split menu-icon text-secondary"></i>
                        <span class="menu-text">Menunggu Konfirmasi</span>
                    </div>
                    <span class="badge-count"><?= $counts['pending'] ?></span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="index.php?page=tickets&filter=resolved" class="sidebar-menu-link <?= ($currentFilter === 'resolved') ? 'active' : '' ?>" title="Tiket Selesai">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-check-circle-fill menu-icon text-success"></i>
                        <span class="menu-text">Tiket Selesai</span>
                    </div>
                    <span class="badge-count"><?= $counts['resolved'] ?></span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="index.php?page=tickets&filter=urgent" class="sidebar-menu-link <?= ($currentFilter === 'urgent') ? 'active' : '' ?>" title="Tiket Darurat">
                    <div class="sidebar-menu-link-inner text-danger">
                        <i class="bi bi-fire menu-icon text-danger"></i>
                        <span class="menu-text">Tiket Darurat</span>
                    </div>
                    <span class="badge-count bg-danger text-white border-0"><?= $counts['urgent'] ?></span>
                </a>
            </li>
        </ul>

        <!-- 3. KATEGORI KENDALA -->
        <div class="sidebar-section-title">KATEGORI KENDALA</div>
        <ul class="sidebar-menu">
            <?php foreach ($categories as $cat): ?>
                <li class="sidebar-menu-item">
                    <a href="index.php?page=tickets&category=<?= $cat['id'] ?>" class="sidebar-menu-link <?= ($currentCategory == $cat['id']) ? 'active' : '' ?>" title="<?= htmlspecialchars($cat['name']) ?>">
                        <div class="sidebar-menu-link-inner text-truncate">
                            <span class="cat-dot" style="background-color: <?= htmlspecialchars($cat['color']) ?>;"></span>
                            <span class="menu-text"><?= htmlspecialchars($cat['name']) ?></span>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- 4. SISTEM -->
        <div class="sidebar-section-title">SISTEM</div>
        <ul class="sidebar-menu">
            <?php if ($currentUser['role'] === 'it'): ?>
                <li class="sidebar-menu-item">
                    <a href="index.php?page=dashboard" class="sidebar-menu-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>" title="Laporan & Statistik">
                        <div class="sidebar-menu-link-inner">
                            <i class="bi bi-bar-chart-line-fill menu-icon"></i>
                            <span class="menu-text">Laporan & Statistik</span>
                        </div>
                    </a>
                </li>
            <?php endif; ?>

            <li class="sidebar-menu-item">
                <a href="index.php?page=about" class="sidebar-menu-link <?= ($currentPage === 'about') ? 'active' : '' ?>" title="Panduan Alur Tiket">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-info-circle-fill menu-icon"></i>
                        <span class="menu-text">Panduan Alur Tiket</span>
                    </div>
                </a>
            </li>

            <!-- Tombol Ganti Peran (Aksen Merah Seperti Keluar di Absensi) -->
            <li class="sidebar-menu-item mt-2 pt-2 border-top">
                <a href="index.php?page=switch_user&user_id=<?= $otherUserId ?>" class="sidebar-menu-link menu-danger" title="Ganti ke <?= $otherRoleName ?>">
                    <div class="sidebar-menu-link-inner">
                        <i class="bi bi-box-arrow-right menu-icon"></i>
                        <span class="menu-text">Ganti ke <?= $otherRoleName ?></span>
                    </div>
                </a>
            </li>
        </ul>

    </div>
</aside>
