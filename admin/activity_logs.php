<?php
/**
 * CommUnity - Admin: Activity Logs
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Activity Logs';
$activePage = 'logs';
$role = 'admin';

// Pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $total = db()->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
    $stmt = db()->prepare("SELECT al.*, u.name AS user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$per_page, $offset]);
    $logs = $stmt->fetchAll();
    $total_pages = (int) ceil($total / $per_page);
} catch (PDOException $e) {
    $logs = [];
    $total_pages = 1;
}

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Activity Logs</h3>
        <span class="text-muted"><?= $total ?> total entries</span>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="empty-state"><p>No activity logs found.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><strong><?= e($l['user_name'] ?? 'System') ?></strong></td>
                                <td><?= e($l['action']) ?></td>
                                <td><span class="badge badge-secondary"><?= e($l['module']) ?></span></td>
                                <td><?= e($l['description'] ?? '') ?></td>
                                <td><?= format_datetime($l['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-center gap-2 mt-3">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline">&larr; Previous</a>
                    <?php endif; ?>
                    <span class="btn btn-sm btn-primary">Page <?= $page ?> of <?= $total_pages ?></span>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline">Next &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
