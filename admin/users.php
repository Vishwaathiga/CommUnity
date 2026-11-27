<?php
/**
 * CommUnity - Admin: User Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Users';
$activePage = 'users';
$role = 'admin';

$action = $_GET['action'] ?? 'list';

// Handle delete
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id !== current_user_id()) {
        try {
            $user = get_user($id);
            $stmt = db()->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            log_activity(current_user_id(), 'Deleted User', 'Users', "Deleted user: " . ($user['name'] ?? 'ID ' . $id));
            set_flash('success', 'User deleted successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to delete user.');
        }
    } else {
        set_flash('danger', 'You cannot delete your own account.');
    }
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

// Handle add watchman/admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_role = $_POST['user_role'] ?? 'watchman';

    if ($user_role !== 'watchman' && $user_role !== 'admin') {
        set_flash('danger', 'Invalid role.');
    } elseif (empty($name) || empty($email) || empty($phone) || empty($password)) {
        set_flash('danger', 'Please fill all required fields.');
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare("INSERT INTO users (name, email, phone, password, role, verification_status) VALUES (?, ?, ?, ?, ?, 'Verified')");
            $stmt->execute([$name, $email, $phone, $hashed, $user_role]);
            log_activity(current_user_id(), 'Added User', 'Users', "Added $user_role: $name.");
            set_flash('success', 'User added successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to add user. Email may already exist.');
        }
    }
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

// Fetch all non-resident users
try {
    $stmt = db()->query("SELECT * FROM users WHERE role != 'resident' ORDER BY role, name");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted">Manage watchmen and administrators.</p>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('active')">+ Add User</button>
</div>

<div class="card">
    <div class="card-header">
        <h3>System Users</h3>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="empty-state"><p>No users found.</p></div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= e($u['name']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($u['phone']) ?></td>
                                <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-primary' : 'badge-info' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                                <td><span class="badge badge-success"><?= e($u['verification_status']) ?></span></td>
                                <td>
                                    <?php if ($u['id'] !== current_user_id()): ?>
                                        <a href="<?= e(BASE_URL) ?>admin/users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone <span class="required">*</span></label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select class="form-control" name="user_role">
                            <option value="watchman">Watchman</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
