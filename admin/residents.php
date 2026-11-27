<?php
/**
 * CommUnity - Admin: Resident Management (CRUD)
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Residents';
$activePage = 'residents';
$role = 'admin';

$action = $_GET['action'] ?? 'list';
$edit_id = (int) ($_GET['edit'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_resident'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $block = trim($_POST['block'] ?? '');
        $door = trim($_POST['door_number'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $emergency = trim($_POST['emergency_contact'] ?? '');

        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            set_flash('danger', 'Please fill all required fields.');
        } elseif (strlen($password) < 6) {
            set_flash('danger', 'Password must be at least 6 characters.');
        } else {
            try {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = db()->prepare("INSERT INTO users (name, email, phone, password, role, block, door_number, occupation, emergency_contact, verification_status) VALUES (?, ?, ?, ?, 'resident', ?, ?, ?, ?, 'Verified')");
                $stmt->execute([$name, $email, $phone, $hashed, $block, $door, $occupation, $emergency]);
                log_activity(current_user_id(), 'Added Resident', 'Residents', "Added resident: $name.");
                set_flash('success', 'Resident added successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to add resident. Email may already exist.');
            }
        }
        header("Location: " . BASE_URL . "admin/residents.php");
        exit;
    }

    if (isset($_POST['update_resident'])) {
        $id = (int) ($_POST['resident_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $block = trim($_POST['block'] ?? '');
        $door = trim($_POST['door_number'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $emergency = trim($_POST['emergency_contact'] ?? '');
        $availability = $_POST['availability'] ?? 'Available';

        try {
            if (!empty($_POST['password'])) {
                $hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = db()->prepare("UPDATE users SET name=?, email=?, phone=?, block=?, door_number=?, occupation=?, emergency_contact=?, availability=?, password=? WHERE id=? AND role='resident'");
                $stmt->execute([$name, $email, $phone, $block, $door, $occupation, $emergency, $availability, $hashed, $id]);
            } else {
                $stmt = db()->prepare("UPDATE users SET name=?, email=?, phone=?, block=?, door_number=?, occupation=?, emergency_contact=?, availability=? WHERE id=? AND role='resident'");
                $stmt->execute([$name, $email, $phone, $block, $door, $occupation, $emergency, $availability, $id]);
            }
            log_activity(current_user_id(), 'Updated Resident', 'Residents', "Updated resident: $name.");
            set_flash('success', 'Resident updated successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to update resident.');
        }
        header("Location: " . BASE_URL . "admin/residents.php");
        exit;
    }
}

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $user = get_user($id);
        $stmt = db()->prepare("DELETE FROM users WHERE id = ? AND role = 'resident'");
        $stmt->execute([$id]);
        log_activity(current_user_id(), 'Deleted Resident', 'Residents', "Deleted resident: " . ($user['name'] ?? 'ID ' . $id));
        set_flash('success', 'Resident deleted successfully.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to delete resident.');
    }
    header("Location: " . BASE_URL . "admin/residents.php");
    exit;
}

// Handle verify
if ($action === 'verify' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = db()->prepare("UPDATE users SET verification_status = 'Verified' WHERE id = ? AND role = 'resident'");
        $stmt->execute([$id]);
        $user = get_user($id);
        if ($user) {
            create_notification($id, 'Account Verified', 'Your account has been verified by the administrator.', 'system');
        }
        log_activity(current_user_id(), 'Verified Resident', 'Residents', "Verified resident ID $id.");
        set_flash('success', 'Resident verified successfully.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to verify resident.');
    }
    header("Location: " . BASE_URL . "admin/residents.php");
    exit;
}

// Fetch residents
try {
    $stmt = db()->query("SELECT * FROM users WHERE role = 'resident' ORDER BY name");
    $residents = $stmt->fetchAll();
} catch (PDOException $e) {
    $residents = [];
}

// Fetch blocks for form
try {
    $blocks = get_blocks();
} catch (PDOException $e) {
    $blocks = [];
}

// Get edit resident
$edit_resident = null;
if ($edit_id > 0) {
    $edit_resident = get_user($edit_id);
    if ($edit_resident && $edit_resident['role'] !== 'resident') {
        $edit_resident = null;
    }
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <div>
        <a href="<?= e(BASE_URL) ?>admin/residents.php" class="btn btn-sm btn-outline">← Back to List</a>
    </div>
    <?php if (!$edit_resident): ?>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('active')">
            + Add Resident
        </button>
    <?php endif; ?>
</div>

<?php if ($edit_resident): ?>
    <div class="card">
        <div class="card-header">
            <h3>Edit Resident: <?= e($edit_resident['name']) ?></h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="resident_id" value="<?= $edit_resident['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?= e($edit_resident['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= e($edit_resident['email']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone <span class="required">*</span></label>
                        <input type="text" class="form-control" name="phone" value="<?= e($edit_resident['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Availability</label>
                        <select class="form-control" name="availability">
                            <option value="Available" <?= $edit_resident['availability'] === 'Available' ? 'selected' : '' ?>>Available</option>
                            <option value="Inside" <?= $edit_resident['availability'] === 'Inside' ? 'selected' : '' ?>>Inside</option>
                            <option value="Not Available" <?= $edit_resident['availability'] === 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Block</label>
                        <select class="form-control" name="block">
                            <option value="">Select Block</option>
                            <?php foreach ($blocks as $b): ?>
                                <option value="<?= e($b['block_name']) ?>" <?= $edit_resident['block'] === $b['block_name'] ? 'selected' : '' ?>><?= e($b['block_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Door Number</label>
                        <input type="text" class="form-control" name="door_number" value="<?= e($edit_resident['door_number']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" class="form-control" name="occupation" value="<?= e($edit_resident['occupation']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" name="emergency_contact" value="<?= e($edit_resident['emergency_contact']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="update_resident" class="btn btn-primary">Update Resident</button>
                    <a href="<?= e(BASE_URL) ?>admin/residents.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3>All Residents</h3>
        </div>
        <div class="card-body">
            <?php if (empty($residents)): ?>
                <div class="empty-state">
                    <p>No residents found.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Block</th>
                                <th>Door No</th>
                                <th>Availability</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($residents as $r): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-center gap-2">
                                            <img src="<?= e(profile_image_url($r['profile_image'])) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                            <strong><?= e($r['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= e($r['email']) ?></td>
                                    <td><?= e($r['phone']) ?></td>
                                    <td><?= e($r['block'] ?? '—') ?></td>
                                    <td><?= e($r['door_number'] ?? '—') ?></td>
                                    <td><?= availability_badge($r['availability']) ?></td>
                                    <td>
                                        <?php if ($r['verification_status'] === 'Verified'): ?>
                                            <span class="badge badge-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?= e(BASE_URL) ?>admin/residents.php?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                                            <?php if ($r['verification_status'] !== 'Verified'): ?>
                                                <a href="<?= e(BASE_URL) ?>admin/residents.php?action=verify&id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Verify</a>
                                            <?php endif; ?>
                                            <a href="<?= e(BASE_URL) ?>admin/residents.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this resident?')">Delete</a>
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
<?php endif; ?>

<!-- Add Resident Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Resident</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone <span class="required">*</span></label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Block</label>
                        <select class="form-control" name="block">
                            <option value="">Select Block</option>
                            <?php foreach ($blocks as $b): ?>
                                <option value="<?= e($b['block_name']) ?>"><?= e($b['block_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Door Number</label>
                        <input type="text" class="form-control" name="door_number" placeholder="e.g. A-101">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" class="form-control" name="occupation">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" name="emergency_contact">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="add_resident" class="btn btn-primary">Add Resident</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
