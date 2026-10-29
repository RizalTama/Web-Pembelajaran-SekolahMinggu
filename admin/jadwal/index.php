<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

$halaman = max(1, (int) ($_GET['halaman'] ?? 1));
$per_halaman = 10;

$total = (int) $pdo->query('SELECT COUNT(*) FROM jadwal')->fetchColumn();
$info = paginate_info($total, $halaman, $per_halaman);
$offset = paginate_offset($info['halaman'], $per_halaman);

$stmt = $pdo->prepare(
    "SELECT jadwal.*, guru.nama AS nama_guru
     FROM jadwal JOIN guru ON guru.id = jadwal.guru_id
     ORDER BY jadwal.tanggal DESC, jadwal.jam_mulai DESC
     LIMIT {$per_halaman} OFFSET {$offset}"
);
$stmt->execute();
$daftar_jadwal = $stmt->fetchAll();

$page_title = 'Jadwal Kegiatan';
require __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <p class="text-sm text-muted">Kelola jadwal kegiatan sekolah minggu.</p>
  <a href="<?= e(BASE_URL) ?>/admin/jadwal/tambah.php"
     class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold transition-colors shrink-0">
    <i class="fa-solid fa-plus"></i> Tambah Jadwal
  </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
  <?php if (empty($daftar_jadwal)): ?>
    <div class="flex flex-col items-center justify-center text-center py-16 px-6">
      <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        <i class="fa-solid fa-calendar-days text-2xl"></i>
      </div>
      <p class="text-ink font-semibold mb-1">Belum ada jadwal</p>
      <p class="text-sm text-muted mb-4">Tambahkan jadwal kegiatan pertama sekolah minggu.</p>
      <a href="<?= e(BASE_URL) ?>/admin/jadwal/tambah.php" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white rounded-xl px-4 py-2.5 font-semibold text-sm">
        <i class="fa-solid fa-plus"></i> Tambah Jadwal
      </a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold text-muted uppercase">
          <tr>
            <th class="px-5 py-3">Judul</th>
            <th class="px-5 py-3">Guru</th>
            <th class="px-5 py-3">Tanggal &amp; Jam</th>
            <th class="px-5 py-3">Lokasi</th>
            <th class="px-5 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($daftar_jadwal as $i => $j): ?>
            <tr class="<?= $i % 2 === 1 ? 'bg-slate-50/50' : '' ?> hover:bg-primary-light/30">
              <td class="px-5 py-3 font-semibold text-ink"><?= e($j['judul']) ?></td>
              <td class="px-5 py-3 text-muted"><?= e($j['nama_guru']) ?></td>
              <td class="px-5 py-3 text-muted">
                <?= format_tanggal($j['tanggal'], false) ?><br>
                <span class="text-xs"><?= format_jam($j['jam_mulai']) ?> - <?= format_jam($j['jam_selesai']) ?></span>
              </td>
              <td class="px-5 py-3 text-muted"><?= e($j['lokasi'] ?: '-') ?></td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <a href="<?= e(BASE_URL) ?>/admin/jadwal/edit.php?id=<?= (int) $j['id'] ?>"
                     class="w-8 h-8 flex items-center justify-center rounded-lg text-primary hover:bg-primary-light" title="Edit">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <form action="<?= e(BASE_URL) ?>/admin/jadwal/hapus.php" method="POST" data-confirm="Jadwal &quot;<?= e($j['judul']) ?>&quot; beserta data absensinya akan dihapus permanen." data-confirm-title="Hapus Jadwal">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $j['id'] ?>">
                    <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-danger hover:bg-danger/10" title="Hapus">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
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
            <a href="?halaman=<?= $info['halaman'] - 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-surface">Sebelumnya</a>
          <?php endif; ?>
          <?php if ($info['halaman'] < $info['total_halaman']): ?>
            <a href="?halaman=<?= $info['halaman'] + 1 ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-surface">Berikutnya</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
