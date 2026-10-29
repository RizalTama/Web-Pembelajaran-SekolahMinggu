<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/upload.php';

require_role('siswa');

$siswa_id = current_siswa_id($pdo);
$stmt = $pdo->prepare('SELECT siswa.*, users.username FROM siswa JOIN users ON users.id = siswa.user_id WHERE siswa.id = ?');
$stmt->execute([$siswa_id]);
$siswa = $stmt->fetch();

$errors = [];
$old = ['no_hp_orang_tua' => $siswa['no_hp_orang_tua'], 'alamat' => $siswa['alamat']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['no_hp_orang_tua'] = trim($_POST['no_hp_orang_tua'] ?? '');
    $old['alamat']          = trim($_POST['alamat'] ?? '');
    $password_lama          = $_POST['password_lama'] ?? '';
    $password_baru          = $_POST['password_baru'] ?? '';

    if ($password_baru !== '') {
        if ($password_lama === '') {
            $errors[] = 'Masukkan password lama untuk mengganti password.';
        } else {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$siswa['user_id']]);
            $hash_lama = $stmt->fetchColumn();
            if (!password_verify($password_lama, $hash_lama)) {
                $errors[] = 'Password lama tidak sesuai.';
            } elseif (strlen($password_baru) < 6) {
                $errors[] = 'Password baru minimal 6 karakter.';
            }
        }
    }

    $foto_path = $siswa['foto'];
    $foto_lama = $siswa['foto'];
    if (empty($errors) && !empty($_FILES['foto']['name'])) {
        try {
            $foto_path = upload_file($_FILES['foto'], 'foto', EXT_FOTO, MAX_FOTO);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE siswa SET no_hp_orang_tua = ?, alamat = ?, foto = ? WHERE id = ?')
            ->execute([$old['no_hp_orang_tua'] ?: null, $old['alamat'] ?: null, $foto_path, $siswa_id]);

        if ($password_baru !== '') {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($password_baru, PASSWORD_DEFAULT), $siswa['user_id']]);
        }

        if ($foto_path !== $foto_lama) {
            delete_uploaded_file($foto_lama);
        }

        flash('success', 'Profil berhasil diperbarui.');
        redirect('/siswa/profil.php');
    } elseif ($foto_path !== $foto_lama) {
        delete_uploaded_file($foto_path);
    }
}

$page_title = 'Profil Saya';
require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <?php if (!empty($errors)): ?>
      <div class="border border-danger/30 bg-danger/10 text-danger rounded-xl px-4 py-3 mb-6 text-sm space-y-1">
        <?php foreach ($errors as $err): ?><p><i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <?= csrf_field() ?>

      <div class="flex items-center gap-4">
        <?php if (!empty($siswa['foto'])): ?>
          <img id="preview-foto" src="<?= e(UPLOAD_URL . '/' . $siswa['foto']) ?>" alt="" class="w-16 h-16 rounded-full object-cover">
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
          <label class="block text-sm font-semibold text-ink mb-1.5">Nama Lengkap</label>
          <input type="text" value="<?= e($siswa['nama']) ?>" disabled
                 class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-muted">
          <p class="text-xs text-muted mt-1">Hanya admin yang dapat mengubah nama.</p>
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Tanggal Lahir</label>
          <input type="text" value="<?= format_tanggal($siswa['tanggal_lahir'], false) ?>" disabled
                 class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-muted">
          <p class="text-xs text-muted mt-1">Hanya admin yang dapat mengubah tanggal lahir.</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">No. HP Orang Tua</label>
        <input type="text" name="no_hp_orang_tua" value="<?= e($old['no_hp_orang_tua']) ?>"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Alamat</label>
        <textarea name="alamat" rows="3"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= e($old['alamat']) ?></textarea>
      </div>

      <div class="border-t border-slate-100 pt-5">
        <p class="text-sm font-semibold text-ink mb-3">Ganti Password (opsional)</p>
        <div class="grid sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-semibold text-ink mb-1.5">Password Lama</label>
            <input type="password" name="password_lama"
                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
          </div>
          <div>
            <label class="block text-sm font-semibold text-ink mb-1.5">Password Baru</label>
            <input type="password" name="password_baru" minlength="6"
                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
