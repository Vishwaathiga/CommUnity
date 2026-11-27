<?php
/**
 * CommUnity - Signup Page
 */

require_once __DIR__ . '/auth/authentication.php';

redirect_if_logged_in();

$flash = get_flash();

// Get blocks for dropdown
try {
    $blocks = get_blocks();
} catch (PDOException $e) {
    $blocks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                </div>
                <h2>Create Account</h2>
                <p>Join your community today</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible">
                    <?= e($flash['message']) ?>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= e(BASE_URL) ?>auth/signup_process.php">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone <span class="required">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="10-digit number" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="block">Block</label>
                        <select class="form-control" id="block" name="block">
                            <option value="">Select Block</option>
                            <?php foreach ($blocks as $b): ?>
                                <option value="<?= e($b['block_name']) ?>"><?= e($b['block_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="door_number">Door Number</label>
                        <input type="text" class="form-control" id="door_number" name="door_number" placeholder="e.g. A-101">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Password <span class="required">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Min 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
            </form>
        </div>
        <div class="auth-footer">
            Already have an account? <a href="<?= e(BASE_URL) ?>login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
