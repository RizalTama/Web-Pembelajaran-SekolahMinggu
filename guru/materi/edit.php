<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('guru');

$guru_id = current_guru_id($pdo);
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM materi WHERE id = ? AND guru_id = ?');
$stmt->execute([$id, $guru_id]);
$materi = $stmt->fetch();

if (!$materi) {
    flash('error', 'Materi tidak ditemukan atau bukan milik Anda.');
    redirect('/guru/materi/index.php');
}

$errors = [];
$old = [
    'judul' => $materi['judul'], 'deskripsi' => $materi['deskripsi'],
    'tipe' => $materi['tipe'], 'link_url' => $materi['link_url'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['judul']      = trim($_POST['judul'] ?? '');
    $old['deskripsi']  = trim($_POST['deskripsi'] ?? '');
    $old['tipe']       = $_POST['tipe'] ?? 'gambar';
    $old['link_url']   = trim($_POST['link_url'] ?? '');

    if ($old['judul'] === '') {
        $errors[] = 'Judul materi wajib diisi.';
    }
    if (!in_array($old['tipe'], ['pdf', 'gambar', 'link'], true)) {
        $errors[] = 'Tipe materi tidak valid.';
    }

    $file_path = $materi['file_path'];
    $file_lama = $materi['file_path'];
    $tipe_berubah = $old['tipe'] !== $materi['tipe'];

    if (empty($errors)) {
        if ($old['tipe'] === 'link') {
            if ($old['link_url'] === '' || !filter_var($old['link_url'], FILTER_VALIDATE_URL)) {
                $errors[] = 'URL link tidak valid.';
            }
            $file_path = null;
        } else {
            $config_map = [
                'pdf'    => ['folder' => 'materi', 'ext' => EXT_PDF, 'max' => MAX_MATERI_PDF],
                'gambar' => ['folder' => 'materi', 'ext' => EXT_FOTO, 'max' => MAX_MATERI_GAMBAR],
            ];
            $cfg = $config_map[$old['tipe']];

            if (!empty($_FILES['file']['name'])) {
                try {
                    $file_path = upload_file($_FILES['file'], $cfg['folder'], $cfg['ext'], $cfg['max']);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            } elseif ($tipe_berubah) {
                $errors[] = 'File materi wajib diupload karena tipe materi diganti.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE materi SET judul = ?, deskripsi = ?, tipe = ?, file_path = ?, link_url = ? WHERE id = ?'
        );
        $stmt->execute([
            $old['judul'], $old['deskripsi'] ?: null, $old['tipe'], $file_path,
            $old['tipe'] === 'link' ? $old['link_url'] : null, $id,
        ]);

        if ($file_path !== $file_lama) {
            delete_uploaded_file($file_lama);
        }

        flash('success', 'Materi berhasil diperbarui.');
        redirect('/guru/materi/index.php');
    } elseif ($file_path !== $file_lama) {
        delete_uploaded_file($file_path);
    }
}

$page_title = 'Edit Materi';
require __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= e(BASE_URL) ?>/guru/materi/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Materi
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
        <label class="block text-sm font-semibold text-ink mb-1.5">Judul <span class="text-danger">*</span></label>
        <input type="text" name="judul" value="<?= e($old['judul']) ?>" required
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Deskripsi</label>
        <textarea name="deskripsi" rows="3"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= e($old['deskripsi']) ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Tipe Materi</label>
        <div class="relative">
          <select name="tipe" id="tipe-materi" class="w-full appearance-none rounded-xl border border-slate-300 pl-4 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            <option value="gambar" <?= $old['tipe'] === 'gambar' ? 'selected' : '' ?>>Gambar</option>
            <option value="pdf" <?= $old['tipe'] === 'pdf' ? 'selected' : '' ?>>PDF</option>
            <option value="link" <?= $old['tipe'] === 'link' ? 'selected' : '' ?>>Link</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-muted text-xs pointer-events-none"></i>
        </div>
      </div>

      <div id="field-file">
        <label class="block text-sm font-semibold text-ink mb-1.5">File <?= $materi['file_path'] ? '(kosongkan jika tidak diganti)' : '' ?></label>
        <label class="inline-block cursor-pointer bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl px-4 py-2 text-sm font-semibold text-ink">
          <i class="fa-solid fa-upload mr-1"></i> <?= $materi['file_path'] ? 'Ganti File' : 'Pilih File' ?>
          <input type="file" name="file" id="input-file" data-preview-name="nama-file" class="hidden">
        </label>
        <p id="nama-file" class="text-xs text-muted mt-1 hidden"></p>
        <p id="keterangan-file" class="text-xs text-muted mt-1">Gambar: JPG/PNG/WEBP maks 15 MB.</p>
      </div>

      <div id="field-link" class="hidden">
        <label class="block text-sm font-semibold text-ink mb-1.5">URL Link</label>
        <input type="url" name="link_url" value="<?= e($old['link_url']) ?>" placeholder="https://..."
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/guru/materi/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var select = document.getElementById('tipe-materi');
  var fieldFile = document.getElementById('field-file');
  var fieldLink = document.getElementById('field-link');
  var inputFile = document.getElementById('input-file');
  var ketFile = document.getElementById('keterangan-file');

  var accept = { gambar: '.jpg,.jpeg,.png,.webp', pdf: '.pdf' };
  var ket = { gambar: 'Gambar: JPG/PNG/WEBP maks 15 MB.', pdf: 'PDF maks 50 MB.' };

  function update() {
    var v = select.value;
    if (v === 'link') {
      fieldFile.classList.add('hidden');
      fieldLink.classList.remove('hidden');
    } else {
      fieldFile.classList.remove('hidden');
      fieldLink.classList.add('hidden');
      inputFile.accept = accept[v] || '';
      ketFile.textContent = ket[v] || '';
    }
  }

  select.addEventListener('change', update);
  update();
})();
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
