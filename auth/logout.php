<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

// Deactivate user before logout
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET message_status = 'inactive', updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Log error but continue with logout
        error_log("Error deactivating user: " . $e->getMessage());
    }
}

// logout using helper and redirect to auth login
logout_user();
header('Location: login.php');
exit();