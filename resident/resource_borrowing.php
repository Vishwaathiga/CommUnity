<?php
/**
 * CommUnity - Resident: Resource Borrowing & Returns
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Resource Borrowing';
$activePage = 'resource_borrowing';
$role = 'resident';

$resident_id = current_user_id();

// Fetch my borrowed resources
try {
    $stmt = db()->prepare("
        SELECT rt.*, r.item_name, r.category, r.sharing_type, u.name AS owner_name
        FROM resource_transactions rt
        JOIN resources r ON rt.resource_id = r.id
        JOIN users u ON r.owner_id = u.id
        WHERE rt.requester_id = ?
        ORDER BY rt.borrowed_at DESC
    ");
    $stmt->execute([$resident_id]);
    $borrowed = $stmt->fetchAll();
} catch (PDOException $e) {
    $borrowed = [];
}

// Fetch my returned resources (completed transactions)
try {
    $stmt = db()->prepare("
        SELECT rt.*, r.item_name, r.category, u.name AS owner_name
        FROM resource_transactions rt
        JOIN resources r ON rt.resource_id = r.id
        JOIN users u ON r.owner_id = u.id
        WHERE rt.requester_id = ? AND rt.status = 'Returned'
        ORDER BY rt.returned_at DESC
    ");
    $stmt->execute([$resident_id]);
    $returned = $stmt->fetchAll();
} catch (PDOException $e) {
    $returned = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="d-flex justify-between align-center mb-3">
    <h2>Resource Borrowing</h2>
    <span class="text-muted">Manage your borrowed items</span>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Currently Borrowed</h3>
        </div>
        <div class="card-body">
            <?php if (empty($borrowed)): ?>
                <div class="empty-state">
                    <p>No items currently borrowed.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Owner</th>
                            <th>Borrowed</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowed as $t): ?>
                            <tr>
                                <td><?= e($t['item_name']) ?></td>
                                <td><?= e($t['owner_name']) ?></td>
                                <td><?= format_datetime($t['borrowed_at']) ?></td>
                                <td><?= format_datetime($t['due_date']) ?></td>
                                <td><span class="badge badge-<?= $t['status'] === 'Borrowed' ? 'warning' : ($t['status'] === 'Returned' ? 'success' : 'danger') ?>"><?= e($t['status']) ?></span></td>
                                <td>
                                    <?php if ($t['status'] === 'Borrowed'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="confirmReturn(<?= $t['id'] ?>)">Mark Returned</button>
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

<div class="card">
    <div class="card-header">
        <h3>Returned Items</h3>
    </div>
    <div class="card-body">
        <?php if (empty($returned)): ?>
            <div class="empty-state">
                <p>No items returned yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Owner</th>
                            <th>Returned</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returned as $t): ?>
                            <tr>
                                <td><?= e($t['item_name']) ?></td>
                                <td><?= e($t['owner_name']) ?></td>
                                <td><?= format_datetime($t['returned_at']) ?></td>
                                <td><span class="badge badge-success">Returned</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mark Return Modal -->
<div class="modal-overlay" id="confirmReturnModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Mark Item as Returned</h3>
            <button class="modal-close" onclick="document.getElementById('confirmReturnModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to mark this item as returned?</p>
            <input type="hidden" id="return_transaction_id">
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="confirmReturnAction()">Yes, Mark Returned</button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('confirmReturnModal').classList.remove('active')">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmReturn(id) {
    document.getElementById('return_transaction_id').value = id;
    document.getElementById('confirmReturnModal').classList.add('active');
}

function confirmReturnAction() {
    const id = document.getElementById('return_transaction_id').value;
    
    fetch(BASE_URL + 'api/resource_transactions.php?action=return', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            setFlashMessage('success', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            setFlashMessage('danger', data.message);
        }
    });
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>