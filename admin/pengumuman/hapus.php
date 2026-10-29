<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/pengumuman/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM pengumuman WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Pengumuman berhasil dihapus.');
} else {
    flash('error', 'Pengumuman tidak ditemukan.');
}

redirect('/admin/pengumuman/index.php');
