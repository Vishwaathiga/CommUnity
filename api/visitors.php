<?php
/**
 * CommUnity - Visitors API
 * PIN lookup, arrival, notification, approve/reject, entry/exit.
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

// --- GET: PIN Lookup ---
if ($action === 'lookup' && $method === 'GET') {
    $pin = trim($_GET['pin'] ?? '');

    try {
        $stmt = db()->prepare("
            SELECT v.*, u.name AS resident_name, u.block, u.door_number
            FROM visitors v
            JOIN users u ON v.resident_id = u.id
            WHERE v.pin = ?
        ");
        $stmt->execute([$pin]);
        $visitor = $stmt->fetch();

        if (!$visitor) {
            echo json_encode(['success' => false, 'message' => 'Visitor PIN is invalid or expired.']);
            exit;
        }

        // Check PIN expiry
        if (strtotime($visitor['pin_expiry']) < time()) {
            echo json_encode(['success' => false, 'message' => 'Visitor PIN has expired.']);
            exit;
        }

        // Check if already exited
        if ($visitor['status'] === 'Exited') {
            echo json_encode(['success' => false, 'message' => 'This visitor has already exited.']);
            exit;
        }

        echo json_encode(['success' => true, 'visitor' => $visitor]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Mark Arrived ---
if ($action === 'arrived' && $method === 'POST') {
    require_role('watchman');
    $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

    try {
        $stmt = db()->prepare("UPDATE visitors SET status = 'Arrived', arrival_time = NOW() WHERE id = ? AND status = 'Expected'");
        $stmt->execute([$visitor_id]);

        if ($stmt->rowCount() > 0) {
            $v = get_visitors_by_id($visitor_id);
            create_notification(
                $v['resident_id'],
                'Visitor Arrived',
                "Your visitor, {$v['visitor_name']}, has arrived at the entrance and is waiting to meet you.",
                'visitor',
                $visitor_id
            );
            log_activity(current_user_id(), 'Visitor Arrived', 'Visitors', "Visitor ID $visitor_id marked as arrived.");
            echo json_encode(['success' => true, 'message' => 'Visitor marked as arrived. Resident has been notified.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not update visitor status.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Notify Resident ---
if ($action === 'notify' && $method === 'POST') {
    require_role('watchman');
    $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

    try {
        $v = get_visitors_by_id($visitor_id);
        if (!$v) {
            echo json_encode(['success' => false, 'message' => 'Visitor not found.']);
            exit;
        }

        create_notification(
            $v['resident_id'],
            'Visitor Arrived',
            "Your visitor, {$v['visitor_name']}, has arrived at the entrance and is waiting to meet you.",
            'visitor',
            $visitor_id
        );

        log_activity(current_user_id(), 'Visitor Notified', 'Visitors', "Notified resident about visitor {$v['visitor_name']}.");
        echo json_encode(['success' => true, 'message' => 'Visitor notification sent successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Approve Entry ---
if ($action === 'approve' && $method === 'POST') {
    require_role('resident');
    $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

    try {
        // Verify the visitor belongs to this resident
        $stmt = db()->prepare("SELECT * FROM visitors WHERE id = ? AND resident_id = ?");
        $stmt->execute([$visitor_id, current_user_id()]);
        $v = $stmt->fetch();

        if (!$v) {
            echo json_encode(['success' => false, 'message' => 'Visitor not found.']);
            exit;
        }

        if ($v['status'] !== 'Arrived' && $v['status'] !== 'Expected') {
            echo json_encode(['success' => false, 'message' => 'Visitor cannot be approved in current status.']);
            exit;
        }

        $stmt = db()->prepare("UPDATE visitors SET approval_status = 'Approved', status = 'Approved', entry_time = NOW() WHERE id = ?");
        $stmt->execute([$visitor_id]);

        // Notify watchmen
        notify_all_watchmen(
            'Visitor Entry Approved',
            "Resident {$v['resident_name']} has approved entry for visitor {$v['visitor_name']}.",
            'visitor',
            $visitor_id
        );

        log_activity(current_user_id(), 'Visitor Approved', 'Visitors', "Approved visitor {$v['visitor_name']}.");
        echo json_encode(['success' => true, 'message' => 'Visitor entry approved.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Reject Entry ---
if ($action === 'reject' && $method === 'POST') {
    require_role('resident');
    $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

    try {
        $stmt = db()->prepare("SELECT * FROM visitors WHERE id = ? AND resident_id = ?");
        $stmt->execute([$visitor_id, current_user_id()]);
        $v = $stmt->fetch();

        if (!$v) {
            echo json_encode(['success' => false, 'message' => 'Visitor not found.']);
            exit;
        }

        $stmt = db()->prepare("UPDATE visitors SET approval_status = 'Rejected', status = 'Rejected' WHERE id = ?");
        $stmt->execute([$visitor_id]);

        notify_all_watchmen(
            'Visitor Entry Rejected',
            "Resident {$v['resident_name']} has rejected entry for visitor {$v['visitor_name']}.",
            'visitor',
            $visitor_id
        );

        log_activity(current_user_id(), 'Visitor Rejected', 'Visitors', "Rejected visitor {$v['visitor_name']}.");
        echo json_encode(['success' => true, 'message' => 'Visitor entry rejected.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Record Exit ---
if ($action === 'exit' && $method === 'POST') {
    require_role('watchman');
    $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

    try {
        $stmt = db()->prepare("UPDATE visitors SET status = 'Exited', exit_time = NOW() WHERE id = ? AND status = 'Inside'");
        $stmt->execute([$visitor_id]);

        if ($stmt->rowCount() > 0) {
            log_activity(current_user_id(), 'Visitor Exit', 'Visitors', "Visitor ID $visitor_id recorded exit.");
            echo json_encode(['success' => true, 'message' => 'Visitor exit recorded.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Visitor is not currently inside.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Record Entry (when approved) ---
if ($action === 'entry' && $method === 'POST') {
    require_role('watchman');
    $visitor_id = (int) ($_POST['visitor_id'] ?? 0);

    try {
        $stmt = db()->prepare("UPDATE visitors SET status = 'Inside', entry_time = NOW() WHERE id = ? AND status = 'Approved'");
        $stmt->execute([$visitor_id]);

        if ($stmt->rowCount() > 0) {
            log_activity(current_user_id(), 'Visitor Entry', 'Visitors', "Visitor ID $visitor_id entered.");
            echo json_encode(['success' => true, 'message' => 'Visitor entry recorded.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Visitor is not approved for entry.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// Helper functions
function get_visitors_by_id(int $id): ?array {
    try {
        $stmt = db()->prepare("SELECT v.*, u.name AS resident_name FROM visitors v JOIN users u ON v.resident_id = u.id WHERE v.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function notify_all_watchmen(string $title, string $message, string $type, ?int $ref_id = null): void {
    try {
        $stmt = db()->query("SELECT id FROM users WHERE role = 'watchman'");
        $watchmen = $stmt->fetchAll();
        foreach ($watchmen as $w) {
            create_notification($w['id'], $title, $message, $type, $ref_id);
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}
