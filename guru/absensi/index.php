<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('guru');

$guru_id = current_guru_id($pdo);
$jumlah_siswa_aktif = count(get_siswa_aktif($pdo));

$stmt = $pdo->prepare(
    "SELECT jadwal.*, (SELECT COUNT(*) FROM absensi WHERE absensi.jadwal_id = jadwal.id) AS jumlah_terisi
     FROM jadwal WHERE guru_id = ? ORDER BY tanggal DESC, jam_mulai DESC"
);
$stmt->execute([$guru_id]);
$daftar_jadwal = $stmt->fetchAll();

$page_title = 'Absensi';
require __DIR__ . '/../../includes/header.php';
?>

<p class="text-sm text-muted mb-6">Pilih jadwal untuk mengisi atau mengubah absensi siswa.</p>

<?php if (empty($daftar_jadwal)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-16 px-6">
    <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
      <i class="fa-solid fa-clipboard-check text-2xl"></i>
    </div>
    <p class="text-ink font-semibold mb-1">Belum ada jadwal mengajar</p>
    <p class="text-sm text-muted">Absensi bisa diisi setelah admin membuat jadwal untuk Anda.</p>
  </div>
<?php else: ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Jadwal</th>
            <th class="px-5 py-3">Tanggal</th>
            <th class="px-5 py-3">Status Absensi</th>
            <th class="px-5 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($daftar_jadwal as $i => $j): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
              <td class="px-5 py-3 font-semibold text-ink"><?= e($j['judul']) ?></td>
              <td class="px-5 py-3 text-muted"><?= format_tanggal($j['tanggal'], false) ?></td>
              <td class="px-5 py-3">
                <?php if ((int) $j['jumlah_terisi'] === 0): ?>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-danger/10 text-danger">Belum diisi</span>
                <?php elseif ((int) $j['jumlah_terisi'] < $jumlah_siswa_aktif): ?>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-accent-light text-accent">Sebagian (<?= (int) $j['jumlah_terisi'] ?>/<?= $jumlah_siswa_aktif ?>)</span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-success/10 text-success">Lengkap</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-right">
                <a href="<?= e(BASE_URL) ?>/guru/absensi/input.php?jadwal_id=<?= (int) $j['id'] ?>"
                   class="inline-flex items-center gap-2 text-primary hover:underline font-semibold text-sm">
                  <i class="fa-solid fa-pen-to-square"></i> <?= (int) $j['jumlah_terisi'] === 0 ? 'Input' : 'Edit' ?> Absensi
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
