<?php

date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi BASE_URL otomatis berdasarkan lokasi project di htdocs
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path  = preg_replace('#/(auth|admin|guru|siswa|includes|config)(/.*)?$#', '', $script_dir);
if ($base_path === '' || $base_path === false) {
    $base_path = '';
}
define('BASE_URL', $base_path);

define('APP_NAME', 'Sistem Informasi Sekolah Minggu');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

define('MAX_FOTO', 15 * 1024 * 1024);
define('MAX_FOTO_KEGIATAN', 15 * 1024 * 1024);
define('MAX_MATERI_PDF', 50 * 1024 * 1024);
define('MAX_MATERI_GAMBAR', 15 * 1024 * 1024);
define('MAX_RAPOR_PDF', 50 * 1024 * 1024);

define('EXT_FOTO', ['jpg', 'jpeg', 'png', 'webp']);
define('EXT_PDF', ['pdf']);
