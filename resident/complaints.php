
<?php
/**
 * CommUnity - Resident Complaint Management
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'My Complaints';
$activePage = 'complaints';
$role = 'resident';

$userId = current_user_id();

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_complaint'])) {

    $type = trim($_POST['type'] ?? 'Other');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $allowedTypes = ['Maintenance', 'Security', 'Facility', 'Noise', 'Other'];

    if (!in_array($type, $allowedTypes, true)) {
        set_flash('danger', 'Invalid complaint type.');
    } elseif ($title === '' || $description === '') {
        set_flash('danger', 'Please fill in all required fields.');
    } else {
        try {
            $stmt = db()->prepare("
                INSERT INTO complaints
                (complainer_id, type, title, description, status)
                VALUES (?, ?, ?, ?, 'Open')
            ");

            $stmt->execute([
                $userId,
                $type,
                $title,
                $description
            ]);

            log_activity(
                $userId,
                'Submitted Complaint',
                'Complaints',
                'Submitted complaint: ' . $title
            );

            set_flash('success', 'Complaint submitted successfully.');

        } catch (PDOException $e) {
            error_log('Resident complaint submission error: ' . $e->getMessage());
            set_flash('danger', 'Failed to submit complaint.');
        }
    }

    header("Location: " . BASE_URL . "resident/complaints.php");
    exit;
}

// Fetch only this resident's complaints
$stmt = db()->prepare("
    SELECT *
    FROM complaints
    WHERE complainer_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$complaints = $stmt->fetchAll();

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Submit a Complaint</h3>
    </div>

    <div class="card-body">
        <form method="POST">

            <div class="form-group">
                <label for="type">Complaint Type</label>
                <select name="type" id="type" class="form-control" required>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Security">Security</option>
                    <option value="Facility">Facility</option>
                    <option value="Noise">Noise</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Complaint Title</label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    class="form-control"
                    maxlength="150"
                    placeholder="Enter complaint title"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea
                    name="description"
                    id="description"
                    class="form-control"
                    rows="4"
                    placeholder="Describe your complaint"
                    required
                ></textarea>
            </div>

            <button type="submit" name="add_complaint" class="btn btn-primary">
                Submit Complaint
            </button>

        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h3>My Complaints</h3>
    </div>

    <div class="card-body">

        <?php if (empty($complaints)): ?>

            <div class="empty-state">
                <p>You have not submitted any complaints yet.</p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td><?= e($complaint['type']) ?></td>

                                <td><?= e($complaint['title']) ?></td>

                                <td><?= e($complaint['description']) ?></td>

                                <td>
                                    <?php
                                    $statusClass = 'warning';

                                    if ($complaint['status'] === 'Resolved') {
                                        $statusClass = 'success';
                                    } elseif ($complaint['status'] === 'In Progress') {
                                        $statusClass = 'info';
                                    } elseif ($complaint['status'] === 'Closed') {
                                        $statusClass = 'secondary';
                                    }
                                    ?>

                                    <span class="badge badge-<?= $statusClass ?>">
                                        <?= e($complaint['status']) ?>
                                    </span>
                                </td>

                                <td><?= format_datetime($complaint['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
