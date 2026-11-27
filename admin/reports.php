<?php
/**
 * CommUnity - Admin: Reports
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Reports';
$activePage = 'reports';
$role = 'admin';

// Fetch report data
try {
    // Visitors by type
    $stmt = db()->query("SELECT visitor_type, COUNT(*) as count FROM visitors GROUP BY visitor_type");
    $visitors_by_type = $stmt->fetchAll();

    // Visitors by status
    $stmt = db()->query("SELECT status, COUNT(*) as count FROM visitors GROUP BY status");
    $visitors_by_status = $stmt->fetchAll();

    // Deliveries by type
    $stmt = db()->query("SELECT delivery_type, COUNT(*) as count FROM deliveries GROUP BY delivery_type");
    $deliveries_by_type = $stmt->fetchAll();

    // Deliveries by status
    $stmt = db()->query("SELECT status, COUNT(*) as count FROM deliveries GROUP BY status");
    $deliveries_by_status = $stmt->fetchAll();

    // Residents by block
    $stmt = db()->query("SELECT block, COUNT(*) as count FROM users WHERE role = 'resident' GROUP BY block");
    $residents_by_block = $stmt->fetchAll();

    // Help requests by status
    $stmt = db()->query("SELECT status, COUNT(*) as count FROM help_requests GROUP BY status");
    $help_by_status = $stmt->fetchAll();

    // Resources by sharing type
    $stmt = db()->query("SELECT sharing_type, COUNT(*) as count FROM resources GROUP BY sharing_type");
    $resources_by_type = $stmt->fetchAll();

    // Skills by category
    $stmt = db()->query("SELECT category, COUNT(*) as count FROM skills GROUP BY category");
    $skills_by_category = $stmt->fetchAll();

    // Recent visitors (last 7 days)
    $stmt = db()->query("SELECT COUNT(*) as count FROM visitors WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $visitors_last_7_days = $stmt->fetchColumn();

    // Recent deliveries (last 7 days)
    $stmt = db()->query("SELECT COUNT(*) as count FROM deliveries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $deliveries_last_7_days = $stmt->fetchColumn();

} catch (PDOException $e) {
    $visitors_by_type = $visitors_by_status = $deliveries_by_type = [];
    $deliveries_by_status = $residents_by_block = $help_by_status = [];
    $resources_by_type = $skills_by_category = [];
    $visitors_last_7_days = $deliveries_last_7_days = 0;
}

require __DIR__ . '/../includes/top.php';
?>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Visitors by Type</h3></div>
        <div class="card-body">
            <?php if (empty($visitors_by_type)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Type</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($visitors_by_type as $v): ?>
                                <tr><td><?= e($v['visitor_type']) ?></td><td><?= $v['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Visitors by Status</h3></div>
        <div class="card-body">
            <?php if (empty($visitors_by_status)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($visitors_by_status as $v): ?>
                                <tr><td><?= visitor_status_badge($v['status']) ?></td><td><?= $v['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Deliveries by Type</h3></div>
        <div class="card-body">
            <?php if (empty($deliveries_by_type)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Type</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($deliveries_by_type as $d): ?>
                                <tr><td><?= e($d['delivery_type']) ?></td><td><?= $d['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Deliveries by Status</h3></div>
        <div class="card-body">
            <?php if (empty($deliveries_by_status)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($deliveries_by_status as $d): ?>
                                <tr><td><?= delivery_status_badge($d['status']) ?></td><td><?= $d['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Residents by Block</h3></div>
        <div class="card-body">
            <?php if (empty($residents_by_block)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Block</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($residents_by_block as $r): ?>
                                <tr><td><?= e($r['block']) ?></td><td><?= $r['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Help Requests by Status</h3></div>
        <div class="card-body">
            <?php if (empty($help_by_status)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($help_by_status as $h): ?>
                                <tr><td><?= help_status_badge($h['status']) ?></td><td><?= $h['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Resources by Sharing Type</h3></div>
        <div class="card-body">
            <?php if (empty($resources_by_type)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Sharing Type</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($resources_by_type as $r): ?>
                                <tr><td><?= e($r['sharing_type']) ?></td><td><?= $r['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Skills by Category</h3></div>
        <div class="card-body">
            <?php if (empty($skills_by_category)): ?>
                <div class="empty-state"><p>No data.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead><tr><th>Category</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($skills_by_category as $s): ?>
                                <tr><td><?= e($s['category']) ?></td><td><?= $s['count'] ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
