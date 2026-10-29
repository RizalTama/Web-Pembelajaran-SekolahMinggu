<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('admin');

$errors = [];
$old = ['username' => '', 'nama' => '', 'jenis_kelamin' => 'L', 'no_hp' => '', 'email' => '', 'alamat' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['username']       = trim($_POST['username'] ?? '');
    $old['nama']            = trim($_POST['nama'] ?? '');
    $old['jenis_kelamin']   = $_POST['jenis_kelamin'] ?? 'L';
    $old['no_hp']           = trim($_POST['no_hp'] ?? '');
    $old['email']           = trim($_POST['email'] ?? '');
    $old['alamat']          = trim($_POST['alamat'] ?? '');
    $password               = $_POST['password'] ?? '';

    if ($old['username'] === '' || $old['nama'] === '' || $password === '') {
        $errors[] = 'Username, nama, dan password wajib diisi.';
    }
    if (!in_array($old['jenis_kelamin'], ['L', 'P'], true)) {
        $errors[] = 'Jenis kelamin tidak valid.';
    }
    if (strlen($password) > 0 && strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$old['username']]);
        if ($stmt->fetch()) {
            $errors[] = 'Username sudah digunakan, silakan pilih username lain.';
        }
    }

    $foto_path = null;
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

            $stmt = $pdo->prepare('INSERT INTO users (username, password, role, is_active) VALUES (?, ?, "guru", 1)');
            $stmt->execute([$old['username'], password_hash($password, PASSWORD_DEFAULT)]);
            $user_id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO guru (user_id, nama, jenis_kelamin, no_hp, email, alamat, foto) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $old['nama'], $old['jenis_kelamin'], $old['no_hp'] ?: null, $old['email'] ?: null, $old['alamat'] ?: null, $foto_path]);

            $pdo->commit();

            flash('success', 'Data guru berhasil ditambahkan.');
            redirect('/admin/guru/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            if ($foto_path) {
                delete_uploaded_file($foto_path);
            }
            $errors[] = 'Gagal menyimpan data guru. Silakan coba lagi.';
        }
    } elseif ($foto_path) {
        delete_uploaded_file($foto_path);
    }
}

$page_title = 'Tambah Guru';
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
        <img id="preview-foto" src="" alt="" class="w-16 h-16 rounded-full object-cover hidden">
        <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center" id="preview-placeholder">
          <i class="fa-solid fa-camera"></i>
        </div>
        <div>
          <label class="inline-block cursor-pointer bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl px-4 py-2 text-sm font-semibold text-ink">
            <i class="fa-solid fa-upload mr-1"></i> Pilih Foto
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="hidden" data-preview="preview-foto" data-preview-name="nama-foto">
          </label>
          <p id="nama-foto" class="text-xs text-muted mt-1 hidden"></p>
          <p class="text-xs text-muted mt-1">JPG, PNG, atau WEBP. Maks 15 MB.</p>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Username <span class="text-danger">*</span></label>
          <input type="text" name="username" value="<?= e($old['username']) ?>" required
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Password <span class="text-danger">*</span></label>
          <input type="password" name="password" required minlength="6"
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

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/admin/guru/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Guru</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
