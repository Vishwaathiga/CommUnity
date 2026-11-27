<?php
/**
 * CommUnity - Resident: Help Requests
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Help Requests';
$activePage = 'help_requests';
$role = 'resident';

// Fetch help requests (both sent and received)
try {
    // Requests I sent
    $stmt = db()->prepare("
        SELECT hr.*, s.skill_name, s.category, u.name AS provider_name
        FROM help_requests hr
        JOIN skills s ON hr.skill_id = s.id
        LEFT JOIN users u ON hr.provider_id = u.id
        WHERE hr.requester_id = ?
        ORDER BY hr.created_at DESC
    ");
    $stmt->execute([current_user_id()]);
    $sent_requests = $stmt->fetchAll();

    // Requests I received (as provider)
    $stmt = db()->prepare("
        SELECT hr.*, s.skill_name, s.category, u.name AS requester_name
        FROM help_requests hr
        JOIN skills s ON hr.skill_id = s.id
        JOIN users u ON hr.requester_id = u.id
        WHERE hr.provider_id = ?
        ORDER BY hr.created_at DESC
    ");
    $stmt->execute([current_user_id()]);
    $received_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $sent_requests = $received_requests = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="grid-2">
    <!-- Sent Requests -->
    <div class="card">
        <div class="card-header">
            <h3>Requests I Sent</h3>
        </div>
        <div class="card-body">
            <?php if (empty($sent_requests)): ?>
                <div class="empty-state"><p>No help requests sent yet.</p></div>
            <?php else: ?>
                <?php foreach ($sent_requests as $hr): ?>
                    <div class="notification-item">
                        <div class="notification-icon help">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </div>
                        <div class="notification-content">
                            <div class="d-flex justify-between align-center">
                                <h5><?= e($hr['skill_name']) ?></h5>
                                <?= help_status_badge($hr['status']) ?>
                            </div>
                            <p><?= e($hr['description']) ?></p>
                            <small class="text-muted">
                                Provider: <?= e($hr['provider_name'] ?? 'Not assigned yet') ?> |
                                Date: <?= format_date($hr['preferred_date']) ?>
                            </small>
                            <?php if ($hr['status'] === 'Pending'): ?>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-danger" onclick="cancelHelpRequest(<?= $hr['id'] ?>)">Cancel</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($hr['status'] === 'Completed' && !$hr['rating']): ?>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-primary" onclick="rateHelpRequest(<?= $hr['id'] ?>)">Rate & Feedback</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($hr['rating']): ?>
                                <div class="mt-2">
                                    <span class="badge badge-success">Rated <?= $hr['rating'] ?>/5</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Received Requests -->
    <div class="card">
        <div class="card-header">
            <h3>Requests I Received</h3>
        </div>
        <div class="card-body">
            <?php if (empty($received_requests)): ?>
                <div class="empty-state"><p>No help requests received yet.</p></div>
            <?php else: ?>
                <?php foreach ($received_requests as $hr): ?>
                    <div class="notification-item">
                        <div class="notification-icon help">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </div>
                        <div class="notification-content">
                            <div class="d-flex justify-between align-center">
                                <h5><?= e($hr['skill_name']) ?></h5>
                                <?= help_status_badge($hr['status']) ?>
                            </div>
                            <p><?= e($hr['description']) ?></p>
                            <small class="text-muted">
                                From: <?= e($hr['requester_name']) ?> |
                                Date: <?= format_date($hr['preferred_date']) ?>
                            </small>
                            <?php if ($hr['status'] === 'Pending'): ?>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-success" onclick="acceptHelpRequest(<?= $hr['id'] ?>)">Accept</button>
                                </div>
                            <?php endif; ?>
                            <?php if ($hr['status'] === 'Accepted'): ?>
                                <div class="notification-actions">
                                    <button class="btn btn-sm btn-primary" onclick="completeHelpRequest(<?= $hr['id'] ?>)">Mark Completed</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function rateHelpRequest(requestId) {
    const rating = prompt('Rate from 1 to 5:', '5');
    if (!rating || rating < 1 || rating > 5) return;
    const feedback = prompt('Feedback (optional):', '');

    fetch(BASE_URL + 'api/help_requests.php?action=rate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'request_id=' + requestId + '&rating=' + rating + '&feedback=' + encodeURIComponent(feedback || '')
    })
    .then(r => r.json())
    .then(data => {
        setFlashMessage(data.success ? 'success' : 'danger', data.message);
        setTimeout(() => location.reload(), 1000);
    });
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
