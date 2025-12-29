<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$activity = null;

function toSqlDatetime($val) {
    if (!$val) return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

if($id) {
    $isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
    $sql = "SELECT * FROM activities WHERE id = :id" . ($isAdmin ? '' : " AND assigned_to = :uid");
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    if(!$isAdmin) { $stmt->bindParam(':uid', $_SESSION['user_id'], PDO::PARAM_INT); }
    $stmt->execute();
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $subject = Helpers::escape($_POST['subject'] ?? '');
    $activity_date = toSqlDatetime($_POST['activity_date'] ?? '');
    $reminder_date = toSqlDatetime($_POST['reminder_date'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';
    if($id && $activity_date) {
        $isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
        $sql = "UPDATE activities SET subject = :subject, activity_date = :activity_date, reminder_date = :reminder_date, status = :status WHERE id = :id" . ($isAdmin ? '' : " AND assigned_to = :uid");
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':activity_date', $activity_date);
        $stmt->bindValue(':reminder_date', $reminder_date, $reminder_date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if(!$isAdmin) { $stmt->bindParam(':uid', $_SESSION['user_id'], PDO::PARAM_INT); }
        $stmt->execute();
        header('Location: /notifications');
        exit();
    } else {
        $error = 'Activity date is required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Activity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>
    <div class="container-fluid py-4">
        <h2>Reschedule Activity</h2>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($activity): ?>
        <form method="POST" class="mt-3">
            <input type="hidden" name="id" value="<?php echo (int)$activity['id']; ?>">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($activity['subject']); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Activity Date</label>
                            <input type="datetime-local" name="activity_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($activity['activity_date'])); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reminder</label>
                            <input type="datetime-local" name="reminder_date" class="form-control" value="<?php echo $activity['reminder_date'] ? date('Y-m-d\TH:i', strtotime($activity['reminder_date'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="scheduled" <?php echo $activity['status']==='scheduled'?'selected':''; ?>>Scheduled</option>
                            <option value="completed" <?php echo $activity['status']==='completed'?'selected':''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $activity['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="text-end mt-3">
                <a href="/notifications" class="btn btn-secondary">Cancel</a>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-warning">Activity not found or access denied.</div>
        <?php endif; ?>
    </div>
    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>
