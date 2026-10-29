<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('guru');

$guru_id = current_guru_id($pdo);
$siswa_id = (int) ($_GET['siswa_id'] ?? 0);

if ($siswa_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM siswa WHERE id = ?');
    $stmt->execute([$siswa_id]);
    $siswa_detail = $stmt->fetch();

    if ($siswa_detail) {
        $stmt = $pdo->prepare(
            "SELECT jadwal.judul, jadwal.tanggal, absensi.status, absensi.keterangan
             FROM absensi
             JOIN jadwal ON jadwal.id = absensi.jadwal_id
             WHERE absensi.siswa_id = ? AND jadwal.guru_id = ?
             ORDER BY jadwal.tanggal DESC"
        );
        $stmt->execute([$siswa_id, $guru_id]);
        $detail_pertemuan = $stmt->fetchAll();
    }
}

$stmt = $pdo->prepare(
    "SELECT siswa.id, siswa.nama,
       SUM(absensi.status = 'hadir') AS hadir,
       SUM(absensi.status = 'izin') AS izin,
       SUM(absensi.status = 'sakit') AS sakit,
       SUM(absensi.status = 'alpa') AS alpa,
       COUNT(absensi.id) AS total
     FROM siswa
     JOIN users ON users.id = siswa.user_id
     LEFT JOIN absensi ON absensi.siswa_id = siswa.id
     LEFT JOIN jadwal ON jadwal.id = absensi.jadwal_id AND jadwal.guru_id = ?
     WHERE users.is_active = 1
     GROUP BY siswa.id, siswa.nama
     ORDER BY siswa.nama ASC"
);
$stmt->execute([$guru_id]);
$rekap = $stmt->fetchAll();

$page_title = 'Riwayat Kehadiran Siswa';
require __DIR__ . '/../../includes/header.php';
?>

<?php if ($siswa_id > 0 && !empty($siswa_detail)): ?>
  <a href="<?= e(BASE_URL) ?>/guru/absensi/riwayat.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Rekap Semua Siswa
  </a>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
    <p class="font-bold text-ink"><?= e($siswa_detail['nama']) ?></p>
    <p class="text-sm text-muted">Detail kehadiran per pertemuan dari jadwal Anda.</p>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <?php if (empty($detail_pertemuan)): ?>
      <div class="flex flex-col items-center justify-center text-center py-12 px-6">
        <p class="text-sm text-muted">Belum ada data kehadiran untuk siswa ini.</p>
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
            <?php foreach ($detail_pertemuan as $i => $d): ?>
              <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
                <td class="px-5 py-3 font-semibold text-ink"><?= e($d['judul']) ?></td>
                <td class="px-5 py-3 text-muted"><?= format_tanggal($d['tanggal'], false) ?></td>
                <td class="px-5 py-3"><?= badge_absensi($d['status']) ?></td>
                <td class="px-5 py-3 text-muted"><?= e($d['keterangan'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>

  <p class="text-sm text-muted mb-6">Rekap kehadiran siswa dari jadwal mengajar Anda.</p>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <?php if (empty($rekap)): ?>
      <div class="flex flex-col items-center justify-center text-center py-16 px-6">
        <p class="text-ink font-semibold mb-1">Belum ada data siswa</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
            <tr>
              <th class="px-5 py-3">Siswa</th>
              <th class="px-5 py-3 text-center">Hadir</th>
              <th class="px-5 py-3 text-center">Izin</th>
              <th class="px-5 py-3 text-center">Sakit</th>
              <th class="px-5 py-3 text-center">Alpa</th>
              <th class="px-5 py-3 text-center">% Hadir</th>
              <th class="px-5 py-3 text-right">Detail</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($rekap as $i => $r): ?>
              <?php $persen = $r['total'] > 0 ? round(($r['hadir'] / $r['total']) * 100) : 0; ?>
              <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
                <td class="px-5 py-3 font-semibold text-ink"><?= e($r['nama']) ?></td>
                <td class="px-5 py-3 text-center text-success font-semibold"><?= (int) $r['hadir'] ?></td>
                <td class="px-5 py-3 text-center text-primary font-semibold"><?= (int) $r['izin'] ?></td>
                <td class="px-5 py-3 text-center text-accent font-semibold"><?= (int) $r['sakit'] ?></td>
                <td class="px-5 py-3 text-center text-danger font-semibold"><?= (int) $r['alpa'] ?></td>
                <td class="px-5 py-3 text-center font-bold text-ink"><?= $persen ?>%</td>
                <td class="px-5 py-3 text-right">
                  <a href="?siswa_id=<?= (int) $r['id'] ?>" class="text-primary hover:underline font-semibold text-sm">Lihat Detail</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
