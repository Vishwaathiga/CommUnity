
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

$allowedStatuses = ['Active', 'Inactive', 'On Leave'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD WORKER
    if (isset($_POST['add_worker'])) {

        $name = trim($_POST['name'] ?? '');
        $workerRole = trim($_POST['role'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $door = trim($_POST['door_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $hireDate = $_POST['hire_date'] ?? '';
        $status = $_POST['status'] ?? 'Active';

        $email = $email !== '' ? $email : null;
        $hireDate = $hireDate !== '' ? $hireDate : null;

        if (empty($name) || empty($workerRole)) {
            set_flash('danger', 'Name and role are required.');
        } elseif (!in_array($status, $allowedStatuses, true)) {
            set_flash('danger', 'Invalid worker status.');
        } else {
            try {
                $stmt = db()->prepare("
                    INSERT INTO workers
                    (name, role, block, door_number, phone, email,
                     emergency_contact, hire_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name, $workerRole, $block, $door, $phone,
                    $email, $emergencyContact, $hireDate, $status
                ]);

                log_activity(
                    current_user_id(),
                    'Added Worker',
                    'Workers',
                    "Added worker: $name."
                );

                set_flash('success', 'Worker added successfully.');

            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    set_flash('danger', 'A worker with this email already exists.');
                } else {
                    set_flash('danger', 'Failed to add worker.');
                }
            }
        }

        header("Location: " . BASE_URL . "admin/workers.php");
        exit;
    }

    // UPDATE WORKER
    if (isset($_POST['update_worker'])) {

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $workerRole = trim($_POST['role'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $door = trim($_POST['door_number'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');
        $hireDate = $_POST['hire_date'] ?? '';
        $status = $_POST['status'] ?? 'Active';

        $email = $email !== '' ? $email : null;
        $hireDate = $hireDate !== '' ? $hireDate : null;

        if ($id <= 0 || empty($name) || empty($workerRole)) {
            set_flash('danger', 'Invalid worker data.');
        } elseif (!in_array($status, $allowedStatuses, true)) {
            set_flash('danger', 'Invalid worker status.');
        } else {
            try {
                $stmt = db()->prepare("
                    UPDATE workers
                    SET name = ?, role = ?, block = ?, door_number = ?,
                        phone = ?, email = ?, emergency_contact = ?,
                        hire_date = ?, status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name, $workerRole, $block, $door, $phone,
                    $email, $emergencyContact, $hireDate, $status, $id
                ]);

                log_activity(
                    current_user_id(),
                    'Updated Worker',
                    'Workers',
                    "Updated worker ID $id."
                );

                set_flash('success', 'Worker updated successfully.');

            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    set_flash('danger', 'A worker with this email already exists.');
                } else {
                    set_flash('danger', 'Failed to update worker.');
                }
            }
        }

        header("Location: " . BASE_URL . "admin/workers.php");
        exit;
    }

    // DELETE WORKER
    if (isset($_POST['delete_worker'])) {

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            set_flash('danger', 'Invalid worker.');
        } else {
            try {
                $stmt = db()->prepare("DELETE FROM workers WHERE id = ?");
                $stmt->execute([$id]);

                log_activity(
                    current_user_id(),
                    'Deleted Worker',
                    'Workers',
                    "Deleted worker ID $id."
                );

                set_flash('success', 'Worker deleted successfully.');

            } catch (PDOException $e) {
                set_flash('danger', 'Failed to delete worker.');
            }
        }

        header("Location: " . BASE_URL . "admin/workers.php");
        exit;
    }
}

// Fetch workers
$stmt = db()->query("SELECT * FROM workers ORDER BY name");
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <h2>Worker Management</h2>

    <button class="btn btn-primary"
            data-toggle="modal"
            data-target="#addWorkerModal">
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
                            <th>Emergency Contact</th>
                            <th>Hire Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($workers as $w): ?>
                            <tr>
                                <td><?= e($w['name']) ?></td>

                                <td>
                                    <span class="badge badge-info">
                                        <?= e($w['role']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(($w['block'] ?? '') . ' ' . ($w['door_number'] ?? '')) ?>
                                </td>

                                <td><?= e($w['phone'] ?? '') ?></td>
                                <td><?= e($w['email'] ?? '') ?></td>
                                <td><?= e($w['emergency_contact'] ?? '') ?></td>
                                <td><?= e($w['hire_date'] ?? 'Not provided') ?></td>

                                <td>
                                    <span class="badge badge-<?= $w['status'] === 'Active' ? 'success' : ($w['status'] === 'Inactive' ? 'danger' : 'primary') ?>">
                                        <?= e($w['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="btn-group btn-group-sm">

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            onclick="editWorker(this)"
                                            data-id="<?= (int) $w['id'] ?>"
                                            data-name="<?= e($w['name']) ?>"
                                            data-role="<?= e($w['role']) ?>"
                                            data-block="<?= e($w['block'] ?? '') ?>"
                                            data-door="<?= e($w['door_number'] ?? '') ?>"
                                            data-phone="<?= e($w['phone'] ?? '') ?>"
                                            data-email="<?= e($w['email'] ?? '') ?>"
                                            data-emergency="<?= e($w['emergency_contact'] ?? '') ?>"
                                            data-hire-date="<?= e($w['hire_date'] ?? '') ?>"
                                            data-status="<?= e($w['status']) ?>">
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-outline-danger"
                                            onclick="deleteWorker(<?= (int) $w['id'] ?>)">
                                            Delete
                                        </button>

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

<!-- ADD WORKER MODAL -->
<div class="modal-overlay" id="addWorkerModal">
    <div class="modal">

        <div class="modal-header">
            <h3>Add New Worker</h3>

            <button class="modal-close"
                    onclick="document.getElementById('addWorkerModal').classList.remove('active')">
                &times;
            </button>
        </div>

        <div class="modal-body">

            <form method="POST">
                <input type="hidden" name="add_worker" value="1">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role *</label>
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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" name="emergency_contact">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hire Date</label>
                        <input type="date" class="form-control" name="hire_date">
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
                    <button type="submit" class="btn btn-primary">
                        Add Worker
                    </button>

                    <button type="button"
                            class="btn btn-outline"
                            onclick="document.getElementById('addWorkerModal').classList.remove('active')">
                        Cancel
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- EDIT WORKER MODAL -->
<div class="modal-overlay" id="editWorkerModal">
    <div class="modal">

        <div class="modal-header">
            <h3>Edit Worker</h3>

            <button class="modal-close"
                    onclick="document.getElementById('editWorkerModal').classList.remove('active')">
                &times;
            </button>
        </div>

        <div class="modal-body">

            <form method="POST">
                <input type="hidden" name="update_worker" value="1">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <input type="text" class="form-control" name="role" id="edit_role" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Block</label>
                        <input type="text" class="form-control" name="block" id="edit_block">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Door Number</label>
                        <input type="text" class="form-control" name="door_number" id="edit_door">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" name="emergency_contact" id="edit_emergency">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hire Date</label>
                        <input type="date" class="form-control" name="hire_date" id="edit_hire_date">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>

                    <select class="form-control" name="status" id="edit_status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="On Leave">On Leave</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Update Worker
                    </button>

                    <button type="button"
                            class="btn btn-outline"
                            onclick="document.getElementById('editWorkerModal').classList.remove('active')">
                        Cancel
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- DELETE WORKER FORM -->
<form method="POST" id="deleteWorkerForm">
    <input type="hidden" name="delete_worker" value="1">
    <input type="hidden" name="id" id="delete_worker_id">
</form>

<script>
function editWorker(button) {
    document.getElementById('edit_id').value = button.dataset.id;
    document.getElementById('edit_name').value = button.dataset.name;
    document.getElementById('edit_role').value = button.dataset.role;
    document.getElementById('edit_block').value = button.dataset.block;
    document.getElementById('edit_door').value = button.dataset.door;
    document.getElementById('edit_phone').value = button.dataset.phone;
    document.getElementById('edit_email').value = button.dataset.email;
    document.getElementById('edit_emergency').value = button.dataset.emergency;
    document.getElementById('edit_hire_date').value = button.dataset.hireDate;
    document.getElementById('edit_status').value = button.dataset.status;

    document.getElementById('editWorkerModal').classList.add('active');
}

function deleteWorker(id) {
    if (confirm('Are you sure you want to delete this worker?')) {
        document.getElementById('delete_worker_id').value = id;
        document.getElementById('deleteWorkerForm').submit();
    }
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
