<?php
/**
 * CommUnity - Skills API
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Search skills
$q      = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

try {
    $sql = "SELECT s.*, u.name AS resident_name, u.block, u.door_number FROM skills s JOIN users u ON s.resident_id = u.id WHERE 1=1";
    $params = [];

    if ($q) {
        $sql .= " AND (s.skill_name LIKE ? OR s.description LIKE ? OR s.category LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($category) {
        $sql .= " AND s.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY s.created_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $skills = $stmt->fetchAll();

    foreach ($skills as &$s) {
        $s['can_request'] = ($s['resident_id'] !== current_user_id());
    }

    echo json_encode(['skills' => $skills]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
