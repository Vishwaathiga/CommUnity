
<?php
/**
 * CommUnity - Admin: Announcement Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Announcements';
$activePage = 'announcements';
$role = 'admin';

// Handle add, update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_announcement'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Published';

        if ($title === '' || $description === '') {
            set_flash('danger', 'Please fill in all required fields.');
        } elseif (!in_array($status, ['Draft', 'Published'], true)) {
            set_flash('danger', 'Invalid announcement status.');
        } else {
            try {
                $stmt = db()->prepare("
                    INSERT INTO announcements
                    (title, description, created_by, status)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title,
                    $description,
                    current_user_id(),
                    $status
                ]);

                log_activity(current_user_id(), 'Added Announcement', 'Announcements', "Added: $title.");
                set_flash('success', 'Announcement added successfully.');
            } catch (PDOException $e) {
                error_log('Announcement creation error: ' . $e->getMessage());
                set_flash('danger', 'Failed to add announcement.');
            }
        }
    }

    if (isset($_POST['update_announcement'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Published';

        if ($id <= 0 || $title === '' || $description === '') {
            set_flash('danger', 'Please provide valid announcement details.');
        } elseif (!in_array($status, ['Draft', 'Published'], true)) {
            set_flash('danger', 'Invalid announcement status.');
        } else {
            try {
                $stmt = db()->prepare("
                    UPDATE announcements
                    SET title = ?, description = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $status, $id]);

                log_activity(current_user_id(), 'Updated Announcement', 'Announcements', "Updated announcement ID $id.");
                set_flash('success', 'Announcement updated successfully.');
            } catch (PDOException $e) {
                error_log('Announcement update error: ' . $e->getMessage());
                set_flash('danger', 'Failed to update announcement.');
            }
        }
    }

    if (isset($_POST['delete_announcement'])) {
        $id = (int) ($_POST['id'] ?? 0);

        try {
            $stmt = db()->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$id]);

            log_activity(current_user_id(), 'Deleted Announcement', 'Announcements', "Deleted announcement ID $id.");
            set_flash('success', 'Announcement deleted successfully.');
        } catch (PDOException $e) {
            error_log('Announcement deletion error: ' . $e->getMessage());
            set_flash('danger', 'Failed to delete announcement.');
        }
    }

    header("Location: " . BASE_URL . "admin/announcements.php");
    exit;
}

// Fetch announcements
$stmt = db()->query("
    SELECT a.*, u.name AS admin_name
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
");
$announcements = $stmt->fetchAll();

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Create Announcement</h3>
    </div>

    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" maxlength="150" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description <span class="required">*</span></label>
                <textarea name="description" class="form-control" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Published">Published</option>
                    <option value="Draft">Draft</option>
                </select>
            </div>

            <button type="submit" name="add_announcement" class="btn btn-primary">
                Publish Announcement
            </button>
        </form>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3>All Announcements</h3>
    </div>

    <div class="card-body">
        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <p>No announcements created yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements as $a): ?>
                            <tr>
                                <td><?= e($a['title']) ?></td>
                                <td><?= e($a['description']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $a['status'] === 'Published' ? 'success' : 'warning' ?>">
                                        <?= e($a['status']) ?>
                                    </span>
                                </td>
                                <td><?= e($a['admin_name']) ?></td>
                                <td><?= format_datetime($a['created_at']) ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline"
                                        onclick="editAnnouncement(
                                            <?= (int) $a['id'] ?>,
                                            <?= htmlspecialchars(json_encode($a['title']), ENT_QUOTES, 'UTF-8') ?>,
                                            <?= htmlspecialchars(json_encode($a['description']), ENT_QUOTES, 'UTF-8') ?>,
                                            <?= htmlspecialchars(json_encode($a['status']), ENT_QUOTES, 'UTF-8') ?>
                                        )"
                                    >Edit</button>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?')">
                                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                        <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal-overlay" id="editAnnouncementModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Announcement</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('editAnnouncementModal').classList.remove('active')">&times;</button>
        </div>

        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="id" id="editAnnouncementId">

                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="editAnnouncementTitle" class="form-control" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="editAnnouncementDescription" class="form-control" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="editAnnouncementStatus" class="form-control">
                        <option value="Published">Published</option>
                        <option value="Draft">Draft</option>
                    </select>
                </div>

                <button type="submit" name="update_announcement" class="btn btn-primary">
                    Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function editAnnouncement(id, title, description, status) {
    document.getElementById('editAnnouncementId').value = id;
    document.getElementById('editAnnouncementTitle').value = title;
    document.getElementById('editAnnouncementDescription').value = description;
    document.getElementById('editAnnouncementStatus').value = status;

    document.getElementById('editAnnouncementModal').classList.add('active');
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
