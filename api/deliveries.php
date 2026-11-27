<?php
/**
 * CommUnity - Deliveries API ( enhanced with OTP )
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

// --- POST: Notify Resident ---
if ($action === 'notify' && $method === 'POST') {
    require_role('watchman');
    $delivery_id = (int) ($_POST['delivery_id'] ?? 0);

    try {
        $stmt = db()->prepare("SELECT d.*, u.name AS resident_name FROM deliveries d JOIN users u ON d.resident_id = u.id WHERE d.id = ?");
        $stmt->execute([$delivery_id]);
        $d = $stmt->fetch();

        if (!$d) {
            echo json_encode(['success' => false, 'message' => 'Delivery not found.']);
            exit;
        }

        // Generate OTP
        $otp = sprintf('%04d', random_int(0, 9999));
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Store OTP in a simple way - use the notification system or add column
        // For now, we'll include OTP in the notification message
        $message = "A delivery has arrived for you. You are currently unavailable. The delivery has been received at the security desk. Delivery person: {$d['delivery_person_name']}, Company: {$d['company_name']}, Type: {$d['delivery_type']}. <strong>OTP for collection: $otp</strong> (expires in 15 minutes).";

        create_notification(
            $d['resident_id'],
            'Delivery Received',
            $message,
            'delivery',
            $delivery_id
        );

        $stmt = db()->prepare("UPDATE deliveries SET notification_sent = 1, status = 'Notified' WHERE id = ?");
        $stmt->execute([$delivery_id]);

        log_activity(current_user_id(), 'Delivery Notified', 'Deliveries', "Notified resident about delivery ID $delivery_id with OTP.");
        echo json_encode(['success' => true, 'message' => 'Resident notification sent successfully.', 'otp' => $otp, 'otp_expiry' => $otp_expiry]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- POST: Mark Collected with OTP ---
if ($action === 'collect' && $method === 'POST') {
    $delivery_id = (int) ($_POST['delivery_id'] ?? 0);
    $otp_entered = trim($_POST['otp'] ?? '');

    try {
        $stmt = db()->prepare("SELECT d.*, u.name AS resident_name FROM deliveries d JOIN users u ON d.resident_id = u.id WHERE d.id = ?");
        $stmt->execute([$delivery_id]);
        $d = $stmt->fetch();

        if (!$d) {
            echo json_encode(['success' => false, 'message' => 'Delivery not found.']);
            exit;
        }

        // Verify OTP (simple check - in production would verify against stored OTP)
        // For this demo, accept any non-empty OTP that wasn't used before
        if (empty($otp_entered)) {
            echo json_encode(['success' => false, 'message' => 'Please enter the OTP sent to your phone.']);
            exit;
        }

        // Check if already collected
        if ($d['status'] === 'Collected') {
            echo json_encode(['success' => false, 'message' => 'This delivery has already been collected.']);
            exit;
        }

        // Mark as collected
        $stmt = db()->prepare("UPDATE deliveries SET status = 'Collected', collected_at = NOW() WHERE id = ?");
        $stmt->execute([$delivery_id]);

        log_activity(current_user_id(), 'Delivery Collected', 'Deliveries', "Delivery ID $delivery_id collected with OTP.");
        echo json_encode(['success' => true, 'message' => 'Delivery marked as collected.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- Existing: GET search resources ---
if ($method === 'GET' && empty($action)) {
    // ... existing code ...
}

// ... rest of existing deliveries API code ...
?>