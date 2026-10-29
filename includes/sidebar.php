<?php
/**
 * Menu sidebar dinamis sesuai role. $user_role sudah diset di header.php.
 */
$current_path = $_SERVER['SCRIPT_NAME'];

$menu_admin = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge', 'href' => '/admin/dashboard.php'],
    ['label' => 'Data Guru', 'icon' => 'fa-chalkboard-user', 'href' => '/admin/guru/index.php'],
    ['label' => 'Data Siswa', 'icon' => 'fa-child', 'href' => '/admin/siswa/index.php'],
    ['label' => 'Jadwal', 'icon' => 'fa-calendar-days', 'href' => '/admin/jadwal/index.php'],
    ['label' => 'Kegiatan', 'icon' => 'fa-star', 'href' => '/admin/kegiatan/index.php'],
    ['label' => 'Pengumuman', 'icon' => 'fa-bullhorn', 'href' => '/admin/pengumuman/index.php'],
    ['label' => 'Rekap Absensi', 'icon' => 'fa-clipboard-check', 'href' => '/admin/absensi/index.php'],
    ['label' => 'Materi', 'icon' => 'fa-book-open', 'href' => '/admin/materi/index.php'],
];

$menu_guru = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge', 'href' => '/guru/dashboard.php'],
    ['label' => 'Profil', 'icon' => 'fa-user', 'href' => '/guru/profil.php'],
    ['label' => 'Jadwal Mengajar', 'icon' => 'fa-calendar-days', 'href' => '/guru/jadwal.php'],
    ['label' => 'Absensi', 'icon' => 'fa-clipboard-check', 'href' => '/guru/absensi/index.php'],
    ['label' => 'Materi', 'icon' => 'fa-book-open', 'href' => '/guru/materi/index.php'],
    ['label' => 'Rapor Siswa', 'icon' => 'fa-file-contract', 'href' => '/guru/rapor/index.php'],
    ['label' => 'Data Siswa', 'icon' => 'fa-child', 'href' => '/guru/siswa.php'],
];

$menu_siswa = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge', 'href' => '/siswa/dashboard.php'],
    ['label' => 'Profil', 'icon' => 'fa-user', 'href' => '/siswa/profil.php'],
    ['label' => 'Jadwal', 'icon' => 'fa-calendar-days', 'href' => '/siswa/jadwal.php'],
    ['label' => 'Materi', 'icon' => 'fa-book-open', 'href' => '/siswa/materi.php'],
    ['label' => 'Absensi Saya', 'icon' => 'fa-clipboard-check', 'href' => '/siswa/absensi.php'],
    ['label' => 'Nilai Raport', 'icon' => 'fa-graduation-cap', 'href' => '/siswa/rapor.php'],
    ['label' => 'Pengumuman', 'icon' => 'fa-bullhorn', 'href' => '/siswa/pengumuman.php'],
];

$menu = match ($user_role) {
    'admin' => $menu_admin,
    'guru'  => $menu_guru,
    'siswa' => $menu_siswa,
    default => [],
};
?>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-primary flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-200 lg:rounded-r-[1.75rem] overflow-hidden">

  <div class="absolute -right-10 -top-16 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
  <div class="absolute -left-12 bottom-16 w-32 h-32 rounded-full bg-white/5 pointer-events-none"></div>

  <div class="h-16 flex items-center gap-3 px-5 shrink-0 relative">
    <div class="w-9 h-9 rounded-2xl bg-white/15 text-white flex items-center justify-center shrink-0">
      <i class="fa-solid fa-church"></i>
    </div>
    <span class="font-extrabold text-white leading-tight text-sm">Sekolah Minggu</span>
    <button id="sidebar-close" type="button" class="ml-auto lg:hidden text-white/70 hover:text-white p-1" aria-label="Tutup menu">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 relative">
    <?php foreach ($menu as $item): ?>
      <?php $is_active = str_starts_with($current_path, BASE_URL . $item['href']) || $current_path === BASE_URL . $item['href']; ?>
      <a href="<?= e(BASE_URL . $item['href']) ?>"
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium transition-colors <?= $is_active ? 'bg-white text-primary shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' ?>">
        <i class="fa-solid <?= e($item['icon']) ?> w-5 text-center"></i>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="p-3 shrink-0 relative">
    <form action="<?= e(BASE_URL) ?>/auth/logout.php" method="POST">
      <?= csrf_field() ?>
      <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white transition-colors">
        <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
        <span>Keluar</span>
      </button>
    </form>
  </div>

</aside>
