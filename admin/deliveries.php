<?php
/**
 * CommUnity - Admin: Deliveries Overview
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Deliveries';
$activePage = 'deliveries';
$role = 'admin';

try {
    $stmt = db()->query("
        SELECT d.*, u.name AS resident_name, u.block, u.door_number
        FROM deliveries d JOIN users u ON d.resident_id = u.id
        ORDER BY d.created_at DESC
    ");
    $deliveries = $stmt->fetchAll();
} catch (PDOException $e) {
    $deliveries = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>All Deliveries</h3>
    </div>
    <div class="card-body">
        <?php if (empty($deliveries)): ?>
            <div class="empty-state"><p>No deliveries found.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Delivery Person</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Resident</th>
                            <th>Arrival Time</th>
                            <th>Status</th>
                            <th>Notification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $d): ?>
                            <tr>
                                <td><strong><?= e($d['delivery_person_name']) ?></strong></td>
                                <td><?= e($d['company_name']) ?></td>
                                <td><?= e($d['delivery_type']) ?></td>
                                <td><?= e($d['resident_name'] . ' (' . $d['block'] . ' ' . $d['door_number'] . ')') ?></td>
                                <td><?= format_datetime($d['arrival_time']) ?></td>
                                <td><?= delivery_status_badge($d['status']) ?></td>
                                <td><?= $d['notification_sent'] ? '<span class="badge badge-success">Sent</span>' : '<span class="badge badge-secondary">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
