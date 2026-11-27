<?php
/**
 * CommUnity - Logout
 */

require_once __DIR__ . '/auth/authentication.php';

if (is_logged_in()) {
    log_activity(current_user_id(), 'Logout', 'Auth', ($_SESSION['name'] ?? 'User') . ' logged out.');
}

session_destroy();
header("Location: " . BASE_URL . "login.php");
exit;
