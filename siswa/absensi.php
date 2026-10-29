<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('siswa');

$siswa_id = current_siswa_id($pdo);

$stmt = $pdo->prepare(
    "SELECT SUM(status = 'hadir') AS hadir, SUM(status = 'izin') AS izin,
            SUM(status = 'sakit') AS sakit, SUM(status = 'alpa') AS alpa, COUNT(*) AS total
     FROM absensi WHERE siswa_id = ?"
);
$stmt->execute([$siswa_id]);
$rekap = $stmt->fetch();
$persen = ($rekap && $rekap['total'] > 0) ? round(($rekap['hadir'] / $rekap['total']) * 100) : 0;

$stmt = $pdo->prepare(
    "SELECT jadwal.judul, jadwal.tanggal, absensi.status, absensi.keterangan
     FROM absensi JOIN jadwal ON jadwal.id = absensi.jadwal_id
     WHERE absensi.siswa_id = ? ORDER BY jadwal.tanggal DESC"
);
$stmt->execute([$siswa_id]);
$riwayat = $stmt->fetchAll();

$page_title = 'Absensi Saya';
require __DIR__ . '/../includes/header.php';
?>

<div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-6">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
    <p class="text-2xl font-extrabold text-ink"><?= $persen ?>%</p>
    <p class="text-sm text-muted">Persentase Kehadiran</p>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
    <p class="text-2xl font-extrabold text-success"><?= (int) $rekap['hadir'] ?></p>
    <p class="text-sm text-muted">Hadir</p>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
    <p class="text-2xl font-extrabold text-primary"><?= (int) $rekap['izin'] ?></p>
    <p class="text-sm text-muted">Izin</p>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
    <p class="text-2xl font-extrabold text-accent"><?= (int) $rekap['sakit'] ?></p>
    <p class="text-sm text-muted">Sakit</p>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
    <p class="text-2xl font-extrabold text-danger"><?= (int) $rekap['alpa'] ?></p>
    <p class="text-sm text-muted">Alpa</p>
  </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
  <?php if (empty($riwayat)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
      <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        <i class="fa-solid fa-clipboard-check text-2xl"></i>
      </div>
      <p class="text-ink font-semibold mb-1">Belum ada riwayat absensi</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Pertemuan</th>
            <th class="px-5 py-3">Tanggal</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3">Keterangan</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($riwayat as $i => $r): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
              <td class="px-5 py-3 font-semibold text-ink"><?= e($r['judul']) ?></td>
              <td class="px-5 py-3 text-muted"><?= format_tanggal($r['tanggal'], false) ?></td>
              <td class="px-5 py-3"><?= badge_absensi($r['status']) ?></td>
              <td class="px-5 py-3 text-muted"><?= e($r['keterangan'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
