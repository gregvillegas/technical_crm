<?php
// schedule_activity.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$error = '';

// Pre-fill date from query (from calendar), use start of day
$prefillDate = isset($_GET['date']) ? $_GET['date'] : '';
if ($prefillDate) {
    // Ensure valid format YYYY-MM-DD
    $ts = strtotime($prefillDate);
    if ($ts) {
        $prefillDate = date('Y-m-d\TH:00', $ts);
    } else {
        $prefillDate = '';
    }
}

function toSqlDatetime($val) {
    if (!$val) return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_type = Helpers::escape($_POST['activity_type'] ?? 'other');
    $subject = Helpers::escape($_POST['subject'] ?? '');
    $description = ($_POST['description'] ?? '') ? Helpers::escape($_POST['description']) : null;

    $related_to = Helpers::escape($_POST['related_to'] ?? ''); // '', 'customer', 'deal', 'lead'
    $related_id = isset($_POST['related_id']) ? (int)$_POST['related_id'] : null;

    $activity_date_raw = $_POST['activity_date'] ?? '';
    $reminder_date_raw = $_POST['reminder_date'] ?? '';

    $activity_date = toSqlDatetime($activity_date_raw);
    $reminder_date = toSqlDatetime($reminder_date_raw);

    $assigned_to = (int)Auth::getUserID();
    $created_by = (int)Auth::getUserID();

    if ($subject === '' || !$activity_date) {
        $error = 'Subject and Activity Date are required.';
    } else {
        try {
            $sql = "INSERT INTO activities (
                        activity_type, subject, description,
                        related_to, related_id,
                        activity_date, reminder_date,
                        status, outcome,
                        assigned_to, created_by
                    ) VALUES (
                        :activity_type, :subject, :description,
                        :related_to, :related_id,
                        :activity_date, :reminder_date,
                        'scheduled', NULL,
                        :assigned_to, :created_by
                    )";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':activity_type', $activity_type);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':related_to', $related_to === '' ? null : $related_to, $related_to === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':related_id', $related_id ?? null, $related_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);
            $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);

            // Datetime bindings
            if ($activity_date === null) {
                throw new Exception('Invalid activity date');
            }
            $stmt->bindParam(':activity_date', $activity_date);
            $stmt->bindValue(':reminder_date', $reminder_date, $reminder_date === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->execute();
            header('Location: /activities?msg=scheduled');
            exit();
        } catch (Exception $e) {
            $error = 'Error scheduling activity: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Activity - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-help { font-size: 0.875rem; color: #6c757d; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <h2>Schedule Activity</h2>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header"><strong>Details</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Type</label>
                                    <select name="activity_type" class="form-select">
                                        <option value="call">Phone Call</option>
                                        <option value="email">Email</option>
                                        <option value="meeting">Client Call</option>
                                        <option value="demo">Demo</option>
                                        <option value="proposal">Proposal</option>
                                        <option value="followup">Follow-up</option>
                                        <option value="other" selected>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" name="subject" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Activity Date *</label>
                                    <input type="datetime-local" name="activity_date" class="form-control" value="<?php echo htmlspecialchars($prefillDate); ?>" required>
                                    <div class="form-help">Local time; saved as server time.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reminder</label>
                                    <input type="datetime-local" name="reminder_date" class="form-control">
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
                                    <option value="">None</option>
                                    <option value="customer">Customer</option>
                                    <option value="deal">Deal</option>
                                    <option value="lead">Lead</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Entity ID</label>
                                <input type="number" name="related_id" class="form-control" min="1" placeholder="e.g., 42">
                                <div class="form-help">Optional; link activity to a specific record.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="/activities" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Schedule</button>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>