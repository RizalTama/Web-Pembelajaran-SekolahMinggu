<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('siswa');

$siswa_id = current_siswa_id($pdo);

$tahun_ajaran = trim($_GET['tahun_ajaran'] ?? '');

$stmt = $pdo->prepare('SELECT DISTINCT tahun_ajaran FROM rapor WHERE siswa_id = ? ORDER BY tahun_ajaran DESC');
$stmt->execute([$siswa_id]);
$daftar_tahun_ajaran = $stmt->fetchAll(PDO::FETCH_COLUMN);

$where = 'WHERE rapor.siswa_id = ?';
$params = [$siswa_id];
if ($tahun_ajaran !== '') {
    $where .= ' AND rapor.tahun_ajaran = ?';
    $params[] = $tahun_ajaran;
}

$stmt = $pdo->prepare(
    "SELECT rapor.*, guru.nama AS nama_guru
     FROM rapor
     JOIN guru ON guru.id = rapor.guru_id
     {$where}
     ORDER BY rapor.tahun_ajaran DESC"
);
$stmt->execute($params);
$daftar_rapor = $stmt->fetchAll();

$page_title = 'Nilai Raport';
require __DIR__ . '/../includes/header.php';
?>

<form method="GET" class="relative w-full sm:max-w-xs mb-6">
  <select name="tahun_ajaran" onchange="this.form.submit()"
          class="w-full appearance-none rounded-xl border border-slate-300 pl-4 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
    <option value="">Semua Tahun Ajaran</option>
    <?php foreach ($daftar_tahun_ajaran as $ta): ?>
      <option value="<?= e($ta) ?>" <?= $tahun_ajaran === $ta ? 'selected' : '' ?>><?= e($ta) ?></option>
    <?php endforeach; ?>
  </select>
  <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-muted text-xs pointer-events-none"></i>
</form>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
  <?php if (empty($daftar_rapor)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
      <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        <i class="fa-solid fa-graduation-cap text-2xl"></i>
      </div>
      <p class="text-ink font-semibold mb-1">Belum ada data rapor</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Tahun Ajaran</th>
            <th class="px-5 py-3">Diupload Oleh</th>
            <th class="px-5 py-3">Tanggal Upload</th>
            <th class="px-5 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($daftar_rapor as $i => $r): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
              <td class="px-5 py-3 font-semibold text-ink"><i class="fa-solid fa-file-pdf text-danger mr-1"></i><?= e($r['tahun_ajaran']) ?></td>
              <td class="px-5 py-3 text-muted"><?= e($r['nama_guru']) ?></td>
              <td class="px-5 py-3 text-muted"><?= format_tanggal($r['uploaded_at']) ?></td>
              <td class="px-5 py-3 text-right">
                <a href="<?= e(UPLOAD_URL . '/' . $r['file_pdf']) ?>" download class="text-primary hover:underline font-semibold text-sm">
                  <i class="fa-solid fa-download mr-1"></i> Unduh
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
