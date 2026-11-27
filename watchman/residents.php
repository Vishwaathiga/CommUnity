<?php
/**
 * CommUnity - Watchman: Resident Directory
 * Table headings: Resident Name | Resident Block | Door No | Status | Action
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('watchman');

$pageTitle = 'Resident Directory';
$activePage = 'residents';
$role = 'watchman';

// Get blocks for filter
try {
    $blocks = get_blocks();
} catch (PDOException $e) {
    $blocks = [];
}

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Resident Directory</h3>
    </div>
    <div class="card-body">
        <!-- Search & Filters -->
        <div class="search-bar">
            <div class="search-input-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" class="form-control search-input" id="residentSearch" placeholder="Search by name, block, or door number...">
            </div>
            <select class="form-control filter-select" id="blockFilter">
                <option value="">All Blocks</option>
                <?php foreach ($blocks as $b): ?>
                    <option value="<?= e($b['block_name']) ?>"><?= e($b['block_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="form-control filter-select" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="Available">Available</option>
                <option value="Inside">Inside</option>
                <option value="Not Available">Not Available</option>
            </select>
        </div>

        <!-- Resident Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Resident Name</th>
                        <th>Resident Block</th>
                        <th>Door No</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="residentTableBody">
                    <tr>
                        <td colspan="5" class="text-center text-muted">Loading residents...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State (hidden by default) -->
        <div class="empty-state" id="residentEmptyState" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <h4>No residents found</h4>
            <p>Try adjusting your search or filters.</p>
        </div>
    </div>
</div>

<script>
// Load residents on page load
document.addEventListener('DOMContentLoaded', function() {
    performResidentSearch();
});
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
