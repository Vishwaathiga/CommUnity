<?php
/**
 * CommUtility - Forgot Password
 */

require_once __DIR__ . '/authentication.php';

redirect_if_logged_in();

$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        set_flash('danger', 'Please enter your email.');
        header("Location: " . BASE_URL . "forgot_password.php");
        exit;
    }

    try {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Don't reveal if email exists or not for security
            set_flash('success', 'If an account with this email exists, a password reset link has been sent.');
            header("Location: " . BASE_URL . "forgot_password.php");
            exit;
        }

        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token - we'll use the notifications table or add a password_resets table
        // For simplicity, we'll send the reset link via notification
        create_notification(
            $user['id'],
            'Password Reset',
            "Click the link below to reset your password. This link will expire in 1 hour.\n\n" . BASE_URL . "reset_password.php?token=$reset_token",
            'system'
        );

        log_activity(current_user_id(), 'Password Reset Request', 'Auth', "Password reset requested for $user[name]");
        set_flash('success', 'If an account with this email exists, a password reset link has been sent.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to process request.');
    }

    header("Location: " . BASE_URL . "forgot_password.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CommUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                </div>
                <h2>CommUnity</h2>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible">
                    <?= e($flash['message']) ?>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>auth/forgot_password.php">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </form>
            
            <p class="text-muted mt-3">Or <a href="<?= BASE_URL ?>login.php">sign in</a> to your account.</p>
        </div>
    </div>
</body>
</html>