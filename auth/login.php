<?php

require_once __DIR__ . '/auth.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Redirect if already logged in based on user role
if (!empty($_SESSION['user_id'])) {
    redirect_after_login($_SESSION['role']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request.';
    } else {
        $res = login_user($identifier, $password);
        if ($res['success']) {
            unset($_SESSION['csrf_token']);
            redirect_after_login($res['user']['role']);
        } else {
            $error = implode(' ', $res['errors']);
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
</head>

<body class="auth-page">
    <main class="card auth">
        <h2>Sign in</h2>
        <p class="subtitle">Access your dashboard with your account.</p>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="field">
                <label class="label" for="username">Username or Email</label>
                <input class="input" id="username" type="text" name="username" placeholder="jane@acme.com" required
                    autofocus>
            </div>
            <div class="field">
                <label class="label" for="password">Password</label>
                <input class="input" id="password" type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="field forgot-password">
                <a href="forgot_password.php">Forgot password?</a>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button class="button" type="submit">Continue</button>
        </form>
        <p class="hint">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </main>
</body>

</html>