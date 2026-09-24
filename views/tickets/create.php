<?php
/**
 * Sistem IT Ticket Advent - Halaman Buat Tiket Baru (Sangat Ramah Pengguna Awam)
 */
$pageTitle = 'Buat Tiket Kendala IT Baru';
require_once __DIR__ . '/../layouts/header.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$currentUser = getCurrentUser();
?>

<div class="container-fluid p-0" style="max-width: 900px;">
    <!-- Breadcrumb & Title -->
    <div class="mb-3">
        <a href="index.php?page=tickets" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1 mb-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Kotak Tiket
        </a>
        <h2 class="fs-5 fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square text-primary"></i> Formulir Pengajuan Tiket Kendala IT
        </h2>
        <p class="text-muted small m-0 mt-1">
            Silakan tuliskan kendala perangkat Anda agar Tim IT dapat segera memeriksa dan memperbaikinya.
        </p>
    </div>

    <!-- Card Form -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-header bg-primary text-white p-3 px-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold mb-0"><i class="bi bi-envelope-plus me-2"></i> Laporkan Masalah / Minta Bantuan IT</h6>
                </div>
                <div class="badge bg-white text-primary px-3 py-1 rounded-pill small fw-bold">
                    <i class="bi bi-lightning-charge-fill text-warning"></i> Respon Cepat Tim IT
                </div>
            </div>
        </div>

        <div class="card-body p-4 p-md-4 bg-white">
            <form action="index.php?action=create_ticket" method="POST" enctype="multipart/form-data" id="createTicketForm">
                
                <!-- 1. Identitas Pelapor -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark">
                                Nama Anda (Pelapor) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm rounded-2" name="reporter_name" placeholder="Contoh: Ibu Maria / Bpk Daniel" value="<?= $currentUser['role'] === 'it' ? 'Tim IT Advent' : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-dark">
                                Bagian / Departemen / Ruangan <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm rounded-2" name="reporter_dept" placeholder="Contoh: Keuangan, Tata Usaha, Guru, Sekretariat" required>
                        </div>
                    </div>
                </div>

                <!-- 2. Pilih Kategori Masalah -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                        <span>1. Pilih Jenis Masalah <span class="text-danger">*</span></span>
                        <span class="small text-muted fw-normal">Klik salah satu kotak berikut</span>
                    </label>
                    
                    <input type="hidden" name="category_id" id="selectedCategoryId" value="1" required>

                    <div class="category-select-grid">
                        <?php foreach ($categories as $index => $cat): ?>
                            <div class="category-option-card <?= $index === 0 ? 'selected' : '' ?>" data-id="<?= $cat['id'] ?>">
                                <div class="category-option-icon" style="background-color: <?= $cat['color'] ?>;">
                                    <i class="bi <?= $cat['icon'] ?>"></i>
                                </div>
                                <div class="category-option-name"><?= htmlspecialchars($cat['name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. Subjek / Judul Masalah -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark" for="ticketTitle">
                        2. Judul / Ringkasan Kendala <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control rounded-3" id="ticketTitle" name="title" placeholder="Contoh: Printer Keuangan Kertas Macet / Wi-Fi Putus di Ruang TU" required>
                </div>

                <!-- 3. Prioritas -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">
                        3. Seberapa Mendesak? <span class="text-danger">*</span>
                    </label>
                    <div class="priority-selector-wrapper">
                        <div class="priority-radio-btn">
                            <input type="radio" name="priority" id="prio_medium" value="medium" checked>
                            <label for="prio_medium">
                                <i class="bi bi-dash-circle text-primary"></i> Normal
                            </label>
                        </div>
                        <div class="priority-radio-btn">
                            <input type="radio" name="priority" id="prio_high" value="high">
                            <label for="prio_high">
                                <i class="bi bi-exclamation-triangle-fill text-warning"></i> Mendesak
                            </label>
                        </div>
                        <div class="priority-radio-btn urgent">
                            <input type="radio" name="priority" id="prio_urgent" value="urgent">
                            <label for="prio_urgent">
                                <i class="bi bi-fire text-danger"></i> Darurat
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 4. Deskripsi Rinci Masalah -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark" for="ticketDesc">
                        4. Jelaskan Masalah Secara Singkat <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control rounded-3" id="ticketDesc" name="description" rows="4" placeholder="Ceritakan apa yang terjadi, sejak kapan terjadi, dan apakah ada lampu indikator merah atau pesan error..." required></textarea>
                </div>

                <!-- 5. Upload Foto / Bukti (Drag & Drop + Realtime Validation) -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                        <span>5. Foto Bukti Masalah (Opsional)</span>
                        <span class="text-muted small fw-normal">Maksimal 5MB (JPG, PNG, WEBP)</span>
                    </label>

                    <!-- Interactive Drag & Drop Zone -->
                    <div class="upload-dropzone p-4 text-center rounded-3 border-2 border-dashed" id="uploadDropzone" style="cursor: pointer; border-style: dashed; background-color: #f8fafc; transition: all 0.2s ease;">
                        <input type="file" id="ticketAttachmentInput" name="attachment" accept="image/jpeg,image/png,image/webp,image/jpg" style="display: none;">
                        <div id="dropzonePrompt">
                            <i class="bi bi-cloud-arrow-up text-primary fs-1 mb-2 d-block"></i>
                            <div class="fw-bold text-dark mb-1">Tarik & Letakkan file foto di sini, atau <span class="text-primary text-decoration-underline">Pilih Berkas</span></div>
                            <div class="text-muted small">Format didukung: JPG, JPEG, PNG, WEBP (Maksimal 5MB)</div>
                        </div>
                    </div>

                    <!-- File Validation Error Alert -->
                    <div id="fileSizeAlert" class="alert alert-danger py-2 px-3 small mt-2 d-none align-items-center gap-2 rounded-3">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                        <span id="fileSizeAlertText">Ukuran berkas melebihi batas 5MB! Silakan pilih foto lain yang lebih kecil.</span>
                    </div>

                    <!-- Preview Thumbnail & File Info Card (with Remove Button) -->
                    <div class="card border rounded-3 p-3 mt-2 shadow-sm d-none" id="attachmentPreviewCard">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <img id="previewImg" src="" alt="Pratinjau Foto" class="rounded-3 border object-fit-cover shadow-sm" style="width: 70px; height: 70px; object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark small text-truncate" id="fileNameDisplay" style="max-width: 250px;">nama_file.jpg</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-secondary-subtle text-secondary small" id="fileSizeBadge">0 KB</span>
                                        <span class="badge bg-success-subtle text-success small" id="fileStatusBadge"><i class="bi bi-check-circle me-1"></i> Siap Diunggah</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold" id="removeAttachmentBtn">
                                    <i class="bi bi-trash me-1"></i> Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Tombol Submit & Batal -->
                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="index.php?page=tickets" class="btn btn-light btn-sm px-3 py-2 rounded-3 border">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-bold shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-send-fill"></i> Kirim Tiket ke Tim IT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
