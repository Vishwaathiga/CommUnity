<?php
/**
 * CommUnity - Resident: My Skills
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'My Skills';
$activePage = 'my_skills';
$role = 'resident';

// Handle add skill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skill_name = trim($_POST['skill_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $expected_cost = (float) ($_POST['expected_cost'] ?? 0);
    $availability = trim($_POST['availability'] ?? '');
    $response_time = trim($_POST['response_time'] ?? '');
    $preferred_contact = trim($_POST['preferred_contact'] ?? '');

    if ($skill_name && $category) {
        try {
            $stmt = db()->prepare("INSERT INTO skills (resident_id, skill_name, category, description, experience, expected_cost, availability, response_time, preferred_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([current_user_id(), $skill_name, $category, $description, $experience, $expected_cost, $availability, $response_time, $preferred_contact]);
            log_activity(current_user_id(), 'Added Skill', 'Skills', "Added skill: $skill_name.");
            set_flash('success', 'Skill added successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to add skill.');
        }
    } else {
        set_flash('danger', 'Please fill all required fields.');
    }
    header("Location: " . BASE_URL . "resident/my_skills.php");
    exit;
}

// Handle delete skill
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $stmt = db()->prepare("DELETE FROM skills WHERE id = ? AND resident_id = ?");
        $stmt->execute([$id, current_user_id()]);
        log_activity(current_user_id(), 'Deleted Skill', 'Skills', "Deleted skill ID $id.");
        set_flash('success', 'Skill deleted successfully.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to delete skill.');
    }
    header("Location: " . BASE_URL . "resident/my_skills.php");
    exit;
}

// Fetch my skills
try {
    $stmt = db()->prepare("SELECT * FROM skills WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([current_user_id()]);
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $skills = [];
}

$categories = ['Plumbing', 'Electrical', 'Computer / Technology', 'Tuition', 'Gardening', 'Cleaning', 'Repairs', 'Cooking', 'Photography', 'Event Assistance', 'Language', 'Fitness', 'Other'];

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <p class="text-muted">Manage the skills you offer to the community.</p>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').classList.add('active')">+ Add Skill</button>
</div>

<?php if (empty($skills)): ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <p>No skills added yet. Start offering your skills to the community!</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="grid-3">
        <?php foreach ($skills as $s): ?>
            <div class="skill-card">
                <div class="skill-card-header">
                    <h4><?= e($s['skill_name']) ?></h4>
                    <span class="category"><?= e($s['category']) ?></span>
                </div>
                <p><?= e($s['description']) ?></p>
                <div class="skill-card-details">
                    <span>💰 ₹<?= e($s['expected_cost']) ?></span>
                    <span>⏱ <?= e($s['response_time']) ?></span>
                    <span>📅 <?= e($s['availability']) ?></span>
                    <span>👤 <?= e($s['experience']) ?></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= e(BASE_URL) ?>resident/my_skills.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this skill?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Skill Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Skill</h3>
            <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Skill Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="skill_name" placeholder="e.g. Plumbing" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category <span class="required">*</span></label>
                        <select class="form-control" name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="Describe your skill..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Experience</label>
                        <input type="text" class="form-control" name="experience" placeholder="e.g. 5 years">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Cost (₹)</label>
                        <input type="number" class="form-control" name="expected_cost" min="0" step="0.01" placeholder="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Availability</label>
                        <input type="text" class="form-control" name="availability" placeholder="e.g. Weekdays 6-9 PM">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Response Time</label>
                        <input type="text" class="form-control" name="response_time" placeholder="e.g. Within 2 hours">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Preferred Contact</label>
                    <select class="form-control" name="preferred_contact">
                        <option value="">Select</option>
                        <option value="Phone">Phone</option>
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="Email">Email</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="add_skill" class="btn btn-primary">Add Skill</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
