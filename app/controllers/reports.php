<?php
// reports.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Deal.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();
$dealModel = new Deal($conn);

// Get time period
$period = $_GET['period'] ?? 'month';
$startDate = '';
$endDate = date('Y-m-d');

switch($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'quarter':
        $startDate = date('Y-m-d', strtotime('-3 months'));
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-1 month'));
}

// Get report data
$reportQuery = "SELECT 
    COUNT(*) as total_deals,
    SUM(CASE WHEN deal_status = 'closed_won' THEN 1 ELSE 0 END) as won_deals,
    SUM(CASE WHEN deal_status = 'closed_lost' THEN 1 ELSE 0 END) as lost_deals,
    SUM(CASE WHEN deal_status = 'closed_won' THEN deal_value ELSE 0 END) as won_value,
    AVG(CASE WHEN deal_status = 'closed_won' THEN deal_value ELSE NULL END) as avg_deal_size,
    AVG(CASE WHEN deal_status = 'closed_won' THEN DATEDIFF(closed_date, created_at) ELSE NULL END) as avg_sales_cycle
    FROM deals 
    WHERE created_at BETWEEN ? AND ?";

$stmt = $conn->prepare($reportQuery);
$stmt->bindParam(1, $startDate);
$stmt->bindParam(2, $endDate);
$stmt->execute();
$reportData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get deals by source
$sourceQuery = "SELECT 
    c.lead_source,
    COUNT(*) as count,
    SUM(d.deal_value) as value
    FROM deals d
    JOIN customers c ON d.customer_id = c.id
    WHERE d.created_at BETWEEN ? AND ?
    AND d.deal_status = 'closed_won'
    GROUP BY c.lead_source
    ORDER BY value DESC";

$sourceStmt = $conn->prepare($sourceQuery);
$sourceStmt->bindParam(1, $startDate);
$sourceStmt->bindParam(2, $endDate);
$sourceStmt->execute();
$leadSources = $sourceStmt->fetchAll(PDO::FETCH_ASSOC);

// Get performance by user
$userQuery = "SELECT 
    u.first_name, u.last_name,
    COUNT(d.id) as total_deals,
    SUM(CASE WHEN d.deal_status = 'closed_won' THEN 1 ELSE 0 END) as won,
    SUM(CASE WHEN d.deal_status = 'closed_won' THEN d.deal_value ELSE 0 END) as value
    FROM users u
    LEFT JOIN deals d ON u.id = d.owner_id
    AND d.created_at BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY value DESC";

$userStmt = $conn->prepare($userQuery);
$userStmt->bindParam(1, $startDate);
$userStmt->bindParam(2, $endDate);
$userStmt->execute();
$userPerformance = $userStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Sales Reports & Analytics</h2>
        
        <!-- Time Period Selector -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-8">
                        <select class="form-select" name="period" onchange="this.form.submit()">
                            <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Last Week</option>
                            <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Last Month</option>
                            <option value="quarter" <?php echo $period == 'quarter' ? 'selected' : ''; ?>>Last Quarter</option>
                            <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Deals</h5>
                        <h2><?php echo $reportData['total_deals'] ?? 0; ?></h2>
                        <small>Period: <?php echo $startDate . ' to ' . $endDate; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Won Deals</h5>
                        <h2><?php echo $reportData['won_deals'] ?? 0; ?></h2>
                        <small><?php echo Helpers::formatCurrency($reportData['won_value'] ?? 0); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Avg Deal Size</h5>
                        <h2><?php echo Helpers::formatCurrency($reportData['avg_deal_size'] ?? 0); ?></h2>
                        <small>Average Value</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h5>Avg Sales Cycle</h5>
                        <h2><?php echo round($reportData['avg_sales_cycle'] ?? 0); ?> days</h2>
                        <small>From creation to close</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Win/Loss Ratio</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="winLossChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Revenue by Lead Source</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="sourceChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance Table -->
        <div class="card">
            <div class="card-header">
                <h5>Sales Team Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sales Rep</th>
                                <th>Total Deals</th>
                                <th>Deals Won</th>
                                <th>Win Rate</th>
                                <th>Revenue Generated</th>
                                <th>Avg Deal Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($userPerformance as $user): 
                                $winRate = $user['total_deals'] > 0 ? round(($user['won'] / $user['total_deals']) * 100) : 0;
                                $avgDealSize = $user['won'] > 0 ? round($user['value'] / $user['won']) : 0;
                            ?>
                                <tr>
                                    <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                                    <td><?php echo $user['total_deals']; ?></td>
                                    <td><?php echo $user['won']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $winRate; ?>%">
                                                <?php echo $winRate; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo Helpers::formatCurrency($user['value']); ?></td>
                                    <td><?php echo Helpers::formatCurrency($avgDealSize); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <h5>Export Reports</h5>
                <a href="export_report.php?type=csv&period=<?php echo $period; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </a>
                <a href="export_report.php?type=pdf&period=<?php echo $period; ?>" class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </a>
                <a href="print_report.php?period=<?php echo $period; ?>" class="btn btn-outline-secondary" target="_blank">
                    <i class="fas fa-print"></i> Print Report
                </a>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../views/footer.php'; 
    ?>
    
    <script>
        // Win/Loss Chart
        const winLossCtx = document.getElementById('winLossChart').getContext('2d');
        const winLossChart = new Chart(winLossCtx, {
            type: 'doughnut',
            data: {
                labels: ['Won Deals', 'Lost Deals', 'In Progress'],
                datasets: [{
                    data: [
                        <?php echo $reportData['won_deals'] ?? 0; ?>,
                        <?php echo $reportData['lost_deals'] ?? 0; ?>,
                        <?php echo ($reportData['total_deals'] ?? 0) - ($reportData['won_deals'] ?? 0) - ($reportData['lost_deals'] ?? 0); ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#dc3545',
                        '#ffc107'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Lead Source Chart
        const sourceCtx = document.getElementById('sourceChart').getContext('2d');
        const sourceChart = new Chart(sourceCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($leadSources, 'lead_source')); ?>,
                datasets: [{
                    label: 'Revenue by Source',
                    data: <?php echo json_encode(array_column($leadSources, 'value')); ?>,
                    backgroundColor: [
                        '#007bff', '#6610f2', '#6f42c1', '#e83e8c', '#fd7e14', '#28a745', '#20c997', '#17a2b8'
                    ]
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>