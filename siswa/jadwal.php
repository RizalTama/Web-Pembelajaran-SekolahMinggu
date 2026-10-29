<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('siswa');

$jadwal_mendatang = $pdo->query(
    "SELECT jadwal.*, guru.nama AS nama_guru FROM jadwal JOIN guru ON guru.id = jadwal.guru_id
     WHERE jadwal.tanggal >= CURDATE() ORDER BY jadwal.tanggal ASC, jadwal.jam_mulai ASC"
)->fetchAll();

$daftar_kegiatan = $pdo->query(
    "SELECT * FROM kegiatan WHERE status != 'selesai' ORDER BY tanggal_mulai ASC"
)->fetchAll();

$page_title = 'Jadwal & Kegiatan';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
  <div>
    <h3 class="font-bold text-ink mb-4">Jadwal Sekolah Minggu</h3>
    <?php if (empty($jadwal_mendatang)): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-12 px-6">
        <div class="w-14 h-14 rounded-full bg-primary-light text-primary flex items-center justify-center mb-3">
          <i class="fa-solid fa-calendar-days text-xl"></i>
        </div>
        <p class="text-ink font-semibold mb-1">Belum ada jadwal mendatang</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($jadwal_mendatang as $j): ?>
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-light text-primary flex flex-col items-center justify-center shrink-0 text-xs font-bold leading-tight">
              <span><?= date('d', strtotime($j['tanggal'])) ?></span>
              <span class="uppercase"><?= date('M', strtotime($j['tanggal'])) ?></span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-bold text-ink"><?= e($j['judul']) ?></p>
              <p class="text-sm text-muted"><?= format_tanggal($j['tanggal']) ?> &middot; <?= format_jam($j['jam_mulai']) ?> - <?= format_jam($j['jam_selesai']) ?></p>
              <p class="text-sm text-muted"><i class="fa-solid fa-chalkboard-user mr-1"></i><?= e($j['nama_guru']) ?></p>
              <?php if ($j['lokasi']): ?><p class="text-sm text-muted"><i class="fa-solid fa-location-dot mr-1"></i><?= e($j['lokasi']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <h3 class="font-bold text-ink mb-4">Kegiatan</h3>
    <?php if (empty($daftar_kegiatan)): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-12 px-6">
        <p class="text-sm text-muted">Belum ada kegiatan mendatang.</p>
      </div>
    <?php else: ?>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($daftar_kegiatan as $k): ?>
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <?php if (!empty($k['foto'])): ?>
              <img src="<?= e(UPLOAD_URL . '/' . $k['foto']) ?>" alt="" class="w-full h-36 object-cover">
            <?php else: ?>
              <div class="w-full h-36 bg-primary-light text-primary flex items-center justify-center">
                <i class="fa-solid fa-star text-3xl"></i>
              </div>
            <?php endif; ?>
            <div class="p-5 flex-1 flex flex-col">
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="font-bold text-ink leading-snug"><?= e($k['nama_kegiatan']) ?></h3>
                <?= badge_kegiatan($k['status']) ?>
              </div>
              <p class="text-xs text-muted mb-3">
                <i class="fa-solid fa-calendar-day mr-1"></i><?= format_tanggal($k['tanggal_mulai'], false) ?>
                <?php if ($k['tanggal_selesai'] && $k['tanggal_selesai'] !== $k['tanggal_mulai']): ?>
                  &ndash; <?= format_tanggal($k['tanggal_selesai'], false) ?>
                <?php endif; ?>
              </p>
              <p class="text-sm text-muted flex-1"><?= e($k['deskripsi'] ?: '-') ?></p>
              <?php if ($k['lokasi']): ?><p class="text-xs text-muted mt-2"><i class="fa-solid fa-location-dot mr-1"></i><?= e($k['lokasi']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
