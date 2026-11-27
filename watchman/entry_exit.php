<?php
/**
 * CommUnity - Watchman: Entry / Exit Terminal
 * PIN-based visitor arrival, notification, approval tracking, and exit recording.
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('watchman');

$pageTitle = 'Entry / Exit';
$activePage = 'entry_exit';
$role = 'watchman';

// Get visitor by ID (for direct link from dashboard)
$selected_visitor = null;
if (isset($_GET['visitor_id'])) {
    $vid = (int) $_GET['visitor_id'];
    try {
        $stmt = db()->prepare("SELECT v.*, u.name AS resident_name, u.block, u.door_number FROM visitors v JOIN users u ON v.resident_id = u.id WHERE v.id = ?");
        $stmt->execute([$vid]);
        $selected_visitor = $stmt->fetch();
    } catch (PDOException $e) {
        $selected_visitor = null;
    }
}

// Fetch visitors currently inside
try {
    $stmt = db()->query("
        SELECT v.*, u.name AS resident_name, u.block, u.door_number
        FROM visitors v JOIN users u ON v.resident_id = u.id
        WHERE v.status = 'Inside' ORDER BY v.entry_time DESC
    ");
    $visitors_inside = $stmt->fetchAll();
} catch (PDOException $e) {
    $visitors_inside = [];
}

// Fetch recent visitor history
try {
    $stmt = db()->query("
        SELECT v.*, u.name AS resident_name, u.block, u.door_number
        FROM visitors v JOIN users u ON v.resident_id = u.id
        WHERE v.status IN ('Approved', 'Rejected', 'Exited')
        ORDER BY v.updated_at DESC LIMIT 10
    ");
    $recent_history = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_history = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="grid-2">
    <!-- PIN Lookup -->
    <div class="card">
        <div class="card-header">
            <h3>Visitor Arrival</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-2">Enter the 6-digit visitor PIN to process arrival.</p>
            <div class="d-flex gap-2">
                <input type="text" class="form-control" id="visitorPinInput" placeholder="Enter 6-digit PIN" maxlength="6" style="font-size:1.2rem;letter-spacing:4px;text-align:center;">
                <button class="btn btn-primary" id="pinSearchBtn">Verify PIN</button>
            </div>
            <div id="pinResult">
                <?php if ($selected_visitor): ?>
                    <div class="card mt-2">
                        <div class="card-body">
                            <div class="d-flex justify-between align-center mb-2">
                                <h3><?= e($selected_visitor['visitor_name']) ?></h3>
                                <?= visitor_status_badge($selected_visitor['status']) ?>
                            </div>
                            <div class="form-row">
                                <div><strong>Resident:</strong> <?= e($selected_visitor['resident_name']) ?></div>
                                <div><strong>Block:</strong> <?= e($selected_visitor['block']) ?> | <strong>Door:</strong> <?= e($selected_visitor['door_number']) ?></div>
                                <div><strong>Purpose:</strong> <?= e($selected_visitor['purpose'] ?? '—') ?></div>
                                <div><strong>Type:</strong> <?= e($selected_visitor['visitor_type']) ?></div>
                                <div><strong>Expected:</strong> <?= format_date($selected_visitor['expected_date']) ?> <?= format_time($selected_visitor['expected_time']) ?></div>
                            </div>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <?php if ($selected_visitor['status'] === 'Expected'): ?>
                                    <button class="btn btn-success" onclick="markArrived(<?= $selected_visitor['id'] ?>)">Visitor Arrived</button>
                                <?php endif; ?>
                                <?php if ($selected_visitor['status'] === 'Arrived'): ?>
                                    <button class="btn btn-primary" onclick="notifyResident(<?= $selected_visitor['id'] ?>)">Notify Resident</button>
                                <?php endif; ?>
                                <?php if ($selected_visitor['status'] === 'Approved'): ?>
                                    <button class="btn btn-success" onclick="recordEntry(<?= $selected_visitor['id'] ?>)">Allow Entry</button>
                                <?php endif; ?>
                                <?php if ($selected_visitor['status'] === 'Inside'): ?>
                                    <button class="btn btn-danger" onclick="recordExit(<?= $selected_visitor['id'] ?>)">Record Exit</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Visitors Currently Inside -->
    <div class="card">
        <div class="card-header">
            <h3>Visitors Currently Inside</h3>
        </div>
        <div class="card-body">
            <?php if (empty($visitors_inside)): ?>
                <div class="empty-state"><p>No visitors currently inside.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Resident</th>
                                <th>Entry Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitors_inside as $v): ?>
                                <tr>
                                    <td><strong><?= e($v['visitor_name']) ?></strong></td>
                                    <td><?= e($v['resident_name']) ?></td>
                                    <td><?= format_datetime($v['entry_time']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="recordExit(<?= $v['id'] ?>)">Record Exit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent History -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Recent Visitor History</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recent_history)): ?>
            <div class="empty-state"><p>No recent history.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Entry</th>
                            <th>Exit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_history as $v): ?>
                            <tr>
                                <td><?= e($v['visitor_name']) ?></td>
                                <td><?= e($v['resident_name']) ?></td>
                                <td><?= e($v['visitor_type']) ?></td>
                                <td><?= visitor_status_badge($v['status']) ?></td>
                                <td><?= format_datetime($v['entry_time']) ?></td>
                                <td><?= format_datetime($v['exit_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
