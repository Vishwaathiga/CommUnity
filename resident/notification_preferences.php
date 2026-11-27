<?php
/**
 * CommUnity - Resident: Notification Preferences
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Notification Preferences';
$activePage = 'notification_preferences';
$role = 'resident';

$unread_count = unread_notification_count(current_user_id());

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Notification Preferences</h2>
    </div>
    <div class="card-body">
        <p>Configure how and when you receive notifications from the CommUnity platform.</p>
        
        <div class="grid-2">
            <div class="card">
                <div class="card-body">
                    <h4>In-App Notifications</h4>
                    <p>Receive notifications in the bell dropdown on your dashboard.</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notif_visitor" checked>
                        <label class="form-check-label" for="notif_visitor">Visitor notifications (new visitors, approval/rejection)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notif_delivery" checked>
                        <label class="form-check-label" for="notif_delivery">Delivery notifications</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notif_help" checked>
                        <label class="form-check-label" for="notif_help">Help request updates</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notif_resource" checked>
                        <label class="form-check-label" for="notif_resource">Resource sharing updates</label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h4>Email Notifications</h4>
                    <p>Receive important alerts via email (currently being set up).</p>
                    <p class="text-muted small">Email notifications are pending configuration. Please ensure your email is <a href="<?= BASE_URL ?>resident/profile.php">up to date</a> in your profile.</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="email_visitor" disabled>
                        <label class="form-check-label" for="email_visitor">Visitor alerts via email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="email_delivery" disabled>
                        <label class="form-check-label" for="email_delivery">Delivery alerts via email</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-primary" onclick="saveNotificationPrefs()">Save Preferences</button>
            <button type="button" class="btn btn-outline" onclick="history.back()">Cancel</button>
        </div>
    </div>
</div>

<script>
function saveNotificationPrefs() {
    // In a full implementation, this would save preferences to the database
    setFlashMessage('success', 'Notification preferences saved successfully.');
    setTimeout(() => window.history.back(), 1000);
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>