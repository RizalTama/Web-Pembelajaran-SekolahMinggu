<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('guru');

$guru_id = current_guru_id($pdo);

$stmt = $pdo->prepare(
    "SELECT * FROM jadwal WHERE guru_id = ? AND tanggal >= CURDATE()
     ORDER BY tanggal ASC, jam_mulai ASC LIMIT 1"
);
$stmt->execute([$guru_id]);
$jadwal_terdekat = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM jadwal WHERE guru_id = ? AND tanggal >= CURDATE()');
$stmt->execute([$guru_id]);
$jumlah_jadwal_mendatang = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM materi WHERE guru_id = ?');
$stmt->execute([$guru_id]);
$jumlah_materi_saya = (int) $stmt->fetchColumn();

$jumlah_siswa = count(get_siswa_aktif($pdo));

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
          <p class="text-xs text-white/70 mb-0.5">Jadwal Mengajar Terdekat</p>
          <p class="font-semibold"><?= e($jadwal_terdekat['judul']) ?></p>
          <p class="text-sm text-white/80"><?= format_tanggal($jadwal_terdekat['tanggal']) ?>, <?= format_jam($jadwal_terdekat['jam_mulai']) ?> - <?= format_jam($jadwal_terdekat['jam_selesai']) ?></p>
        </div>
      </div>
    <?php else: ?>
      <p class="text-white/80 text-sm">Belum ada jadwal mengajar mendatang.</p>
    <?php endif; ?>
  </div>
</div>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
  <div class="rounded-[1.75rem] bg-primary/10 p-5">
    <div class="w-11 h-11 rounded-2xl bg-primary text-white flex items-center justify-center mb-4 shadow-lg shadow-primary/25">
      <i class="fa-solid fa-calendar-days"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_jadwal_mendatang ?></p>
    <p class="text-sm text-muted">Jadwal Mendatang</p>
  </div>
  <div class="rounded-[1.75rem] bg-accent/15 p-5">
    <div class="w-11 h-11 rounded-2xl bg-accent text-white flex items-center justify-center mb-4 shadow-lg shadow-accent/25">
      <i class="fa-solid fa-book-open"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_materi_saya ?></p>
    <p class="text-sm text-muted">Materi Saya</p>
  </div>
  <div class="rounded-[1.75rem] bg-primary/10 p-5">
    <div class="w-11 h-11 rounded-2xl bg-primary text-white flex items-center justify-center mb-4 shadow-lg shadow-primary/25">
      <i class="fa-solid fa-child"></i>
    </div>
    <p class="text-2xl font-extrabold text-ink"><?= $jumlah_siswa ?></p>
    <p class="text-sm text-muted">Siswa Aktif</p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
