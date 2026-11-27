<?php
/**
 * CommUnity - Workers API
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// --- GET: List workers ---
if ($method === 'GET' && empty($action)) {
    try {
        $stmt = db()->query("SELECT * FROM workers ORDER BY name");
        $workers = $stmt->fetchAll();
        echo json_encode(['workers' => $workers]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// --- POST: Add worker ---
if ($action === 'add' && $method === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $block = trim($_POST['block'] ?? '');
    $door = trim($_POST['door_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $hire_date = trim($_POST['hire_date'] ?? '');

    if (empty($name) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Name and role are required.']);
        exit;
    }

    try {
        $stmt = db()->prepare("INSERT INTO workers (name, role, block, door_number, phone, email, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $role, $block, $door, $phone, $email, $status, $hire_date]);

        log_activity(current_user_id(), 'Added Worker', 'Workers', "Added worker: $name.");
        echo json_encode(['success' => true, 'message' => 'Worker added successfully.']);
    } catch (PDOException $e) {
        // Check for duplicate email
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo json_encode(['success' => false, 'message' => 'A worker with this email already exists.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    }
    exit;
}

// --- POST: Update worker ---
if ($action === 'update' && $method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $block = trim($_POST['block'] ?? '');
    $door = trim($_POST['door_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if (empty($id) || empty($name) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'Invalid worker data.']);
        exit;
    }

    try {
        $stmt = db()->prepare("UPDATE workers SET name = ?, role = ?, block = ?, door_number = ?, phone = ?, email = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $role, $block, $door, $phone, $email, $status, $id]);

        log_activity(current_user_id(), 'Updated Worker', 'Workers', "Updated worker ID $id.");
        echo json_encode(['success' => true, 'message' => 'Worker updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Delete worker ---
if ($action === 'delete' && $method === 'POST') {
    $id = (int) $_POST['id'] ?? 0;

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid worker.']);
        exit;
    }

    try {
        // Check if assigned to any residents
        $stmt = db()->query("SELECT COUNT(*) as cnt FROM users WHERE assigned_worker_id = ?");
        // Note: we don't have assigned_worker_id in users yet, so we'll just delete

        $stmt = db()->prepare("DELETE FROM workers WHERE id = ?");
        $stmt->execute([$id]);

        log_activity(current_user_id(), 'Deleted Worker', 'Workers', "Deleted worker ID $id.");
        echo json_encode(['success' => true, 'message' => 'Worker deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}