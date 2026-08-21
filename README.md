# 🎫 Sistem IT Ticket Advent (Fabkin Modern Template)

Sistem Informasi Layanan Bantuan & Manajemen Kendala IT (**IT Support & Ticketing System**) berbasis web yang dirancang khusus untuk kebutuhan internal kantor, sekolah, dan institusi **Gereja Masehi Advent Hari Ketujuh**. 

Sistem ini didesain dengan antarmuka yang sangat ramah pengguna awam (**gaya Mailbox / Email Inbox**) menggunakan basis template modern **Fabkin (Bootstrap 5)** dan dapat berjalan **100% di Localhost** tanpa perlu hosting cloud.

---

## 🌟 Fitur Utama Sistem

### 1. 📥 Antarmuka Bergaya Email (Mailbox / Inbox Style)
* **Navigasi Folder Email**:
  * 📥 **Semua Tiket (Inbox)**: Menampilkan seluruh tiket masuk dengan badge prioritas, kategori, dan status.
  * 📨 **Tiket Saya**: Khusus menampilkan tiket yang diajukan oleh pengguna yang sedang aktif.
  * 🛠️ **Tugas Saya**: Khusus staf/teknisi IT untuk memantau pekerjaan yang ditugaskan ke dirinya.
  * ⏳ **Sedang Diproses**: Tiket yang sedang dalam proses perbaikan teknisi.
  * ⏸️ **Menunggu Konfirmasi**: Tiket yang memerlukan informasi tambahan / suku cadang.
  * ✅ **Tiket Selesai**: Tiket yang telah berhasil diselesaikan.
  * 🔥 **Tiket Darurat (Urgent)**: Tiket prioritas tinggi yang memerlukan tindakan segera.
* **Pencarian Realtime**: Filter tiket secara langsung berdasarkan judul, nama pelapor, maupun ruangan tanpa memuat ulang halaman (*instant search*).

### 2. 📝 Formulir Buat Tiket yang Sangat Ramah Orang Awam
* **Pilihan Kategori Visual Interaktif**: Cukup klik ikon kategori yang diinginkan (Wi-Fi, Printer, PC/Laptop, Software, Akun/Password, Sound/Proyektor, Sarana IT).
* **Otomatisasi Data**: Nama dan unit pelapor otomatis terisi dari sesi pengguna.
* **Tingkat Prioritas**: Pilihan jelas (Normal, Mendesak, Darurat).
* **Unggah Foto/Screenshot Kendala**: Mendukung unggah foto bukti kendala dengan pratinjau instan (*live preview*).

### 3. 💬 Detail Tiket & Percakapan Kronologis (Email Thread)
* **Thread Balasan Pesan**: Staf dan teknisi dapat saling berkirim pesan klarifikasi persis seperti membalas email.
* **Catatan Internal IT**: Tim IT dapat menambahkan catatan rahasia/teknis perbaikan yang hanya bisa dibaca oleh sesama teknisi.
* **Ubah Status & Penugasan**: Teknisi dapat mengubah status tiket (*Open &rarr; In Progress &rarr; Pending &rarr; Resolved &rarr; Closed*) dan menugaskan teknisi pendamping.
* **Catatan Solusi**: Dokumentasi perbaikan yang tersimpan permanen sebagai *knowledge base*.
* **Penilaian Bintang Kepuasan**: Pelapor dapat memberikan rating 1-5 bintang dan ulasan setelah kendala terselesaikan.

### 4. 📊 Dashboard Analitik & Statistik
* 4 Kartu Statistik Cepat: Total Tiket Masuk, Sedang Diproses, Selesai Ditangani, Tiket Urgent.
* Diagram Sebaran Kategori Masalah IT dengan persentase.
* Indeks Kepuasan Pengguna (CSAT).
* Ringkasan 5 tiket terkini.

### 5. 🖨️ Cetak Lembar Kerja Tiket
* Format siap cetak resmi untuk keperluan tanda tangan serah terima perangkat atau arsip fisik kantor.

### 6. 🔄 Fitur Ganti Akun Instan (Localhost Role Switcher)
* Terdapat dropdown di pojok kanan atas untuk beralih peran (*Admin IT*, *Teknisi Jaringan/Hardware*, *Staf Keuangan*, *Staf TU*, *Sekretariat*) dalam 1 klik tanpa harus repot logout-login saat pengujian di localhost.

---

## 🚀 Cara Menjalankan di Localhost

Aplikasi ini menggunakan database **SQLite bawaan (Zero-Configuration)**, sehingga Anda tidak perlu membuat database MySQL secara manual. Tabel dan data sampel awal otomatis terbuat saat aplikasi pertama kali dibuka.

### Cara 1: Menggunakan PHP Built-in Server (Paling Cepat & Praktis)

1. Buka Terminal / PowerShell di folder ini (`Sistem-IT-Ticket-Advent`):
   ```bash
   php -S localhost:8000
   ```
2. Buka browser dan akses alamat:
   ```
   http://localhost:8000
   ```

### Cara 2: Menggunakan XAMPP / Laragon

1. Salin atau pindahkan folder `Sistem-IT-Ticket-Advent` ke dalam direktori:
   * **XAMPP**: `C:\xampp\htdocs\Sistem-IT-Ticket-Advent`
   * **Laragon**: `C:\laragon\www\Sistem-IT-Ticket-Advent`
2. Jalankan Apache di XAMPP Control Panel.
3. Buka browser dan akses:
   ```
   http://localhost/Sistem-IT-Ticket-Advent/
   ```

> [!TIP]
> **Ingin beralih ke MySQL XAMPP?**
> Cukup buka file [config.php](file:///c:/Users/MeakhelG/Documents/GitHub/Sistem-IT-Ticket-Advent/config.php), lalu ubah `define('DB_DRIVER', 'sqlite');` menjadi `define('DB_DRIVER', 'mysql');` dan sesuaikan nama database serta password MySQL Anda.

---

## 👥 Data Akun Pengguna Bawaan (Demo)

| Nama Pengguna | Peran (Role) | Unit / Departemen | Email | Password |
|---|---|---|---|---|
| **Michael G** | Admin IT | Divisi IT & Multimedia | `admin.it@advent.local` | `admin123` |
| **Samuel Siregar** | Teknisi IT | Divisi IT & Multimedia | `samuel.it@advent.local` | `teknisi123` |
| **Ibu Maria Hutapea** | Pengguna / Staf | Bagian Keuangan & Kasir | `maria.keuangan@advent.local` | `user123` |
| **Bpk. Daniel Tumanggor** | Pengguna / Staf | Bagian Tata Usaha & SDM | `daniel.tu@advent.local` | `user123` |
| **Pdt. Johanes Manurung** | Pengguna / Staf | Sekretariat Kantor | `johanes.sekretariat@advent.local` | `user123` |

*(Gunakan menu dropdown **"Masuk Sebagai"** di kanan atas topbar untuk berganti akun secara instan).*

---

## 📂 Struktur Direktori

```
Sistem-IT-Ticket-Advent/
├── index.php                # Router dan Controller utama aplikasi
├── config.php               # Konfigurasi sistem & koneksi database
├── database.php             # Skema tabel database & seeder data contoh
├── helpers.php              # Fungsi utilitas (format tanggal, badge, filter counter)
├── data/                    # Folder penyimpanan database SQLite (it_ticket.db)
├── uploads/                 # Folder penyimpanan foto & screenshot bukti kendala
├── assets/
│   ├── css/
│   │   ├── fabkin.css       # Desain tema modern Fabkin (Bootstrap 5)
│   │   └── custom.css       # Gaya kustom mailbox, rating, & print mode
│   └── js/
│       └── app.js           # Live filter pencarian, image preview, zoom modal
├── views/
│   ├── layouts/
│   │   ├── header.php       # Layout header, topbar & role switcher
│   │   ├── sidebar.php      # Layout sidebar navigasi gaya email mailbox
│   │   └── footer.php       # Layout footer & modal zoom gambar
│   ├── tickets/
│   │   ├── index.php        # Daftar tiket masuk (Email Inbox)
│   │   ├── create.php       # Formulir pengajuan tiket baru (Compose)
│   │   ├── show.php         # Detail tiket & riwayat balasan (Email Thread)
│   │   └── print.php        # Lembar kerja tiket format cetak
│   ├── dashboard.php        # Halaman analitik statistik & diagram
│   ├── users/
│   │   └── index.php        # Halaman daftar staf & teknisi
│   └── about.php            # Halaman panduan penggunaan untuk staf
└── README.md
```

---

## 🛠️ Lisensi & Pengembangan
Dibuat dengan ❤️ untuk mendukung pelayanan dan kelancaran operasional sarana teknologi informasi pada institusi **Gereja Masehi Advent Hari Ketujuh**.