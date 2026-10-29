<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('guru');

$guru_id = current_guru_id($pdo);
$jadwal_id = (int) ($_GET['jadwal_id'] ?? $_POST['jadwal_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM jadwal WHERE id = ? AND guru_id = ?');
$stmt->execute([$jadwal_id, $guru_id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    flash('error', 'Jadwal tidak ditemukan atau bukan milik Anda.');
    redirect('/guru/absensi/index.php');
}

$daftar_siswa = get_siswa_aktif($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $status_valid = ['hadir', 'izin', 'sakit', 'alpa'];
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO absensi (jadwal_id, siswa_id, status, keterangan) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)'
        );

        foreach ($daftar_siswa as $s) {
            $siswa_id = (int) $s['id'];
            $status = $_POST['status'][$siswa_id] ?? 'hadir';
            if (!in_array($status, $status_valid, true)) {
                $status = 'hadir';
            }
            $keterangan = trim($_POST['keterangan'][$siswa_id] ?? '');
            $stmt->execute([$jadwal_id, $siswa_id, $status, $keterangan ?: null]);
        }

        $pdo->commit();
        flash('success', 'Absensi berhasil disimpan.');
        redirect('/guru/absensi/index.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Gagal menyimpan absensi. Silakan coba lagi.');
    }
}

$stmt = $pdo->prepare('SELECT siswa_id, status, keterangan FROM absensi WHERE jadwal_id = ?');
$stmt->execute([$jadwal_id]);
$absensi_ada = [];
foreach ($stmt->fetchAll() as $row) {
    $absensi_ada[$row['siswa_id']] = $row;
}

$page_title = 'Input Absensi';
require __DIR__ . '/../../includes/header.php';
?>

<a href="<?= e(BASE_URL) ?>/guru/absensi/index.php" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink mb-4">
  <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Absensi
</a>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
  <p class="font-bold text-ink"><?= e($jadwal['judul']) ?></p>
  <p class="text-sm text-muted"><?= format_tanggal($jadwal['tanggal']) ?> &middot; <?= format_jam($jadwal['jam_mulai']) ?> - <?= format_jam($jadwal['jam_selesai']) ?></p>
</div>

<?php if (empty($daftar_siswa)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-16 px-6">
    <p class="text-ink font-semibold mb-1">Belum ada siswa aktif</p>
    <p class="text-sm text-muted">Absensi bisa diisi setelah ada data siswa aktif.</p>
  </div>
<?php else: ?>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="jadwal_id" value="<?= (int) $jadwal_id ?>">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
            <tr>
              <th class="px-5 py-3">Siswa</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">Keterangan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($daftar_siswa as $i => $s): ?>
              <?php $status_now = $absensi_ada[$s['id']]['status'] ?? 'hadir'; ?>
              <?php $ket_now = $absensi_ada[$s['id']]['keterangan'] ?? ''; ?>
              <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
                <td class="px-5 py-3 font-semibold text-ink"><?= e($s['nama']) ?></td>
                <td class="px-5 py-3">
                  <select name="status[<?= (int) $s['id'] ?>]" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                    <option value="hadir" <?= $status_now === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                    <option value="izin" <?= $status_now === 'izin' ? 'selected' : '' ?>>Izin</option>
                    <option value="sakit" <?= $status_now === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                    <option value="alpa" <?= $status_now === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                  </select>
                </td>
                <td class="px-5 py-3">
                  <input type="text" name="keterangan[<?= (int) $s['id'] ?>]" value="<?= e($ket_now) ?>" placeholder="Opsional"
                         class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <a href="<?= e(BASE_URL) ?>/guru/absensi/index.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-muted hover:bg-surface">Batal</a>
      <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-xl px-5 py-2.5 font-semibold text-sm">Simpan Absensi</button>
    </div>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
