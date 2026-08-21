<?php
/**
 * Sistem IT Ticket Advent - Layout Header & Topbar
 */
$currentUser = getCurrentUser();
$db = getDB();
$allUsers = $db->query("SELECT id, name, role, department FROM users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Fabkin & Advent Clean Theme Stylesheet -->
    <link rel="stylesheet" href="assets/css/fabkin.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Dimasukkan Terpisah -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Topbar Header -->
        <header class="app-header">
            <div class="header-left">
                <!-- Toggle Button Mobile & Desktop -->
                <button class="sidebar-toggle-btn me-2" id="sidebarToggle" type="button" title="Buka / Tutup Menu">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h1 class="header-title fs-6 m-0 fw-bold text-dark">
                        <?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Daftar Tiket IT' ?>
                    </h1>
                </div>
            </div>

            <div class="header-right">
                <!-- Dropdown Pilih Peran -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 px-3 py-1 rounded-3 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-badge text-primary"></i>
                        <span class="d-none d-md-inline text-muted small">Peran:</span>
                        <strong class="text-dark small"><?= $currentUser['role'] === 'it' ? 'Tim IT' : 'Staf Kantor' ?></strong>
                        <?= getRoleBadge($currentUser['role']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="min-width: 240px; border-radius: 10px;">
                        <li class="dropdown-header text-uppercase small fw-bold text-muted px-3 py-1">Pilih Peran Sistem:</li>
                        <?php foreach ($allUsers as $u): ?>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 px-3 d-flex align-items-center justify-content-between <?= $u['id'] == $currentUser['id'] ? 'bg-primary-subtle active fw-bold' : '' ?>" href="index.php?page=switch_user&user_id=<?= $u['id'] ?>">
                                    <div>
                                        <div class="small fw-semibold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($u['department']) ?></div>
                                    </div>
                                    <div><?= getRoleBadge($u['role']) ?></div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Info User Profil Singkat -->
                <div class="user-profile-badge d-none d-sm-flex">
                    <div class="user-avatar-circle">
                        <i class="bi <?= $currentUser['role'] === 'it' ? 'bi-shield-fill-check' : 'bi-person-fill' ?>"></i>
                    </div>
                    <div class="text-start">
                        <div class="fw-bold small text-dark lh-1"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="text-muted" style="font-size: 0.7rem;"><?= $currentUser['role'] === 'it' ? 'Admin IT' : 'Staf' ?></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Body -->
        <main class="app-content">
            <?php 
            $flash = getFlash();
            if ($flash): 
            ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show auto-dismiss-alert shadow-sm border-0 rounded-3 mb-3 py-2 px-3" role="alert">
                    <div class="d-flex align-items-center gap-2 small">
                        <i class="bi <?= $flash['type'] == 'success' ? 'bi-check-circle-fill text-success' : 'bi-info-circle-fill text-primary' ?> fs-6"></i>
                        <div><?= $flash['message'] ?></div>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
