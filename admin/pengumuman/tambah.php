<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

$errors = [];
$old = ['judul' => '', 'isi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['judul'] = trim($_POST['judul'] ?? '');
    $old['isi']   = trim($_POST['isi'] ?? '');

    if ($old['judul'] === '' || $old['isi'] === '') {
        $errors[] = 'Judul dan isi pengumuman wajib diisi.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO pengumuman (judul, isi, created_by) VALUES (?, ?, ?)');
        $stmt->execute([$old['judul'], $old['isi'], $_SESSION['user_id']]);

        flash('success', 'Pengumuman berhasil ditambahkan.');
        redirect('/admin/pengumuman/index.php');
    }
}

$page_title = 'Tambah Pengumuman';
require __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= e(BASE_URL) ?>/admin/pengumuman/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Pengumuman
  </a>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <?php if (!empty($errors)): ?>
      <div class="border border-danger/30 bg-danger/10 text-danger rounded-xl px-4 py-3 mb-6 text-sm space-y-1">
        <?php foreach ($errors as $err): ?><p><i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" value="<?= e($old['judul']) ?>" required
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Isi Pengumuman <span class="text-danger">*</span></label>
        <textarea name="isi" rows="6" required
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= e($old['isi']) ?></textarea>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/admin/pengumuman/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Pengumuman</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
