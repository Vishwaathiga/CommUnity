<?php
/**
 * CommUnity - Admin: Advanced Reports & Analytics
 */

require_once __DIR__ . '/../auth/authentication.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$pageTitle = 'Advanced Reports';
$activePage = 'reports_advanced';
$role = 'admin';

// Handle report generation
$report_type = $_GET['type'] ?? '';

// ... [This would integrate with PHP's DomPDF or generate CSV/Excel files]
// For now, we'll create the interface that triggers report generation

require __DIR__ . '/../includes/top.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Advanced Reports & Analytics</h2>
    </div>
    <div class="card-body">
        <p class="text-muted">Generate PDF or Excel reports for the following:</p>
        
        <div class="grid-2">
            <div class="card">
                <div class="card-body">
                    <h4>Resident Reports</h4>
                    <ul class="list-unstyled">
                        <li>Resident directory with demographics</li>
                        <list>
                            <li>Occupation distribution</li>
                            <li>Availability status counts</li>
                            <list>
                        </ul>
                        <button class="btn btn-sm btn-outline mt-2" onclick="generateReport('resident_demographics')">Generate PDF</button>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h4>Visitor Reports</h4>
                    <ul class="list-unstyled">
                        <li>Visitor statistics by type</li>
                        <li>Entry/exit patterns</li>
                        <li>PIN attempt history</li>
                        <li>Pending approvals</li>
                    </ul>
                    <button class="btn btn-sm btn-outline mt-2" onclick="generateReport('visitor_stats')">Generate PDF</button>
                </ul>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <div class="card-body">
                    <h4>Delivery Reports</h4>
                    <ul class="list-unstyled">
                        <li>Deliveries by type</li>
                        <li>Collection status</li>
                        <li>OTP confirmation rates</li>
                    </ul>
                    <button class="btn btn-sm btn-outline mt-2" onclick="generateReport('delivery_stats')">Generate PDF</button>
                </ul>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h4>Resource Reports</h4>
                    <ul class="list-unstyled">
                        <li>Resource popularity</li>
                        <li>Borrowing trends</li>
                        <li>Return rates</li>
                        <li>Transaction history</li>
                    </ul>
                    <button class="btn btn-sm btn-outline mt-2" onclick="generateReport('resource_trends')">Generate PDF</button>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h4>System Analytics</h4>
                <ul class="list-unstyled">
                    <li>Active users</li>
                    <li>Most active modules</li>
                    <li>Complaint resolution rates</li>
                    <li>Worker performance</li>
                </ul>
                <button class="btn btn-sm btn-outline mt-2" onclick="generateReport('system_analytics')">Generate PDF</button>
            </div>
        </div>
    </div>
</div>

<script>
function generateReport(type) {
    // In a full implementation, this would trigger server-side PDF generation
    // using libraries like DomPDF, TCPDF, or PHPExcel/PhpSpreadsheet
    alert('Report generation for: ' + type + '\n\n' +
        'This would generate a ' + (type.includes('PDF') ? 'PDF' : 'Excel') + '\n' +
        'file containing the selected analytics data.');
}
</script>

<?php require __DIR__ . '/../includes/bottom.php'; ?>