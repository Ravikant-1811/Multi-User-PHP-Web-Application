<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../config/config.php';

// Only allow existing admins to create new admin accounts
// Comment out the line below if you want to allow anyone to create the first admin
require_role('admin');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if ($name === '' || !preg_match('/^[A-Za-z0-9_\s]{3,50}$/', $name)) {
        $errors[] = 'Name must be 3-50 chars: letters, numbers, underscore, space.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Register as admin user
        $result = register_user($name, $email, $password, 'admin');
        if ($result['success']) {
            $success = true;
        } else {
            $errors = array_merge($errors, $result['errors']);
        }
    }
}

// Minimal HTML form + show errors/success
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
</head>

<body class="auth-page">
    <main class="card auth">
        <h2>Admin Registration</h2>
        <p class="subtitle">Create a new administrator account.</p>
        <?php if ($success): ?>
            <div class="success">
                <p>Admin registration successful! The new administrator can now log in.</p>
                <p><a href="<?php echo url('admin/dashboard.php'); ?>">Back to Dashboard</a></p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="field">
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" name="name" type="text" required
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="field">
                    <label class="label" for="email">Email</label>
                    <input class="input" id="email" name="email" type="email" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="field">
                    <label class="label" for="password">Password</label>
                    <input class="input" id="password" name="password" type="password" required>
                </div>

                <div class="field">
                    <label class="label" for="password_confirm">Confirm Password</label>
                    <input class="input" id="password_confirm" name="password_confirm" type="password" required>
                </div>

                <button class="button" type="submit" style="margin-top:12px;">Register Admin</button>
            </form>
        <?php endif; ?>
        <p class="hint"><a href="<?php echo url('admin/dashboard.php'); ?>">Back to Dashboard</a></p>
    </main>
</body>

</html>