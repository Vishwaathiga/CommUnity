<?php
/**
 * CommUnity - Resident Family Members API
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

// --- GET: List family members for a resident ---
if ($method === 'GET' && empty($action) && isset($_GET['resident_id'])) {
    $resident_id = (int) $_GET['resident_id'];

    try {
        $stmt = db()->query("SELECT * FROM resident_family_members WHERE resident_id = ORDER BY relation, family_member_name");
        $family = $stmt->fetchAll();
        echo json_encode(['family' => $family]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// --- POST: Add family member ---
if ($action === 'add' && $method === 'POST') {
    $resident_id = (int) ($_POST['resident_id'] ?? 0);
    $family_member_name = trim($_POST['family_member_name'] ?? '');
    $relation = trim($_POST['relation'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $move_in_date = trim($_POST['move_in_date'] ?? '');

    if (empty($resident_id) || empty($family_member_name) || empty($relation)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }

    try {
        $stmt = db()->prepare("INSERT INTO resident_family_members (resident_id, family_member_name, relation, phone, email, address, move_in_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$resident_id, $family_member_name, $relation, $phone, $email, $address, $move_in_date]);

        log_activity(current_user_id(), 'Added Family Member', 'Family', "Added family member: $family_member_name");
        echo json_encode(['success' => true, 'message' => 'Family member added successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Update family member ---
if ($action === 'update' && $method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $family_member_name = trim($_POST['family_member_name'] ?? '');
    $relation = trim($_POST['relation'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $move_out_date = trim($_POST['move_out_date'] ?? '');

    if (empty($id) || empty($family_member_name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid family member data.']);
        exit;
    }

    try {
        $stmt = db()->prepare("UPDATE resident_family_members SET family_member_name = ?, relation = ?, phone = ?, email = ?, address = ?, move_out_date = ? WHERE id = ?");
        $stmt->execute([$family_member_name, $relation, $phone, $email, $address, $move_out_date, $id]);

        log_activity(current_user_id(), 'Updated Family Member', 'Family', "Updated family member ID $id.");
        echo json_encode(['success' => true, 'message' => 'Family member updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Remove family member ---
if ($action === 'remove' && $method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid family member.']);
        exit;
    }

    try {
        $stmt = db()->prepare("DELETE FROM resident_family_members WHERE id = ?");
        $stmt->execute([$id]);

        log_activity(current_user_id(), 'Removed Family Member', 'Family', "Removed family member ID $id.");
        echo json_encode(['success' => true, 'message' => 'Family member removed successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}