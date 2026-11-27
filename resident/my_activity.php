<?php
/**
 * CommUnity - Resident: My Activity
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'My Activity';
$activePage = 'my_activity';
$role = 'resident';

// Fetch my activity logs
try {
    $stmt = db()->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([current_user_id()]);
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    $activities = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>My Activity Log</h3>
    </div>
    <div class="card-body">
        <?php if (empty($activities)): ?>
            <div class="empty-state">
                <p>No activity recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $a): ?>
                            <tr>
                                <td><?= e($a['action']) ?></td>
                                <td><span class="badge badge-secondary"><?= e($a['module']) ?></span></td>
                                <td><?= e($a['description'] ?? '') ?></td>
                                <td><?= format_datetime($a['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
