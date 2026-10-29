<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('guru');

$tahun_ajaran = trim($_GET['tahun_ajaran'] ?? '');

$daftar_tahun_ajaran = $pdo->query('SELECT DISTINCT tahun_ajaran FROM rapor ORDER BY tahun_ajaran DESC')->fetchAll(PDO::FETCH_COLUMN);

$where = '';
$params = [];
if ($tahun_ajaran !== '') {
    $where = 'WHERE rapor.tahun_ajaran = ?';
    $params[] = $tahun_ajaran;
}

$stmt = $pdo->prepare(
    "SELECT rapor.*, siswa.nama AS nama_siswa, guru.nama AS nama_guru
     FROM rapor
     JOIN siswa ON siswa.id = rapor.siswa_id
     JOIN guru ON guru.id = rapor.guru_id
     {$where}
     ORDER BY siswa.nama ASC, rapor.tahun_ajaran DESC"
);
$stmt->execute($params);
$daftar_rapor = $stmt->fetchAll();

$page_title = 'Rapor Siswa';
require __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <form method="GET" class="relative w-full sm:max-w-xs">
    <select name="tahun_ajaran" onchange="this.form.submit()"
            class="w-full appearance-none rounded-xl border border-slate-300 pl-4 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      <option value="">Semua Tahun Ajaran</option>
      <?php foreach ($daftar_tahun_ajaran as $ta): ?>
        <option value="<?= e($ta) ?>" <?= $tahun_ajaran === $ta ? 'selected' : '' ?>><?= e($ta) ?></option>
      <?php endforeach; ?>
    </select>
    <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-muted text-xs pointer-events-none"></i>
  </form>
  <div class="flex flex-wrap items-center gap-2 shrink-0">
    <a href="<?= e(BASE_URL) ?>/guru/rapor/tambah.php"
       class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold transition-colors">
      <i class="fa-solid fa-plus"></i> Tambah Rapor
    </a>
  </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
  <?php if (empty($daftar_rapor)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
      <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        <i class="fa-solid fa-file-contract text-2xl"></i>
      </div>
      <p class="text-ink font-semibold mb-1">Belum ada data rapor</p>
      <p class="text-sm text-muted mb-4">Upload rapor siswa pertama Anda.</p>
      <a href="<?= e(BASE_URL) ?>/guru/rapor/tambah.php" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold text-sm">
        <i class="fa-solid fa-plus"></i> Tambah Rapor
      </a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Siswa</th>
            <th class="px-5 py-3">Tahun Ajaran</th>
            <th class="px-5 py-3">Diupload Oleh</th>
            <th class="px-5 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($daftar_rapor as $i => $r): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
              <td class="px-5 py-3 font-semibold text-ink"><?= e($r['nama_siswa']) ?></td>
              <td class="px-5 py-3 text-muted"><?= e($r['tahun_ajaran']) ?></td>
              <td class="px-5 py-3 text-muted">
                <?= e($r['nama_guru']) ?>
                <br><span class="text-xs"><?= format_tanggal($r['uploaded_at']) ?></span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <a href="<?= e(UPLOAD_URL . '/' . $r['file_pdf']) ?>" download
                     class="w-8 h-8 flex items-center justify-center rounded-lg text-primary hover:bg-primary-light" title="Unduh">
                    <i class="fa-solid fa-download"></i>
                  </a>
                  <a href="<?= e(BASE_URL) ?>/guru/rapor/edit.php?id=<?= (int) $r['id'] ?>"
                     class="w-8 h-8 flex items-center justify-center rounded-lg text-primary hover:bg-primary-light" title="Edit">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <form action="<?= e(BASE_URL) ?>/guru/rapor/hapus.php" method="POST" data-confirm="Rapor &quot;<?= e($r['nama_siswa']) ?>&quot; tahun ajaran <?= e($r['tahun_ajaran']) ?> akan dihapus permanen." data-confirm-title="Hapus Rapor">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-danger hover:bg-danger/10" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
