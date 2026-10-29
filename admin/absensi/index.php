<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$jadwal_id = (int) ($_GET['jadwal_id'] ?? 0);

$daftar_jadwal_filter = $pdo->query('SELECT id, judul, tanggal FROM jadwal ORDER BY tanggal DESC')->fetchAll();

$where_jadwal = 'jadwal.tanggal BETWEEN ? AND ?';
$params = [$dari, $sampai];
if ($jadwal_id > 0) {
    $where_jadwal .= ' AND jadwal.id = ?';
    $params[] = $jadwal_id;
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
       AND absensi.jadwal_id IN (SELECT jadwal.id FROM jadwal WHERE {$where_jadwal})
     WHERE users.is_active = 1
     GROUP BY siswa.id, siswa.nama
     ORDER BY siswa.nama ASC"
);
$stmt->execute($params);
$rekap = $stmt->fetchAll();

$page_title = 'Rekap Absensi';
require __DIR__ . '/../../includes/header.php';
?>

<form method="GET" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
  <div class="grid sm:grid-cols-4 gap-4 items-end">
    <div>
      <label class="block text-sm font-semibold text-ink mb-1.5">Dari Tanggal</label>
      <input type="date" name="dari" value="<?= e($dari) ?>"
             class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
    </div>
    <div>
      <label class="block text-sm font-semibold text-ink mb-1.5">Sampai Tanggal</label>
      <input type="date" name="sampai" value="<?= e($sampai) ?>"
             class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
    </div>
    <div>
      <label class="block text-sm font-semibold text-ink mb-1.5">Jadwal</label>
      <select name="jadwal_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        <option value="0">Semua Jadwal</option>
        <?php foreach ($daftar_jadwal_filter as $j): ?>
          <option value="<?= (int) $j['id'] ?>" <?= $jadwal_id === (int) $j['id'] ? 'selected' : '' ?>>
            <?= e($j['judul']) ?> (<?= format_tanggal($j['tanggal'], false) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold text-sm">
      <i class="fa-solid fa-filter mr-1"></i> Tampilkan
    </button>
  </div>
</form>

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
            <th class="px-5 py-3 text-center">Total Pertemuan</th>
            <th class="px-5 py-3 text-center">% Kehadiran</th>
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
              <td class="px-5 py-3 text-center text-muted"><?= (int) $r['total'] ?></td>
              <td class="px-5 py-3 text-center font-bold text-ink"><?= $persen ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
