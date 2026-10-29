<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/app.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

$_SESSION = [];
session_destroy();

session_start();
flash('success', 'Anda telah keluar dari sistem.');
redirect('/auth/login.php');
