<?php
/**
 * CommUnity - Resident: Resource Sharing
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Resource Sharing';
$activePage = 'resources';
$role = 'resident';

// Handle add resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $item_name = trim($_POST['item_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $condition = trim($_POST['condition'] ?? '');
    $sharing_type = $_POST['sharing_type'] ?? 'Borrow';
    $price = (float) ($_POST['price'] ?? 0);
    $security_deposit = (float) ($_POST['security_deposit'] ?? 0);
    $availability = $_POST['availability'] ?? 'Available';
    $preferred_contact = trim($_POST['preferred_contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Debug: Check if required fields are present
    if (!$item_name || !$category) {
        set_flash('danger', 'Please fill all required fields. (Item name: ' . ($item_name ? 'OK' : 'MISSING') . ', Category: ' . ($category ? 'OK' : 'MISSING') . ')');
        header("Location: " . BASE_URL . "resident/resources.php");
        exit;
    }

    try {
        $image = '';
        $upload_debug = [];
        
        // Debug: Check file upload
        if (!empty($_FILES['image']['tmp_name'])) {
            $upload_debug[] = "File received: " . $_FILES['image']['name'];
            $upload_debug[] = "Type: " . $_FILES['image']['type'];
            $upload_debug[] = "Size: " . $_FILES['image']['size'];
            $upload_debug[] = "Error code: " . $_FILES['image']['error'];
            
            $result = handle_file_upload($_FILES['image'], 'resource_');
            $upload_debug[] = "Result: " . $result;
            
            if (!empty($result) && !in_array($result, ['Invalid file type. Only JPG, PNG, GIF, WebP allowed.', 'File too large. Maximum 2MB allowed.', 'Invalid image file.', 'Failed to upload file.'])) {
                $image = $result;
            } elseif (!empty($result)) {
                set_flash('danger', 'Upload error: ' . $result . ' | Debug: ' . implode(' | ', $upload_debug));
                header("Location: " . BASE_URL . "resident/resources.php");
                exit;
            }
        }

$stmt = db()->prepare("INSERT INTO resources (owner_id, item_name, category, description, image, `condition`, sharing_type, price, security_deposit, availability, preferred_contact, location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([current_user_id(), $item_name, $category, $description, $image, $condition, $sharing_type, $price, $security_deposit, $availability, $preferred_contact, $location, $notes]);

        log_activity(current_user_id(), 'Added Resource', 'Resources', "Added resource: $item_name.");
        set_flash('success', 'Resource added successfully.');
    } catch (PDOException $e) {
        // Show actual database error for debugging
        set_flash('danger', 'Database error: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
    }
    header("Location: " . BASE_URL . "resident/resources.php");
    exit;
}

$categories = ['Tools', 'Books', 'Furniture', 'Electronics', 'Kitchen', 'Appliances', 'Sports', "Children's Items", 'Others'];

require __DIR__ . '/../includes/top.php';
?>

<div class="grid-2">
    <!-- Add Resource -->
    <div class="card">
        <div class="card-header">
            <h3>Add New Item</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Item Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="item_name" required>
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
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Condition</label>
                        <select class="form-control" name="condition">
                            <option value="">Select</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sharing Type</label>
                        <select class="form-control" name="sharing_type">
                            <option value="Borrow">Borrow</option>
                            <option value="Lend">Lend</option>
                            <option value="Rent">Rent</option>
                            <option value="Sell">Sell</option>
                            <option value="Donate">Donate</option>
                            <option value="Exchange">Exchange</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" class="form-control" name="price" min="0" step="0.01" placeholder="0 for free">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Security Deposit (₹)</label>
                        <input type="number" class="form-control" name="security_deposit" min="0" step="0.01" placeholder="0 if none">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Availability</label>
                        <select class="form-control" name="availability">
                            <option value="Available">Available</option>
                            <option value="Currently Unavailable">Currently Unavailable</option>
                        </select>
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
                </div>
                <div class="form-group">
                    <label class="form-label">Location / Block</label>
                    <input type="text" class="form-control" name="location" placeholder="e.g. Block A">
                </div>
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
                <button type="submit" name="add_resource" class="btn btn-primary">Add Item</button>
            </form>
        </div>
    </div>

    <!-- Search Items -->
    <div class="card">
        <div class="card-header">
            <h3>Search Items</h3>
        </div>
        <div class="card-body">
            <div class="search-bar">
                <div class="search-input-wrapper">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" class="form-control search-input" id="resourceSearch" placeholder="Search items... e.g. Hammer">
                </div>
                <select class="form-control filter-select" id="resourceCategory">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control filter-select" id="resourceSharingType">
                    <option value="">All Types</option>
                    <option value="Borrow">Borrow</option>
                    <option value="Lend">Lend</option>
                    <option value="Rent">Rent</option>
                    <option value="Sell">Sell</option>
                    <option value="Donate">Donate</option>
                    <option value="Exchange">Exchange</option>
                </select>
                <select class="form-control filter-select" id="resourceAvailability">
                    <option value="">All</option>
                    <option value="Available">Available</option>
                    <option value="Currently Unavailable">Currently Unavailable</option>
                </select>
                <select class="form-control filter-select" id="resourcePrice">
                    <option value="">Any Price</option>
                    <option value="Free">Free</option>
                    <option value="Under ₹100">Under ₹100</option>
                    <option value="₹100–₹500">₹100–₹500</option>
                    <option value="Above ₹500">Above ₹500</option>
                </select>
            </div>

            <div id="resourceResults">
                <div class="empty-state">
                    <p>Search for items available in your community.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
