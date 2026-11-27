<?php
/**
 * CommUnity - Admin: Worker Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Worker Management';
$activePage = 'workers';
$role = 'admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_worker'])) {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $door = trim($_POST['door_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if (empty($name) || empty($role)) {
            set_flash('danger', 'Name and role are required.');
        } else {
            try {
                $stmt = db()->prepare("INSERT INTO workers (name, role, block, door_number, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role, $block, $door, $phone, $email, $status]);
                log_activity(current_user_id(), 'Added Worker', 'Workers', "Added worker: $name.");
                set_flash('success', 'Worker added successfully.');
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    set_flash('danger', 'A worker with this email already exists.');
                } else {
                    set_flash('danger', 'Database error.');
                }
            }
        }
        header("Location: " . BASE_URL . "admin/workers_management.php");
        exit;
    }

    if (isset($_POST['update_worker'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $door = trim($_POST['door_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if (empty($id) || empty($name) || empty($role)) {
            set_flash('danger', 'Invalid worker data.');
        } else {
            try {
                $stmt = db()->prepare("UPDATE workers SET name = ?, role = ?, block = ?, door_number = ?, phone = ?, email = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $role, $block, $door, $phone, $email, $status, $id]);
                log_activity(current_user_id(), 'Updated Worker', 'Workers', "Updated worker ID $id.");
                set_flash('success', 'Worker updated successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to update worker.');
            }
        }
        header("Location: " . BASE_URL . "admin/workers_management.php");
        exit;
    }

    if (isset($_POST['delete_worker'])) {
        $id = (int) $_POST['id'];
        try {
            $stmt = db()->prepare("DELETE FROM workers WHERE id = ?");
            $stmt->execute([$id]);
            log_activity(current_user_id(), 'Deleted Worker', 'Workers', "Deleted worker ID $id.");
            set_flash('success', 'Worker deleted successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to delete worker.');
        }
        header("Location: " . BASE_URL . "admin/workers_management.php");
        exit;
    }
}

// Fetch workers
try {
    $stmt = db()->query("SELECT * FROM workers ORDER BY name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}

// Fetch all users for reference
try {
    $stmt = db()->query("SELECT id, name, role FROM users WHERE role IN ('admin','watchman') ORDER BY name");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <h2>Worker Management</h2>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addWorkerModal">
        + Add Worker
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h3>Community Workers</h3>
    </div>
    <div class="card-body">
        <?php if (empty($workers)): ?>
            <div class="empty-state">
                <p>No workers found. Add the first worker above.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Block/Door</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workers as $w): ?>
                            <tr>
                                <td><?= e($w['name']) ?></td>
                                <td><span class="badge badge-info"><?= e($w['role']) ?></span></td>
                                <td><?= e($w['block'] . ' ' . $w['door_number']) ?></td>
                                <td><?= e($w['phone']) ?></td>
                                <td><?= e($w['email']) ?></td>
                                <td><span class="badge badge-<?= $w['status'] === 'Active' ? 'success' : ($w['status'] === 'Inactive' ? 'danger' : 'primary') ?>"><?= e($w['status']) ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editWorker(<?= $w['id'] ?>)">Edit</button>
                                        <button class="btn btn-outline-danger" onclick="deleteWorker(<?= $w['id'] ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Worker Modal -->
<div class="modal-overlay" id="addWorkerModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Worker</h3>
            <button class="modal-close" onclick="document.getElementById('addWorkerModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="add_worker" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <input type="text" class="form-control" name="role" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Block</label>
                        <input type="text" class="form-control" name="block">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Door Number</label>
                        <input type="text" class="form-control" name="door_number">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="On Leave">On Leave</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Add Worker</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addWorkerModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Worker Modal (similar structure, omitted for brevity) -->

<?php require __DIR__ . '/../includes/bottom.php'; ?>