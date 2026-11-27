<?php
/**
 * CommUnity - Resident: Local Help (Skill Search)
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('resident');

$pageTitle = 'Local Help';
$activePage = 'local_help';
$role = 'resident';

// Get categories
$categories = ['Plumbing', 'Electrical', 'Computer / Technology', 'Tuition', 'Gardening', 'Cleaning', 'Repairs', 'Cooking', 'Photography', 'Event Assistance', 'Language', 'Fitness', 'Other'];

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Search Skills</h3>
        <a href="<?= e(BASE_URL) ?>resident/my_skills.php" class="btn btn-sm btn-primary">Offer My Skill</a>
    </div>
    <div class="card-body">
        <div class="search-bar">
            <div class="search-input-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" class="form-control search-input" id="skillSearch" placeholder="Search skills... e.g. Plumber">
            </div>
            <select class="form-control filter-select" id="skillCategoryFilter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="skillResults">
            <div class="empty-state">
                <p>Search for skills available in your community.</p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
