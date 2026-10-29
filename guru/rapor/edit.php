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

$stmt = $pdo->prepare('SELECT rapor.*, siswa.nama AS nama_siswa FROM rapor JOIN siswa ON siswa.id = rapor.siswa_id WHERE rapor.id = ?');
$stmt->execute([$id]);
$rapor = $stmt->fetch();

if (!$rapor) {
    flash('error', 'Data rapor tidak ditemukan.');
    redirect('/guru/rapor/index.php');
}

$bulan_sekarang = (int) date('n');
$tahun_sekarang = (int) date('Y');
$tahun_ajaran_berjalan = $bulan_sekarang >= 7 ? $tahun_sekarang : $tahun_sekarang - 1;

$opsi_tahun_ajaran = [];
for ($y = $tahun_ajaran_berjalan + 1; $y >= $tahun_ajaran_berjalan - 3; $y--) {
    $opsi_tahun_ajaran[] = $y . '/' . ($y + 1);
}
if (!in_array($rapor['tahun_ajaran'], $opsi_tahun_ajaran, true)) {
    $opsi_tahun_ajaran[] = $rapor['tahun_ajaran'];
}

$errors = [];
$old = ['tahun_ajaran' => $rapor['tahun_ajaran']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['tahun_ajaran'] = trim($_POST['tahun_ajaran'] ?? '');

    if (!preg_match('/^(\d{4})\/(\d{4})$/', $old['tahun_ajaran'], $m) || (int) $m[2] !== (int) $m[1] + 1) {
        $errors[] = 'Tahun ajaran tidak valid.';
    }

    if (empty($errors) && $old['tahun_ajaran'] !== $rapor['tahun_ajaran']) {
        $stmt = $pdo->prepare('SELECT id FROM rapor WHERE siswa_id = ? AND tahun_ajaran = ? AND id != ?');
        $stmt->execute([$rapor['siswa_id'], $old['tahun_ajaran'], $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Siswa ini sudah punya rapor untuk tahun ajaran tersebut.';
        }
    }

    $file_path = $rapor['file_pdf'];
    $file_lama = $rapor['file_pdf'];

    if (empty($errors) && !empty($_FILES['file_pdf']['name'])) {
        try {
            $file_path = upload_file($_FILES['file_pdf'], 'rapor', EXT_PDF, MAX_RAPOR_PDF);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE rapor SET guru_id = ?, tahun_ajaran = ?, file_pdf = ? WHERE id = ?');
        $stmt->execute([$guru_id, $old['tahun_ajaran'], $file_path, $id]);

        if ($file_path !== $file_lama) {
            delete_uploaded_file($file_lama);
        }

        flash('success', 'Rapor berhasil diperbarui.');
        redirect('/guru/rapor/index.php');
    } elseif ($file_path !== $file_lama) {
        delete_uploaded_file($file_path);
    }
}

$page_title = 'Edit Rapor';
require __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= e(BASE_URL) ?>/guru/rapor/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Rapor Siswa
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
        <label class="block text-sm font-semibold text-ink mb-1.5">Siswa</label>
        <input type="text" value="<?= e($rapor['nama_siswa']) ?>" disabled
               class="w-full rounded-xl border border-slate-200 bg-surface px-4 py-2.5 text-sm text-muted">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Tahun Ajaran</label>
        <div class="relative">
          <select name="tahun_ajaran" class="w-full appearance-none rounded-xl border border-slate-300 pl-4 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            <?php foreach ($opsi_tahun_ajaran as $ta): ?>
              <option value="<?= e($ta) ?>" <?= $old['tahun_ajaran'] === $ta ? 'selected' : '' ?>><?= e($ta) ?></option>
            <?php endforeach; ?>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-muted text-xs pointer-events-none"></i>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">File PDF Rapor (kosongkan jika tidak diganti)</label>
        <label class="inline-block cursor-pointer bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl px-4 py-2 text-sm font-semibold text-ink">
          <i class="fa-solid fa-upload mr-1"></i> Ganti File
          <input type="file" name="file_pdf" accept=".pdf" data-preview-name="nama-file" class="hidden">
        </label>
        <p id="nama-file" class="text-xs text-muted mt-1 hidden"></p>
        <p class="text-xs text-muted mt-1">PDF maks 50 MB.</p>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/guru/rapor/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
