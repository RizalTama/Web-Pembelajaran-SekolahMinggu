<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/siswa/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT foto, user_id FROM siswa WHERE id = ?');
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    flash('error', 'Data siswa tidak ditemukan.');
    redirect('/admin/siswa/index.php');
}

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$siswa['user_id']]);

delete_uploaded_file($siswa['foto']);

flash('success', 'Data siswa berhasil dihapus.');
redirect('/admin/siswa/index.php');
