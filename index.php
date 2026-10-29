<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/app.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(dashboard_path($_SESSION['role']));
}

redirect('/auth/login.php');
