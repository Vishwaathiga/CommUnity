<?php
/**
 * CommUnity - Resident: Visitor Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Visitors';
$activePage = 'visitors';
$role = 'resident';

// Handle add visitor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visitor'])) {
    $visitor_name = trim($_POST['visitor_name'] ?? '');
    $visitor_phone = trim($_POST['visitor_phone'] ?? '');
    $visitor_type = $_POST['visitor_type'] ?? 'Guest';
    $purpose = trim($_POST['purpose'] ?? '');
    $expected_date = $_POST['expected_date'] ?? '';
    $expected_time = $_POST['expected_time'] ?? '';

    if ($visitor_name && $visitor_phone && $expected_date) {
        try {
            $pin = generate_pin();
            $pin_expiry = date('Y-m-d H:i:s', strtotime('+' . VISITOR_PIN_EXPIRY_HOURS . ' hours'));

            $stmt = db()->prepare("INSERT INTO visitors (resident_id, visitor_name, visitor_phone, visitor_type, purpose, expected_date, expected_time, pin, pin_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([current_user_id(), $visitor_name, $visitor_phone, $visitor_type, $purpose, $expected_date, $expected_time, $pin, $pin_expiry]);

            $visitor_id = (int) db()->lastInsertId();
            log_activity(current_user_id(), 'Created Visitor', 'Visitors', "Created visitor: $visitor_name, PIN: $pin.");
            set_flash('success', "Visitor added successfully. PIN: $pin");
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to add visitor.');
        }
    } else {
        set_flash('danger', 'Please fill all required fields.');
    }
    header("Location: " . BASE_URL . "resident/visitors.php");
    exit;
}

// Fetch my visitors
try {
    $stmt = db()->prepare("SELECT * FROM visitors WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([current_user_id()]);
    $visitors = $stmt->fetchAll();
} catch (PDOException $e) {
    $visitors = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="grid-2">
    <!-- Add Visitor -->
    <div class="card">
        <div class="card-header">
            <h3>Create Visitor Request</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Visitor Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="visitor_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Visitor Phone <span class="required">*</span></label>
                        <input type="text" class="form-control" name="visitor_phone" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Visitor Type</label>
                        <select class="form-control" name="visitor_type">
                            <option value="Guest">Guest</option>
                            <option value="Delivery">Delivery</option>
                            <option value="Service Personnel">Service Personnel</option>
                            <option value="Cab / Driver">Cab / Driver</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Purpose</label>
                        <input type="text" class="form-control" name="purpose" placeholder="e.g. Family visit">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Expected Date <span class="required">*</span></label>
                        <input type="date" class="form-control" name="expected_date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Time</label>
                        <input type="time" class="form-control" name="expected_time">
                    </div>
                </div>
                <button type="submit" name="add_visitor" class="btn btn-primary">Generate Visitor PIN</button>
            </form>
        </div>
    </div>

    <!-- My Visitors List -->
    <div class="card">
        <div class="card-header">
            <h3>My Visitors</h3>
        </div>
        <div class="card-body">
            <?php if (empty($visitors)): ?>
                <div class="empty-state"><p>No visitors yet.</p></div>
            <?php else: ?>
                <?php foreach ($visitors as $v): ?>
                    <div class="notification-item">
                        <div class="notification-icon visitor">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
                        </div>
                        <div class="notification-content">
                            <div class="d-flex justify-between align-center">
                                <h5><?= e($v['visitor_name']) ?></h5>
                                <?= visitor_status_badge($v['status']) ?>
                            </div>
                            <p><?= e($v['visitor_type']) ?> | <?= format_date($v['expected_date']) ?></p>
                            <?php if ($v['status'] === 'Expected' || $v['status'] === 'Arrived'): ?>
                                <div class="pin-display" style="padding:12px;margin:8px 0;">
                                    <div class="pin-code" style="font-size:1.5rem;"><?= e($v['pin']) ?></div>
                                    <div class="pin-label">Visitor PIN</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($v['status'] === 'Arrived'): ?>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-success" onclick="approveVisitor(<?= $v['id'] ?>)">Approve Entry</button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectVisitor(<?= $v['id'] ?>)">Reject Entry</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
