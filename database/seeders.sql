-- ========================================================
-- SEED DATA AWAL (USERS, CATEGORIES, & SAMPLE TICKETS)
-- ========================================================

-- 1. SEED KATEGORI KENDALA IT
INSERT INTO `categories` (`id`, `nama_kategori`, `icon`, `warna`, `deskripsi`) VALUES
(1, 'Jaringan & Internet', 'bi-wifi', '#0284c7', 'Kendala Wi-Fi, kabel LAN, internet lambat, atau akses web portal terputus'),
(2, 'Printer & Mesin Cetak', 'bi-printer', '#7c3aed', 'Kertas macet, tinta habis, scanner rusak, atau printer offline'),
(3, 'Komputer & Laptop', 'bi-pc-display', '#2563eb', 'PC lambat, Blue Screen (BSOD), monitor mati, keyboard/mouse rusak'),
(4, 'Software & Aplikasi', 'bi-window-stack', '#059669', 'Aplikasi error, install program baru, aktivasi Office, kendala meeting'),
(5, 'Akun, Email & Sandi', 'bi-person-badge', '#d97706', 'Lupa password email, reset akun SIAK/Sistem, registrasi akun staf baru'),
(6, 'Proyektor & Sound System', 'bi-projector', '#dc2626', 'Koneksi HDMI proyektor ruang ibadah/aula, audio berdengung, mic wireless'),
(7, 'Sarana IT Lainnya', 'bi-wrench-adjustable', '#4b5563', 'Permintaan backup data, instalasi kabel, konsultasi perangkat IT');

-- 2. SEED PENGGUNA (Password default: 'password123')
-- Hash bcrypt untuk 'password123': $2y$10$wO0I7d4sSffzG5sJ.7X2eej6W4FfQ6r6t2fVfPq1z3u5Kx8L5q5x6
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `departemen`, `no_hp`) VALUES
(1, 'Tim IT Advent', 'it@advent.local', '$2y$10$wO0I7d4sSffzG5sJ.7X2eej6W4FfQ6r6t2fVfPq1z3u5Kx8L5q5x6', 'it', 'Divisi IT & Multimedia', '081234567890'),
(2, 'Staf Kantor', 'staf@advent.local', '$2y$10$wO0I7d4sSffzG5sJ.7X2eej6W4FfQ6r6t2fVfPq1z3u5Kx8L5q5x6', 'staff', 'Bagian Keuangan & Kasir', '081398765432');

-- 3. SEED TIKET AWAL CONTOH
INSERT INTO `tickets` (`id`, `kode_tiket`, `user_id`, `category_id`, `assigned_to`, `judul`, `deskripsi`, `lokasi`, `prioritas`, `status`, `lampiran_foto`, `solusi`, `created_at`) VALUES
(1, 'TKT-202609-001', 2, 2, 1, 'Printer Epson di Ruang Keuangan Lampu Merah Berkedip & Kertas Macet', 'Selamat siang tim IT, printer Epson L3110 di meja kasir keuangan tidak mau mencetak kuitansi. Lampu indikator tinta dan kertas berkedip merah bersamaan. Mohon bantuannya segera karena antrian pembayaran sedang banyak. Terima kasih.', 'Gedung A, Lantai 1 - Ruang Keuangan', 'high', 'in_progress', NULL, '', NOW());

-- 4. SEED BALASAN TIKET
INSERT INTO `ticket_replies` (`ticket_id`, `user_id`, `pesan`, `lampiran`, `is_internal`, `created_at`) VALUES
(1, 1, 'Halo Bu Maria, tiket sudah kami terima. Kami sedang menuju ke ruang keuangan untuk memeriksa roller penarik kertas.', NULL, 0, NOW()),
(1, 1, 'Catatan Internal Tim IT: Kemungkinan roller penarik kertas aus atau kotor. Siapkan alkohol pembersih atau karet cadangan.', NULL, 1, NOW());

-- 5. SEED LOG AKTIVITAS
INSERT INTO `ticket_logs` (`ticket_id`, `user_id`, `aksi`, `catatan`, `created_at`) VALUES
(1, 2, 'Tiket Diajukan', 'Tiket baru diajukan oleh Staf Kantor (Bagian Keuangan)', NOW()),
(1, 1, 'Status Diubah', 'Status diubah menjadi: Sedang Dikerjakan (In Progress) oleh Tim IT', NOW());
