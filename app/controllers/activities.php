<?php
// activities.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Filters
$typeFilter = $_GET['type'] ?? '';
$startDate = $_GET['start'] ?? '';
$endDate = $_GET['end'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');

// Build query for activities
$sql = "SELECT a.* FROM activities a";
$where = [];
$params = [];
if(!$isAdmin) {
    $where[] = "(a.assigned_to = ? OR a.created_by = ?)";
    $params[] = $_SESSION['user_id'];
    $params[] = $_SESSION['user_id'];
}
if ($typeFilter !== '') {
    $where[] = "a.activity_type = ?";
    $params[] = $typeFilter;
}
if ($startDate !== '') {
    $where[] = "a.activity_date >= ?";
    $params[] = $startDate . ' 00:00:00';
}
if ($endDate !== '') {
    $where[] = "a.activity_date <= ?";
    $params[] = $endDate . ' 23:59:59';
}
if ($categoryFilter !== '') {
    $where[] = "a.description LIKE ?";
    $params[] = '%[category:' . $categoryFilter . ']%';
}
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY a.activity_date DESC LIMIT 50';

$stmt = $conn->prepare($sql);
foreach($params as $idx => $val) {
    $stmt->bindValue($idx+1, $val);
}
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Activities</h2>
            <div>
                <a href="/log_touch" class="btn btn-outline-primary me-2">
                    <i class="fas fa-hand-pointer"></i> Log Sales Touch
                </a>
                <a href="/schedule_activity" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Schedule Activity
                </a>
            </div>
        </div>

        <form method="GET" class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All</option>
                            <option value="call" <?php echo $typeFilter==='call'?'selected':''; ?>>Call</option>
                            <option value="email" <?php echo $typeFilter==='email'?'selected':''; ?>>Email</option>
                            <option value="meeting" <?php echo $typeFilter==='meeting'?'selected':''; ?>>Meeting</option>
                            <option value="demo" <?php echo $typeFilter==='demo'?'selected':''; ?>>Demo</option>
                            <option value="proposal" <?php echo $typeFilter==='proposal'?'selected':''; ?>>Proposal</option>
                            <option value="followup" <?php echo $typeFilter==='followup'?'selected':''; ?>>Follow-up</option>
                            <option value="other" <?php echo $typeFilter==='other'?'selected':''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            <option value="phone" <?php echo $categoryFilter==='phone'?'selected':''; ?>>Phone</option>
                            <option value="email" <?php echo $categoryFilter==='email'?'selected':''; ?>>Email</option>
                            <option value="linkedin" <?php echo $categoryFilter==='linkedin'?'selected':''; ?>>LinkedIn</option>
                            <option value="walkin" <?php echo $categoryFilter==='walkin'?'selected':''; ?>>Walk-in</option>
                            <option value="followup" <?php echo $categoryFilter==='followup'?'selected':''; ?>>Follow-up</option>
                            <option value="invite" <?php echo $categoryFilter==='invite'?'selected':''; ?>>Webinar Invite</option>
                            <option value="proposal" <?php echo $categoryFilter==='proposal'?'selected':''; ?>>Proposal</option>
                            <option value="quote" <?php echo $categoryFilter==='quote'?'selected':''; ?>>Quote</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start</label>
                        <input type="date" name="start" value="<?php echo htmlspecialchars($startDate); ?>" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End</label>
                        <input type="date" name="end" value="<?php echo htmlspecialchars($endDate); ?>" class="form-control">
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-secondary">Filter</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>My Upcoming Activities</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if(empty($activities)): ?>
                            <li class="list-group-item text-center text-muted">No activities found.</li>
                        <?php else: ?>
                            <?php foreach($activities as $a): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <?php
                                            $icon = 'calendar';
                                            switch($a['activity_type']) {
                                                case 'call': $icon = 'phone'; break;
                                                case 'email': $icon = 'envelope'; break;
                                                case 'meeting': $icon = 'users'; break;
                                                case 'demo': $icon = 'desktop'; break;
                                                case 'proposal': $icon = 'file-contract'; break;
                                                case 'followup': $icon = 'reply'; break;
                                                default: $icon = 'calendar';
                                            }
                                        ?>
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                        <?php echo htmlspecialchars($a['subject']); ?>
                                        <?php if(!empty($a['description'])): ?>
                                            <small class="text-muted"> - <?php echo htmlspecialchars($a['description']); ?></small>
                                        <?php endif; ?>
                                    </span>
                                    <span class="badge bg-secondary"><?php echo Helpers::formatDate($a['activity_date']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>