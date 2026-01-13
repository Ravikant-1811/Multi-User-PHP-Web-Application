<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_messages':
            $partnerId = (int) ($_GET['partner_id'] ?? 0);
            $lastId = (int) ($_GET['last_id'] ?? 0);

            if ($partnerId <= 0) {
                throw new Exception('Invalid partner ID');
            }

            // STRICT one-to-one verification
            // Verify partner relationship and ensure privacy
            if ($userRole === 'client') {
                // Client can ONLY chat with admin - no other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'admin' LIMIT 1");
                $stmt->execute(['id' => $partnerId]);
                if (!$stmt->fetch()) {
                    throw new Exception('Clients can only chat with administrators');
                }
            } elseif ($userRole === 'admin') {
                // Admin can ONLY chat with individual clients - isolated conversations
                $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'client' LIMIT 1");
                $stmt->execute(['id' => $partnerId]);
                if (!$stmt->fetch()) {
                    throw new Exception('Administrators can only chat with clients');
                }
            } else {
                throw new Exception('Invalid user role');
            }

            // Get messages - ONLY between these two users (one-to-one isolation)
            $stmt = $conn->prepare("
                SELECT id, sender_id, receiver_id, message, is_read, created_at
                FROM messages
                WHERE ((sender_id = ? AND receiver_id = ?)
                   OR (sender_id = ? AND receiver_id = ?))
                AND id > ?
                ORDER BY created_at ASC
                LIMIT 100
            ");
            $stmt->execute([
                $userId,
                $partnerId,
                $partnerId,
                $userId,
                $lastId
            ]);

            $messages = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $messages[] = [
                    'id' => (int) $row['id'],
                    'sender_id' => (int) $row['sender_id'],
                    'receiver_id' => (int) $row['receiver_id'],
                    'message' => htmlspecialchars($row['message']),
                    'is_read' => (int) $row['is_read'],
                    'time' => date('g:i A', strtotime($row['created_at']))
                ];
            }

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        case 'send_message':
            $receiverId = (int) ($_POST['receiver_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');

            if ($receiverId <= 0) {
                throw new Exception('Invalid receiver ID');
            }

            if (empty($message)) {
                throw new Exception('Message cannot be empty');
            }

            if (strlen($message) > 1000) {
                throw new Exception('Message too long');
            }

            // STRICT one-to-one send verification
            if ($userRole === 'client') {
                // Client can ONLY send to admin
                $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'admin' LIMIT 1");
                $stmt->execute(['id' => $receiverId]);
                if (!$stmt->fetch()) {
                    throw new Exception('Clients can only message administrators');
                }
            } elseif ($userRole === 'admin') {
                // Admin can ONLY send to individual clients
                $stmt = $conn->prepare("SELECT id FROM users WHERE id = :id AND role = 'client' LIMIT 1");
                $stmt->execute(['id' => $receiverId]);
                if (!$stmt->fetch()) {
                    throw new Exception('Administrators can only message clients');
                }
            } else {
                throw new Exception('Invalid user role');
            }

            // Insert message
            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at)
                VALUES (:sender_id, :receiver_id, :message, 0, NOW())
            ");
            $stmt->execute([
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message' => $message
            ]);

            echo json_encode([
                'success' => true,
                'message_id' => $conn->lastInsertId()
            ]);
            break;

        case 'mark_read':
            $partnerId = (int) ($_POST['partner_id'] ?? 0);

            if ($partnerId <= 0) {
                throw new Exception('Invalid partner ID');
            }

            // Mark all messages from partner as read
            $stmt = $conn->prepare("
                UPDATE messages
                SET is_read = 1
                WHERE receiver_id = :user_id
                AND sender_id = :partner_id
                AND is_read = 0
            ");
            $stmt->execute([
                'user_id' => $userId,
                'partner_id' => $partnerId
            ]);

            echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
            break;

        case 'get_users':
            if ($userRole !== 'admin') {
                throw new Exception('Admin access required');
            }

            $search = trim($_GET['search'] ?? '');

            // Use positional parameters for multiple occurrences
            $sql = "
                SELECT u.id, u.name, u.email, u.message_status,
                    (SELECT COUNT(*) FROM messages 
                     WHERE sender_id = u.id 
                     AND receiver_id = ? 
                     AND is_read = 0) as unread_count,
                    (SELECT COUNT(*) FROM messages 
                     WHERE (sender_id = u.id AND receiver_id = ?)
                     OR (sender_id = ? AND receiver_id = u.id)) as total_messages,
                    (SELECT message FROM messages 
                     WHERE (sender_id = u.id AND receiver_id = ?)
                     OR (sender_id = ? AND receiver_id = u.id)
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages 
                     WHERE (sender_id = u.id AND receiver_id = ?)
                     OR (sender_id = ? AND receiver_id = u.id)
                     ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM users u
                WHERE u.role = 'client'
            ";

            $params = [];
            // Add userId 7 times for the 7 occurrences in subqueries
            $params[] = $userId;  // unread_count - sender_id = u.id AND receiver_id = ?
            $params[] = $userId;  // total_messages - receiver_id = ?
            $params[] = $userId;  // total_messages - sender_id = ?
            $params[] = $userId;  // last_message - receiver_id = ?
            $params[] = $userId;  // last_message - sender_id = ?
            $params[] = $userId;  // last_message_time - receiver_id = ?
            $params[] = $userId;  // last_message_time - sender_id = ?

            if (!empty($search)) {
                $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }

            $sql .= " ORDER BY last_message_time DESC, u.name ASC";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);

            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $lastMessage = $row['last_message'] ? htmlspecialchars($row['last_message']) : 'No messages yet';
                if (strlen($lastMessage) > 50) {
                    $lastMessage = substr($lastMessage, 0, 50) . '...';
                }

                $lastMessageTime = '';
                if ($row['last_message_time']) {
                    $time = strtotime($row['last_message_time']);
                    $today = strtotime('today');
                    $yesterday = strtotime('yesterday');

                    if ($time >= $today) {
                        // Today - show time only
                        $lastMessageTime = date('g:i A', $time);
                    } elseif ($time >= $yesterday && $time < $today) {
                        // Yesterday - show "Yesterday"
                        $lastMessageTime = 'Yesterday';
                    } else {
                        // Older - show date and time
                        $lastMessageTime = date('M j, g:i A', $time);
                    }
                }

                $users[] = [
                    'id' => (int) $row['id'],
                    'name' => htmlspecialchars($row['name']),
                    'email' => htmlspecialchars($row['email']),
                    'is_online' => ($row['message_status'] === 'active'),
                    'unread_count' => (int) $row['unread_count'],
                    'total_messages' => (int) $row['total_messages'],
                    'last_message' => $lastMessage,
                    'last_message_time' => $lastMessageTime
                ];
            }

            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'get_unread_count':
            // Get total unread message count
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM messages
                WHERE receiver_id = :user_id
                AND is_read = 0
            ");
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'count' => (int) $result['count']
            ]);
            break;

        case 'get_user_status':
            $partnerId = (int) ($_GET['user_id'] ?? 0);

            if ($partnerId <= 0) {
                throw new Exception('Invalid user ID');
            }

            // Enforce one-to-one visibility rules
            if ($userRole === 'client') {
                $stmt = $conn->prepare("SELECT message_status FROM users WHERE id = :id AND role = 'admin' LIMIT 1");
                $stmt->execute(['id' => $partnerId]);
            } elseif ($userRole === 'admin') {
                $stmt = $conn->prepare("SELECT message_status FROM users WHERE id = :id AND role = 'client' LIMIT 1");
                $stmt->execute(['id' => $partnerId]);
            } else {
                throw new Exception('Invalid user role');
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new Exception('User not found');
            }

            $online = ($row['message_status'] === 'active');
            echo json_encode(['success' => true, 'online' => $online, 'status' => $online ? 'Online' : 'Offline']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
