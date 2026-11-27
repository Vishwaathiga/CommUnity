<?php
/**
 * CommUnity - Landing Page
 */

require_once __DIR__ . '/auth/authentication.php';

redirect_if_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> — <?= e(APP_TAGLINE) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
</head>
<body class="landing-page">
    <nav class="landing-nav">
        <a href="<?= e(BASE_URL) ?>" class="brand-link">
            <span class="brand-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
            </span>
            <span class="brand-text"><?= e(APP_NAME) ?></span>
        </a>
        <div class="d-flex gap-2">
            <a href="<?= e(BASE_URL) ?>login.php" class="btn btn-outline btn-sm">Login</a>
            <a href="<?= e(BASE_URL) ?>signup.php" class="btn btn-primary btn-sm">Sign Up</a>
        </div>
    </nav>

    <main class="landing-hero">
        <div>
            <h1>Welcome to CommUnity</h1>
            <p>A unified residential community platform connecting residents, watchmen, and administrators. Manage visitors, deliveries, local help, and resource sharing — all in one place.</p>
            <div class="landing-buttons">
                <a href="<?= e(BASE_URL) ?>login.php" class="btn btn-primary btn-lg">Login</a>
                <a href="<?= e(BASE_URL) ?>signup.php" class="btn btn-outline btn-lg">Create Account</a>
            </div>
        </div>
    </main>

    <footer style="text-align:center;padding:20px;color:#c7d2fe;font-size:0.8rem;">
        &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Final Year Project
    </footer>
</body>
</html>
