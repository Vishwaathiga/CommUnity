
<?php
/**
 * CommUnity - Admin: Reports & Analytics
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Reports';
$activePage = 'reports';
$role = 'admin';

// Validate date filters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

if (
    ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) ||
    ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) ||
    ($startDate !== '' && !checkdate((int) substr($startDate, 5, 2), (int) substr($startDate, 8, 2), (int) substr($startDate, 0, 4))) ||
    ($endDate !== '' && !checkdate((int) substr($endDate, 5, 2), (int) substr($endDate, 8, 2), (int) substr($endDate, 0, 4))) ||
    ($startDate !== '' && $endDate !== '' && $startDate > $endDate)
) {
    $startDate = '';
    $endDate = '';
}

// Get date condition for a table
$dateCondition = function ($table) use ($startDate, $endDate) {

    if ($startDate === '' || $endDate === '') {
        return ['1=1', []];
    }

    try {
        $check = db()->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");

        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            return ['1=1', []];
        }

        return [
            'DATE(created_at) BETWEEN ? AND ?',
            [$startDate, $endDate]
        ];

    } catch (PDOException $e) {
        return ['1=1', []];
    }
};

// Count records
$countRecords = function ($table, $extraCondition = '1=1', $extraParams = []) use ($dateCondition) {

    [$condition, $dateParams] = $dateCondition($table);

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM `$table` WHERE $condition AND ($extraCondition)"
    );

    $stmt->execute(array_merge($dateParams, $extraParams));

    return (int) $stmt->fetchColumn();
};

// Group records for reports
$fetchGrouped = function ($table, $column) use ($dateCondition) {

    [$condition, $params] = $dateCondition($table);

    $stmt = db()->prepare("
        SELECT `$column` AS label, COUNT(*) AS count
        FROM `$table`
        WHERE $condition
        GROUP BY `$column`
        ORDER BY `$column`
    ");

    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};

// Fetch all report data
try {

    // Summary cards
    $totalResidents = $countRecords('users', "role = 'resident'");
    $totalVisitors = $countRecords('visitors');
    $totalDeliveries = $countRecords('deliveries');
    $openComplaints = $countRecords('complaints', "status = 'Open'");
    $activeWorkers = $countRecords('workers', "status = 'Active'");

    // Existing reports
    $visitors_by_type = $fetchGrouped('visitors', 'visitor_type');
    $visitors_by_status = $fetchGrouped('visitors', 'status');

    $deliveries_by_type = $fetchGrouped('deliveries', 'delivery_type');
    $deliveries_by_status = $fetchGrouped('deliveries', 'status');

    $residents_by_block = $fetchGrouped('users', 'block');
    $help_by_status = $fetchGrouped('help_requests', 'status');

    $resources_by_type = $fetchGrouped('resources', 'sharing_type');
    $skills_by_category = $fetchGrouped('skills', 'category');

    // New complaint reports
    $complaints_by_status = $fetchGrouped('complaints', 'status');
    $complaints_by_type = $fetchGrouped('complaints', 'type');

    // New worker reports
    $workers_by_role = $fetchGrouped('workers', 'role');
    $workers_by_status = $fetchGrouped('workers', 'status');

    // New announcement report
    $announcements_by_status = $fetchGrouped('announcements', 'status');

} catch (PDOException $e) {

    $totalResidents = $totalVisitors = $totalDeliveries = 0;
    $openComplaints = $activeWorkers = 0;

    $visitors_by_type = $visitors_by_status = [];
    $deliveries_by_type = $deliveries_by_status = [];
    $residents_by_block = $help_by_status = [];
    $resources_by_type = $skills_by_category = [];
    $complaints_by_status = $complaints_by_type = [];
    $workers_by_role = $workers_by_status = [];
    $announcements_by_status = [];

    set_flash('danger', 'Unable to load some report data. Please check your database tables.');
}

// Display report table
function display_report_table($title, $firstColumn, $rows) {
    ?>
    <div class="card">
        <div class="card-header">
            <h3><?= e($title) ?></h3>
        </div>

        <div class="card-body">
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <p>No data available.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?= e($firstColumn) ?></th>
                                <th>Count</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= e($row['label'] ?? 'Not specified') ?></td>
                                    <td><?= (int) $row['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

require __DIR__ . '/../includes/top.php';
?>

<style>
.report-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.report-summary .card {
    margin-bottom: 0;
}

.report-number {
    font-size: 28px;
    font-weight: 700;
    margin-top: 8px;
}

.report-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.report-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .report-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .sidebar,
    .topbar,
    .report-actions,
    .btn,
    .page-header button {
        display: none !important;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .card {
        break-inside: avoid;
        box-shadow: none !important;
    }

    body {
        background: white !important;
    }

    .report-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- PAGE HEADER -->
<div class="d-flex justify-between align-center mb-3">
    <div>
        <h2>Reports & Analytics</h2>
        <p>Community activity and management overview.</p>
    </div>

    <div class="report-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            Print / Save PDF
        </button>
    </div>
</div>

<!-- DATE FILTER -->
<div class="card">
    <div class="card-header">
        <h3>Filter Reports by Date</h3>
    </div>

    <div class="card-body">
        <form method="GET" class="report-actions">

            <div class="form-group">
                <label class="form-label">From Date</label>
                <input
                    type="date"
                    name="start_date"
                    class="form-control"
                    value="<?= e($startDate) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">To Date</label>
                <input
                    type="date"
                    name="end_date"
                    class="form-control"
                    value="<?= e($endDate) ?>">
            </div>

            <button type="submit" class="btn btn-primary">
                Apply Filter
            </button>

            <a href="<?= e(BASE_URL) ?>admin/reports.php" class="btn btn-outline">
                Clear Filter
            </a>

        </form>

        <small>
            Select both dates to filter records by their creation date.
            Without a date range, all records are included.
        </small>
    </div>
</div>

<!-- SUMMARY CARDS -->
<h3 class="mb-3">Community Overview</h3>

<div class="report-summary">

    <div class="card">
        <div class="card-body">
            <p>Total Residents</p>
            <div class="report-number"><?= $totalResidents ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p>Total Visitors</p>
            <div class="report-number"><?= $totalVisitors ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p>Total Deliveries</p>
            <div class="report-number"><?= $totalDeliveries ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p>Open Complaints</p>
            <div class="report-number"><?= $openComplaints ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p>Active Workers</p>
            <div class="report-number"><?= $activeWorkers ?></div>
        </div>
    </div>

</div>

<!-- EXISTING REPORTS -->
<h3 class="mb-3">Existing Community Reports</h3>

<div class="report-grid">

    <?php display_report_table('Visitors by Type', 'Visitor Type', $visitors_by_type); ?>

    <div class="card">
        <div class="card-header"><h3>Visitors by Status</h3></div>
        <div class="card-body">
            <?php if (empty($visitors_by_status)): ?>
                <div class="empty-state"><p>No data available.</p></div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Status</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($visitors_by_status as $v): ?>
                            <tr>
                                <td><?= visitor_status_badge($v['label']) ?></td>
                                <td><?= (int) $v['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php display_report_table('Deliveries by Type', 'Delivery Type', $deliveries_by_type); ?>

    <div class="card">
        <div class="card-header"><h3>Deliveries by Status</h3></div>
        <div class="card-body">
            <?php if (empty($deliveries_by_status)): ?>
                <div class="empty-state"><p>No data available.</p></div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Status</th><th>Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($deliveries_by_status as $d): ?>
                            <tr>
                                <td><?= delivery_status_badge($d['label']) ?></td>
                                <td><?= (int) $d['count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php display_report_table('Residents by Block', 'Block', $residents_by_block); ?>
    <?php display_report_table('Help Requests by Status', 'Status', $help_by_status); ?>
    <?php display_report_table('Resources by Sharing Type', 'Sharing Type', $resources_by_type); ?>
    <?php display_report_table('Skills by Category', 'Category', $skills_by_category); ?>

</div>

<!-- NEW COMPLAINT REPORTS -->
<h3 class="mb-3" style="margin-top: 30px;">Complaint Reports</h3>

<div class="report-grid">
    <?php display_report_table('Complaints by Status', 'Status', $complaints_by_status); ?>
    <?php display_report_table('Complaints by Type', 'Complaint Type', $complaints_by_type); ?>
</div>

<!-- NEW WORKER REPORTS -->
<h3 class="mb-3" style="margin-top: 30px;">Worker Reports</h3>

<div class="report-grid">
    <?php display_report_table('Workers by Role', 'Role', $workers_by_role); ?>
    <?php display_report_table('Workers by Status', 'Status', $workers_by_status); ?>
</div>

<!-- ANNOUNCEMENT REPORT -->
<h3 class="mb-3" style="margin-top: 30px;">Announcement Reports</h3>

<div class="report-grid">
    <?php display_report_table('Announcements by Status', 'Status', $announcements_by_status); ?>
</div>

<?php require __DIR__ . '/../includes/bottom.php'; ?>
