<?php

require_once __DIR__ . '/auth.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Validate token on page load
$token_valid = false;
if ($token) {
    $validation = validate_reset_token($token);
    if ($validation['success']) {
        $token_valid = true;
    } else {
        $error = implode(' ', $validation['errors']);
    }
} else {
    $error = 'No reset token provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = reset_password($token, $password);
        if ($result['success']) {
            $success = true;
        } else {
            $error = implode(' ', $result['errors']);
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
</head>

<body class="auth-page">
    <main class="card auth">
        <h2>Reset Password</h2>
        <p class="subtitle">Enter your new password below.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                Password has been reset successfully! You can now log in with your new password.
            </div>
            <p class="hint"><a href="login.php">Go to login</a></p>
        <?php elseif ($token_valid): ?>
            <form method="post" action="">
                <div class="field">
                    <label class="label" for="password">New Password</label>
                    <input class="input" id="password" type="password" name="password" placeholder="••••••••" required
                        autofocus>
                </div>
                <div class="field">
                    <label class="label" for="password_confirm">Confirm New Password</label>
                    <input class="input" id="password_confirm" type="password" name="password_confirm"
                        placeholder="••••••••" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button class="button" type="submit">Reset Password</button>
            </form>
            <p class="hint"><a href="login.php">Back to login</a></p>
        <?php else: ?>
            <p class="hint"><a href="forgot_password.php">Request a new reset link</a> | <a href="login.php">Back to
                    login</a></p>
        <?php endif; ?>
    </main>
</body>

</html>