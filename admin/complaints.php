<?php
/**
 * CommUnity - Admin: Complaint Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Complaint Management';
$activePage = 'complaints';
$role = 'admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_complaint'])) {
        $type = trim($_POST['type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assign_to = (int) ($_POST['assign_to'] ?? 0);

        if (empty($type) || empty($title) || empty($description)) {
            set_flash('danger', 'Please fill all required fields.');
        } else {
            try {
                $stmt = db()->prepare("INSERT INTO complaints (complainer_id, type, title, description, status, assigned_to) VALUES (?, ?, ?, ?, 'Open', ?)");
                $stmt->execute([current_user_id(), $type, $title, $description, $assign_to]);
                log_activity(current_user_id(), 'Created Complaint', 'Complaints', "Created complaint: $title.");
                set_flash('success', 'Complaint created successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to create complaint.');
            }
        }
        header("Location: " . BASE_URL . "admin/complaints.php");
        exit;
    }

    if (isset($_POST['resolve_complaint'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');

        if (empty($id)) {
            set_flash('danger', 'Invalid complaint.');
        } else {
            try {
                $updates = "status = '$status', resolved_at = NOW()";
                if (!empty($resolution_notes)) {
                    $updates .= ", resolution_notes = '$resolution_notes'";
                }
                $stmt = db()->prepare("UPDATE complaints SET $updates WHERE id = ?");
                $stmt->execute([$id]);
                log_activity(current_user_id(), 'Resolved Complaint', 'Complaints', "Resolved complaint ID $id.");
                set_flash('success', 'Complaint resolved successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to resolve complaint.');
            }
        }
        header("Location: " . BASE_URL . "admin/complaints.php");
        exit;
    }
}

// Fetch complaints
try {
    $status_filter = $_GET['status'] ?? '';
    $sql = "SELECT c.*, u.name AS complainer_name, u.block AS complainer_block, (SELECT name FROM users WHERE id = c.assigned_to) AS assigned_name FROM complaints c JOIN users u ON c.complainer_id = u.id";
    $params = [];

    if (!empty($status_filter)) {
        $sql .= " WHERE c.status = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY c.created_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll();
} catch (PDOException $e) {
    $complaints = [];
}

// Fetch workers for assignment
try {
    $stmt = db()->query("SELECT * FROM workers WHERE status = 'Active' ORDER BY name");
    $workers = $stmt->fetchAll();
} catch (PDOException $e) {
    $workers = [];
}

// Fetch all users for assignment
try {
    $stmt = db()->query("SELECT id, name FROM users WHERE role = 'admin' OR role = 'watchman' ORDER BY name");
    $assign_users = $stmt->fetchAll();
} catch (PDOException $e) {
    $assign_users = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <h2>Complaint Management</h2>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addComplaintModal">
        + Add Complaint
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h3>Complaints</h3>
        <span class="text-muted">
            <a href="?status=Open" class="btn btn-sm btn-outline">Open</a>
            <a href="?status=In Progress" class="btn btn-sm btn-outline">In Progress</a>
            <a href="?status=Resolved" class="btn btn-sm btn-outline">Resolved</a>
        </span>
    </div>
    <div class="card-body">
        <?php if (empty($complaints)): ?>
            <div class="empty-state">
                <p>No complaints found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Complainer</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $c): ?>
                            <tr>
                                <td><strong><?= e($c['title']) ?></strong></td>
                                <td><?= e($c['complainer_name']) ?> (<?= e($c['complainer_block']) ?>)</td>
                                <td><span class="badge badge-info"><?= e($c['type']) ?></span></td>
                                <td><span class="badge badge-<?= $c['status'] === 'Open' ? 'warning' : ($c['status'] === 'In Progress' ? 'primary' : 'success') ?>"><?= e($c['status']) ?></span></td>
                                <td><?= e($c['assigned_name'] ?? 'Unassigned') ?></td>
                                <td><?= format_datetime($c['created_at']) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($c['status'] !== 'Resolved'): ?>
                                            <button class="btn btn-outline-primary" onclick="resolveModal(<?= $c['id'] ?>)">Resolve</button>
                                            <button class="btn btn-outline-danger" onclick="deleteComplaint(<?= $c['id'] ?>)">Delete</button>
                                        <?php else: ?>
                                            <span class="text-muted">Resolved</span>
                                        <?php endif; ?>
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

<!-- Add Complaint Modal -->
<div class="modal-overlay" id="addComplaintModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Complaint</h3>
            <button class="modal-close" onclick="document.getElementById('addComplaintModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="add_complaint" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type <span class="required">*</span></label>
                        <select class="form-control" name="type" required>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Security">Security</option>
                            <option value="Facility">Facility</option>
                            <option value="Noise">Noise</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Title <span class="required">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description <span class="required">*</span></label>
                    <textarea class="form-control" name="description" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assign To</label>
                        <select class="form-control" name="assign_to">
                            <option value="0">Unassigned</option>
                            <?php foreach ($assign_users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control">
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Complaint</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addComplaintModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>