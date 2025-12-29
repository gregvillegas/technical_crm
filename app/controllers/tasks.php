<?php
// tasks.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $newStatus = $_POST['status'] ?? '';
    $allowed = ['pending','in_progress','completed','cancelled'];
    if($taskId && in_array($newStatus, $allowed, true)) {
        $isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
        $cond = $isAdmin ? '' : 'AND (assigned_to = :uid OR created_by = :uid)';
        $sql = "UPDATE tasks SET status = :status, completed_date = CASE WHEN :status = 'completed' THEN NOW() ELSE NULL END WHERE id = :id $cond";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $taskId, PDO::PARAM_INT);
        if(!$isAdmin) { $stmt->bindParam(':uid', $_SESSION['user_id'], PDO::PARAM_INT); }
        $stmt->execute();
    }
    header('Location: /tasks');
    exit();
}

include __DIR__ . '/../views/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Tasks</h2>
        <a href="/create_task" class="btn btn-primary"><i class="fas fa-plus"></i> New Task</a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>My Tasks</h5>
                </div>
                <div class="card-body">
                    <?php
                        $isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
                        $where = '';
                        $params = [];
                        if(!$isAdmin) {
                            $where = 'WHERE assigned_to = ? OR created_by = ?';
                            $params = [$_SESSION['user_id'], $_SESSION['user_id']];
                        }
                        $sql = 'SELECT * FROM tasks ' . $where . ' ORDER BY COALESCE(due_date, DATE(created_at)) ASC LIMIT 50';
                        $stmt = $conn->prepare($sql);
                        foreach($params as $i => $p) { $stmt->bindValue($i+1, $p); }
                        $stmt->execute();
                        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <ul class="list-group">
                        <?php if(empty($tasks)): ?>
                            <li class="list-group-item text-center text-muted">No tasks yet. Create one to get started.</li>
                        <?php else: ?>
                            <?php foreach($tasks as $t): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($t['title']); ?></strong>
                                        <?php if(!empty($t['description'])): ?>
                                            <small class="text-muted"> - <?php echo htmlspecialchars(substr($t['description'],0,120)); ?>...</small>
                                        <?php endif; ?>
                                        <div>
                                            <span class="badge bg-<?php echo $t['priority']==='urgent'?'danger':($t['priority']==='high'?'warning':'secondary'); ?>">
                                                <?php echo ucfirst($t['priority']); ?>
                                            </span>
                                            <span class="badge bg-info"><?php echo ucfirst($t['task_type']); ?></span>
                                            <?php if($t['due_date']): ?>
                                                <span class="badge bg-secondary">Due: <?php echo Helpers::formatDate($t['due_date']); ?></span>
                                            <?php endif; ?>
                                            <?php if($t['status']): ?>
                                                <span class="badge bg-light text-dark">Status: <?php echo ucfirst($t['status']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                            <select name="status" class="form-select form-select-sm me-2">
                                                <option value="pending" <?php echo $t['status']==='pending'?'selected':''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo $t['status']==='in_progress'?'selected':''; ?>>In Progress</option>
                                                <option value="completed" <?php echo $t['status']==='completed'?'selected':''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $t['status']==='cancelled'?'selected':''; ?>>Cancelled</option>
                                            </select>
                                            <button class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                        <?php if($t['activity_id']): ?>
                                            <a href="/activities" class="btn btn-sm btn-outline-primary">View Activity</a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
