<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM jadwal WHERE id = ?');
$stmt->execute([$id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    flash('error', 'Jadwal tidak ditemukan.');
    redirect('/admin/jadwal/index.php');
}

$daftar_guru = $pdo->query('SELECT id, nama FROM guru ORDER BY nama ASC')->fetchAll();

$errors = [];
$old = [
    'judul' => $jadwal['judul'], 'guru_id' => $jadwal['guru_id'], 'tanggal' => $jadwal['tanggal'],
    'jam_mulai' => substr($jadwal['jam_mulai'], 0, 5), 'jam_selesai' => substr($jadwal['jam_selesai'], 0, 5),
    'lokasi' => $jadwal['lokasi'], 'keterangan' => $jadwal['keterangan'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (array_keys($old) as $key) {
        $old[$key] = trim($_POST[$key] ?? '');
    }

    if ($old['judul'] === '' || $old['guru_id'] === '' || $old['tanggal'] === '' || $old['jam_mulai'] === '' || $old['jam_selesai'] === '') {
        $errors[] = 'Judul, guru, tanggal, jam mulai, dan jam selesai wajib diisi.';
    } elseif ($old['jam_selesai'] <= $old['jam_mulai']) {
        $errors[] = 'Jam selesai harus setelah jam mulai.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE jadwal SET judul = ?, guru_id = ?, tanggal = ?, jam_mulai = ?, jam_selesai = ?, lokasi = ?, keterangan = ? WHERE id = ?');
        $stmt->execute([
            $old['judul'], (int) $old['guru_id'], $old['tanggal'], $old['jam_mulai'], $old['jam_selesai'],
            $old['lokasi'] ?: null, $old['keterangan'] ?: null, $id,
        ]);

        flash('success', 'Jadwal berhasil diperbarui.');
        redirect('/admin/jadwal/index.php');
    }
}

$page_title = 'Edit Jadwal';
require __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= e(BASE_URL) ?>/admin/jadwal/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Jadwal
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
        <label class="block text-sm font-semibold text-ink mb-1.5">Guru Pengajar <span class="text-danger">*</span></label>
        <select name="guru_id" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
          <option value="">Pilih guru</option>
          <?php foreach ($daftar_guru as $g): ?>
            <option value="<?= (int) $g['id'] ?>" <?= $old['guru_id'] == $g['id'] ? 'selected' : '' ?>><?= e($g['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid sm:grid-cols-3 gap-5">
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Tanggal <span class="text-danger">*</span></label>
          <input type="date" name="tanggal" value="<?= e($old['tanggal']) ?>" required
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Jam Mulai <span class="text-danger">*</span></label>
          <input type="time" name="jam_mulai" value="<?= e($old['jam_mulai']) ?>" required
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-ink mb-1.5">Jam Selesai <span class="text-danger">*</span></label>
          <input type="time" name="jam_selesai" value="<?= e($old['jam_selesai']) ?>" required
                 class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Lokasi</label>
        <input type="text" name="lokasi" value="<?= e($old['lokasi']) ?>"
               class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
      </div>

      <div>
        <label class="block text-sm font-semibold text-ink mb-1.5">Keterangan</label>
        <textarea name="keterangan" rows="3"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"><?= e($old['keterangan']) ?></textarea>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(BASE_URL) ?>/admin/jadwal/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
