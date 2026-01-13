<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

header('Content-Type: application/json');

$adminId = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_users');

try {
    switch ($action) {
        case 'get_users':
            $search = $_GET['search'] ?? '';
            $roleFilter = $_GET['role'] ?? '';
            $statusFilter = $_GET['status'] ?? '';
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT id, name, email, role, status, message_status, created_at, updated_at, last_login FROM users WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $search = '%' . $search . '%';
                $sql .= " AND (name LIKE ? OR email LIKE ?)";
                $params[] = $search;
                $params[] = $search;
            }

            if (!empty($roleFilter)) {
                $sql .= " AND role = ?";
                $params[] = $roleFilter;
            }

            if (!empty($statusFilter)) {
                $sql .= " AND status = ?";
                $params[] = $statusFilter;
            }

            $sql .= " ORDER BY created_at DESC";

            // Get total count
            $countSql = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as count FROM', $sql);
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalUsers = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            $totalPages = ceil($totalUsers / $perPage);

            // Get paginated results
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $users = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = [
                    'id' => (int) $row['id'],
                    'name' => htmlspecialchars($row['name']),
                    'email' => htmlspecialchars($row['email']),
                    'role' => $row['role'],
                    'status' => $row['status'],
                    'message_status' => $row['message_status'],
                    'last_login' => $row['last_login'] ? date('M j, Y g:i A', strtotime($row['last_login'])) : 'Never',
                    'created_at' => date('M j, Y', strtotime($row['created_at']))
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_users' => $totalUsers,
                        'per_page' => $perPage,
                        'offset' => $offset
                    ]
                ]
            ]);
            break;

        case 'add_user':
            $userName = trim($_POST['name'] ?? '');
            $userEmail = trim($_POST['email'] ?? '');
            $userPassword = $_POST['password'] ?? '';
            $userRole = $_POST['role'] ?? '';
            $userStatus = $_POST['status'] ?? '';

            if (empty($userName) || empty($userEmail) || empty($userPassword)) {
                throw new Exception('All fields are required');
            }

            if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            if (strlen($userPassword) < 8) {
                throw new Exception('Password must be at least 8 characters');
            }

            if (!in_array($userRole, ['admin', 'client'])) {
                throw new Exception('Invalid role');
            }

            if (!in_array($userStatus, ['active', 'inactive', 'pending'])) {
                throw new Exception('Invalid status');
            }

            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $userEmail]);
            if ($stmt->fetch()) {
                throw new Exception('Email already exists');
            }

            $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, role, status, created_at)
                VALUES (:name, :email, :password, :role, :status, NOW())
            ");
            $stmt->execute([
                'name' => $userName,
                'email' => $userEmail,
                'password' => $hashedPassword,
                'role' => $userRole,
                'status' => $userStatus
            ]);

            echo json_encode(['success' => true, 'message' => 'User created successfully']);
            break;

        case 'update_user':
            $userId = (int) ($_POST['user_id'] ?? 0);
            $userName = trim($_POST['name'] ?? '');
            $userEmail = trim($_POST['email'] ?? '');
            $userRole = $_POST['role'] ?? '';

            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            if (empty($userName) || empty($userEmail)) {
                throw new Exception('Name and email are required');
            }

            if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            if (!in_array($userRole, ['admin', 'client'])) {
                throw new Exception('Invalid role');
            }

            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $userEmail, 'id' => $userId]);
            if ($stmt->fetch()) {
                throw new Exception('Email already exists');
            }

            $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id");
            $stmt->execute([
                'name' => $userName,
                'email' => $userEmail,
                'role' => $userRole,
                'id' => $userId
            ]);

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'update_status':
            $userId = (int) ($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? '';

            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            if (!in_array($status, ['active', 'inactive', 'pending'])) {
                throw new Exception('Invalid status');
            }

            // Sync message_status with user status
            $messageStatus = ($status === 'active') ? 'active' : 'inactive';

            $stmt = $conn->prepare("UPDATE users SET status = :status, message_status = :message_status WHERE id = :id");
            $stmt->execute([
                'status' => $status,
                'message_status' => $messageStatus,
                'id' => $userId
            ]);

            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            break;

        case 'delete_user':
            $userId = (int) ($_POST['user_id'] ?? 0);

            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            if ($userId === $adminId) {
                throw new Exception('Cannot delete your own account');
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>