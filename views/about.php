<?php
/**
 * Sistem IT Ticket Advent - Halaman Panduan Penggunaan Sistem
 */
$pageTitle = 'Panduan Penggunaan Sistem IT Ticket';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="container-fluid p-0" style="max-width: 980px;">
    <div class="mb-4">
        <h2 class="fs-4 fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill text-primary"></i> Panduan Alur Pelayanan IT Ticket
        </h2>
        <p class="text-muted small m-0 mt-1">
            Panduan praktis bagi seluruh staf kantor, guru, dan pelayan jemaat dalam menyampaikan kendala perangkat teknologi.
        </p>
    </div>

    <!-- Alur 4 Langkah Sederhana -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-primary text-white p-4">
            <h5 class="fw-bold m-0"><i class="bi bi-diagram-3 me-2"></i> 4 Langkah Mudah Menyampaikan Masalah IT</h5>
        </div>
        <div class="card-body p-4 bg-white">
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 border rounded-3 text-center h-100 bg-light-subtle">
                        <div class="badge bg-primary rounded-circle p-3 mb-3 fs-5">1</div>
                        <h6 class="fw-bold text-dark">Buat Tiket</h6>
                        <p class="small text-muted mb-0">Klik tombol <strong>+ Buat Tiket Baru</strong>, pilih kategori kendala (misal: Printer / Wi-Fi) dan jelaskan lokasinya.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 border rounded-3 text-center h-100 bg-light-subtle">
                        <div class="badge bg-warning text-dark rounded-circle p-3 mb-3 fs-5">2</div>
                        <h6 class="fw-bold text-dark">Respon Teknisi</h6>
                        <p class="small text-muted mb-0">Tim IT menerima notifikasi tiket dan mengubah status menjadi <strong>Sedang Dikerjakan</strong>.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 border rounded-3 text-center h-100 bg-light-subtle">
                        <div class="badge bg-info text-dark rounded-circle p-3 mb-3 fs-5">3</div>
                        <h6 class="fw-bold text-dark">Pengerjaan di Lokasi</h6>
                        <p class="small text-muted mb-0">Teknisi datang ke ruangan Anda atau memberikan panduan perbaikan melalui kolom pesan.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 border rounded-3 text-center h-100 bg-light-subtle">
                        <div class="badge bg-success rounded-circle p-3 mb-3 fs-5">4</div>
                        <h6 class="fw-bold text-dark">Tiket Selesai</h6>
                        <p class="small text-muted mb-0">Setelah kendala teratasi, tiket dinyatakan <strong>Selesai</strong> dan pelapor dapat memberikan bintang kepuasan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ & Penjelasan Status -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-tags-fill text-primary"></i> Arti Warna Status Tiket
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3 d-flex align-items-start gap-2">
                        <?= getStatusBadge('open') ?>
                        <div class="small">Tiket baru diajukan dan sedang menunggu jadwal antrean pemeriksaan oleh tim IT.</div>
                    </div>
                    <div class="mb-3 d-flex align-items-start gap-2">
                        <?= getStatusBadge('in_progress') ?>
                        <div class="small">Teknisi telah ditugaskan dan sedang dalam proses perbaikan fisik maupun software.</div>
                    </div>
                    <div class="mb-3 d-flex align-items-start gap-2">
                        <?= getStatusBadge('pending') ?>
                        <div class="small">Perbaikan menunggu konfirmasi suku cadang baru atau konfirmasi dari staf bersangkutan.</div>
                    </div>
                    <div class="mb-2 d-flex align-items-start gap-2">
                        <?= getStatusBadge('resolved') ?>
                        <div class="small">Perangkat telah berfungsi normal kembali dan siap digunakan.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-3 border-bottom">
                    <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-hdd-network-fill text-primary"></i> Informasi Server & Localhost
                    </h6>
                </div>
                <div class="card-body p-3 small text-muted">
                    <p class="mb-2">Aplikasi ini dirancang khusus untuk berjalan di jaringan internal kantor (<strong>Localhost / Intranet LAN</strong>) tanpa memerlukan koneksi internet keluar ataupun cloud hosting berbayar.</p>
                    <div class="p-3 bg-light rounded-3 text-dark">
                        <div><strong>Versi Aplikasi:</strong> <?= APP_VERSION ?></div>
                        <div><strong>Database:</strong> SQLite Local Embedded File (Zero Config)</div>
                        <div><strong>Organisasi:</strong> <?= APP_ORGANIZATION ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
