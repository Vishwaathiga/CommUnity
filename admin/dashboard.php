<?php
/**
 * CommUnity - Admin Dashboard
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$role = 'admin';

// Fetch stats
try {
    $total_residents = db()->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
    $total_watchmen = db()->query("SELECT COUNT(*) FROM users WHERE role = 'watchman'")->fetchColumn();
    $total_visitors_today = db()->query("SELECT COUNT(*) FROM visitors WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $total_deliveries_today = db()->query("SELECT COUNT(*) FROM deliveries WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $total_skills = db()->query("SELECT COUNT(*) FROM skills")->fetchColumn();
    $total_resources = db()->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $pending_visitors = db()->query("SELECT COUNT(*) FROM visitors WHERE status = 'Expected'")->fetchColumn();
} catch (PDOException $e) {
    $total_residents = $total_watchmen = $total_visitors_today = 0;
    $total_deliveries_today = $total_skills = $total_resources = $pending_visitors = 0;
}

// Recent activity
try {
    $stmt = db()->query("SELECT al.*, u.name AS user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");
    $recent_activity = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_activity = [];
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
            <h4><?= $total_visitors_today ?></h4>
            <p>Visitors Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $total_deliveries_today ?></h4>
            <p>Deliveries Today</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $total_skills ?></h4>
            <p>Skills Offered</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $total_resources ?></h4>
            <p>Resources Shared</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $total_watchmen ?></h4>
            <p>Watchmen</p>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Recent Activity</h3>
            <a href="<?= e(BASE_URL) ?>admin/activity_logs.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_activity)): ?>
                <div class="empty-state">
                    <p>No recent activity.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activity as $a): ?>
                                <tr>
                                    <td><?= e($a['user_name'] ?? 'System') ?></td>
                                    <td><?= e($a['action']) ?></td>
                                    <td><span class="badge badge-secondary"><?= e($a['module']) ?></span></td>
                                    <td><?= format_datetime($a['created_at']) ?></td>
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
            <h3>Pending Visitor Requests</h3>
            <a href="<?= e(BASE_URL) ?>admin/visitors.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php
            try {
                $stmt = db()->query("SELECT v.*, u.name AS resident_name, u.block, u.door_number FROM visitors v JOIN users u ON v.resident_id = u.id WHERE v.status = 'Expected' ORDER BY v.created_at DESC LIMIT 5");
                $pending = $stmt->fetchAll();
            } catch (PDOException $e) {
                $pending = [];
            }
            ?>
            <?php if (empty($pending)): ?>
                <div class="empty-state">
                    <p>No pending visitor requests.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Resident</th>
                                <th>Expected</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $v): ?>
                                <tr>
                                    <td><?= e($v['visitor_name']) ?></td>
                                    <td><?= e($v['resident_name']) ?></td>
                                    <td><?= format_date($v['expected_date']) ?></td>
                                    <td><?= visitor_status_badge($v['status']) ?></td>
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
