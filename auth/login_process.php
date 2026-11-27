<?php
/**
 * CommUnity - Login Processing
 */

require_once __DIR__ . '/authentication.php';

redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    set_flash('danger', 'Please fill in all fields.');
    header("Location: " . BASE_URL . "login.php");
    exit;
}

try {
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        set_flash('danger', 'Invalid login credentials.');
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    // Set session
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['name']      = $user['name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];

    log_activity($user['id'], 'Login', 'Auth', $user['name'] . ' logged in.');

    // Redirect based on role
    $redirect = match ($user['role']) {
        'admin'     => BASE_URL . "admin/dashboard.php",
        'watchman'  => BASE_URL . "watchman/dashboard.php",
        'resident'  => BASE_URL . "resident/dashboard.php",
        default     => BASE_URL . "index.php",
    };

    set_flash('success', 'Welcome back, ' . e($user['name']) . '!');
    header("Location: " . $redirect);
    exit;

} catch (PDOException $e) {
    set_flash('danger', 'Login failed. Please try again.');
    header("Location: " . BASE_URL . "login.php");
    exit;
}
