<?php

/**
 * Generate token CSRF (dibuat sekali per sesi) dan kembalikan nilainya.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Cetak input hidden berisi token CSRF untuk dipakai di dalam <form>.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifikasi token CSRF dari request POST. Hentikan proses jika tidak valid.
 */
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        flash('error', 'Sesi tidak valid, silakan coba lagi.');
        $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/');
        header('Location: ' . $referer);
        exit;
    }
}
