<?php
/**
 * Sistem IT Ticket Advent - Halaman Cetak Lembar Kerja / Laporan Tiket
 */
$ticketId = $_GET['id'] ?? 0;
$db = getDB();
$stmt = $db->prepare("
    SELECT t.*, c.name as category_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Tiket tidak ditemukan.");
}

$replies = $db->prepare("
    SELECT r.*, u.name as user_name, u.role as user_role 
    FROM ticket_replies r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.ticket_id = ? AND r.is_internal = 0 
    ORDER BY r.created_at ASC
");
$replies->execute([$ticketId]);
$replyList = $replies->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Tiket <?= htmlspecialchars($ticket['ticket_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #111; font-size: 13px; }
        .print-header { border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 18px; }
        .table-bordered th, .table-bordered td { border-color: #555 !important; padding: 6px 10px; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="p-4">

<div class="no-print mb-3 text-end">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">🖨️ Cetak Dokumen Ini</button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm px-3 ms-1">Tutup</button>
</div>

<div class="print-header d-flex align-items-center justify-content-between">
    <div>
        <h4 class="fw-bold mb-1"><?= APP_ORGANIZATION ?></h4>
        <h5 class="text-primary fw-bold mb-0">LEMBAR PENANGANAN KENDALA IT (IT TICKET)</h5>
        <div class="text-muted small">Sistem Pelayanan IT Support Advent</div>
    </div>
    <div class="text-end">
        <div class="border p-2 rounded bg-light text-center">
            <div class="small text-muted fw-bold">NOMOR TIKET</div>
            <div class="h5 fw-bold font-monospace mb-0"><?= htmlspecialchars($ticket['ticket_code']) ?></div>
        </div>
    </div>
</div>

<table class="table table-bordered mb-4">
    <tbody>
        <tr>
            <th class="bg-light" style="width: 25%;">Nama Pelapor</th>
            <td style="width: 25%;"><strong><?= htmlspecialchars($ticket['reporter_name']) ?></strong></td>
            <th class="bg-light" style="width: 25%;">Tanggal Laporan</th>
            <td style="width: 25%;"><?= formatDateIndo($ticket['created_at']) ?></td>
        </tr>
        <tr>
            <th class="bg-light">Departemen / Ruang</th>
            <td><?= htmlspecialchars($ticket['reporter_dept']) ?></td>
            <th class="bg-light">Prioritas</th>
            <td><strong><?= strtoupper($ticket['priority']) ?></strong></td>
        </tr>
        <tr>
            <th class="bg-light">Lokasi Spesifik</th>
            <td><?= htmlspecialchars($ticket['location']) ?></td>
            <th class="bg-light">Status Terkini</th>
            <td><strong><?= strtoupper(getStatusText($ticket['status'])) ?></strong></td>
        </tr>
        <tr>
            <th class="bg-light">Kategori Kendala</th>
            <td><?= htmlspecialchars($ticket['category_name']) ?></td>
            <th class="bg-light">Penanggung Jawab</th>
            <td><strong>Tim IT Advent</strong></td>
        </tr>
    </tbody>
</table>

<div class="mb-4">
    <h6 class="fw-bold border-bottom pb-1">1. DESKRIPSI KENDALA YANG DILAPORKAN</h6>
    <div class="p-2 border rounded bg-light">
        <strong>Subjek: <?= htmlspecialchars($ticket['title']) ?></strong>
        <p class="mt-2 mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></p>
    </div>
</div>

<?php if (count($replyList) > 0): ?>
<div class="mb-4">
    <h6 class="fw-bold border-bottom pb-1">2. RIWAYAT BALASAN & TINDAKAN PERBAIKAN</h6>
    <table class="table table-bordered">
        <thead>
            <tr class="bg-light">
                <th style="width: 25%;">Waktu</th>
                <th style="width: 25%;">Oleh</th>
                <th>Catatan / Tanggapan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($replyList as $r): ?>
            <tr>
                <td><?= formatDateIndo($r['created_at']) ?></td>
                <td><strong><?= htmlspecialchars($r['sender_name'] ?: $r['user_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['message']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="mb-4">
    <h6 class="fw-bold border-bottom pb-1">3. SOLUSI AKHIR & KESIMPULAN PENYELESAIAN</h6>
    <div class="p-3 border rounded" style="min-height: 70px;">
        <?= !empty($ticket['solution']) ? htmlspecialchars($ticket['solution']) : '<em>(Diisi oleh Tim IT saat kendala selesai diperbaiki)</em>' ?>
    </div>
</div>

<div class="row mt-5 text-center">
    <div class="col-6">
        <div>Pelapor / Pemohon,</div>
        <div style="height: 60px;"></div>
        <div class="fw-bold">( <?= htmlspecialchars($ticket['reporter_name']) ?> )</div>
    </div>
    <div class="col-6">
        <div>Tim IT Pelaksana,</div>
        <div style="height: 60px;"></div>
        <div class="fw-bold">( Tim IT Advent )</div>
    </div>
</div>

</body>
</html>
