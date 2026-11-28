
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

    // Add Complaint
    if (isset($_POST['add_complaint'])) {

        $type = trim($_POST['type'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        $assign_to = (int) ($_POST['assign_to'] ?? 0);

        // Convert Unassigned (0) into NULL
        if ($assign_to <= 0) {
            $assign_to = null;
        }

        if (empty($type) || empty($title) || empty($description)) {
            set_flash('danger', 'Please fill all required fields.');
        } else {
            try {
                $stmt = db()->prepare(
                    "INSERT INTO complaints
                    (complainer_id, type, title, description, status, assigned_to)
                    VALUES (?, ?, ?, ?, 'Open', ?)"
                );

                $stmt->execute([
                    current_user_id(),
                    $type,
                    $title,
                    $description,
                    $assign_to
                ]);

                log_activity(
                    current_user_id(),
                    'Created Complaint',
                    'Complaints',
                    "Created complaint: $title."
                );

                set_flash('success', 'Complaint created successfully.');

            } catch (PDOException $e) {
                error_log('Complaint creation error: ' . $e->getMessage());
                set_flash('danger', 'Failed to create complaint. Please check the details.');
            }
        }

        header("Location: " . BASE_URL . "admin/complaints.php");
        exit;
    }

    // Update Complaint Status
    if (isset($_POST['resolve_complaint'])) {

        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $allowed_statuses = ['In Progress', 'Resolved', 'Closed'];

        if ($id <= 0 || !in_array($status, $allowed_statuses, true)) {

            set_flash('danger', 'Invalid complaint or status.');

        } else {
            try {

                if ($status === 'Resolved' || $status === 'Closed') {

                    $stmt = db()->prepare(
                        "UPDATE complaints
                         SET status = ?, resolved_at = NOW()
                         WHERE id = ?"
                    );

                } else {

                    $stmt = db()->prepare(
                        "UPDATE complaints
                         SET status = ?, resolved_at = NULL
                         WHERE id = ?"
                    );
                }

                $stmt->execute([$status, $id]);

                log_activity(
                    current_user_id(),
                    'Updated Complaint',
                    'Complaints',
                    "Updated complaint ID $id to $status."
                );

                set_flash('success', 'Complaint status updated successfully.');

            } catch (PDOException $e) {
                error_log('Complaint update error: ' . $e->getMessage());
                set_flash('danger', 'Failed to update complaint.');
            }
        }

        header("Location: " . BASE_URL . "admin/complaints.php");
        exit;
    }

    // Delete Complaint
    if (isset($_POST['delete_complaint'])) {

        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $stmt = db()->prepare(
                    "DELETE FROM complaints WHERE id = ?"
                );

                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {

                    log_activity(
                        current_user_id(),
                        'Deleted Complaint',
                        'Complaints',
                        "Deleted complaint ID $id."
                    );

                    set_flash('success', 'Complaint deleted successfully.');

                } else {
                    set_flash('danger', 'Complaint not found.');
                }

            } catch (PDOException $e) {
                error_log('Complaint deletion error: ' . $e->getMessage());
                set_flash('danger', 'Failed to delete complaint.');
            }
        } else {
            set_flash('danger', 'Invalid complaint ID.');
        }

        header("Location: " . BASE_URL . "admin/complaints.php");
        exit;
    }
}

// Fetch complaints
try {

    $status_filter = $_GET['status'] ?? '';

    $sql = "SELECT
                c.*,
                u.name AS complainer_name,
                u.block AS complainer_block,
                (SELECT name FROM users WHERE id = c.assigned_to) AS assigned_name
            FROM complaints c
            JOIN users u ON c.complainer_id = u.id";

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

    error_log('Complaint listing error: ' . $e->getMessage());
    $complaints = [];
}

// Fetch users for assignment
try {

    $stmt = db()->query(
        "SELECT id, name
         FROM users
         WHERE role IN ('admin', 'watchman')
         ORDER BY name"
    );

    $assign_users = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log('Complaint assignment users error: ' . $e->getMessage());
    $assign_users = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">

    <h2>Complaint Management</h2>

    <button type="button"
            class="btn btn-primary"
            onclick="document.getElementById('addComplaintModal').classList.add('active')">
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

                                <td>
                                    <strong><?= e($c['title']) ?></strong>
                                </td>

                                <td>
                                    <?= e($c['complainer_name']) ?>
                                    (<?= e($c['complainer_block'] ?? '') ?>)
                                </td>

                                <td>
                                    <span class="badge badge-info">
                                        <?= e($c['type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-<?= $c['status'] === 'Open' ? 'warning' : ($c['status'] === 'In Progress' ? 'primary' : 'success') ?>">
                                        <?= e($c['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($c['assigned_name'] ?? 'Unassigned') ?>
                                </td>

                                <td>
                                    <?= format_datetime($c['created_at']) ?>
                                </td>

                                <td>

                                    <div class="btn-group btn-group-sm">

                                        <?php if ($c['status'] !== 'Resolved' && $c['status'] !== 'Closed'): ?>

                                            <button type="button"
                                                    class="btn btn-outline-primary"
                                                    onclick="resolveModal(<?= (int) $c['id'] ?>)">
                                                Resolve
                                            </button>

                                        <?php endif; ?>

                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                onclick="deleteComplaint(<?= (int) $c['id'] ?>)">
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


<!-- Add Complaint Modal -->

<div class="modal-overlay" id="addComplaintModal">

    <div class="modal">

        <div class="modal-header">

            <h3>Add New Complaint</h3>

            <button type="button"
                    class="modal-close"
                    onclick="document.getElementById('addComplaintModal').classList.remove('active')">
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form method="POST">

                <input type="hidden" name="add_complaint" value="1">

                <div class="form-row">

                    <div class="form-group">

                        <label class="form-label">
                            Type <span class="required">*</span>
                        </label>

                        <select class="form-control" name="type" required>

                            <option value="Maintenance">Maintenance</option>
                            <option value="Security">Security</option>
                            <option value="Facility">Facility</option>
                            <option value="Noise">Noise</option>
                            <option value="Other">Other</option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Title <span class="required">*</span>
                        </label>

                        <input type="text"
                               class="form-control"
                               name="title"
                               maxlength="150"
                               required>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Description <span class="required">*</span>
                    </label>

                    <textarea class="form-control"
                              name="description"
                              rows="3"
                              required></textarea>

                </div>

                <div class="form-group">

                    <label class="form-label">Assign To</label>

                    <select class="form-control" name="assign_to">

                        <option value="0">Unassigned</option>

                        <?php foreach ($assign_users as $u): ?>

                            <option value="<?= (int) $u['id'] ?>">
                                <?= e($u['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Create Complaint
                    </button>

                    <button type="button"
                            class="btn btn-outline"
                            onclick="document.getElementById('addComplaintModal').classList.remove('active')">
                        Cancel
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- Resolve Complaint Modal -->

<div class="modal-overlay" id="resolveComplaintModal">

    <div class="modal">

        <div class="modal-header">

            <h3>Update Complaint Status</h3>

            <button type="button"
                    class="modal-close"
                    onclick="document.getElementById('resolveComplaintModal').classList.remove('active')">
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form method="POST">

                <input type="hidden" name="resolve_complaint" value="1">
                <input type="hidden" name="id" id="resolveComplaintId">

                <div class="form-group">

                    <label class="form-label">Select Status</label>

                    <select name="status" class="form-control" required>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>

                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Update Status
                    </button>

                    <button type="button"
                            class="btn btn-outline"
                            onclick="document.getElementById('resolveComplaintModal').classList.remove('active')">
                        Cancel
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- Hidden Delete Form -->

<form method="POST" id="deleteComplaintForm" style="display:none;">

    <input type="hidden" name="delete_complaint" value="1">
    <input type="hidden" name="id" id="deleteComplaintId">

</form>


<!-- Complaint JavaScript Functions -->

<script>

function resolveModal(id) {

    document.getElementById('resolveComplaintId').value = id;

    document.getElementById('resolveComplaintModal')
        .classList.add('active');
}

function deleteComplaint(id) {

    if (confirm('Are you sure you want to delete this complaint?')) {

        document.getElementById('deleteComplaintId').value = id;

        document.getElementById('deleteComplaintForm').submit();
    }
}

</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
