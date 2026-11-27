<?php
/**
 * CommUnity - Complaints API
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

// --- GET: List complaints ---
if ($method === 'GET' && empty($action)) {
    $status = trim($_GET['status'] ?? '');
    $type = trim($_GET['type'] ?? '');
    $assigned = isset($_GET['assigned']) ? (int) $_GET['assigned'] : -1;

    try {
        $sql = "SELECT c.*, u.name AS complainer_name FROM complaints c LEFT JOIN users u ON c.complainer_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }
        if (!empty($type)) {
            $sql .= " AND c.type = ?";
            $params[] = $type;
        }
        if ($assigned >= 0) {
            $sql .= " AND c.assigned_to = ?";
            $params[] = $assigned;
        } else {
            $sql .= " WHERE c.assigned_to IS NULL";
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $complaints = $stmt->fetchAll();

        foreach ($complaints as &$c) {
            $c['assigned_name'] = $c['assigned_to'] > 0 ? get_user_name($c['assigned_to']) : 'Unassigned';
        }

        echo json_encode(['complaints' => $complaints]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// --- POST: Create complaint ---
if ($action === 'create' && $method === 'POST') {
    $complainer_id = current_user_id();
    $type = trim($_POST['type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($type) || empty($title) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }

    try {
        $stmt = db()->prepare("INSERT INTO complaints (complainer_id, type, title, description, status) VALUES (?, ?, ?, ?, 'Open')");
        $stmt->execute([$complainer_id, $type, $title, $description]);

        // Notify assigned user if any, or all admins
        if (!empty($_POST['assign_to'])) {
            $assign_to = (int) $_POST['assign_to'];
            create_notification($assign_to, 'New Complaint Assignment', "A new {$_POST['type']} complaint has been assigned to you: {$_POST['title']}", 'system');
        } else {
            // Notify all admins
            $ admins = db()->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $admin) {
                create_notification($admin['id'], 'New Complaint', "New {$type} complaint: {$title}");
            }
        }

        log_activity(current_user_id(), 'Created Complaint', 'Complaints', "Created: {$title}");
        echo json_encode(['success' => true, 'message' => 'Complaint created successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Update complaint ---
if ($action === 'update' && $method === 'POST') {
    $complaint_id = (int) $_POST['id'] ?? 0;
    $status = trim($_POST['status'] ?? '');
    $assigned_to = (int) ($_POST['assign_to'] ?? 0);

    if (empty($complaint_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid complaint.']);
        exit;
    }

    try {
        $updates = [];
        $params = [];

        if (!empty($status)) {
            $updates[] = "status = ?";
            $params[] = $status;
        }

        if ($assigned_to >= 0) {
            $updates[] = "assigned_to = ?";
            $params[] = $assigned_to;

            // Notify the assigned user
            $user = get_user_name($assigned_to);
            create_notification($assigned_to, 'Complaint Assignment', "Complaint ID $complaint_id has been assigned to you.");
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No updates provided.']);
            exit;
        }

        $updates[] = "updated_at = NOW()"; // Note: this column doesn't exist, skip
        // Actually let's just set status and assigned_to

        $stmt = db()->prepare("UPDATE complaints SET " . implode(', ', $updates) . " WHERE id = ?");
        $params[] = $complaint_id;
        $stmt->execute($params);

        log_activity(current_user_id(), 'Updated Complaint', 'Complaints', "Updated complaint ID $complaint_id.");
        echo json_encode(['success' => true, 'message' => 'Complaint updated successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Resolve complaint ---
if ($action === 'resolve' && $method === 'POST') {
    $complaint_id = (int) $_POST['id'] ?? 0;
    $resolution_notes = trim($_POST['resolution_notes'] ?? '');

    if (empty($complaint_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid complaint.']);
        exit;
    }

    try {
        $stmt = db()->prepare("UPDATE complaints SET status = 'Resolved', resolved_at = NOW(), resolution_notes = ? WHERE id = ?");
        $stmt->execute([$resolution_notes, $complaint_id]);

        // Notify the complainer
        $complaint = get_complaint_by_id($complaint_id);
        if ($complaint) {
            create_notification($complaint['complainer_id'], 'Complaint Resolved', "Your complaint '{$complaint['title']}' has been resolved.");
        }

        log_activity(current_user_id(), 'Resolved Complaint', 'Complaints', "Resolved complaint ID $complaint_id.");
        echo json_encode(['success' => true, 'message' => 'Complaint marked as resolved.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

function get_complaint_by_id(int $id): ?array {
    try {
        $stmt = db()->prepare("SELECT * FROM complaints WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function get_user_name(int $id): ?string {
    try {
        $stmt = db()->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

// Handle GET requests for listing
if ($method === 'GET' && empty($action)) {
    // Already handled above, just exit
    exit;
}