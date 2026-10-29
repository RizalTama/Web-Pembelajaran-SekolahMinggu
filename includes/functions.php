<?php

/**
 * Escape output untuk HTML (mencegah XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Simpan pesan flash ke session.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Ambil dan hapus semua pesan flash.
 */
function get_flash(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Redirect ke URL lain lalu hentikan eksekusi.
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Format tanggal ke format Indonesia, misal "Minggu, 28 September 2026".
 */
function format_tanggal(?string $tanggal, bool $dengan_hari = true): string
{
    if (empty($tanggal)) {
        return '-';
    }

    $hari_list = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan_list = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return '-';
    }

    $hari   = $hari_list[(int) date('w', $timestamp)];
    $tgl    = (int) date('j', $timestamp);
    $bulan  = $bulan_list[(int) date('n', $timestamp)];
    $tahun  = date('Y', $timestamp);

    if ($dengan_hari) {
        return "{$hari}, {$tgl} {$bulan} {$tahun}";
    }

    return "{$tgl} {$bulan} {$tahun}";
}

/**
 * Format jam HH:MM dari nilai TIME database.
 */
function format_jam(?string $jam): string
{
    if (empty($jam)) {
        return '-';
    }
    return substr($jam, 0, 5);
}

/**
 * Nama bulan Indonesia dari angka 1-12.
 */
function nama_bulan(int $bulan): string
{
    $bulan_list = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    return $bulan_list[$bulan] ?? '-';
}

/**
 * Ambil semua siswa aktif. Karena tidak ada fitur kelas, semua siswa
 * aktif tampil untuk semua guru. Terpusat di sini agar mudah diubah
 * jika ke depannya siswa perlu dikaitkan ke guru tertentu.
 */
function get_siswa_aktif(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT siswa.*, users.username, users.is_active
         FROM siswa
         JOIN users ON users.id = siswa.user_id
         WHERE users.is_active = 1
         ORDER BY siswa.nama ASC"
    );
    return $stmt->fetchAll();
}

/**
 * Hitung offset pagination sederhana.
 */
function paginate_offset(int $halaman, int $per_halaman = 10): int
{
    $halaman = max(1, $halaman);
    return ($halaman - 1) * $per_halaman;
}

/**
 * Buat elemen navigasi pagination sederhana (kembalikan array info).
 */
function paginate_info(int $total_data, int $halaman, int $per_halaman = 10): array
{
    $total_halaman = max(1, (int) ceil($total_data / $per_halaman));
    $halaman = max(1, min($halaman, $total_halaman));
    return [
        'halaman' => $halaman,
        'total_halaman' => $total_halaman,
        'per_halaman' => $per_halaman,
        'total_data' => $total_data,
    ];
}

/**
 * Badge warna untuk status absensi.
 */
function badge_absensi(string $status): string
{
    $map = [
        'hadir' => 'bg-success/10 text-success',
        'izin'  => 'bg-primary-light text-primary',
        'sakit' => 'bg-accent-light text-accent',
        'alpa'  => 'bg-danger/10 text-danger',
    ];
    $class = $map[$status] ?? 'bg-slate-100 text-slate-600';
    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . $class . '">' . e(ucfirst($status)) . '</span>';
}

/**
 * Badge status kegiatan.
 */
function badge_kegiatan(string $status): string
{
    $map = [
        'akan_datang' => 'bg-primary-light text-primary',
        'berjalan'    => 'bg-accent-light text-accent',
        'selesai'     => 'bg-success/10 text-success',
    ];
    $label_map = [
        'akan_datang' => 'Akan Datang',
        'berjalan'    => 'Berjalan',
        'selesai'     => 'Selesai',
    ];
    $class = $map[$status] ?? 'bg-slate-100 text-slate-600';
    $label = $label_map[$status] ?? ucfirst($status);
    return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . $class . '">' . e($label) . '</span>';
}
