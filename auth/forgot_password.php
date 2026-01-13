<?php

require_once __DIR__ . '/auth.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$reset_link = ''; // For development only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request.';
    } else {
        $result = request_password_reset($email);
        if ($result['success']) {
            $success = $result['message'];
            // For development - show reset link (REMOVE IN PRODUCTION)
            if (isset($result['reset_link'])) {
                $reset_link = $result['reset_link'];
            }
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
    <title>Forgot Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('assets/style.css'); ?>">
</head>

<body class="auth-page">
    <main class="card auth">
        <h2>Forgot Password</h2>
        <p class="subtitle">Enter your email to receive a password reset link.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                <?php echo htmlspecialchars($success); ?>
                <!---
                
                <?php if ($reset_link): ?>
                    <br><br>
                    <strong>Development Mode:</strong><br>
                    <a href="<?php echo htmlspecialchars($reset_link); ?>" style="word-break: break-all;">
                        <?php echo htmlspecialchars($reset_link); ?>
                    </a>
                <?php endif; ?>
                --->
        </div>
        <p class="hint"><a href="login.php">Back to login</a></p>
        <?php else: ?>
        <form method="post" action="">
            <div class="field">
                <label class="label" for="email">Email Address</label>
                <input class="input" id="email" type="email" name="email" placeholder="your@email.com" required
                    autofocus>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button class="button" type="submit">Send Reset Link</button>
        </form>
        <p class="hint">Remember your password? <a href="login.php">Log in</a></p>
        <?php endif; ?>
    </main>
</body>

</html>