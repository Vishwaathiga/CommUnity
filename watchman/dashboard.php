<?php
/**
 * CommUnity - Watchman Dashboard
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('watchman');

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$role = 'watchman';

// Fetch stats
try {
    $total_residents = db()->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
    $visitors_today = db()->query("SELECT COUNT(*) FROM visitors WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $visitors_inside = db()->query("SELECT COUNT(*) FROM visitors WHERE status = 'Inside'")->fetchColumn();
    $deliveries_today = db()->query("SELECT COUNT(*) FROM deliveries WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $pending_approvals = db()->query("SELECT COUNT(*) FROM visitors WHERE status = 'Arrived'")->fetchColumn();
} catch (PDOException $e) {
    $total_residents = $visitors_today = $visitors_inside = $deliveries_today = $pending_approvals = 0;
}

// Recent activities
try {
    $stmt = db()->query("
        SELECT al.*, u.name AS user_name FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.module IN ('Visitors', 'Deliveries')
        ORDER BY al.created_at DESC LIMIT 8
    ");
    $recent_activity = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_activity = [];
}

// Pending visitor approvals
try {
    $stmt = db()->query("
        SELECT v.*, u.name AS resident_name, u.block, u.door_number
        FROM visitors v JOIN users u ON v.resident_id = u.id
        WHERE v.status = 'Arrived' ORDER BY v.arrival_time DESC
    ");
    $pending_visitors = $stmt->fetchAll();
} catch (PDOException $e) {
    $pending_visitors = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $total_residents ?></h4>
            <p>Total Residents</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11h6M20 8v6"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $visitors_today ?></h4>
            <p>Visitors Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $visitors_inside ?></h4>
            <p>Currently Inside</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $deliveries_today ?></h4>
            <p>Deliveries Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $pending_approvals ?></h4>
            <p>Pending Approvals</p>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Recent Activity</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_activity)): ?>
                <div class="empty-state"><p>No recent activity.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Activity</th>
                                <th>Resident</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $a): ?>
                                <tr>
                                    <td><?= format_datetime($a['created_at']) ?></td>
                                    <td><?= e($a['action']) ?></td>
                                    <td><?= e($a['user_name'] ?? '—') ?></td>
                                    <td><span class="badge badge-secondary"><?= e($a['module']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Pending Visitor Approvals</h3>
            <a href="<?= e(BASE_URL) ?>watchman/entry_exit.php" class="btn btn-sm btn-outline">Entry/Exit</a>
        </div>
        <div class="card-body">
            <?php if (empty($pending_visitors)): ?>
                <div class="empty-state"><p>No visitors waiting for approval.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Resident</th>
                                <th>Arrival</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_visitors as $v): ?>
                                <tr>
                                    <td><strong><?= e($v['visitor_name']) ?></strong></td>
                                    <td><?= e($v['resident_name']) ?></td>
                                    <td><?= format_datetime($v['arrival_time']) ?></td>
                                    <td>
                                        <a href="<?= e(BASE_URL) ?>watchman/entry_exit.php?visitor_id=<?= $v['id'] ?>" class="btn btn-sm btn-primary">Process</a>
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

<?php require __DIR__ . '/../includes/bottom.php'; ?>
