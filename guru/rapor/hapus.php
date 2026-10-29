<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/functions.php';
require __DIR__ . '/../../includes/csrf.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/upload.php';

require_role('guru');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/guru/rapor/index.php');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT file_pdf FROM rapor WHERE id = ?');
$stmt->execute([$id]);
$rapor = $stmt->fetch();

if (!$rapor) {
    flash('error', 'Data rapor tidak ditemukan.');
    redirect('/guru/rapor/index.php');
}

$stmt = $pdo->prepare('DELETE FROM rapor WHERE id = ?');
$stmt->execute([$id]);

delete_uploaded_file($rapor['file_pdf']);

flash('success', 'Rapor berhasil dihapus.');
redirect('/guru/rapor/index.php');
