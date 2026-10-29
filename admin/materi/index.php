<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

$q = trim($_GET['q'] ?? '');
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 10;

$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE materi.judul LIKE ? OR guru.nama LIKE ?';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

$total = $pdo->prepare("SELECT COUNT(*) FROM materi JOIN guru ON guru.id = materi.guru_id {$where}");
$total->execute($params);
$total_data = (int) $total->fetchColumn();

$info = paginate_info($total_data, $halaman, $per_halaman);
$offset = paginate_offset($info['halaman'], $per_halaman);

$stmt = $pdo->prepare(
    "SELECT materi.*, guru.nama AS nama_guru
     FROM materi JOIN guru ON guru.id = materi.guru_id
     {$where}
     ORDER BY materi.created_at DESC
     LIMIT {$per_halaman} OFFSET {$offset}"
);
$stmt->execute($params);
$daftar_materi = $stmt->fetchAll();

$icon_tipe = ['pdf' => 'fa-file-pdf', 'gambar' => 'fa-image', 'link' => 'fa-link'];

$page_title = 'Materi';
require __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <p class="text-sm text-muted">Daftar materi yang diupload guru. Materi langsung tampil untuk siswa tanpa persetujuan.</p>
  <form method="GET" class="relative w-full sm:max-w-xs">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau nama guru..."
           class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
  </form>
</div>

<?php if (empty($daftar_materi)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-16 px-6">
    <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
      <i class="fa-solid fa-book-open text-2xl"></i>
    </div>
    <p class="text-ink font-semibold mb-1">Tidak ada materi</p>
    <p class="text-sm text-muted">Belum ada materi yang diupload guru.</p>
  </div>
<?php else: ?>
  <div class="space-y-4 mb-6">
    <?php foreach ($daftar_materi as $m): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <div class="min-w-0">
          <h3 class="font-bold text-ink mb-1.5"><?= e($m['judul']) ?></h3>
          <p class="text-xs text-muted mb-2">
            <i class="fa-solid fa-chalkboard-user mr-1"></i><?= e($m['nama_guru']) ?>
            &middot; <i class="fa-solid <?= $icon_tipe[$m['tipe']] ?? 'fa-file' ?> mr-1"></i><?= e(ucfirst($m['tipe'])) ?>
            &middot; <?= format_tanggal($m['created_at']) ?>
          </p>
          <?php if ($m['deskripsi']): ?><p class="text-sm text-muted mb-2"><?= e($m['deskripsi']) ?></p><?php endif; ?>

          <?php if ($m['tipe'] === 'link' && $m['link_url']): ?>
            <a href="<?= e($m['link_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
              <i class="fa-solid fa-up-right-from-square"></i> Buka Link
            </a>
          <?php elseif ($m['tipe'] === 'gambar' && $m['file_path']): ?>
            <img src="<?= e(UPLOAD_URL . '/' . $m['file_path']) ?>" alt="" class="max-w-xs rounded-xl border border-slate-200 mt-1">
          <?php elseif ($m['tipe'] === 'pdf' && $m['file_path']): ?>
            <a href="<?= e(UPLOAD_URL . '/' . $m['file_path']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
              <i class="fa-solid fa-file-pdf"></i> Lihat PDF
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($info['total_halaman'] > 1): ?>
    <div class="flex items-center justify-between px-1 text-sm">
      <p class="text-muted">Halaman <?= $info['halaman'] ?> dari <?= $info['total_halaman'] ?> (<?= $info['total_data'] ?> data)</p>
      <div class="flex gap-2">
        <?php if ($info['halaman'] > 1): ?>
          <a href="?q=<?= urlencode($q) ?>&halaman=<?= $info['halaman'] - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-surface">Sebelumnya</a>
        <?php endif; ?>
        <?php if ($info['halaman'] < $info['total_halaman']): ?>
          <a href="?q=<?= urlencode($q) ?>&halaman=<?= $info['halaman'] + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-surface">Berikutnya</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
