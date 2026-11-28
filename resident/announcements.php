
<?php
/**
 * CommUnity - Resident Announcements
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Announcements';
$activePage = 'announcements';
$role = 'resident';

$stmt = db()->prepare("
    SELECT a.*, u.name AS admin_name
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    WHERE a.status = 'Published'
    ORDER BY a.published_at DESC, a.created_at DESC
");

$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/top.php';
?>

<div class="page-header">
    <div>
        <h2>Community Announcements</h2>
        <p>Stay updated with the latest news and information from your community.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Published Announcements</h3>
    </div>

    <div class="card-body">

        <?php if (empty($announcements)): ?>

            <div class="empty-state">
                <h3>No Announcements Yet</h3>
                <p>There are currently no published announcements.</p>
            </div>

        <?php else: ?>

            <div class="announcement-list">

                <?php foreach ($announcements as $announcement): ?>

                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-body">

                            <h3><?= e($announcement['title']) ?></h3>

                            <p style="white-space: pre-line;">
                                <?= e($announcement['description']) ?>
                            </p>

                            <hr>

                            <small>
                                Published by:
                                <?= e($announcement['admin_name']) ?>
                            </small>

                            <br>

                            <small>
                                Date:
                                <?= e(date('d M Y, h:i A', strtotime($announcement['published_at']))) ?>
                            </small>

                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/bottom.php'; ?>
