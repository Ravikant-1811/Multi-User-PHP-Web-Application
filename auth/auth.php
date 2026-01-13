<?php
// Authentication helper functions - PDO version
// Provides: register_user(), login_user(), logout_user()

require_once __DIR__ . '/../config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function register_user($name, $email, $password, $role = 'client')
{
    global $conn;
    $errors = [];

    $name = trim($name);
    $email = trim($email);

    if ($name === '' || !preg_match('/^[A-Za-z0-9_\s]{3,50}$/', $name)) {
        $errors[] = 'Name must be 3-50 chars: letters, numbers, underscore, space.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    $allowed_roles = ['admin', 'client'];
    if (!in_array($role, $allowed_roles, true)) {
        $role = 'client';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        // Check if email or name already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR name = ? LIMIT 1');
        $stmt->execute([$email, $name]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'errors' => ['Email or name already in use.']];
        }

        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $password_hash, $role, 'active']);

        $user_id = $conn->lastInsertId();
        return ['success' => true, 'user_id' => $user_id];

    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
    }
}

function login_user($identifier, $password)
{
    global $conn;
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    try {
        // Find user by name or email
        $stmt = $conn->prepare('SELECT id, name, email, password, role, status FROM users WHERE name = ? OR email = ? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'errors' => ['Invalid username or password.']];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'errors' => ['Invalid username or password.']];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'errors' => ['Account is not active.']];
        }

        // Update message_status to active and record last login
        $update_stmt = $conn->prepare("UPDATE users SET message_status = 'active', last_login = NOW(), updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);

        // Set session variables
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        return ['success' => true, 'user' => $user];

    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
    }
}

function logout_user()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    return true;
}

// Require a logged-in user; redirect to login if missing
function require_login()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

// Require that the current user has one of the allowed roles
function require_role($allowed_roles)
{
    require_login();
    $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    $role = $_SESSION['role'] ?? null;
    if ($role === null || !in_array($role, $allowed, true)) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
}

// Redirect to role-specific dashboard after login
function redirect_after_login($role)
{
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'client/dashboard.php');
    }
    exit;
}

// Request password reset - generate token and store in database
function request_password_reset($email)
{
    global $conn;
    $email = trim($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'errors' => ['Invalid email address.']];
    }

    try {
        // Check if user exists
        $stmt = $conn->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Don't reveal if email exists for security
            return ['success' => true, 'message' => 'If an account exists with this email, a reset link has been sent.'];
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));

        // Store token in database with expiry using MySQL's time to avoid timezone issues
        $stmt = $conn->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?');
        $stmt->execute([$token, $user['id']]);

        // Create reset link
        $reset_link = url("auth/reset_password.php?token=" . $token);

        // Send email with reset link
        $to = $email;
        $subject = "Password Reset Request";
        $message = "Hello " . htmlspecialchars($user['name']) . ",\n\n";
        $message .= "You requested to reset your password. Click the link below to proceed:\n\n";
        $message .= $reset_link . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you didn't request this, please ignore this email.\n\n";
        $message .= "Best regards,\nMUPWA Team";

        $headers = "From: noreply@mupwa.local\r\n";
        $headers .= "Reply-To: support@mupwa.local\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // Send email
        $email_sent = mail($to, $subject, $message, $headers);

        if (!$email_sent) {
            // Log email failure but still return success to not reveal whether user exists
            error_log("Failed to send password reset email to: " . $email);
        }

        return [
            'success' => true,
            'message' => 'If an account exists with this email, a reset link has been sent.',
            'reset_link' => $reset_link, // Remove this in production
            'token' => $token // Remove this in production
        ];

    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
    }
}

// Validate reset token
function validate_reset_token($token)
{
    global $conn;

    if (empty($token)) {
        return ['success' => false, 'errors' => ['Invalid reset token.']];
    }

    try {
        $stmt = $conn->prepare('SELECT id, name, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'errors' => ['Invalid or expired reset token.']];
        }

        return ['success' => true, 'user' => $user];

    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
    }
}

// Reset password using token
function reset_password($token, $new_password)
{
    global $conn;

    if (strlen($new_password) < 8) {
        return ['success' => false, 'errors' => ['Password must be at least 8 characters.']];
    }

    // Validate token first
    $validation = validate_reset_token($token);
    if (!$validation['success']) {
        return $validation;
    }

    try {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and clear reset token
        $stmt = $conn->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?');
        $stmt->execute([$password_hash, $token]);

        return ['success' => true, 'message' => 'Password has been reset successfully.'];

    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
    }
}


