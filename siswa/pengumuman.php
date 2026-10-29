<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('siswa');

$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 10;

$total = (int) $pdo->query('SELECT COUNT(*) FROM pengumuman')->fetchColumn();
$info = paginate_info($total, $halaman, $per_halaman);
$offset = paginate_offset($info['halaman'], $per_halaman);

$stmt = $pdo->prepare("SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT {$per_halaman} OFFSET {$offset}");
$stmt->execute();
$daftar_pengumuman = $stmt->fetchAll();

$page_title = 'Pengumuman';
require __DIR__ . '/../includes/header.php';
?>

<?php if (empty($daftar_pengumuman)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-16 px-6">
    <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
      <i class="fa-solid fa-bullhorn text-2xl"></i>
    </div>
    <p class="text-ink font-semibold mb-1">Belum ada pengumuman</p>
  </div>
<?php else: ?>
  <div class="space-y-4 mb-6">
    <?php foreach ($daftar_pengumuman as $p): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-bold text-ink mb-2"><?= e($p['judul']) ?></h3>
        <p class="text-sm text-muted whitespace-pre-line mb-2"><?= e($p['isi']) ?></p>
        <p class="text-xs text-muted"><i class="fa-regular fa-clock mr-1"></i><?= format_tanggal($p['created_at']) ?></p>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
