<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
$uid = Auth::getUserID();

function buildRangeWhere(&$where, &$params, $field, $start, $end) {
    if($start) { $where[] = "$field >= ?"; $params[] = $start . ' 00:00:00'; }
    if($end) { $where[] = "$field <= ?"; $params[] = $end . ' 23:59:59'; }
}

// Emails (sent)
$ew = [];$ep=[];
if(!$isAdmin) { $ew[] = 'sent_by = ?'; $ep[] = $uid; }
buildRangeWhere($ew,$ep,'sent_at',$start,$end);
$esql = 'SELECT * FROM email_logs';
if($ew) { $esql .= ' WHERE ' . implode(' AND ', $ew); }
$esql .= ' ORDER BY sent_at DESC LIMIT 100';
$estmt = $conn->prepare($esql);
foreach($ep as $i=>$v){$estmt->bindValue($i+1,$v);} $estmt->execute();
$emails = $estmt->fetchAll(PDO::FETCH_ASSOC);

// Activities (completed)
$aw=[];$ap=[];
if(!$isAdmin) { $aw[] = 'assigned_to = ?'; $ap[] = $uid; }
$aw[] = "status = 'completed'";
buildRangeWhere($aw,$ap,'completed_date',$start,$end);
$asql = 'SELECT * FROM activities';
if($aw){$asql .= ' WHERE ' . implode(' AND ', $aw);} $asql .= ' ORDER BY completed_date DESC LIMIT 100';
$astmt = $conn->prepare($asql); foreach($ap as $i=>$v){$astmt->bindValue($i+1,$v);} $astmt->execute();
$activities = $astmt->fetchAll(PDO::FETCH_ASSOC);

// Tasks (completed)
$tw=[];$tp=[];
if(!$isAdmin){$tw[]='assigned_to = ?'; $tp[]=$uid;}
$tw[] = "status = 'completed'";
buildRangeWhere($tw,$tp,'completed_date',$start,$end);
$tsql = 'SELECT * FROM tasks';
if($tw){$tsql .= ' WHERE ' . implode(' AND ', $tw);} $tsql .= ' ORDER BY completed_date DESC, updated_at DESC LIMIT 100';
$tstmt = $conn->prepare($tsql); foreach($tp as $i=>$v){$tstmt->bindValue($i+1,$v);} $tstmt->execute();
$tasks = $tstmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Outcome Summary</h2>
            <form class="d-flex align-items-end" method="GET">
                <div class="me-2">
                    <label class="form-label">Start</label>
                    <input type="date" name="start" value="<?php echo htmlspecialchars($start); ?>" class="form-control">
                </div>
                <div class="me-2">
                    <label class="form-label">End</label>
                    <input type="date" name="end" value="<?php echo htmlspecialchars($end); ?>" class="form-control">
                </div>
                <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><strong>Emails Sent</strong> <span class="badge bg-primary ms-2"><?php echo count($emails); ?></span></div>
                    <ul class="list-group list-group-flush">
                        <?php if(empty($emails)): ?>
                            <li class="list-group-item text-muted">No emails found.</li>
                        <?php else: foreach($emails as $e): ?>
                            <li class="list-group-item">
                                <div class="fw-bold"><?php echo htmlspecialchars($e['subject']); ?></div>
                                <div class="small text-muted">To: <?php echo htmlspecialchars($e['recipient_email']); ?> • <?php echo Helpers::formatDate($e['sent_at']); ?></div>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><strong>Activities Completed</strong> <span class="badge bg-success ms-2"><?php echo count($activities); ?></span></div>
                    <ul class="list-group list-group-flush">
                        <?php if(empty($activities)): ?>
                            <li class="list-group-item text-muted">No activities found.</li>
                        <?php else: foreach($activities as $a): ?>
                            <li class="list-group-item">
                                <div class="fw-bold"><?php echo htmlspecialchars($a['subject']); ?></div>
                                <div class="small text-muted">Type: <?php echo htmlspecialchars($a['activity_type']); ?> • Completed: <?php echo Helpers::formatDate($a['completed_date']); ?></div>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><strong>Tasks Completed</strong> <span class="badge bg-warning text-dark ms-2"><?php echo count($tasks); ?></span></div>
                    <ul class="list-group list-group-flush">
                        <?php if(empty($tasks)): ?>
                            <li class="list-group-item text-muted">No tasks found.</li>
                        <?php else: foreach($tasks as $t): ?>
                            <li class="list-group-item">
                                <div class="fw-bold"><?php echo htmlspecialchars($t['title']); ?></div>
                                <div class="small text-muted">Completed: <?php echo $t['completed_date'] ? Helpers::formatDate($t['completed_date']) : Helpers::formatDate($t['updated_at']); ?></div>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>
