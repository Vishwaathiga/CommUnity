<?php
/**
 * CommUnity - Resource Transactions API
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

// --- GET: List resource transactions ---
if ($method === 'GET' && empty($action)) {
    $resource_id = isset($_GET['resource_id']) ? (int) $_GET['resource_id'] : 0;
    $requester_id = isset($_GET['requester_id']) ? (int) $_GET['requester_id'] : 0;

    try {
        $sql = "SELECT rt.*, u.name AS requester_name, r.item_name FROM resource_transactions rt LEFT JOIN users u ON rt.requester_id = u.id LEFT JOIN resources r ON rt.resource_id = r.id WHERE 1=1";
        $params = [];

        if ($resource_id > 0) {
            $sql .= " AND rt.resource_id = ?";
            $params[] = $resource_id;
        }
        if ($requester_id > 0) {
            $sql .= " AND rt.requester_id = ?";
            $params[] = $requester_id;
        }

        $sql .= " ORDER BY rt.borrowed_at DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        echo json_encode(['transactions' => $transactions]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// --- POST: Create transaction ---
if ($action === 'create' && $method === 'POST') {
    $resource_id = (int) ($_POST['resource_id'] ?? 0);
    $requester_id = (int) ($_POST['requester_id'] ?? 0);

    if (empty($resource_id) || empty($requester_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    try {
        $due_date = date('Y-m-d H:i:s', strtotime('+7 days')); // 7 days default
        $stmt = db()->prepare("INSERT INTO resource_transactions (resource_id, requester_id, borrowed_at, due_date, status) VALUES (?, ?, NOW(), ?, 'Borrowed')");
        $stmt->execute([$resource_id, $requester_id, $due_date]);

        // Update resource status to Rented
        $stmt = db()->prepare("UPDATE resources SET status = 'Rented' WHERE id = ?");
        $stmt->execute([$resource_id]);

        log_activity(current_user_id(), 'Started Resource Transaction', 'Resources', "Started transaction for resource ID $resource_id.");
        echo json_encode(['success' => true, 'message' => 'Resource borrowed successfully.', 'due_date' => $due_date]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Update transaction (return) ---
if ($action === 'return' && $method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction.']);
        exit;
    }

    try {
        $stmt = db()->prepare("UPDATE resource_transactions SET returned_at = NOW(), status = 'Returned' WHERE id = ?");
        $stmt->execute([$id]);

        // Update resource status back to Available
        $stmt = db()->prepare("SELECT resource_id FROM resource_transactions WHERE id = ?");
        $stmt->execute([$id]);
        $rt = $stmt->fetch();
        if ($rt) {
            $stmt = db()->prepare("UPDATE resources SET status = 'Available' WHERE id = ?");
            $stmt->execute([$rt['resource_id']]);
        }

        log_activity(current_user_id(), 'Returned Resource', 'Resources', "Returned resource ID $id.");
        echo json_encode(['success' => true, 'message' => 'Resource returned successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}