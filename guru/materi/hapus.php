<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('guru');

$guru_id = current_guru_id($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/guru/materi/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT file_path FROM materi WHERE id = ? AND guru_id = ?');
$stmt->execute([$id, $guru_id]);
$materi = $stmt->fetch();

if (!$materi) {
    flash('error', 'Materi tidak ditemukan atau bukan milik Anda.');
    redirect('/guru/materi/index.php');
}

$stmt = $pdo->prepare('DELETE FROM materi WHERE id = ? AND guru_id = ?');
$stmt->execute([$id, $guru_id]);

delete_uploaded_file($materi['file_path']);

flash('success', 'Materi berhasil dihapus.');
redirect('/guru/materi/index.php');
