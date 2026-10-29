<?php
/**
 * Dipakai di setiap halaman setelah require config+auth+proses POST.
 * Variabel yang bisa diisi sebelum include:
 * - $page_title (string) judul halaman, wajib.
 */
$page_title = $page_title ?? APP_NAME;
$user_nama  = $_SESSION['nama'] ?? '';
$user_role  = $_SESSION['role'] ?? '';
$role_label = match ($user_role) {
    'admin' => 'Admin / Pengelola',
    'guru'  => 'Guru',
    'siswa' => 'Siswa',
    default => '',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> · <?= e(APP_NAME) ?></title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: { DEFAULT: '#5B67D8', dark: '#454FC2', light: '#EBEAFB' },
        accent:  { DEFAULT: '#FFB84D', light: '#FFF2DC' },
        peach:   { DEFAULT: '#FF9587', light: '#FFE7E2' },
        mint:    { DEFAULT: '#3FC28E', light: '#DFF7EC' },
        ink:     '#342F4E',
        muted:   '#8C86A6',
        surface: '#F8F6FC',
        success: '#3FAE7E',
        danger:  '#E56767',
      },
      fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
    }
  }
}
</script>
<style>
  body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
  *:focus-visible { outline: 2px solid #5B67D8; outline-offset: 2px; }
</style>
</head>
<body class="bg-surface text-ink antialiased">

<div class="min-h-screen flex">

  <div id="sidebar-overlay" class="fixed inset-0 bg-ink/40 z-30 hidden lg:hidden"></div>

  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="flex-1 flex flex-col min-w-0 lg:ml-64">

    <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-ink/5 px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3 min-w-0">
        <button id="sidebar-toggle" type="button" class="lg:hidden text-muted hover:text-ink p-2 -ml-2" aria-label="Buka menu">
          <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <h1 class="text-lg sm:text-xl font-bold text-ink truncate"><?= e($page_title) ?></h1>
      </div>
      <div class="flex items-center gap-3 shrink-0">
        <div class="hidden sm:flex flex-col items-end leading-tight">
          <span class="text-sm font-semibold text-ink"><?= e($user_nama) ?></span>
          <span class="text-xs text-muted"><?= e($role_label) ?></span>
        </div>
        <div class="w-9 h-9 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-sm shrink-0">
          <?= e(mb_strtoupper(mb_substr($user_nama ?: '?', 0, 1))) ?>
        </div>
      </div>
    </header>

    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

      <?php $flashes = get_flash(); ?>
      <?php if (!empty($flashes)): ?>
        <div class="space-y-3 mb-6">
          <?php foreach ($flashes as $f): ?>
            <?php
              $is_success = $f['type'] === 'success';
              $box_class  = $is_success ? 'bg-success/10 border-success/30 text-success' : 'bg-danger/10 border-danger/30 text-danger';
              $icon       = $is_success ? 'fa-circle-check' : 'fa-circle-exclamation';
            ?>
            <div class="flash-message flex items-start gap-3 border rounded-xl px-4 py-3 <?= $box_class ?>">
              <i class="fa-solid <?= $icon ?> mt-0.5"></i>
              <p class="flex-1 text-sm font-medium"><?= e($f['message']) ?></p>
              <button type="button" onclick="this.closest('.flash-message').remove()" class="text-current opacity-70 hover:opacity-100" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
