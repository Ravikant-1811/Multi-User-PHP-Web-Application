<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('client');

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$action = $_GET['action'] ?? 'get_data';

try {
    switch ($action) {
        case 'get_data':
            // Profile & status
            $stmt = $conn->prepare("SELECT id, name, email, role, status, created_at, updated_at, last_login, message_status FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new Exception('User not found');
            }

            $createdAt = $user['created_at'];
            $daysActive = 0;
            if ($createdAt) {
                $diff = time() - strtotime($createdAt);
                $daysActive = max(0, (int) floor($diff / 86400));
            }

            $lastLogin = $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'First login';
            $onlineStatus = $user['message_status'] === 'active' ? 'active' : 'inactive';

            // Unread count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = :userId AND is_read = 0");
            $stmt->execute(['userId' => $userId]);
            $unreadCount = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

            // Recent messages from admin
            $stmt = $conn->prepare("
                SELECT m.id, m.message, m.created_at, m.is_read, u.name as sender_name
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE m.receiver_id = :userId AND u.role = 'admin'
                ORDER BY m.created_at DESC
                LIMIT 2
            ");
            $stmt->execute(['userId' => $userId]);
            $recent = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $recent[] = [
                    'id' => (int) $row['id'],
                    'message' => $row['message'],
                    'created_at' => date('M j, Y g:i A', strtotime($row['created_at'])),
                    'is_read' => (int) $row['is_read'],
                    'sender_name' => $row['sender_name']
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'profile' => [
                        'id' => (int) $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'status' => $user['status'],
                        'created_at' => $createdAt,
                        'days_active' => $daysActive
                    ],
                    'last_login' => $lastLogin,
                    'online_status' => $onlineStatus,
                    'unread_count' => $unreadCount,
                    'recent_messages' => $recent
                ]
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
