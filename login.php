<?php
/**
 * CommUnity - Login Page
 */

require_once __DIR__ . '/auth/authentication.php';

redirect_if_logged_in();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                </div>
                <h2><?= e(APP_NAME) ?></h2>
                <p>Sign in to your account</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible">
                    <?= e($flash['message']) ?>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(BASE_URL) ?>auth/login_process.php">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
            </form>
        </div>
        <div class="auth-footer">
            Don't have an account? <a href="<?= e(BASE_URL) ?>signup.php">Sign up</a>
        </div>
    </div>
</body>
</html>
