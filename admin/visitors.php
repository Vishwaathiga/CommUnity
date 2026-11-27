<?php
/**
 * CommUnity - Admin: Visitors Overview
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Visitors';
$activePage = 'visitors';
$role = 'admin';

// Fetch all visitors with resident info
try {
    $stmt = db()->query("
        SELECT v.*, u.name AS resident_name, u.block, u.door_number
        FROM visitors v JOIN users u ON v.resident_id = u.id
        ORDER BY v.created_at DESC
    ");
    $visitors = $stmt->fetchAll();
} catch (PDOException $e) {
    $visitors = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>All Visitors</h3>
    </div>
    <div class="card-body">
        <?php if (empty($visitors)): ?>
            <div class="empty-state"><p>No visitors found.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Visitor</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Resident</th>
                            <th>Block/Door</th>
                            <th>Expected</th>
                            <th>Status</th>
                            <th>PIN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitors as $v): ?>
                            <tr>
                                <td><strong><?= e($v['visitor_name']) ?></strong></td>
                                <td><?= e($v['visitor_phone']) ?></td>
                                <td><?= e($v['visitor_type']) ?></td>
                                <td><?= e($v['resident_name']) ?></td>
                                <td><?= e($v['block'] . ' ' . $v['door_number']) ?></td>
                                <td><?= format_date($v['expected_date']) ?></td>
                                <td><?= visitor_status_badge($v['status']) ?></td>
                                <td><code><?= e($v['pin']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
