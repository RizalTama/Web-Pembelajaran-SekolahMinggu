<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

require_role('guru');

$guru_id = current_guru_id($pdo);

$stmt = $pdo->prepare('SELECT * FROM jadwal WHERE guru_id = ? AND tanggal >= CURDATE() ORDER BY tanggal ASC, jam_mulai ASC');
$stmt->execute([$guru_id]);
$jadwal_mendatang = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM jadwal WHERE guru_id = ? AND tanggal < CURDATE() ORDER BY tanggal DESC, jam_mulai DESC');
$stmt->execute([$guru_id]);
$jadwal_lewat = $stmt->fetchAll();

function render_kartu_jadwal(array $j): string
{
    ob_start();
    ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-start gap-4">
      <div class="w-12 h-12 rounded-xl bg-primary-light text-primary flex flex-col items-center justify-center shrink-0 text-xs font-bold leading-tight">
        <span><?= date('d', strtotime($j['tanggal'])) ?></span>
        <span class="uppercase"><?= date('M', strtotime($j['tanggal'])) ?></span>
      </div>
      <div class="min-w-0 flex-1">
        <p class="font-bold text-ink"><?= e($j['judul']) ?></p>
        <p class="text-sm text-muted"><?= format_tanggal($j['tanggal']) ?> &middot; <?= format_jam($j['jam_mulai']) ?> - <?= format_jam($j['jam_selesai']) ?></p>
        <?php if ($j['lokasi']): ?><p class="text-sm text-muted"><i class="fa-solid fa-location-dot mr-1"></i><?= e($j['lokasi']) ?></p><?php endif; ?>
        <?php if ($j['keterangan']): ?><p class="text-sm text-muted mt-1"><?= e($j['keterangan']) ?></p><?php endif; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

$page_title = 'Jadwal Mengajar';
require __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
  <div>
    <h3 class="font-bold text-ink mb-4">Akan Datang</h3>
    <?php if (empty($jadwal_mendatang)): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-12 px-6">
        <div class="w-14 h-14 rounded-full bg-primary-light text-primary flex items-center justify-center mb-3">
          <i class="fa-solid fa-calendar-days text-xl"></i>
        </div>
        <p class="text-ink font-semibold mb-1">Belum ada jadwal mendatang</p>
        <p class="text-sm text-muted">Jadwal mengajar baru akan tampil di sini.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($jadwal_mendatang as $j): ?><?= render_kartu_jadwal($j) ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <h3 class="font-bold text-ink mb-4">Sudah Lewat</h3>
    <?php if (empty($jadwal_lewat)): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center py-12 px-6">
        <p class="text-sm text-muted">Belum ada riwayat jadwal.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3 opacity-75">
        <?php foreach ($jadwal_lewat as $j): ?><?= render_kartu_jadwal($j) ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
