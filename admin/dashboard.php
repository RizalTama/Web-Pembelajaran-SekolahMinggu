<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('admin');

$jumlah_siswa = (int) $pdo->query('SELECT COUNT(*) FROM siswa JOIN users ON users.id = siswa.user_id WHERE users.is_active = 1')->fetchColumn();
$jumlah_guru = (int) $pdo->query('SELECT COUNT(*) FROM guru JOIN users ON users.id = guru.user_id WHERE users.is_active = 1')->fetchColumn();
$jumlah_kegiatan_berjalan = (int) $pdo->query("SELECT COUNT(*) FROM kegiatan WHERE status = 'berjalan'")->fetchColumn();
$jumlah_materi = (int) $pdo->query('SELECT COUNT(*) FROM materi')->fetchColumn();

$stmt = $pdo->query(
    "SELECT
       SUM(status = 'hadir') AS hadir,
       COUNT(*) AS total
     FROM absensi
     JOIN jadwal ON jadwal.id = absensi.jadwal_id
     WHERE MONTH(jadwal.tanggal) = MONTH(CURDATE()) AND YEAR(jadwal.tanggal) = YEAR(CURDATE())"
);
$row = $stmt->fetch();
$persen_kehadiran = ($row && $row['total'] > 0) ? round(($row['hadir'] / $row['total']) * 100) : 0;

$stmt = $pdo->query(
    "SELECT jadwal.*, guru.nama AS nama_guru
     FROM jadwal JOIN guru ON guru.id = jadwal.guru_id
     WHERE jadwal.tanggal >= CURDATE()
     ORDER BY jadwal.tanggal ASC, jadwal.jam_mulai ASC
     LIMIT 1"
);
$jadwal_terdekat = $stmt->fetch();

$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="bg-primary rounded-[1.75rem] p-6 sm:p-8 text-white mb-6 relative overflow-hidden">
  <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/5"></div>
  <div class="absolute right-16 bottom-4 w-16 h-16 rounded-2xl bg-accent/20 rotate-12 hidden sm:block"></div>
  <div class="relative">
    <p class="text-white/80 text-sm mb-1">Selamat datang,</p>
    <h2 class="text-2xl font-extrabold mb-4">Halo, <?= e($_SESSION['nama']) ?> <span class="inline-block">👋</span></h2>

    <?php if ($jadwal_terdekat): ?>
      <div class="bg-white/10 rounded-2xl p-4 inline-flex items-start gap-3 max-w-md">
        <div class="w-10 h-10 rounded-xl bg-accent text-ink flex items-center justify-center shrink-0">
          <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div>
          <p class="text-xs text-white/70 mb-0.5">Jadwal Terdekat</p>
          <p class="font-semibold"><?= e($jadwal_terdekat['judul']) ?> &middot; <?= e($jadwal_terdekat['nama_guru']) ?></p>
          <p class="text-sm text-white/80"><?= format_tanggal($jadwal_terdekat['tanggal']) ?>, <?= format_jam($jadwal_terdekat['jam_mulai']) ?> - <?= format_jam($jadwal_terdekat['jam_selesai']) ?></p>
        </div>
      </div>
    <?php else: ?>
      <p class="text-white/80 text-sm">Belum ada jadwal mendatang.</p>
    <?php endif; ?>
  </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
  <div class="rounded-[1.75rem] bg-primary/10 p-5">
    <div class="w-11 h-11 rounded-2xl bg-primary text-white flex items-center justify-center mb-4 shadow-lg shadow-primary/25">
      <i class="fa-solid fa-child"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_siswa ?></p>
    <p class="text-sm text-muted">Siswa Aktif</p>
  </div>
  <div class="rounded-[1.75rem] bg-mint/15 p-5">
    <div class="w-11 h-11 rounded-2xl bg-mint text-white flex items-center justify-center mb-4 shadow-lg shadow-mint/25">
      <i class="fa-solid fa-chalkboard-user"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_guru ?></p>
    <p class="text-sm text-muted">Guru Aktif</p>
  </div>
  <div class="rounded-[1.75rem] bg-peach/15 p-5">
    <div class="w-11 h-11 rounded-2xl bg-peach text-white flex items-center justify-center mb-4 shadow-lg shadow-peach/25">
      <i class="fa-solid fa-star"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_kegiatan_berjalan ?></p>
    <p class="text-sm text-muted">Kegiatan Berjalan</p>
  </div>
  <div class="rounded-[1.75rem] bg-accent/15 p-5">
    <div class="w-11 h-11 rounded-2xl bg-accent text-white flex items-center justify-center mb-4 shadow-lg shadow-accent/25">
      <i class="fa-solid fa-book-open"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_materi ?></p>
    <p class="text-sm text-muted">Total Materi</p>
  </div>
  <div class="rounded-[1.75rem] bg-primary/10 p-5">
    <div class="w-11 h-11 rounded-2xl bg-primary text-white flex items-center justify-center mb-4 shadow-lg shadow-primary/25">
      <i class="fa-solid fa-clipboard-check"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $persen_kehadiran ?>%</p>
    <p class="text-sm text-muted">Kehadiran Bulan Ini</p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
