<?php
/**
 * CommUnity - Resources API
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

// --- GET: Search Resources ---
if ($method === 'GET' && empty($action)) {
    $q            = trim($_GET['q'] ?? '');
    $category     = trim($_GET['category'] ?? '');
    $sharing_type = trim($_GET['sharing_type'] ?? '');
    $availability = trim($_GET['availability'] ?? '');
    $price        = trim($_GET['price'] ?? '');

    try {
        $sql = "SELECT r.*, u.name AS owner_name, u.block, u.door_number FROM resources r JOIN users u ON r.owner_id = u.id WHERE 1=1";
        $params = [];

        if ($q) {
            $sql .= " AND (r.item_name LIKE ? OR r.description LIKE ? OR r.category LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($category) {
            $sql .= " AND r.category = ?";
            $params[] = $category;
        }
        if ($sharing_type) {
            $sql .= " AND r.sharing_type = ?";
            $params[] = $sharing_type;
        }
        if ($availability === 'Available') {
            $sql .= " AND r.availability = 'Available'";
        } elseif ($availability === 'Currently Unavailable') {
            $sql .= " AND r.availability = 'Currently Unavailable'";
        }
        if ($price === 'Free') {
            $sql .= " AND (r.price = 0 OR r.price IS NULL)";
        } elseif ($price === 'Under ₹100') {
            $sql .= " AND r.price > 0 AND r.price < 100";
        } elseif ($price === '₹100–₹500') {
            $sql .= " AND r.price >= 100 AND r.price <= 500";
        } elseif ($price === 'Above ₹500') {
            $sql .= " AND r.price > 500";
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $resources = $stmt->fetchAll();

        foreach ($resources as &$r) {
            $r['image'] = $r['image'] ? UPLOAD_URL . $r['image'] : BASE_URL . 'assets/images/default-resource.png';
            $r['can_request'] = ($r['owner_id'] !== current_user_id() && $r['status'] === 'Available');
        }

        echo json_encode(['resources' => $resources]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
    exit;
}

// --- POST: Request Resource ---
if ($action === 'request' && $method === 'POST') {
    $resource_id = (int) ($_POST['resource_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    try {
        $stmt = db()->prepare("SELECT * FROM resources WHERE id = ?");
        $stmt->execute([$resource_id]);
        $resource = $stmt->fetch();

        if (!$resource) {
            echo json_encode(['success' => false, 'message' => 'Resource not found.']);
            exit;
        }

        if ($resource['owner_id'] === current_user_id()) {
            echo json_encode(['success' => false, 'message' => 'You cannot request your own item.']);
            exit;
        }

        if ($resource['status'] !== 'Available') {
            echo json_encode(['success' => false, 'message' => 'This item is not available.']);
            exit;
        }

        $requester = get_user(current_user_id());

        $stmt = db()->prepare("INSERT INTO resource_requests (resource_id, requester_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$resource_id, current_user_id(), $message]);

        $request_id = (int) db()->lastInsertId();

        // Update resource status
        $stmt = db()->prepare("UPDATE resources SET status = 'Request Pending' WHERE id = ?");
        $stmt->execute([$resource_id]);

        // Notify owner
        create_notification(
            $resource['owner_id'],
            'New Resource Request',
            "{$requester['name']} ({$resource['block']} {$resource['door_number']}) is requesting your item: {$resource['item_name']}. Sharing type: {$resource['sharing_type']}.",
            'resource',
            $request_id
        );

        log_activity(current_user_id(), 'Resource Request', 'Resources', "Requested resource: {$resource['item_name']}.");
        echo json_encode(['success' => true, 'message' => 'Resource request sent successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Accept Resource Request ---
if ($action === 'accept_request' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    try {
        $stmt = db()->prepare("SELECT rr.*, r.item_name, r.owner_id FROM resource_requests rr JOIN resources r ON rr.resource_id = r.id WHERE rr.id = ?");
        $stmt->execute([$request_id]);
        $rr = $stmt->fetch();

        if (!$rr || $rr['owner_id'] !== current_user_id()) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }

        $stmt = db()->prepare("UPDATE resource_requests SET status = 'Accepted', approved_at = NOW() WHERE id = ?");
        $stmt->execute([$request_id]);

        // Update resource status based on sharing type
        $new_status = match ($rr['sharing_type'] ?? 'Borrow') {
            'Borrow'   => 'Borrowed',
            'Rent'     => 'Rented',
            'Sell'     => 'Sold',
            'Donate'   => 'Donated',
            'Exchange' => 'Exchanged',
            default    => 'Reserved',
        };

        $stmt = db()->prepare("UPDATE resources SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $rr['resource_id']]);

        // Notify requester
        create_notification(
            $rr['requester_id'],
            'Resource Request Accepted',
            "Your request for '{$rr['item_name']}' has been accepted. You can now coordinate with the owner.",
            'resource',
            $request_id
        );

        log_activity(current_user_id(), 'Resource Accepted', 'Resources', "Accepted request for: {$rr['item_name']}.");
        echo json_encode(['success' => true, 'message' => 'Resource request accepted.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Reject Resource Request ---
if ($action === 'reject_request' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    try {
        $stmt = db()->prepare("SELECT rr.*, r.item_name, r.owner_id FROM resource_requests rr JOIN resources r ON rr.resource_id = r.id WHERE rr.id = ?");
        $stmt->execute([$request_id]);
        $rr = $stmt->fetch();

        if (!$rr || $rr['owner_id'] !== current_user_id()) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }

        $stmt = db()->prepare("UPDATE resource_requests SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$request_id]);

        // Revert resource status if no other pending requests
        $stmt = db()->prepare("SELECT COUNT(*) FROM resource_requests WHERE resource_id = ? AND status = 'Pending'");
        $stmt->execute([$rr['resource_id']]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = db()->prepare("UPDATE resources SET status = 'Available' WHERE id = ?");
            $stmt->execute([$rr['resource_id']]);
        }

        // Notify requester
        create_notification(
            $rr['requester_id'],
            'Resource Request Rejected',
            "Your request for '{$rr['item_name']}' has been rejected by the owner.",
            'resource',
            $request_id
        );

        log_activity(current_user_id(), 'Resource Rejected', 'Resources', "Rejected request for: {$rr['item_name']}.");
        echo json_encode(['success' => true, 'message' => 'Resource request rejected.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Complete Resource Request ---
if ($action === 'complete_request' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    try {
        $stmt = db()->prepare("SELECT rr.*, r.item_name, r.owner_id FROM resource_requests rr JOIN resources r ON rr.resource_id = r.id WHERE rr.id = ?");
        $stmt->execute([$request_id]);
        $rr = $stmt->fetch();

        if (!$rr || $rr['owner_id'] !== current_user_id()) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }

        $stmt = db()->prepare("UPDATE resource_requests SET status = 'Completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$request_id]);

        // Notify requester
        create_notification(
            $rr['requester_id'],
            'Transaction Completed',
            "Your transaction for '{$rr['item_name']}' has been marked as completed.",
            'resource',
            $request_id
        );

        log_activity(current_user_id(), 'Resource Completed', 'Resources', "Completed transaction for: {$rr['item_name']}.");
        echo json_encode(['success' => true, 'message' => 'Transaction marked as completed.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}
