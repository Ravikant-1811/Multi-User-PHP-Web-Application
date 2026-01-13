<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

try {
    switch ($action) {
        case 'get_stats':
            // Total users count
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Active/Inactive/Pending users
            $stmt = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
            $userStats = ['active' => 0, 'inactive' => 0, 'pending' => 0];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $userStats[$row['status']] = $row['count'];
            }

            // Total clients
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
            $totalClients = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Total messages
            $stmt = $conn->query("SELECT COUNT(*) as count FROM messages");
            $totalMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Unread messages for current admin
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = :id AND is_read = 0");
            $stmt->execute(['id' => $userId]);
            $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Today's new users
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()");
            $newUsersToday = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Today's messages
            $stmt = $conn->query("SELECT COUNT(*) as count FROM messages WHERE DATE(created_at) = CURDATE()");
            $messagesToday = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'totalUsers' => $totalUsers,
                    'userStats' => $userStats,
                    'totalClients' => $totalClients,
                    'totalMessages' => $totalMessages,
                    'unreadMessages' => $unreadMessages,
                    'newUsersToday' => $newUsersToday,
                    'messagesToday' => $messagesToday
                ]
            ]);
            break;

        case 'get_activity':
            // Recent user activity (last 10)
            $stmt = $conn->query("
                SELECT id, name, email, role, status, created_at, updated_at
                FROM users
                ORDER BY updated_at DESC
                LIMIT 10
            ");
            $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $recentActivity
            ]);
            break;

        case 'get_notifications':
            // Unread messages per client
            $stmt = $conn->query("
                SELECT u.id, u.name, COUNT(m.id) as unread_count
                FROM users u
                JOIN messages m ON m.sender_id = u.id AND m.receiver_id = $userId AND m.is_read = 0
                WHERE u.role = 'client'
                GROUP BY u.id, u.name
                ORDER BY unread_count DESC
                LIMIT 5
            ");
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
            break;

        case 'get_recent_messages':
            // Recent chat messages (last 10)
            $stmt = $conn->query("
                SELECT m.id, m.message, m.created_at, m.is_read,
                       u1.name as sender_name, u1.id as sender_id,
                       u2.name as receiver_name, u2.id as receiver_id
                FROM messages m
                JOIN users u1 ON m.sender_id = u1.id
                JOIN users u2 ON m.receiver_id = u2.id
                ORDER BY m.created_at DESC
                LIMIT 10
            ");
            $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $recentMessages
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>