<?php
/**
 * CommUnity - Shared Helper Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Escape HTML output to prevent XSS.
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Set a flash message (shown once, then cleared).
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message.
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Log an activity to the activity_logs table.
 */
function log_activity(?int $user_id, string $action, string $module, string $description): void {
    try {
        $stmt = db()->prepare("INSERT INTO activity_logs (user_id, action, module, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $module, $description]);
    } catch (PDOException $e) {
        // Silently fail - don't break the app for logging
    }
}

/**
 * Create a notification for a user.
 */
function create_notification(int $user_id, string $title, string $message, string $type = 'info', ?int $reference_id = null): void {
    try {
        $stmt = db()->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type, $reference_id]);
    } catch (PDOException $e) {
        // Silently fail
    }
}

/**
 * Get unread notification count for a user.
 */
function unread_notification_count(int $user_id): int {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get all notifications for a user.
 */
function get_notifications(int $user_id, int $limit = 50): array {
    try {
        $stmt = db()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Mark a notification as read.
 */
function mark_notification_read(int $notification_id, int $user_id): void {
    try {
        $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        // Silently fail
    }
}

/**
 * Mark all notifications as read for a user.
 */
function mark_all_notifications_read(int $user_id): void {
    try {
        $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        // Silently fail
    }
}

/**
 * Generate a random 6-digit PIN.
 */
function generate_pin(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Format a datetime for display.
 */
function format_datetime(?string $dt): string {
    if (!$dt) return '—';
    return date('d M Y, h:i A', strtotime($dt));
}

/**
 * Format a date for display.
 */
function format_date(?string $d): string {
    if (!$d) return '—';
    return date('d M Y', strtotime($d));
}

/**
 * Format a time for display.
 */
function format_time(?string $t): string {
    if (!$t) return '—';
    return date('h:i A', strtotime($t));
}

/**
 * Get a setting value.
 */
function get_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Get all residents (users with role = resident).
 */
function get_residents(): array {
    try {
        $stmt = db()->query("SELECT * FROM users WHERE role = 'resident' ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get a single user by ID.
 */
function get_user(int $id): ?array {
    try {
        $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get all blocks.
 */
function get_blocks(): array {
    try {
        $stmt = db()->query("SELECT * FROM blocks ORDER BY block_name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get block name by user's block field.
 */
function block_badge(string $block): string {
    return '<span class="badge badge-info">' . e($block) . '</span>';
}

/**
 * Get availability badge HTML.
 */
function availability_badge(string $status): string {
    $class = match ($status) {
        'Available'     => 'badge-success',
        'Inside'        => 'badge-primary',
        'Not Available' => 'badge-danger',
        default         => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Get visitor status badge HTML.
 */
function visitor_status_badge(string $status): string {
    $class = match ($status) {
        'Expected'  => 'badge-secondary',
        'Arrived'   => 'badge-warning',
        'Approved'  => 'badge-info',
        'Rejected'  => 'badge-danger',
        'Inside'    => 'badge-primary',
        'Exited'    => 'badge-success',
        default     => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Get delivery status badge HTML.
 */
function delivery_status_badge(string $status): string {
    $class = match ($status) {
        'Received'  => 'badge-warning',
        'Notified'  => 'badge-info',
        'Collected' => 'badge-success',
        default     => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Get help request status badge HTML.
 */
function help_status_badge(string $status): string {
    $class = match ($status) {
        'Pending'     => 'badge-warning',
        'Accepted'    => 'badge-info',
        'In Progress' => 'badge-primary',
        'Completed'   => 'badge-success',
        'Cancelled'   => 'badge-danger',
        default       => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Get resource status badge HTML.
 */
function resource_status_badge(string $status): string {
    $class = match ($status) {
        'Available'        => 'badge-success',
        'Request Pending'  => 'badge-warning',
        'Reserved'         => 'badge-info',
        'Borrowed'         => 'badge-primary',
        'Rented'           => 'badge-primary',
        'Sold'             => 'badge-success',
        'Donated'          => 'badge-success',
        'Exchanged'        => 'badge-success',
        'Unavailable'      => 'badge-danger',
        default            => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Get resource request status badge HTML.
 */
function resource_request_badge(string $status): string {
    $class = match ($status) {
        'Pending'   => 'badge-warning',
        'Accepted'  => 'badge-success',
        'Rejected'  => 'badge-danger',
        'Completed' => 'badge-primary',
        'Cancelled' => 'badge-secondary',
        default     => 'badge-secondary',
    };
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

/**
 * Handle file upload securely.
 * Returns the filename on success, or an error string on failure.
 */
function handle_file_upload(array $file, string $prefix = ''): string {
    if (!isset($file['tmp_name']) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed_types)) {
        return 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.';
    }
    if ($file['size'] > $max_size) {
        return 'File too large. Maximum 2MB allowed.';
    }

    // Verify it's actually an image
    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        return 'Invalid image file.';
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '_' . time() . '.' . strtolower($ext);
    $filepath = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return 'Failed to upload file.';
}

/**
 * Get profile image URL or default avatar.
 */
function profile_image_url(?string $image): string {
    if ($image && file_exists(UPLOAD_DIR . $image)) {
        return UPLOAD_URL . $image;
    }
    return BASE_URL . 'assets/images/default-avatar.png';
}

/**
 * Get resource image URL or default.
 */
function resource_image_url(?string $image): string {
    if ($image && file_exists(UPLOAD_DIR . $image)) {
        return UPLOAD_URL . $image;
    }
    return BASE_URL . 'assets/images/default-resource.png';
}
