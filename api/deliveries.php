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

// --- POST: Notify Resident with Collection OTP ---
if ($action === 'notify' && $method === 'POST') {
    require_role('watchman');

    $delivery_id = (int) ($_POST['delivery_id'] ?? 0);

    try {
        $stmt = db()->prepare("
            SELECT d.*, u.name AS resident_name
            FROM deliveries d
            JOIN users u ON d.resident_id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$delivery_id]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$d) {
            echo json_encode(['success' => false, 'message' => 'Delivery not found.']);
            exit;
        }

        if ($d['status'] === 'Collected') {
            echo json_encode(['success' => false, 'message' => 'This delivery has already been collected.']);
            exit;
        }

        // Generate a four-digit OTP and store only its hash
        $otp = sprintf('%04d', random_int(0, 9999));
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $message = "A delivery has arrived for you and is being kept at the security desk.\n"
            . "Delivery Person: {$d['delivery_person_name']}\n"
            . "Company: {$d['company_name']}\n"
            . "Delivery Type: {$d['delivery_type']}\n"
            . "Collection OTP: {$otp}\n"
            . "This OTP expires in 15 minutes. Please share it with the watchman when collecting your delivery.";

        // Save OTP hash and expiry
        $stmt = db()->prepare("
            UPDATE deliveries
            SET otp_hash = ?, otp_expires_at = ?, notification_sent = 1, status = 'Notified'
            WHERE id = ?
        ");
        $stmt->execute([$otp_hash, $otp_expiry, $delivery_id]);

        // Send OTP through the resident notification system
        create_notification(
            $d['resident_id'],
            'Delivery Received',
            $message,
            'delivery',
            $delivery_id
        );

        log_activity(
            current_user_id(),
            'Delivery OTP Sent',
            'Deliveries',
            "Sent collection OTP for delivery ID $delivery_id."
        );

        echo json_encode([
            'success' => true,
            'message' => 'Collection OTP sent to the resident notification section.'
        ]);

    } catch (Throwable $e) {
        error_log('Delivery notification error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to send collection OTP.']);
    }

    exit;
}

// --- POST: Mark Collected with OTP Verification ---
if ($action === 'collect' && $method === 'POST') {
    require_role('watchman');

    $delivery_id = (int) ($_POST['delivery_id'] ?? 0);
    $otp_entered = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{4}$/', $otp_entered)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid four-digit OTP.'
        ]);
        exit;
    }

    try {
        $stmt = db()->prepare("
            SELECT *
            FROM deliveries
            WHERE id = ?
        ");
        $stmt->execute([$delivery_id]);
        $d = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$d) {
            echo json_encode(['success' => false, 'message' => 'Delivery not found.']);
            exit;
        }

        if ($d['status'] === 'Collected') {
            echo json_encode(['success' => false, 'message' => 'This delivery has already been collected.']);
            exit;
        }

        if (empty($d['otp_hash']) || empty($d['otp_expires_at'])) {
            echo json_encode([
                'success' => false,
                'message' => 'No collection OTP has been generated. Send the OTP to the resident first.'
            ]);
            exit;
        }

        if (strtotime($d['otp_expires_at']) < time()) {
            echo json_encode([
                'success' => false,
                'message' => 'The OTP has expired. Please send a new OTP to the resident.'
            ]);
            exit;
        }

        if (!password_verify($otp_entered, $d['otp_hash'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Incorrect OTP. Please check with the resident and try again.'
            ]);
            exit;
        }

        // OTP is correct: mark delivery as collected and clear OTP
        $stmt = db()->prepare("
            UPDATE deliveries
            SET status = 'Collected',
                collected_at = NOW(),
                otp_hash = NULL,
                otp_expires_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$delivery_id]);

        log_activity(
            current_user_id(),
            'Delivery Collected',
            'Deliveries',
            "Delivery ID $delivery_id collected after OTP verification."
        );

        echo json_encode([
            'success' => true,
            'message' => 'OTP verified. Delivery marked as collected successfully.'
        ]);

    } catch (Throwable $e) {
        error_log('Delivery collection error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to process delivery collection.']);
    }

    exit;
}

// --- Existing: GET search resources ---
if ($method === 'GET' && empty($action)) {
    // ... existing code ...
}

// ... rest of existing deliveries API code ...
?>