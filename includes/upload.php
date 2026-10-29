<?php

/**
 * Upload file ke folder tertentu dalam uploads/ dengan validasi ekstensi,
 * MIME asli, dan ukuran. Mengembalikan path relatif (mis. "materi/abc.pdf").
 * Melempar Exception jika gagal.
 */
function upload_file(array $file, string $folder, array $allowed_ext, int $max_bytes): string
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Parameter upload tidak valid.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('Tidak ada file yang dipilih.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('Ukuran file melebihi batas maksimum.');
        default:
            throw new Exception('Terjadi kesalahan saat upload file.');
    }

    if ($file['size'] > $max_bytes) {
        $mb = round($max_bytes / 1024 / 1024, 1);
        throw new Exception("Ukuran file melebihi batas maksimum ({$mb} MB).");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        throw new Exception('Ekstensi file tidak diizinkan. Ekstensi yang diizinkan: ' . implode(', ', $allowed_ext) . '.');
    }

    $mime_map = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = $mime_map[$ext] ?? [];
    if (empty($allowed_mimes) || !in_array($real_mime, $allowed_mimes, true)) {
        throw new Exception('Jenis file tidak sesuai dengan ekstensinya.');
    }

    $target_dir = rtrim(UPLOAD_DIR, '/') . '/' . trim($folder, '/');
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0775, true);
    }

    $new_name = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $target_path = $target_dir . '/' . $new_name;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Gagal menyimpan file yang diupload.');
    }

    return trim($folder, '/') . '/' . $new_name;
}

/**
 * Hapus file fisik di dalam uploads/ berdasarkan path relatif yang
 * tersimpan di database. Aman dipanggil meski file tidak ada.
 */
function delete_uploaded_file(?string $relative_path): void
{
    if (empty($relative_path)) {
        return;
    }

    $full_path = rtrim(UPLOAD_DIR, '/') . '/' . ltrim($relative_path, '/');
    if (is_file($full_path)) {
        unlink($full_path);
    }
}
