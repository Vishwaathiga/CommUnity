<?php
/**
 * CommUnity - Resident: My Profile
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'My Profile';
$activePage = 'profile';
$role = 'resident';

$me = get_user(current_user_id());

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $emergency = trim($_POST['emergency_contact'] ?? '');

    try {
        $sql = "UPDATE users SET name=?, phone=?, email=?, occupation=?, emergency_contact=? WHERE id=?";
        $params = [$name, $phone, $email, $occupation, $emergency, current_user_id()];

        // Handle password change
        if (!empty($_POST['new_password'])) {
            if (strlen($_POST['new_password']) < 6) {
                set_flash('danger', 'Password must be at least 6 characters.');
                header("Location: " . BASE_URL . "resident/profile.php");
                exit;
            }
            $hashed = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $sql = "UPDATE users SET name=?, phone=?, email=?, occupation=?, emergency_contact=?, password=? WHERE id=?";
            $params = [$name, $phone, $email, $occupation, $emergency, $hashed, current_user_id()];
        }

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['tmp_name'])) {
            $result = handle_file_upload($_FILES['profile_image'], 'profile_');
            if (!empty($result) && !in_array($result, ['Invalid file type. Only JPG, PNG, GIF, WebP allowed.', 'File too large. Maximum 2MB allowed.', 'Invalid image file.', 'Failed to upload file.'])) {
                $sql = "UPDATE users SET name=?, phone=?, email=?, occupation=?, emergency_contact=?, profile_image=? WHERE id=?";
                $params = [$name, $phone, $email, $occupation, $emergency, $result, current_user_id()];
                if (!empty($_POST['new_password'])) {
                    $hashed = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                    $sql = "UPDATE users SET name=?, phone=?, email=?, occupation=?, emergency_contact=?, profile_image=?, password=? WHERE id=?";
                    $params = [$name, $phone, $email, $occupation, $emergency, $result, $hashed, current_user_id()];
                }
            } elseif (!empty($result)) {
                set_flash('danger', $result);
                header("Location: " . BASE_URL . "resident/profile.php");
                exit;
            }
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $_SESSION['name'] = $name;
        log_activity(current_user_id(), 'Updated Profile', 'Profile', 'Updated profile information.');
        set_flash('success', 'Profile updated successfully.');
    } catch (PDOException $e) {
        set_flash('danger', 'Failed to update profile.');
    }
    header("Location: " . BASE_URL . "resident/profile.php");
    exit;
}

// Handle availability update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $availability = $_POST['availability'] ?? 'Available';
    if (in_array($availability, ['Available', 'Inside', 'Not Available'])) {
        try {
            $stmt = db()->prepare("UPDATE users SET availability = ? WHERE id = ?");
            $stmt->execute([$availability, current_user_id()]);
            log_activity(current_user_id(), 'Updated Availability', 'Profile', "Availability changed to: $availability.");
            set_flash('success', 'Availability updated successfully.');
        } catch (PDOException $e) {
            set_flash('danger', 'Failed to update availability.');
        }
    }
    header("Location: " . BASE_URL . "resident/profile.php");
    exit;
}

// Get my skills
try {
    $stmt = db()->prepare("SELECT * FROM skills WHERE resident_id = ? ORDER BY created_at DESC");
    $stmt->execute([current_user_id()]);
    $my_skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $my_skills = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>My Profile</h3>
    </div>
    <div class="card-body">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="<?= e(profile_image_url($me['profile_image'] ?? null)) ?>" alt="Profile">
            </div>
            <div class="profile-info">
                <h2><?= e($me['name']) ?></h2>
                <p><?= e($me['occupation'] ?? 'Resident') ?></p>
                <div class="profile-meta">
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                        <?= e($me['block'] . ' ' . $me['door_number']) ?>
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <?= e($me['phone']) ?>
                    </span>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                        <?= e($me['email']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Edit Profile -->
    <div class="card">
        <div class="card-header">
            <h3>Edit Profile</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Profile Image</label>
                    <input type="file" class="form-control" name="profile_image" accept="image/*">
                    <small class="form-hint">JPG, PNG, GIF, WebP. Max 2MB.</small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?= e($me['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= e($me['email']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= e($me['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" class="form-control" name="occupation" value="<?= e($me['occupation']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Emergency Contact</label>
                    <input type="text" class="form-control" name="emergency_contact" value="<?= e($me['emergency_contact']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" name="new_password" placeholder="Min 6 characters">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>

    <!-- Availability & Skills -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3>My Availability</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">This status is visible to the Watchman for security and delivery purposes.</p>
                <form method="POST">
                    <div class="availability-toggle mb-3">
                        <button type="button" class="availability-btn <?= $me['availability'] === 'Available' ? 'active-available' : '' ?>" onclick="setAvailability('Available')">Available</button>
                        <button type="button" class="availability-btn <?= $me['availability'] === 'Inside' ? 'active-inside' : '' ?>" onclick="setAvailability('Inside')">Inside</button>
                        <button type="button" class="availability-btn <?= $me['availability'] === 'Not Available' ? 'active-not-available' : '' ?>" onclick="setAvailability('Not Available')">Not Available</button>
                    </div>
                    <input type="hidden" name="availability" id="availabilityInput" value="<?= e($me['availability']) ?>">
                    <button type="submit" name="update_availability" class="btn btn-primary btn-sm">Save Availability</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3>My Skills</h3>
                <a href="<?= e(BASE_URL) ?>resident/my_skills.php" class="btn btn-sm btn-outline">Manage</a>
            </div>
            <div class="card-body">
                <?php if (empty($my_skills)): ?>
                    <div class="empty-state"><p>No skills added yet.</p></div>
                <?php else: ?>
                    <?php foreach ($my_skills as $s): ?>
                        <div class="d-flex justify-between align-center mb-2">
                            <div>
                                <strong><?= e($s['skill_name']) ?></strong>
                                <span class="badge badge-info ml-2"><?= e($s['category']) ?></span>
                            </div>
                            <small class="text-muted">₹<?= e($s['expected_cost']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function setAvailability(status) {
    document.getElementById('availabilityInput').value = status;
    document.querySelectorAll('.availability-btn').forEach(btn => {
        btn.classList.remove('active-available', 'active-inside', 'active-not-available');
    });
    event.target.classList.add('active-' + status.toLowerCase().replace(' ', '-'));
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
