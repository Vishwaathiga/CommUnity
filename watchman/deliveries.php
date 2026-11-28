<?php
/**
 * CommUnity - Watchman: Delivery Management
 * Fields: Delivery Person Name | Arrival Time | Company Name | Delivery Type
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('watchman');

$pageTitle = 'Deliveries';
$activePage = 'deliveries';
$role = 'watchman';

// Handle add delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_delivery'])) {
    $resident_id = (int) ($_POST['resident_id'] ?? 0);
    $person_name = trim($_POST['delivery_person_name'] ?? '');
    $arrival_time = trim($_POST['arrival_time'] ?? '');
    $company = trim($_POST['company_name'] ?? '');
    $type = $_POST['delivery_type'] ?? 'Other';

    if ($resident_id > 0 && $person_name && $arrival_time && $company) {
        try {
            // Save delivery
            $stmt = db()->prepare("
                INSERT INTO deliveries
                (resident_id, delivery_person_name, arrival_time, company_name, delivery_type)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $resident_id,
                $person_name,
                $arrival_time,
                $company,
                $type
            ]);

            // Get newly inserted delivery ID
            $delivery_id = db()->lastInsertId();

            // Get resident details
            $resident = get_user($resident_id);

            // Automatically notify the resident
            $notificationSent = false;

            if ($resident) {
                $message = "A new delivery has arrived for you.\n"
                    . "Delivery Person: {$person_name}\n"
                    . "Company: {$company}\n"
                    . "Delivery Type: {$type}\n"
                    . "Arrival Time: {$arrival_time}\n"
                    . "Please contact the watchman to collect your delivery.";

                try {
                    create_notification(
                        $resident_id,
                        'Delivery Arrived',
                        $message,
                        'delivery',
                        $delivery_id
                    );

                    $notificationSent = true;
                } catch (Throwable $notificationError) {
                    error_log('Delivery notification failed: ' . $notificationError->getMessage());
                }
            }

            // Show appropriate message
            if ($notificationSent) {
                if ($resident['availability'] === 'Not Available') {
                    set_flash(
                        'warning',
                        'Delivery added and resident notified automatically. You can also send the OTP notification using the button below.'
                    );
                } else {
                    set_flash('success', 'Delivery added successfully. Resident has been notified.');
                }
            } else {
                set_flash(
                    'warning',
                    'Delivery saved successfully, but the resident notification could not be sent.'
                );
            }

            log_activity(
                current_user_id(),
                'Added Delivery',
                'Deliveries',
                "Delivery for resident ID $resident_id by $person_name."
            );

        } catch (PDOException $e) {
            error_log('Delivery save failed: ' . $e->getMessage());
            set_flash('danger', 'Failed to add delivery.');
        }
    } else {
        set_flash('danger', 'Please fill all required fields.');
    }

    header("Location: " . BASE_URL . "watchman/deliveries.php");
    exit;
}

// Fetch residents for dropdown
try {
    $residents = get_residents();
} catch (PDOException $e) {
    $residents = [];
}

// Fetch all deliveries
try {
    $stmt = db()->query("
        SELECT d.*, u.name AS resident_name, u.block, u.door_number, u.availability
        FROM deliveries d JOIN users u ON d.resident_id = u.id
        ORDER BY d.created_at DESC
    ");
    $deliveries = $stmt->fetchAll();
} catch (PDOException $e) {
    $deliveries = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="grid-2">
    <!-- Add Delivery Form -->
    <div class="card">
        <div class="card-header">
            <h3>Add New Delivery</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Select Resident <span class="required">*</span></label>
                    <select class="form-control" id="deliveryResident" name="resident_id" required>
                        <option value="">Select Resident</option>
                        <?php foreach ($residents as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= e($r['name']) ?> — <?= e($r['block'] . ' ' . $r['door_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Availability Alert -->
                <div class="alert" id="availabilityAlert" style="display:none;"></div>

                <div class="form-group">
                    <label class="form-label">Delivery Person Name <span class="required">*</span></label>
                    <input type="text" class="form-control" name="delivery_person_name" placeholder="Enter delivery person name" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Arrival Time <span class="required">*</span></label>
                        <input type="datetime-local" class="form-control" name="arrival_time" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="company_name" placeholder="e.g. Amazon, Swiggy" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Delivery Type <span class="required">*</span></label>
                    <select class="form-control" name="delivery_type" required>
                        <option value="Food">Food</option>
                        <option value="Courier">Courier</option>
                        <option value="E-commerce">E-commerce</option>
                        <option value="Grocery">Grocery</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <button type="submit" name="add_delivery" class="btn btn-primary">Save Delivery</button>
            </form>
        </div>
    </div>

    <!-- Delivery List -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Deliveries</h3>
        </div>
        <div class="card-body">
            <?php if (empty($deliveries)): ?>
                <div class="empty-state"><p>No deliveries recorded yet.</p></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Delivery Person</th>
                                <th>Company</th>
                                <th>Type</th>
                                <th>Resident</th>
                                <th>Status</th>
                                <th>Collection Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $d): ?>
                                <tr>
                                    <td><strong><?= e($d['delivery_person_name']) ?></strong></td>
                                    <td><?= e($d['company_name']) ?></td>
                                    <td><?= e($d['delivery_type']) ?></td>
                                    <td><?= e($d['resident_name'] . ' (' . $d['block'] . ' ' . $d['door_number'] . ')') ?></td>
                                   <td><?= delivery_status_badge($d['status']) ?></td>

<td>
    <?php if ($d['status'] === 'Collected'): ?>
        <span class="badge badge-success">Completed</span>
    <?php else: ?>

        <button
            class="btn btn-sm btn-warning"
            onclick="notifyDeliveryResident(<?= (int) $d['id'] ?>)">
            <?= $d['notification_sent'] ? 'Resend OTP' : 'Send Collection OTP' ?>
        </button>

        <?php if ($d['notification_sent']): ?>
            <button
                class="btn btn-sm btn-success"
                onclick="markDeliveryCollected(<?= (int) $d['id'] ?>)">
                Mark Collected
            </button>
        <?php endif; ?>

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
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
