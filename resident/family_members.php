<?php
/**
 * CommUnity - Resident: Family Members
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Family Members';
$activePage = 'family_members';
$role = 'resident';

$resident_id = current_user_id();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_family'])) {
        $family_member_name = trim($_POST['family_member_name'] ?? '');
        $relation = trim($_POST['relation'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $move_in_date = trim($_POST['move_in_date'] ?? '');

        if (empty($family_member_name) || empty($relation)) {
            set_flash('danger', 'Please fill required fields: name and relation.');
        } else {
            try {
                $stmt = db()->prepare("INSERT INTO resident_family_members (resident_id, family_member_name, relation, phone, email, address, move_in_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
                $stmt->execute([$resident_id, $family_member_name, $relation, $phone, $email, $address, $move_in_date]);
                log_activity(current_user_id(), 'Added Family Member', 'Family', "Added family member: $family_member_name");
                set_flash('success', 'Family member added successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to add family member.');
            }
        }
        header("Location: " . BASE_URL . "resident/family_members.php");
        exit;
    }

    if (isset($_POST['remove_family'])) {
        $id = (int) ($_POST['id'] ?? 0);
        if (!empty($id)) {
            try {
                $stmt = db()->prepare("DELETE FROM resident_family_members WHERE id = ? AND resident_id = ?");
                $stmt->execute([$id, $resident_id]);
                log_activity(current_user_id(), 'Removed Family Member', 'Family', "Removed family member ID $id.");
                set_flash('success', 'Family member removed successfully.');
            } catch (PDOException $e) {
                set_flash('danger', 'Failed to remove family member.');
            }
        }
        header("Location: " . BASE_URL . "resident/family_members.php");
        exit;
    }
}

// Fetch family members
try {
    $stmt = db()->query("SELECT * FROM resident_family_members WHERE resident_id = $resident_id ORDER BY relation, family_member_name");
    $family = $stmt->fetchAll();
} catch (PDOException $e) {
    $family = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <h2>Family Members</h2>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addFamilyModal">
        + Add Family Member
    </button>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($family)): ?>
            <div class="empty-state">
                <p>No family members added yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Relation</th>
                            <th>Phone</th>
                            <th>Move-in Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($family as $f): ?>
                            <tr>
                                <td><?= e($f['family_member_name']) ?></td>
                                <td><span class="badge badge-info"><?= e($f['relation']) ?></span></td>
                                <td><?= e($f['phone'] ?: '—') ?></td>
                                <td><?= $f['move_in_date'] ? format_date($f['move_in_date']) : '—' ?></td>
                                <td><span class="badge badge-<?= $f['status'] === 'Active' ? 'success' : 'danger' ?>"><?= e($f['status']) ?></span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <span class="text-muted">—</span>
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

<!-- Add Family Member Modal -->
<div class="modal-overlay" id="addFamilyModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Family Member</h3>
            <button class="modal-close" onclick="document.getElementById('addFamilyModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="add_family" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Family Member Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="family_member_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relation <span class="required">*</span></label>
                        <input type="text" class="form-control" name="relation" required>
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
                    <label class="form-label">Move-in Date</label>
                    <input type="date" class="form-control" name="move_in_date">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Add Family Member</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addFamilyModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>