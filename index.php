<?php
require_once __DIR__ . '/auth/auth.php';

// If logged in, send users to their role dashboard; otherwise to login
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit();
    }
    if ($role === 'client') {
        header('Location: ' . BASE_URL . 'client/dashboard.php');
        exit();
    }
}

header('Location: ' . BASE_URL . 'auth/login.php');
exit();
?>