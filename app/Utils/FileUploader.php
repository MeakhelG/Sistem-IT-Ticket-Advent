<?php
namespace App\Utils;

class FileUploader {
    // Ukuran maksimal 5MB
    private const MAX_SIZE = 5 * 1024 * 1024;
    
    // Whitelist MIME types dan ekstensi yang diizinkan
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/pjpeg'=> 'jpg',
        'image/png'  => 'png',
        'image/x-png'=> 'png',
        'image/webp' => 'webp'
    ];

    /**
     * Memproses upload file secara aman
     *
     * @param array|null $file $_FILES['input_name']
     * @param string $targetDir Direktori penyimpanan
     * @return string|null Nama file yang berhasil disimpan atau null
     * @throws \Exception Jika validasi gagal
     */
    public static function upload(?array $file, string $targetDir = ''): ?string {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Terjadi kesalahan saat mengunggah file.');
        }

        // Validasi ukuran maksimal 5MB
        if ($file['size'] > self::MAX_SIZE) {
            throw new \Exception('Ukuran file maksimal adalah 5MB.');
        }

        // Validasi MIME Type asli dari isi biner file (anti-spoofing)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!array_key_exists($mimeType, self::ALLOWED_MIME)) {
            throw new \Exception('Format file tidak didukung! Hanya gambar format JPG, JPEG, PNG, dan WEBP.');
        }

        $extension = self::ALLOWED_MIME[$mimeType];
        $filename = 'tkt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

        if (empty($targetDir)) {
            $targetDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../../uploads/';
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $destination = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Gagal memindahkan file yang diunggah.');
        }

        return $filename;
    }
}
