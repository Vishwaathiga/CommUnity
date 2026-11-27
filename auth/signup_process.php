<?php
/**
 * CommUnity - Signup Processing
 */

require_once __DIR__ . '/authentication.php';

redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "signup.php");
    exit;
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$block    = trim($_POST['block'] ?? '');
$door     = trim($_POST['door_number'] ?? '');

// Validation
$errors = [];
if (empty($name))     $errors[] = 'Full name is required.';
if (empty($email))    $errors[] = 'Email is required.';
if (empty($phone))    $errors[] = 'Phone number is required.';
if (empty($password)) $errors[] = 'Password is required.';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if ($password !== $confirm) $errors[] = 'Passwords do not match.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    header("Location: " . BASE_URL . "signup.php");
    exit;
}

try {
    // Check if email already exists
    $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        set_flash('danger', 'An account with this email already exists.');
        header("Location: " . BASE_URL . "signup.php");
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $stmt = db()->prepare("INSERT INTO users (name, email, phone, password, role, block, door_number, verification_status) VALUES (?, ?, ?, ?, 'resident', ?, ?, 'Pending')");
    $stmt->execute([$name, $email, $phone, $hashed, $block ?: null, $door ?: null]);

    $user_id = (int) db()->lastInsertId();

    log_activity($user_id, 'Signup', 'Auth', $name . ' registered as resident.');

    set_flash('success', 'Account created successfully! Please login.');
    header("Location: " . BASE_URL . "login.php");
    exit;

} catch (PDOException $e) {
    set_flash('danger', 'Registration failed. Please try again.');
    header("Location: " . BASE_URL . "signup.php");
    exit;
}
