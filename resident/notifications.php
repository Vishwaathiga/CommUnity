<?php
/**
 * CommUnity - Resident: Notifications
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Notifications';
$activePage = 'notifications';
$role = 'resident';

// Handle mark all read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    mark_all_notifications_read(current_user_id());
    set_flash('success', 'All notifications marked as read.');
    header("Location: " . BASE_URL . "resident/notifications.php");
    exit;
}

// Fetch notifications
$notifications = get_notifications(current_user_id());

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted"><?= count($notifications) ?> notification<?= count($notifications) !== 1 ? 's' : '' ?></p>
    <?php if (!empty($notifications)): ?>
        <a href="<?= e(BASE_URL) ?>resident/notifications.php?action=mark_all_read" class="btn btn-sm btn-outline">Mark All as Read</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <h4>No notifications</h4>
                <p>You're all caught up!</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notification-item <?= $n['is_read'] ? '' : 'unread' ?>" id="notif-<?= $n['id'] ?>">
                    <div class="notification-icon <?= e($n['type']) ?>">
                        <?php if ($n['type'] === 'visitor'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/></svg>
                        <?php elseif ($n['type'] === 'delivery'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5M21 3l-7 7M8 21H3v-5M3 21l7-7"/></svg>
                        <?php elseif ($n['type'] === 'help'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <?php elseif ($n['type'] === 'resource'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="notification-content">
                        <h5><?= e($n['title']) ?></h5>
                        <p><?= e($n['message']) ?></p>
                        <span class="notification-time"><?= format_datetime($n['created_at']) ?></span>

                        <!-- Visitor notification with approve/reject -->
                        <?php if ($n['type'] === 'visitor' && $n['reference_id']):
                            $visitor_stmt = db()->prepare("SELECT * FROM visitors WHERE id = ?");
                            $visitor_stmt->execute([$n['reference_id']]);
                            $visitor = $visitor_stmt->fetch();
                            if ($visitor && $visitor['status'] === 'Arrived'):
                        ?>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-success" onclick="approveVisitor(<?= $visitor['id'] ?>)">Approve Entry</button>
                                <button class="btn btn-sm btn-danger" onclick="rejectVisitor(<?= $visitor['id'] ?>)">Reject Entry</button>
                            </div>
                        <?php
                            endif;
                        endif; ?>

                        <!-- Delivery notification with collect button -->
                        <?php if ($n['type'] === 'delivery' && $n['reference_id']):
                            $delivery_stmt = db()->prepare("SELECT * FROM deliveries WHERE id = ?");
                            $delivery_stmt->execute([$n['reference_id']]);
                            $delivery = $delivery_stmt->fetch();
                            if ($delivery && $delivery['status'] !== 'Collected'):
                        ?>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-primary" onclick="markDeliveryCollected(<?= $delivery['id'] ?>)">Mark Collected</button>
                            </div>
                        <?php
                            endif;
                        endif; ?>

                        <?php if (!$n['is_read']): ?>
                            <div class="notification-actions">
                                <a href="#" onclick="markNotificationRead(<?= $n['id'] ?>, event)" class="text-muted" style="font-size:0.75rem;">Mark as read</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
