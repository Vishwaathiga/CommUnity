<?php
/**
 * CommUnity - Help Requests API
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

// --- POST: Request Help ---
if ($action === 'request' && $method === 'POST') {
    $skill_id = (int) ($_POST['skill_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $preferred_date = trim($_POST['preferred_date'] ?? '');
    $preferred_time = trim($_POST['preferred_time'] ?? '');

    if (empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Please describe your problem.']);
        exit;
    }

    try {
        $stmt = db()->prepare("SELECT * FROM skills WHERE id = ?");
        $stmt->execute([$skill_id]);
        $skill = $stmt->fetch();

        if (!$skill) {
            echo json_encode(['success' => false, 'message' => 'Skill not found.']);
            exit;
        }

        if ($skill['resident_id'] === current_user_id()) {
            echo json_encode(['success' => false, 'message' => 'You cannot request help for your own skill.']);
            exit;
        }

        $requester = get_user(current_user_id());

        $stmt = db()->prepare("INSERT INTO help_requests (requester_id, skill_id, provider_id, description, preferred_date, preferred_time, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            current_user_id(),
            $skill_id,
            $skill['resident_id'],
            $description,
            $preferred_date ?: null,
            $preferred_time ?: null,
            ($requester['block'] ?? '') . ' ' . ($requester['door_number'] ?? '')
        ]);

        $request_id = (int) db()->lastInsertId();

     $requesterLocation = trim(
    ($requester['block'] ?? '') . ' ' . ($requester['door_number'] ?? '')
);

$requesterPhone = $requester['phone'] ?? 'Not provided';

$requestMessage = "{$requester['name']} is requesting help with: {$skill['skill_name']}.
Problem: {$description}.
Location: {$requesterLocation}.
Phone: {$requesterPhone}.";

create_notification(
    $skill['resident_id'],
    'New Help Request',
    $requestMessage,
    'help',
    $request_id
);

        log_activity(current_user_id(), 'Help Request', 'Help', "Requested help for skill: {$skill['skill_name']}.");
        echo json_encode(['success' => true, 'message' => 'Help request submitted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Accept Help Request ---
if ($action === 'accept' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    try {
        $stmt = db()->prepare("SELECT * FROM help_requests WHERE id = ? AND provider_id = ?");
        $stmt->execute([$request_id, current_user_id()]);
        $hr = $stmt->fetch();

        if (!$hr) {
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }

        $stmt = db()->prepare("UPDATE help_requests SET status = 'Accepted', provider_id = ? WHERE id = ?");
        $stmt->execute([current_user_id(), $request_id]);

        $skill = get_skill_by_id($hr['skill_id']);
        create_notification(
            $hr['requester_id'],
            'Help Request Accepted',
            "Your help request for '{$skill['skill_name']}' has been accepted. The provider will contact you soon.",
            'help',
            $request_id
        );

        log_activity(current_user_id(), 'Help Accepted', 'Help', "Accepted help request ID $request_id.");
        echo json_encode(['success' => true, 'message' => 'Help request accepted.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Complete Help Request ---
if ($action === 'complete' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    try {
        $stmt = db()->prepare("UPDATE help_requests SET status = 'Completed' WHERE id = ? AND provider_id = ?");
        $stmt->execute([$request_id, current_user_id()]);

        if ($stmt->rowCount() > 0) {
            $stmt = db()->prepare("SELECT * FROM help_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $hr = $stmt->fetch();
            $skill = get_skill_by_id($hr['skill_id']);
            create_notification(
                $hr['requester_id'],
                'Help Request Completed',
                "Your help request for '{$skill['skill_name']}' has been marked as completed. Please rate the service.",
                'help',
                $request_id
            );

            log_activity(current_user_id(), 'Help Completed', 'Help', "Completed help request ID $request_id.");
            echo json_encode(['success' => true, 'message' => 'Help request marked as completed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not complete request.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Cancel Help Request ---
if ($action === 'cancel' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);

    try {
        $stmt = db()->prepare("UPDATE help_requests SET status = 'Cancelled' WHERE id = ? AND requester_id = ?");
        $stmt->execute([$request_id, current_user_id()]);

        if ($stmt->rowCount() > 0) {
            log_activity(current_user_id(), 'Help Cancelled', 'Help', "Cancelled help request ID $request_id.");
            echo json_encode(['success' => true, 'message' => 'Help request cancelled.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not cancel request.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Rate Help Request ---
if ($action === 'rate' && $method === 'POST') {
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
        exit;
    }

    try {
        $stmt = db()->prepare("UPDATE help_requests SET rating = ?, feedback = ? WHERE id = ? AND requester_id = ?");
        $stmt->execute([$rating, $feedback, $request_id, current_user_id()]);

        if ($stmt->rowCount() > 0) {
            log_activity(current_user_id(), 'Rated Help', 'Help', "Rated help request ID $request_id: $rating/5.");
            echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not rate this request.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

function get_skill_by_id(int $id): ?array {
    try {
        $stmt = db()->prepare("SELECT * FROM skills WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}
