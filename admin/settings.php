<?php
/**
 * CommUnity - Admin: Settings
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Settings';
$activePage = 'settings';
$role = 'admin';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $community_name = trim($_POST['community_name'] ?? '');
    $tagline = trim($_POST['community_tagline'] ?? '');
    $announcement = trim($_POST['announcement'] ?? '');

    try {
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('community_name', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$community_name]);
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('community_tagline', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$tagline]);
        db()->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('announcement', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")->execute([$announcement]);

        // Notify all users about announcement
        $users = db()->query("SELECT id FROM users")->fetchAll();
        foreach ($users as $u) {
            create_notification($u['id'], 'System Announcement', $announcement, 'system');
        }

        log_activity(current_user_id(), 'Updated Settings', 'Settings', 'Updated system settings.');
        set_flash('success', 'Settings updated successfully.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to update settings.');
    }
    header("Location: " . BASE_URL . "admin/settings.php");
    exit;
}

$community_name = get_setting('community_name', 'CommUnity');
$tagline = get_setting('community_tagline', 'Unified Residential Community Platform');
$announcement = get_setting('announcement', '');

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>System Settings</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Community Name</label>
                <input type="text" class="form-control" name="community_name" value="<?= e($community_name) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tagline</label>
                <input type="text" class="form-control" name="community_tagline" value="<?= e($tagline) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Announcement (sent to all users as notification)</label>
                <textarea class="form-control" name="announcement" rows="3"><?= e($announcement) ?></textarea>
            </div>
            <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3>Demo Accounts</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge badge-primary">Admin</span></td>
                        <td>admin@community.com</td>
                        <td><code>admin123</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-info">Watchman</span></td>
                        <td>watchman@community.com</td>
                        <td><code>watchman123</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success">Resident</span></td>
                        <td>arun@community.com</td>
                        <td><code>resident123</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success">Resident</span></td>
                        <td>priya@community.com</td>
                        <td><code>resident123</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success">Resident</span></td>
                        <td>vishwa@community.com</td>
                        <td><code>resident123</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-success">Resident</span></td>
                        <td>karthik@community.com</td>
                        <td><code>resident123</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
