<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/guru/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT guru.foto, guru.user_id FROM guru WHERE guru.id = ?');
$stmt->execute([$id]);
$guru = $stmt->fetch();

if (!$guru) {
    flash('error', 'Data guru tidak ditemukan.');
    redirect('/admin/guru/index.php');
}

// Kumpulkan file materi & rapor milik guru ini sebelum dihapus (cascade oleh FK)
$stmt = $pdo->prepare('SELECT file_path FROM materi WHERE guru_id = ? AND file_path IS NOT NULL');
$stmt->execute([$id]);
$file_materi = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT file_pdf FROM rapor WHERE guru_id = ?');
$stmt->execute([$id]);
$file_rapor = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$guru['user_id']]);

delete_uploaded_file($guru['foto']);
foreach ($file_materi as $f) {
    delete_uploaded_file($f);
}
foreach ($file_rapor as $f) {
    delete_uploaded_file($f);
}

flash('success', 'Data guru berhasil dihapus.');
redirect('/admin/guru/index.php');
