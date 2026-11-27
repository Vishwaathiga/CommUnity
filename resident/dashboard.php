<?php
/**
 * CommUnity - Resident Dashboard
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$role = 'resident';

$me = get_user(current_user_id());

// Fetch stats
try {
    $my_visitors = db()->prepare("SELECT COUNT(*) FROM visitors WHERE resident_id = ?");
    $my_visitors->execute([current_user_id()]);
    $my_visitors_count = $my_visitors->fetchColumn();

    $my_pending_visitors = db()->prepare("SELECT COUNT(*) FROM visitors WHERE resident_id = ? AND status = 'Arrived'");
    $my_pending_visitors->execute([current_user_id()]);
    $my_pending_count = $my_pending_visitors->fetchColumn();

    $my_deliveries = db()->prepare("SELECT COUNT(*) FROM deliveries WHERE resident_id = ? AND status != 'Collected'");
    $my_deliveries->execute([current_user_id()]);
    $my_deliveries_count = $my_deliveries->fetchColumn();

    $my_skills = db()->prepare("SELECT COUNT(*) FROM skills WHERE resident_id = ?");
    $my_skills->execute([current_user_id()]);
    $my_skills_count = $my_skills->fetchColumn();

    $my_resources = db()->prepare("SELECT COUNT(*) FROM resources WHERE owner_id = ?");
    $my_resources->execute([current_user_id()]);
    $my_resources_count = $my_resources->fetchColumn();

    $unread_notifs = unread_notification_count(current_user_id());
} catch (PDOException $e) {
    $my_visitors_count = $my_pending_count = $my_deliveries_count = 0;
    $my_skills_count = $my_resources_count = $unread_notifs = 0;
}

// Recent notifications
$recent_notifications = get_notifications(current_user_id(), 5);

require __DIR__ . '/../includes/top.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11h6M20 8v6"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $my_visitors_count ?></h4>
            <p>My Visitors</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $my_pending_count ?></h4>
            <p>Pending Approvals</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $my_deliveries_count ?></h4>
            <p>Active Deliveries</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $my_skills_count ?></h4>
            <p>My Skills</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $my_resources_count ?></h4>
            <p>My Resources</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <div class="stat-info">
            <h4><?= $unread_notifs ?></h4>
            <p>Unread Notifications</p>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Profile Summary -->
    <div class="card">
        <div class="card-header">
            <h3>My Profile</h3>
            <a href="<?= e(BASE_URL) ?>resident/profile.php" class="btn btn-sm btn-outline">Edit Profile</a>
        </div>
        <div class="card-body">
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?= e(profile_image_url($me['profile_image'] ?? null)) ?>" alt="Profile">
                </div>
                <div class="profile-info">
                    <h2><?= e($me['name']) ?></h2>
                    <p><?= e($me['occupation'] ?? 'Resident') ?></p>
                    <div class="profile-meta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                            <?= e($me['block'] . ' ' . $me['door_number']) ?>
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <?= e($me['phone']) ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <?= availability_badge($me['availability']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Notifications</h3>
            <a href="<?= e(BASE_URL) ?>resident/notifications.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_notifications)): ?>
                <div class="empty-state"><p>No notifications.</p></div>
            <?php else: ?>
                <?php foreach ($recent_notifications as $n): ?>
                    <div class="notification-item <?= $n['is_read'] ? '' : 'unread' ?>">
                        <div class="notification-icon <?= e($n['type']) ?>">
                            <?php if ($n['type'] === 'visitor'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
                            <?php elseif ($n['type'] === 'delivery'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <h5><?= e($n['title']) ?></h5>
                            <p><?= e($n['message']) ?></p>
                            <span class="notification-time"><?= format_datetime($n['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
