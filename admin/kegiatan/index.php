<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 10;

$total = (int) $pdo->query('SELECT COUNT(*) FROM kegiatan')->fetchColumn();
$info = paginate_info($total, $halaman, $per_halaman);
$offset = paginate_offset($info['halaman'], $per_halaman);

$stmt = $pdo->prepare("SELECT * FROM kegiatan ORDER BY tanggal_mulai DESC LIMIT {$per_halaman} OFFSET {$offset}");
$stmt->execute();
$daftar_kegiatan = $stmt->fetchAll();

$page_title = 'Kegiatan';
require __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <p class="text-sm text-muted">Kelola data kegiatan sekolah minggu.</p>
  <a href="<?= e(BASE_URL) ?>/admin/kegiatan/tambah.php"
     class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold transition-colors shrink-0">
    <i class="fa-solid fa-plus"></i> Tambah Kegiatan
  </a>
</div>

<?php if (empty($daftar_kegiatan)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-16 px-6">
    <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
      <i class="fa-solid fa-star text-2xl"></i>
    </div>
    <p class="text-ink font-semibold mb-1">Belum ada kegiatan</p>
    <p class="text-sm text-muted mb-4">Tambahkan kegiatan pertama sekolah minggu.</p>
    <a href="<?= e(BASE_URL) ?>/admin/kegiatan/tambah.php" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold text-sm">
      <i class="fa-solid fa-plus"></i> Tambah Kegiatan
    </a>
  </div>
<?php else: ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
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
          <p class="text-sm text-muted line-clamp-2 flex-1"><?= e($k['deskripsi'] ?: '-') ?></p>
          <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-slate-100">
            <a href="<?= e(BASE_URL) ?>/admin/kegiatan/edit.php?id=<?= (int) $k['id'] ?>"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-primary hover:bg-primary-light" title="Edit">
              <i class="fa-solid fa-pen"></i>
            </a>
            <form action="<?= e(BASE_URL) ?>/admin/kegiatan/hapus.php" method="POST" data-confirm="Kegiatan &quot;<?= e($k['nama_kegiatan']) ?>&quot; akan dihapus permanen." data-confirm-title="Hapus Kegiatan">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
              <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-danger hover:bg-danger/10" title="Hapus">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($info['total_halaman'] > 1): ?>
    <div class="flex items-center justify-between px-1 text-sm">
      <p class="text-muted">Halaman <?= $info['halaman'] ?> dari <?= $info['total_halaman'] ?> (<?= $info['total_data'] ?> data)</p>
      <div class="flex gap-2">
        <?php if ($info['halaman'] > 1): ?>
          <a href="?halaman=<?= $info['halaman'] - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-surface">Sebelumnya</a>
        <?php endif; ?>
        <?php if ($info['halaman'] < $info['total_halaman']): ?>
          <a href="?halaman=<?= $info['halaman'] + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-surface">Berikutnya</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
