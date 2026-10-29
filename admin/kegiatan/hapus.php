<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/kegiatan/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT foto FROM kegiatan WHERE id = ?');
$stmt->execute([$id]);
$kegiatan = $stmt->fetch();

if (!$kegiatan) {
    flash('error', 'Kegiatan tidak ditemukan.');
    redirect('/admin/kegiatan/index.php');
}

$stmt = $pdo->prepare('DELETE FROM kegiatan WHERE id = ?');
$stmt->execute([$id]);

delete_uploaded_file($kegiatan['foto']);

flash('success', 'Kegiatan berhasil dihapus.');
redirect('/admin/kegiatan/index.php');
