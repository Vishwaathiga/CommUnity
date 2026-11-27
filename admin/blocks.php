<?php
/**
 * CommUnity - Admin: Block Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Blocks';
$activePage = 'blocks';
$role = 'admin';

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_block'])) {
        $name = trim($_POST['block_name'] ?? '');
        $floors = (int) ($_POST['floors'] ?? 0);
        if ($name && $floors > 0) {
            try {
                $stmt = db()->prepare("INSERT INTO blocks (block_name, floors) VALUES (?, ?)");
                $stmt->execute([$name, $floors]);
                log_activity(current_user_id(), 'Added Block', 'Blocks', "Added block: $name.");
                set_flash('success', 'Block added successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to add block. Name may already exist.');
            }
        } else {
            set_flash('danger', 'Please fill all fields.');
        }
        header("Location: " . BASE_URL . "admin/blocks.php");
        exit;
    }

    if (isset($_POST['delete_block'])) {
        $id = (int) $_POST['block_id'];
        try {
            $stmt = db()->prepare("DELETE FROM blocks WHERE id = ?");
            $stmt->execute([$id]);
            log_activity(current_user_id(), 'Deleted Block', 'Blocks', "Deleted block ID $id.");
            set_flash('success', 'Block deleted successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to delete block.');
        }
        header("Location: " . BASE_URL . "admin/blocks.php");
        exit;
    }
}

// Fetch blocks with occupancy
try {
    $stmt = db()->query("
        SELECT b.*,
            (SELECT COUNT(*) FROM users WHERE role = 'resident' AND users.block = b.block_name) AS occupancy
        FROM blocks b ORDER BY b.block_name
    ");
    $blocks = $stmt->fetchAll();
} catch (PDOException $e) {
    $blocks = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted">Manage apartment blocks, floors, and occupancy.</p>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('active')">+ Add Block</button>
</div>

<div class="grid-3">
    <?php foreach ($blocks as $b): ?>
        <div class="card">
            <div class="card-header">
                <h3><?= e($b['block_name']) ?></h3>
                <a href="<?= e(BASE_URL) ?>admin/blocks.php?action=delete&id=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this block?')">Delete</a>
            </div>
            <div class="card-body">
                <p><strong>Floors:</strong> <?= (int) $b['floors'] ?></p>
                <p><strong>Occupancy:</strong> <?= (int) $b['occupancy'] ?> residents</p>
                <p><strong>Flats per floor:</strong> 3</p>
                <p class="text-muted" style="font-size:0.8rem;">
                    <?php
                    $flats = [];
                    for ($f = 1; $f <= $b['floors']; $f++) {
                        for ($flat = 1; $flat <= 3; $flat++) {
                            $flats[] = str_replace('Block ', '', $b['block_name']) . '-' . ($f * 100 + $flat);
                        }
                    }
                    echo e(implode(', ', array_slice($flats, 0, 12))) . (count($flats) > 12 ? '...' : '');
                    ?>
                </p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add Block Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Block</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Block Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="block_name" placeholder="e.g. Block D" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Number of Floors <span class="required">*</span></label>
                    <input type="number" class="form-control" name="floors" min="1" value="3" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="add_block" class="btn btn-primary">Add Block</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
