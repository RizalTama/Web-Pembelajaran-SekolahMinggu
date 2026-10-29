<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('guru');

$guru_id = current_guru_id($pdo);

$q = trim($_GET['q'] ?? '');
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 10;

$where = 'WHERE guru_id = ?';
$params = [$guru_id];
if ($q !== '') {
    $where .= ' AND judul LIKE ?';
    $params[] = '%' . $q . '%';
}

$total = $pdo->prepare("SELECT COUNT(*) FROM materi {$where}");
$total->execute($params);
$total_data = (int) $total->fetchColumn();

$info = paginate_info($total_data, $halaman, $per_halaman);
$offset = paginate_offset($info['halaman'], $per_halaman);

$stmt = $pdo->prepare("SELECT * FROM materi {$where} ORDER BY created_at DESC LIMIT {$per_halaman} OFFSET {$offset}");
$stmt->execute($params);
$daftar_materi = $stmt->fetchAll();

$icon_tipe = ['pdf' => 'fa-file-pdf', 'gambar' => 'fa-image', 'link' => 'fa-link'];

$page_title = 'Materi Saya';
require __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <form method="GET" class="relative w-full sm:max-w-xs">
    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm"></i>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul materi..."
           class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
  </form>
  <a href="<?= e(BASE_URL) ?>/guru/materi/tambah.php"
     class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold transition-colors shrink-0">
    <i class="fa-solid fa-plus"></i> Upload Materi
  </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
  <?php if (empty($daftar_materi)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
      <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        <i class="fa-solid fa-book-open text-2xl"></i>
      </div>
      <p class="text-ink font-semibold mb-1">Belum ada materi</p>
      <p class="text-sm text-muted mb-4">Upload materi pertama Anda.</p>
      <a href="<?= e(BASE_URL) ?>/guru/materi/tambah.php" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold text-sm">
        <i class="fa-solid fa-plus"></i> Upload Materi
      </a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Materi</th>
            <th class="px-5 py-3">Tipe</th>
            <th class="px-5 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($daftar_materi as $i => $m): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
              <td class="px-5 py-3">
                <p class="font-semibold text-ink"><?= e($m['judul']) ?></p>
                <p class="text-xs text-muted"><?= format_tanggal($m['created_at']) ?></p>
              </td>
              <td class="px-5 py-3 text-muted">
                <i class="fa-solid <?= $icon_tipe[$m['tipe']] ?? 'fa-file' ?> mr-1"></i><?= e(ucfirst($m['tipe'])) ?>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button type="button" onclick="document.getElementById('modal-lihat-<?= (int) $m['id'] ?>').classList.remove('hidden'); document.getElementById('modal-lihat-<?= (int) $m['id'] ?>').classList.add('flex')"
                          class="w-8 h-8 flex items-center justify-center rounded-lg text-muted hover:bg-surface" title="Lihat">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                  <a href="<?= e(BASE_URL) ?>/guru/materi/edit.php?id=<?= (int) $m['id'] ?>"
                     class="w-8 h-8 flex items-center justify-center rounded-lg text-primary hover:bg-primary-light" title="Edit">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <form action="<?= e(BASE_URL) ?>/guru/materi/hapus.php" method="POST" data-confirm="Materi &quot;<?= e($m['judul']) ?>&quot; akan dihapus permanen." data-confirm-title="Hapus Materi">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-danger hover:bg-danger/10" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>

            <div id="modal-lihat-<?= (int) $m['id'] ?>" class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/40 px-4"
                 onclick="if (event.target === this) { this.classList.add('hidden'); this.classList.remove('flex'); }">
              <div class="bg-white rounded-2xl shadow-sm border border-slate-200 max-w-md w-full p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                  <h3 class="text-lg font-bold text-ink"><?= e($m['judul']) ?></h3>
                  <button type="button" onclick="document.getElementById('modal-lihat-<?= (int) $m['id'] ?>').classList.add('hidden'); document.getElementById('modal-lihat-<?= (int) $m['id'] ?>').classList.remove('flex')"
                          class="w-8 h-8 flex items-center justify-center rounded-lg text-muted hover:bg-surface shrink-0" title="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
                <?php if ($m['deskripsi']): ?><p class="text-sm text-muted mb-4"><?= e($m['deskripsi']) ?></p><?php endif; ?>

                <?php if ($m['tipe'] === 'link' && $m['link_url']): ?>
                  <div class="flex justify-center">
                    <a href="<?= e($m['link_url']) ?>" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2 font-semibold text-sm">
                      <i class="fa-solid fa-up-right-from-square"></i> Buka Link
                    </a>
                  </div>
                <?php elseif ($m['tipe'] === 'gambar' && $m['file_path']): ?>
                  <img src="<?= e(UPLOAD_URL . '/' . $m['file_path']) ?>" alt="<?= e($m['judul']) ?>" class="w-full rounded-xl border border-slate-200">
                <?php elseif ($m['tipe'] === 'pdf' && $m['file_path']): ?>
                  <div class="flex justify-center">
                    <a href="<?= e(UPLOAD_URL . '/' . $m['file_path']) ?>" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2 font-semibold text-sm">
                      <i class="fa-solid fa-file-pdf"></i> Buka / Unduh PDF
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($info['total_halaman'] > 1): ?>
      <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100 text-sm">
        <p class="text-muted">Halaman <?= $info['halaman'] ?> dari <?= $info['total_halaman'] ?> (<?= $info['total_data'] ?> data)</p>
        <div class="flex gap-2">
          <?php if ($info['halaman'] > 1): ?>
            <a href="?q=<?= urlencode($q) ?>&halaman=<?= $info['halaman'] - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-surface">Sebelumnya</a>
          <?php endif; ?>
          <?php if ($info['halaman'] < $info['total_halaman']): ?>
            <a href="?q=<?= urlencode($q) ?>&halaman=<?= $info['halaman'] + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-surface">Berikutnya</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
