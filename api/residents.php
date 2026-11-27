<?php
/**
 * CommUnity - Residents API
 * Used by Watchman dashboard for resident search and availability checks.
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'availability') {
    $resident_id = (int) ($_GET['resident_id'] ?? 0);
    $user = get_user($resident_id);
    if ($user) {
        echo json_encode(['availability' => $user['availability']]);
    } else {
        echo json_encode(['availability' => null]);
    }
    exit;
}

// Search residents
$q      = trim($_GET['q'] ?? '');
$block  = trim($_GET['block'] ?? '');
$status = trim($_GET['status'] ?? '');

try {
    $sql = "SELECT id, name, block, door_number, availability, profile_image FROM users WHERE role = 'resident'";
    $params = [];

    if ($q) {
        $sql .= " AND (name LIKE ? OR block LIKE ? OR door_number LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($block) {
        $sql .= " AND block = ?";
        $params[] = $block;
    }
    if ($status) {
        $sql .= " AND availability = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY name";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $residents = $stmt->fetchAll();

    // Build full URLs for profile images
    foreach ($residents as &$r) {
        $r['profile_image'] = $r['profile_image'] ? UPLOAD_URL . $r['profile_image'] : BASE_URL . 'assets/images/default-avatar.png';
    }

    echo json_encode(['residents' => $residents]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
