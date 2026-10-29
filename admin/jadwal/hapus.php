<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/jadwal/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM jadwal WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Jadwal berhasil dihapus.');
} else {
    flash('error', 'Jadwal tidak ditemukan.');
}

redirect('/admin/jadwal/index.php');
