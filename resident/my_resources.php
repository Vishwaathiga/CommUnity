<?php
/**
 * CommUnity - Resident: My Resources
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'My Resources';
$activePage = 'my_resources';
$role = 'resident';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = db()->prepare("DELETE FROM resources WHERE id = ? AND owner_id = ?");
        $stmt->execute([$id, current_user_id()]);
        log_activity(current_user_id(), 'Deleted Resource', 'Resources', "Deleted resource ID $id.");
        set_flash('success', 'Resource deleted successfully.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to delete resource.');
    }
    header("Location: " . BASE_URL . "resident/my_resources.php");
    exit;
}

// Fetch my resources
try {
    $stmt = db()->prepare("SELECT * FROM resources WHERE owner_id = ? ORDER BY created_at DESC");
    $stmt->execute([current_user_id()]);
    $resources = $stmt->fetchAll();
} catch (PDOException $e) {
    $resources = [];
}

// Fetch resource requests for my resources
try {
    $stmt = db()->prepare("
      SELECT rr.*, r.item_name,
       u.name AS requester_name,
       u.block,
       u.door_number,
       u.phone AS requester_phone
FROM resource_requests rr
JOIN resources r ON rr.resource_id = r.id
JOIN users u ON rr.requester_id = u.id
WHERE r.owner_id = ?
ORDER BY rr.requested_at DESC
    ");
    $stmt->execute([current_user_id()]);
    $resource_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $resource_requests = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted">Manage items you've shared with the community.</p>
    <a href="<?= e(BASE_URL) ?>resident/resources.php" class="btn btn-primary btn-sm">+ Add Item</a>
</div>

<?php if (empty($resources)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <p>No resources added yet. Start sharing items with your community!</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid-3">
        <?php foreach ($resources as $r): ?>
            <div class="resource-card">
                <img src="<?= e(resource_image_url($r['image'])) ?>" alt="<?= e($r['item_name']) ?>" class="resource-card-image" onerror="this.src='<?= e(BASE_URL) ?>assets/images/default-resource.png'">
                <div class="resource-card-body">
                    <h4><?= e($r['item_name']) ?></h4>
                    <p><?= e($r['description']) ?></p>
                    <div class="resource-card-meta">
                        <span class="resource-card-price <?= $r['price'] > 0 ? '' : 'free' ?>"><?= $r['price'] > 0 ? '₹' . e($r['price']) : 'Free' ?></span>
                        <?= resource_status_badge($r['status']) ?>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge badge-info"><?= e($r['sharing_type']) ?></span>
                        <span class="badge badge-secondary"><?= e($r['condition'] ?? '—') ?></span>
                    </div>
                    <a href="<?= e(BASE_URL) ?>resident/my_resources.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Resource Requests -->
<?php if (!empty($resource_requests)): ?>
    <div class="card mt-3">
        <div class="card-header">
            <h3>Requests for My Items</h3>
        </div>
        <div class="card-body">
            <?php foreach ($resource_requests as $rr): ?>
                <div class="notification-item">
                    <div class="notification-icon resource">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <div class="notification-content">
                        <div class="d-flex justify-between align-center">
                            <h5><?= e($rr['item_name']) ?></h5>
                            <?= resource_request_badge($rr['status']) ?>
                        </div>
                      <p><strong>From:</strong> <?= e($rr['requester_name']) ?></p>
<p><strong>Location:</strong> <?= e($rr['block'] . ' - ' . $rr['door_number']) ?></p>
<p><strong>Phone:</strong> <?= e($rr['requester_phone'] ?? 'Not provided') ?></p>
                        <?php if ($rr['message']): ?>
                            <p><strong>Message:</strong> <?= e($rr['message']) ?></p>
                        <?php endif; ?>
                        <small class="text-muted">Requested: <?= format_datetime($rr['requested_at']) ?></small>
                        <?php if ($rr['status'] === 'Pending'): ?>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-success" onclick="acceptResourceRequest(<?= $rr['id'] ?>)">Accept</button>
                                <button class="btn btn-sm btn-danger" onclick="rejectResourceRequest(<?= $rr['id'] ?>)">Reject</button>
                            </div>
                        <?php endif; ?>
                        <?php if ($rr['status'] === 'Accepted'): ?>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-primary" onclick="completeResourceRequest(<?= $rr['id'] ?>)">Mark Completed</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
