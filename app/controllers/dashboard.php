<?php
// dashboard.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Deal.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$dealModel = new Deal($conn);
$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
$stats = $dealModel->getDashboardStats($isAdmin ? null : Auth::getUserID());

// Get recent activities
$activityQuery = "SELECT a.*, 
                 c.company_name,
                 u.first_name as assigned_first, u.last_name as assigned_last
                 FROM activities a
                 LEFT JOIN customers c ON a.related_id = c.id AND a.related_to = 'customer'
                 LEFT JOIN users u ON a.assigned_to = u.id";
if(!$isAdmin) {
    $activityQuery .= " WHERE a.assigned_to = ? OR a.created_by = ?";
}
$activityQuery .= " ORDER BY a.activity_date DESC LIMIT 10";
$activityStmt = $conn->prepare($activityQuery);
if(!$isAdmin) {
    $activityStmt->bindParam(1, $_SESSION['user_id']);
    $activityStmt->bindParam(2, $_SESSION['user_id']);
}
$activityStmt->execute();
$recentActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming followups
$followupQuery = "SELECT f.*, d.deal_name, c.company_name
                 FROM followups f
                 JOIN deals d ON f.deal_id = d.id
                 JOIN customers c ON d.customer_id = c.id
                 WHERE f.status = 'pending'
                 AND f.reminder_date >= CURDATE()";
if(!$isAdmin) {
    $followupQuery .= " AND f.assigned_to = ?";
}
$followupQuery .= " ORDER BY f.reminder_date ASC LIMIT 5";
$followupStmt = $conn->prepare($followupQuery);
if(!$isAdmin) {
    $followupStmt->bindParam(1, $_SESSION['user_id']);
}
$followupStmt->execute();
$upcomingFollowups = $followupStmt->fetchAll(PDO::FETCH_ASSOC);

// Sales Touches metrics
$DAILY_TARGET = 20; // default daily target (per-user by default)

// Build WHERE filter based on role
$touchWhere = "a.description LIKE '%[category:%]%'";
if(!$isAdmin) {
    $touchWhere .= " AND (a.assigned_to = :uid OR a.created_by = :uid2)";
}

// Touches today
$todaySql = "SELECT COUNT(*) AS cnt FROM activities a WHERE $touchWhere AND DATE(a.activity_date) = CURDATE()";
$todayStmt = $conn->prepare($todaySql);
if(!$isAdmin) {
    $todayStmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
    $todayStmt->bindValue(':uid2', $_SESSION['user_id'], PDO::PARAM_INT);
}
$todayStmt->execute();
$touchesToday = (int)$todayStmt->fetchColumn();

// Touches this week
$weekSql = "SELECT COUNT(*) AS cnt FROM activities a WHERE $touchWhere AND YEARWEEK(a.activity_date, 1) = YEARWEEK(CURDATE(), 1)";
$weekStmt = $conn->prepare($weekSql);
if(!$isAdmin) {
    $weekStmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
    $weekStmt->bindValue(':uid2', $_SESSION['user_id'], PDO::PARAM_INT);
}
$weekStmt->execute();
$touchesWeek = (int)$weekStmt->fetchColumn();

// 7-day trend
$trendSql = "SELECT DATE(a.activity_date) AS d, COUNT(*) AS cnt
             FROM activities a
             WHERE $touchWhere AND a.activity_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(a.activity_date)
             ORDER BY DATE(a.activity_date) ASC";
$trendStmt = $conn->prepare($trendSql);
if(!$isAdmin) {
    $trendStmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
    $trendStmt->bindValue(':uid2', $_SESSION['user_id'], PDO::PARAM_INT);
}
$trendStmt->execute();
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} day"));
    $trendData[$day] = 0;
}
foreach($trendRows as $r) {
    $trendData[$r['d']] = (int)$r['cnt'];
}

// Conversion to qualified leads (last 7 days)
$leadSql = "SELECT COUNT(*) FROM leads l WHERE l.lead_status = 'qualified' AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
if(!$isAdmin) {
    $leadSql .= " AND l.assigned_to = ?";
}
$leadStmt = $conn->prepare($leadSql);
if(!$isAdmin) {
    $leadStmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
}
$leadStmt->execute();
$qualifiedLeads7d = (int)$leadStmt->fetchColumn();

$touches7dTotal = array_sum($trendData);
$conversionRate = $touches7dTotal > 0 ? round(($qualifiedLeads7d / $touches7dTotal) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --yellow-funnel: #ffc107;
            --pink-funnel: #e83e8c;
            --green-funnel: #28a745;
            --blue-funnel: #007bff;
        }
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            padding: 0;
        }
        .sidebar-brand {
            padding: 20px;
            color: white;
            text-align: center;
            border-bottom: 1px solid #34495e;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        .sidebar-menu li {
            padding: 0;
        }
        .sidebar-menu a {
            color: #bdc3c7;
            padding: 15px 20px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover {
            background: #34495e;
            color: white;
        }
        .sidebar-menu a.active {
            background: #1abc9c;
            color: white;
        }
        .sidebar-menu a i {
            width: 20px;
            margin-right: 10px;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
        }
        .stat-card.won { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-card.lost { background: linear-gradient(135deg, #dc3545, #e83e8c); }
        .stat-card.pipeline { background: linear-gradient(135deg, #007bff, #6610f2); }
        .stat-card.deals { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .funnel-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-yellow { background-color: var(--yellow-funnel); color: #000; }
        .badge-pink { background-color: var(--pink-funnel); color: white; }
        .badge-green { background-color: var(--green-funnel); color: white; }
        .badge-blue { background-color: var(--blue-funnel); color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>
    <div class="container-fluid py-4">
        <h2 class="mb-4">Dashboard</h2>
        <!-- Sales Touches Goals & Analytics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daily Touches Goal</h5>
                        <small class="text-muted">Target: <?php echo $DAILY_TARGET; ?></small>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div><strong>Today:</strong> <?php echo $touchesToday; ?></div>
                            <div><strong>Weekly:</strong> <?php echo $touchesWeek; ?></div>
                        </div>
                        <?php $pct = min(100, round(($touchesToday / max(1, $DAILY_TARGET)) * 100)); ?>
                        <div class="progress" style="height: 24px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pct; ?>%;">
                                <?php echo $pct; ?>%
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Keep momentum: aim for <?php echo $DAILY_TARGET; ?> touches/day.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">7‑Day Touches Trend</h5>
                        <small class="text-muted">Conversion: <?php echo $conversionRate; ?>%</small>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <?php foreach($trendData as $day => $cnt): ?>
                                <div class="col-2">
                                    <div class="p-2 bg-light rounded">
                                        <strong><?php echo $cnt; ?></strong>
                                        <div><small><?php echo date('D', strtotime($day)); ?></small></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Qualified leads last 7 days: <?php echo $qualifiedLeads7d; ?> | Touches: <?php echo $touches7dTotal; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card deals">
                            <h5>Total Deals</h5>
                            <h2><?php echo $stats['total_deals']; ?></h2>
                            <small>Active Opportunities</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card won">
                            <h5>Won Deals</h5>
                            <h2><?php echo $stats['deals_won']; ?></h2>
                            <small><?php echo Helpers::formatCurrency($stats['won_value']); ?></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card lost">
                            <h5>Lost Deals</h5>
                            <h2><?php echo $stats['deals_lost']; ?></h2>
                            <small>Need Follow-up</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card pipeline">
                            <h5>Pipeline Value</h5>
                            <h2><?php echo Helpers::formatCurrency($stats['pipeline_value']); ?></h2>
                            <small>Weighted Value</small>
                        </div>
                    </div>
                </div>
                
                <!-- Funnel Distribution -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Sales Funnel Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-3">
                                        <div class="p-3 bg-warning rounded">
                                            <h3><?php echo $stats['funnel_yellow']; ?></h3>
                                            <small>Closable This Month</small>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-3 bg-danger text-white rounded">
                                            <h3><?php echo $stats['funnel_pink']; ?></h3>
                                            <small>Newly Quoted</small>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-3 bg-success text-white rounded">
                                            <h3><?php echo $stats['funnel_green']; ?></h3>
                                            <small>Project Based</small>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="p-3 bg-primary text-white rounded">
                                            <h3><?php echo $stats['funnel_blue']; ?></h3>
                                            <small>Services Offered</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
            <a href="/add_customer" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Customer
            </a>
                                    <a href="/add_deal" class="btn btn-success">
                                        <i class="fas fa-plus-circle"></i> Create Deal
                                    </a>
                                    <a href="/email" class="btn btn-info text-white">
                                        <i class="fas fa-envelope"></i> Send Email
                                    </a>
                                    <a href="/schedule_activity" class="btn btn-warning">
                                        <i class="fas fa-calendar-plus"></i> Schedule Activity
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        
        <!-- Recent Activities & Upcoming Follow-ups -->
        <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach($recentActivities as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-<?php 
                                                        switch($activity['activity_type']) {
                                                            case 'email': echo 'envelope'; break;
                                                            case 'call': echo 'phone'; break;
                                                            case 'meeting': echo 'users'; break;
                                                            default: echo 'task';
                                                        }
                                                    ?>"></i>
                                                    <?php echo $activity['subject']; ?>
                                                </h6>
                                                <small><?php echo Helpers::formatDate($activity['activity_date'], 'M d'); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo substr($activity['description'], 0, 100); ?>...</p>
                                            <small>Customer: <?php echo $activity['company_name'] ?? 'N/A'; ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Upcoming Follow-ups</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach($upcomingFollowups as $followup): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo $followup['deal_name']; ?></h6>
                                                <span class="badge bg-warning"><?php echo Helpers::formatDate($followup['reminder_date']); ?></span>
                                            </div>
                                            <p class="mb-1"><?php echo $followup['company_name']; ?></p>
                                            <small><?php echo $followup['notes']; ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>
    </div>
    <?php
        include __DIR__ . '/../views/footer.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh dashboard every 60 seconds
        setTimeout(function() {
            window.location.reload();
        }, 60000);
    </script>
</body>
</html>
