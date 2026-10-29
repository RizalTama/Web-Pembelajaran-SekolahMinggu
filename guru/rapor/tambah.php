<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('guru');

$guru_id = current_guru_id($pdo);

$bulan_sekarang = (int) date('n');
$tahun_sekarang = (int) date('Y');
$tahun_ajaran_berjalan = $bulan_sekarang >= 7 ? $tahun_sekarang : $tahun_sekarang - 1;

$opsi_tahun_ajaran = [];
for ($y = $tahun_ajaran_berjalan + 1; $y >= $tahun_ajaran_berjalan - 3; $y--) {
    $opsi_tahun_ajaran[] = $y . '/' . ($y + 1);
}

$errors = [];
$info_pengganti = null;
$old = ['siswa_id' => 0, 'tahun_ajaran' => $tahun_ajaran_berjalan . '/' . ($tahun_ajaran_berjalan + 1)];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['siswa_id']     = (int) ($_POST['siswa_id'] ?? 0);
    $old['tahun_ajaran'] = trim($_POST['tahun_ajaran'] ?? '');

    $stmt = $pdo->prepare('SELECT siswa.id, siswa.nama FROM siswa JOIN users ON users.id = siswa.user_id WHERE siswa.id = ? AND users.is_active = 1');
    $stmt->execute([$old['siswa_id']]);
    $siswa = $stmt->fetch();

    if (!$siswa) {
        $errors[] = 'Siswa tidak valid atau tidak aktif.';
    }

    if (!preg_match('/^(\d{4})\/(\d{4})$/', $old['tahun_ajaran'], $m) || (int) $m[2] !== (int) $m[1] + 1) {
        $errors[] = 'Tahun ajaran tidak valid.';
    }

    $rapor_ada = null;
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT rapor.*, guru.nama AS nama_guru FROM rapor JOIN guru ON guru.id = rapor.guru_id WHERE siswa_id = ? AND tahun_ajaran = ?');
        $stmt->execute([$old['siswa_id'], $old['tahun_ajaran']]);
        $rapor_ada = $stmt->fetch();
    }

    $file_path = null;
    if (empty($errors)) {
        if (empty($_FILES['file_pdf']['name'])) {
            $errors[] = 'File PDF rapor wajib diupload.';
        } else {
            try {
                $file_path = upload_file($_FILES['file_pdf'], 'rapor', EXT_PDF, MAX_RAPOR_PDF);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        if ($rapor_ada) {
            $stmt = $pdo->prepare('UPDATE rapor SET guru_id = ?, file_pdf = ?, uploaded_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$guru_id, $file_path, $rapor_ada['id']]);
            delete_uploaded_file($rapor_ada['file_pdf']);
            flash('success', 'Rapor ' . $siswa['nama'] . ' tahun ajaran ' . $old['tahun_ajaran'] . ' berhasil diganti.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO rapor (siswa_id, guru_id, tahun_ajaran, file_pdf) VALUES (?, ?, ?, ?)');
            $stmt->execute([$old['siswa_id'], $guru_id, $old['tahun_ajaran'], $file_path]);
            flash('success', 'Rapor ' . $siswa['nama'] . ' berhasil diupload.');
        }

        redirect('/guru/rapor/index.php');
    } elseif ($file_path) {
        delete_uploaded_file($file_path);
    }

    if ($rapor_ada) {
        $info_pengganti = $rapor_ada;
    }
}

$semua_siswa = get_siswa_aktif($pdo);

$page_title = 'Tambah Rapor';
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

    <?php if ($info_pengganti): ?>
      <div class="border border-accent/40 bg-accent-light text-ink rounded-xl px-4 py-3 mb-6 text-sm">
        <i class="fa-solid fa-triangle-exclamation mr-1 text-accent"></i>
        Rapor siswa ini untuk tahun ajaran tersebut sudah ada (diupload oleh <?= e($info_pengganti['nama_guru']) ?> pada <?= format_tanggal($info_pengganti['uploaded_at']) ?>). Mengupload file baru akan menggantikannya.
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Siswa <span class="text-danger">*</span></label>
        <div class="relative">
          <select name="siswa_id" required class="w-full appearance-none rounded-xl border border-slate-300 pl-4 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
            <option value="">Pilih siswa...</option>
            <?php foreach ($semua_siswa as $s): ?>
              <option value="<?= (int) $s['id'] ?>" <?= $old['siswa_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nama']) ?></option>
            <?php endforeach; ?>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-muted text-xs pointer-events-none"></i>
        </div>
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
        <label class="block text-sm font-semibold text-ink mb-1.5">File PDF Rapor <span class="text-danger">*</span></label>
        <label class="inline-block cursor-pointer bg-surface hover:bg-slate-100 border border-slate-300 rounded-xl px-4 py-2 text-sm font-semibold text-ink">
          <i class="fa-solid fa-upload mr-1"></i> Pilih File PDF
          <input type="file" name="file_pdf" accept=".pdf" data-preview-name="nama-file" class="hidden">
        </label>
        <p id="nama-file" class="text-xs text-muted mt-1 hidden"></p>
        <p class="text-xs text-muted mt-1">PDF maks 50 MB.</p>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/guru/rapor/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Upload Rapor</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
