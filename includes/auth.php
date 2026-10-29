<?php

/**
 * Pastikan pengguna sudah login, jika tidak arahkan ke halaman login.
 */
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('/auth/login.php');
    }
}

/**
 * Pastikan pengguna sudah login DAN memiliki role tertentu.
 * Jika role tidak sesuai, arahkan ke dashboard role miliknya.
 */
function require_role(string $role): void
{
    require_login();

    if ($_SESSION['role'] !== $role) {
        redirect(dashboard_path($_SESSION['role']));
    }
}

/**
 * Kembalikan path dashboard sesuai role.
 */
function dashboard_path(string $role): string
{
    return match ($role) {
        'admin' => '/admin/dashboard.php',
        'guru'  => '/guru/dashboard.php',
        'siswa' => '/siswa/dashboard.php',
        default => '/auth/login.php',
    };
}

/**
 * Ambil ID guru (tabel guru.id) milik user yang sedang login.
 */
function current_guru_id(PDO $pdo): ?int
{
    static $guru_id = null;
    if ($guru_id === null && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM guru WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        $guru_id = $row ? (int) $row['id'] : 0;
    }
    return $guru_id ?: null;
}

/**
 * Ambil ID siswa (tabel siswa.id) milik user yang sedang login.
 */
function current_siswa_id(PDO $pdo): ?int
{
    static $siswa_id = null;
    if ($siswa_id === null && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM siswa WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        $siswa_id = $row ? (int) $row['id'] : 0;
    }
    return $siswa_id ?: null;
}
