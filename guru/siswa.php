<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('guru');

$q = trim($_GET['q'] ?? '');
$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 10;

$semua_siswa = get_siswa_aktif($pdo);
if ($q !== '') {
    $semua_siswa = array_values(array_filter($semua_siswa, fn ($s) => stripos($s['nama'], $q) !== false));
}

$total_data = count($semua_siswa);
$info = paginate_info($total_data, $halaman, $per_halaman);
$offset = paginate_offset($info['halaman'], $per_halaman);
$daftar_siswa = array_slice($semua_siswa, $offset, $per_halaman);

$page_title = 'Data Siswa';
require __DIR__ . '/../includes/header.php';
?>

<form method="GET" class="relative w-full sm:max-w-xs mb-6">
  <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm"></i>
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama siswa..."
         class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
</form>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
  <?php if (empty($daftar_siswa)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
      <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        <i class="fa-solid fa-child text-2xl"></i>
      </div>
      <p class="text-ink font-semibold mb-1">Belum ada data siswa</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Siswa</th>
            <th class="px-5 py-3">Tanggal Lahir</th>
            <th class="px-5 py-3">Orang Tua</th>
            <th class="px-5 py-3">Alamat</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($daftar_siswa as $i => $s): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?>">
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <?php if (!empty($s['foto'])): ?>
                    <img src="<?= e(UPLOAD_URL . '/' . $s['foto']) ?>" alt="" class="w-9 h-9 rounded-full object-cover shrink-0">
                  <?php else: ?>
                    <div class="w-9 h-9 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-sm shrink-0">
                      <?= e(mb_strtoupper(mb_substr($s['nama'], 0, 1))) ?>
                    </div>
                  <?php endif; ?>
                  <div class="min-w-0">
                    <p class="font-semibold text-ink truncate"><?= e($s['nama']) ?></p>
                    <p class="text-xs text-muted"><?= $s['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-3 text-muted"><?= format_tanggal($s['tanggal_lahir'], false) ?></td>
              <td class="px-5 py-3 text-muted">
                <?= e($s['nama_orang_tua'] ?: '-') ?>
                <?php if ($s['no_hp_orang_tua']): ?><br><span class="text-xs"><?= e($s['no_hp_orang_tua']) ?></span><?php endif; ?>
              </td>
              <td class="px-5 py-3 text-muted"><?= e($s['alamat'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($info['total_halaman'] > 1): ?>
      <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100 text-sm">
        <p class="text-muted">Halaman <?= $info['halaman'] ?> dari <?= $info['total_halaman'] ?> (<?= $info['total_data'] ?> data)</p>
        <div class="flex gap-2">
          <?php if ($info['halaman'] > 1): ?>
            <a href="?q=<?= urlencode($q) ?>&halaman=<?= $info['halaman'] - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-surface">Sebelumnya</a>
          <?php endif; ?>
          <?php if ($info['halaman'] < $info['total_halaman']): ?>
            <a href="?q=<?= urlencode($q) ?>&halaman=<?= $info['halaman'] + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-surface">Berikutnya</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
