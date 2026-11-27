<?php
/**
 * CommUnity - Notifications API
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'count':
        echo json_encode(['count' => unread_notification_count(current_user_id())]);
        break;

    case 'mark_read':
        $id = (int) ($_GET['id'] ?? 0);
        mark_notification_read($id, current_user_id());
        echo json_encode(['success' => true]);
        break;

    case 'mark_all_read':
        mark_all_notifications_read(current_user_id());
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
