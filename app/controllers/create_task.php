<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$error = '';

function toSqlDate($val) {
    if (!$val) return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d', $ts) : null;
}

function toSqlDatetime($val) {
    if (!$val) return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = Helpers::escape($_POST['title'] ?? '');
    $description = ($_POST['description'] ?? '') ? Helpers::escape($_POST['description']) : null;
    $task_type = Helpers::escape($_POST['task_type'] ?? 'general');
    $priority = Helpers::escape($_POST['priority'] ?? 'medium');
    $due_date_raw = $_POST['due_date'] ?? '';
    $due_date = toSqlDate($due_date_raw);

    $related_to = Helpers::escape($_POST['related_to'] ?? 'none');
    $related_id = isset($_POST['related_id']) ? (int)$_POST['related_id'] : null;

    $assigned_to = (int)Auth::getUserID();
    $created_by = (int)Auth::getUserID();

    $create_activity = isset($_POST['create_activity']) && $_POST['create_activity'] === '1';
    $activity_type = Helpers::escape($_POST['activity_type'] ?? 'followup');
    $activity_subject = Helpers::escape($_POST['activity_subject'] ?? '');
    $activity_date = toSqlDatetime($_POST['activity_date'] ?? '');
    $reminder_date = toSqlDatetime($_POST['reminder_date'] ?? '');

    if ($title === '') {
        $error = 'Title is required.';
    } else {
        try {
            // Insert task
            $sql = "INSERT INTO tasks (
                        title, description, task_type,
                        related_to, related_id,
                        priority, status,
                        due_date, completed_date,
                        assigned_to, created_by, activity_id
                    ) VALUES (
                        :title, :description, :task_type,
                        :related_to, :related_id,
                        :priority, 'pending',
                        :due_date, NULL,
                        :assigned_to, :created_by, NULL
                    )";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':title', $title);
            $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':task_type', $task_type);
            $stmt->bindParam(':priority', $priority);
            $stmt->bindValue(':related_to', $related_to === 'none' ? null : $related_to, $related_to === 'none' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':related_id', $related_id ?? null, $related_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':due_date', $due_date, $due_date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);
            $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
            $stmt->execute();
            $taskId = (int)$conn->lastInsertId();

            // Optionally create linked activity
            if ($create_activity) {
                $act_subject = $activity_subject !== '' ? $activity_subject : ('Follow-up: ' . $title);
                $act_date = $activity_date ?: date('Y-m-d H:i:s', strtotime('+1 day'));
                $asql = "INSERT INTO activities (
                            activity_type, subject, description,
                            related_to, related_id,
                            activity_date, reminder_date,
                            status, outcome,
                            assigned_to, created_by, task_id
                        ) VALUES (
                            :activity_type, :subject, NULL,
                            'task', :related_id,
                            :activity_date, :reminder_date,
                            'scheduled', NULL,
                            :assigned_to, :created_by, :task_id
                        )";
                $astmt = $conn->prepare($asql);
                $astmt->bindParam(':activity_type', $activity_type);
                $astmt->bindParam(':subject', $act_subject);
                $astmt->bindParam(':related_id', $taskId, PDO::PARAM_INT);
                $astmt->bindParam(':activity_date', $act_date);
                $astmt->bindValue(':reminder_date', $reminder_date, $reminder_date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $astmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);
                $astmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
                $astmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
                $astmt->execute();
                $activityId = (int)$conn->lastInsertId();

                // Back-link activity to task
                $usql = "UPDATE tasks SET activity_id = :activity_id WHERE id = :id";
                $ustmt = $conn->prepare($usql);
                $ustmt->bindParam(':activity_id', $activityId, PDO::PARAM_INT);
                $ustmt->bindParam(':id', $taskId, PDO::PARAM_INT);
                $ustmt->execute();
            }

            header('Location: /tasks?msg=created');
            exit();
        } catch (Exception $e) {
            $error = 'Error creating task: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Create Task</h2>
            <a href="/tasks" class="btn btn-secondary">Back to Tasks</a>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><strong>Task Details</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type</label>
                                <select name="task_type" class="form-select">
                                    <option value="general">General</option>
                                    <option value="followup">Follow-up</option>
                                    <option value="proposal">Proposal</option>
                                    <option value="research">Research</option>
                                    <option value="administrative">Administrative</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><strong>Related To</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Entity</label>
                            <select name="related_to" class="form-select">
                                <option value="none">None</option>
                                <option value="customer">Customer</option>
                                <option value="deal">Deal</option>
                                <option value="lead">Lead</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Entity ID</label>
                            <input type="number" name="related_id" class="form-control" min="1" placeholder="e.g., 42">
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><strong>Optional: Create Linked Activity</strong></div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="createActivity" name="create_activity" value="1">
                            <label class="form-check-label" for="createActivity">Create an activity for this task</label>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Activity Type</label>
                                <select name="activity_type" class="form-select">
                                    <option value="followup">Follow-up</option>
                                    <option value="call">Call</option>
                                    <option value="email">Email</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="demo">Demo</option>
                                    <option value="proposal">Proposal</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Activity Date</label>
                                <input type="datetime-local" name="activity_date" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Activity Subject</label>
                            <input type="text" name="activity_subject" class="form-control" placeholder="Defaults to 'Follow-up: Title'">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reminder</label>
                            <input type="datetime-local" name="reminder_date" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <a href="/tasks" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Task</button>
                </div>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>
