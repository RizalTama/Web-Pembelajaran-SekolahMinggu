<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT guru.*, users.username, users.is_active FROM guru JOIN users ON users.id = guru.user_id WHERE guru.id = ?');
$stmt->execute([$id]);
$guru = $stmt->fetch();

if (!$guru) {
    flash('error', 'Data guru tidak ditemukan.');
    redirect('/admin/guru/index.php');
}

$errors = [];
$old = [
    'nama' => $guru['nama'],
    'jenis_kelamin' => $guru['jenis_kelamin'],
    'no_hp' => $guru['no_hp'],
    'email' => $guru['email'],
    'alamat' => $guru['alamat'],
    'is_active' => (int) $guru['is_active'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['nama']          = trim($_POST['nama'] ?? '');
    $old['jenis_kelamin'] = $_POST['jenis_kelamin'] ?? 'L';
    $old['no_hp']         = trim($_POST['no_hp'] ?? '');
    $old['email']         = trim($_POST['email'] ?? '');
    $old['alamat']        = trim($_POST['alamat'] ?? '');
    $old['is_active']     = isset($_POST['is_active']) ? 1 : 0;
    $password_baru        = $_POST['password_baru'] ?? '';

    if ($old['nama'] === '') {
        $errors[] = 'Nama wajib diisi.';
    }
    if (!in_array($old['jenis_kelamin'], ['L', 'P'], true)) {
        $errors[] = 'Jenis kelamin tidak valid.';
    }
    if ($password_baru !== '' && strlen($password_baru) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }

    $foto_path = $guru['foto'];
    $foto_lama = $guru['foto'];
    if (empty($errors) && !empty($_FILES['foto']['name'])) {
        try {
            $foto_path = upload_file($_FILES['foto'], 'foto', EXT_FOTO, MAX_FOTO);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE guru SET nama = ?, jenis_kelamin = ?, no_hp = ?, email = ?, alamat = ?, foto = ? WHERE id = ?');
            $stmt->execute([$old['nama'], $old['jenis_kelamin'], $old['no_hp'] ?: null, $old['email'] ?: null, $old['alamat'] ?: null, $foto_path, $id]);

            $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
            $stmt->execute([$old['is_active'], $guru['user_id']]);

            if ($password_baru !== '') {
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([password_hash($password_baru, PASSWORD_DEFAULT), $guru['user_id']]);
            }

            $pdo->commit();

            if ($foto_path !== $foto_lama) {
                delete_uploaded_file($foto_lama);
            }

            flash('success', 'Data guru berhasil diperbarui.');
            redirect('/admin/guru/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($foto_path !== $foto_lama) {
                delete_uploaded_file($foto_path);
            }
            $errors[] = 'Gagal memperbarui data guru. Silakan coba lagi.';
        }
    } elseif ($foto_path !== $foto_lama) {
        delete_uploaded_file($foto_path);
    }
}

$page_title = 'Edit Guru';
require __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= e(BASE_URL) ?>/admin/guru/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Data Guru
  </a>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <?php if (!empty($errors)): ?>
      <div class="border border-danger/30 bg-danger/10 text-danger rounded-xl px-4 py-3 mb-6 text-sm space-y-1">
        <?php foreach ($errors as $err): ?><p><i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <?= csrf_field() ?>

      <div class="flex items-center gap-4">
        <?php if (!empty($guru['foto'])): ?>
          <img id="preview-foto" src="<?= e(UPLOAD_URL . '/' . $guru['foto']) ?>" alt="" class="w-16 h-16 rounded-full object-cover">
        <?php else: ?>
          <img id="preview-foto" src="" alt="" class="w-16 h-16 rounded-full object-cover hidden">
          <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center">
            <i class="fa-solid fa-camera"></i>
          </div>
        <?php endif; ?>
        <div>
          <label class="inline-block cursor-pointer bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl px-4 py-2 text-sm font-semibold text-ink">
            <i class="fa-solid fa-upload mr-1"></i> Ganti Foto
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="hidden" data-preview="preview-foto" data-preview-name="nama-foto">
          </label>
          <p id="nama-foto" class="text-xs text-muted mt-1 hidden"></p>
          <p class="text-xs text-muted mt-1">JPG, PNG, atau WEBP. Maks 15 MB.</p>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Username</label>
          <input type="text" value="<?= e($guru['username']) ?>" disabled
                 class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-muted">
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Password Baru</label>
          <input type="password" name="password_baru" minlength="6" placeholder="Kosongkan jika tidak diubah"
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" value="<?= e($old['nama']) ?>" required
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Jenis Kelamin</label>
          <select name="jenis_kelamin" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            <option value="L" <?= $old['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="P" <?= $old['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">No. HP</label>
          <input type="text" name="no_hp" value="<?= e($old['no_hp']) ?>"
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Email</label>
        <input type="email" name="email" value="<?= e($old['email']) ?>"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Alamat</label>
        <textarea name="alamat" rows="3"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= e($old['alamat']) ?></textarea>
      </div>

      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" <?= $old['is_active'] === 1 ? 'checked' : '' ?>
               class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary">
        <span class="text-sm font-semibold text-ink">Akun aktif (bisa login)</span>
      </label>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/admin/guru/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
