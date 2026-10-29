<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM kegiatan WHERE id = ?');
$stmt->execute([$id]);
$kegiatan = $stmt->fetch();

if (!$kegiatan) {
    flash('error', 'Kegiatan tidak ditemukan.');
    redirect('/admin/kegiatan/index.php');
}

$errors = [];
$old = [
    'nama_kegiatan' => $kegiatan['nama_kegiatan'], 'deskripsi' => $kegiatan['deskripsi'],
    'tanggal_mulai' => $kegiatan['tanggal_mulai'], 'tanggal_selesai' => $kegiatan['tanggal_selesai'],
    'lokasi' => $kegiatan['lokasi'], 'status' => $kegiatan['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (array_keys($old) as $key) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if ($old['nama_kegiatan'] === '' || $old['tanggal_mulai'] === '') {
        $errors[] = 'Nama kegiatan dan tanggal mulai wajib diisi.';
    }
    if (!in_array($old['status'], ['akan_datang', 'berjalan', 'selesai'], true)) {
        $errors[] = 'Status tidak valid.';
    }

    $foto_path = $kegiatan['foto'];
    $foto_lama = $kegiatan['foto'];
    if (empty($errors) && !empty($_FILES['foto']['name'])) {
        try {
            $foto_path = upload_file($_FILES['foto'], 'kegiatan', EXT_FOTO, MAX_FOTO_KEGIATAN);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE kegiatan SET nama_kegiatan = ?, deskripsi = ?, tanggal_mulai = ?, tanggal_selesai = ?, lokasi = ?, foto = ?, status = ? WHERE id = ?');
        $stmt->execute([
            $old['nama_kegiatan'], $old['deskripsi'] ?: null, $old['tanggal_mulai'],
            $old['tanggal_selesai'] ?: null, $old['lokasi'] ?: null, $foto_path, $old['status'], $id,
        ]);

        if ($foto_path !== $foto_lama) {
            delete_uploaded_file($foto_lama);
        }

        flash('success', 'Kegiatan berhasil diperbarui.');
        redirect('/admin/kegiatan/index.php');
    } elseif ($foto_path !== $foto_lama) {
        delete_uploaded_file($foto_path);
    }
}

$page_title = 'Edit Kegiatan';
require __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= e(BASE_URL) ?>/admin/kegiatan/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Kegiatan
  </a>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <?php if (!empty($errors)): ?>
      <div class="border border-danger/30 bg-danger/10 text-danger rounded-xl px-4 py-3 mb-6 text-sm space-y-1">
        <?php foreach ($errors as $err): ?><p><i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Nama Kegiatan <span class="text-danger">*</span></label>
        <input type="text" name="nama_kegiatan" value="<?= e($old['nama_kegiatan']) ?>" required
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Deskripsi</label>
        <textarea name="deskripsi" rows="3"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= e($old['deskripsi']) ?></textarea>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Tanggal Mulai <span class="text-danger">*</span></label>
          <input type="date" name="tanggal_mulai" value="<?= e($old['tanggal_mulai']) ?>" required
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" value="<?= e($old['tanggal_selesai']) ?>"
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Lokasi</label>
          <input type="text" name="lokasi" value="<?= e($old['lokasi']) ?>"
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Status</label>
          <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            <option value="akan_datang" <?= $old['status'] === 'akan_datang' ? 'selected' : '' ?>>Akan Datang</option>
            <option value="berjalan" <?= $old['status'] === 'berjalan' ? 'selected' : '' ?>>Berjalan</option>
            <option value="selesai" <?= $old['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Foto Kegiatan</label>
        <div class="flex items-center gap-4">
          <?php if (!empty($kegiatan['foto'])): ?>
            <img id="preview-foto" src="<?= e(UPLOAD_URL . '/' . $kegiatan['foto']) ?>" alt="" class="w-20 h-20 rounded-xl object-cover">
          <?php else: ?>
            <img id="preview-foto" src="" alt="" class="w-20 h-20 rounded-xl object-cover hidden">
          <?php endif; ?>
          <div>
            <label class="inline-block cursor-pointer bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl px-4 py-2 text-sm font-semibold text-ink">
              <i class="fa-solid fa-upload mr-1"></i> Ganti Foto
              <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="hidden" data-preview="preview-foto" data-preview-name="nama-foto">
            </label>
            <p id="nama-foto" class="text-xs text-muted mt-1 hidden"></p>
            <p class="text-xs text-muted mt-1">Opsional. JPG, PNG, atau WEBP. Maks 15 MB.</p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/admin/kegiatan/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
