<?php
/**
 * CommUnity - Authentication & Session Management
 */

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Check if a user is logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user's role.
 */
function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/**
 * Get the current logged-in user's ID.
 */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Require authentication. Redirect to login if not logged in.
 */
function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Please login to access this page.";
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

/**
 * Require a specific role. Redirect to the user's own dashboard if unauthorized.
 */
function require_role(string $role): void {
    require_login();
    if (current_role() !== $role) {
        // Redirect to the correct dashboard for the user's actual role
        $redirect = match (current_role()) {
            'admin'     => BASE_URL . "admin/dashboard.php",
            'watchman'  => BASE_URL . "watchman/dashboard.php",
            'resident'  => BASE_URL . "resident/dashboard.php",
            default     => BASE_URL . "login.php",
        };
        $_SESSION['flash_error'] = "You do not have permission to access that page.";
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Redirect if already logged in (for login/signup pages).
 */
function redirect_if_logged_in(): void {
    if (is_logged_in()) {
        $redirect = match (current_role()) {
            'admin'     => BASE_URL . "admin/dashboard.php",
            'watchman'  => BASE_URL . "watchman/dashboard.php",
            'resident'  => BASE_URL . "resident/dashboard.php",
            default     => BASE_URL . "index.php",
        };
        header("Location: " . $redirect);
        exit;
    }
}
