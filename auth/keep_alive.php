<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Update user status to active and update timestamp
    $stmt = $conn->prepare("UPDATE users SET message_status = 'active', updated_at = NOW() WHERE id = :id");
    $stmt->execute(['id' => $userId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
