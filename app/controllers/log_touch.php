<?php
// log_touch.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$error = '';

function toSqlDatetime($val) {
    if (!$val) return null;
    $ts = strtotime($val);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base_type = Helpers::escape($_POST['activity_type'] ?? 'other'); // call/email/other
    $category = Helpers::escape($_POST['touch_category'] ?? '');
    $subject = Helpers::escape($_POST['subject'] ?? '');
    $description = ($_POST['description'] ?? '') ? Helpers::escape($_POST['description']) : '';
    $related_to = Helpers::escape($_POST['related_to'] ?? '');
    $related_id = isset($_POST['related_id']) ? (int)$_POST['related_id'] : null;
    $activity_date_raw = $_POST['activity_date'] ?? '';
    $activity_date = toSqlDatetime($activity_date_raw) ?? date('Y-m-d H:i:s');

    $assigned_to = (int)Auth::getUserID();
    $created_by = (int)Auth::getUserID();

    // Prefix description with category tag for easy filtering later
    if ($category !== '') {
        $description = '[category:' . $category . '] ' . $description;
    }

    if ($subject === '') {
        $error = 'Subject is required.';
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
                        :activity_date, NULL,
                        'scheduled', NULL,
                        :assigned_to, :created_by
                    )";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':activity_type', $base_type);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);
            $stmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
            $stmt->bindValue(':description', $description === '' ? null : $description, $description === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':related_to', $related_to === '' ? null : $related_to, $related_to === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':related_id', $related_id ?? null, $related_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':activity_date', $activity_date);
            $stmt->execute();

            // Optional follow-up automation
            $create_followup = isset($_POST['create_followup']) && $_POST['create_followup'] === '1';
            $followup_in_days = isset($_POST['followup_in_days']) ? (int)$_POST['followup_in_days'] : 0;
            if ($create_followup && $followup_in_days > 0) {
                $f_date = date('Y-m-d H:i:s', strtotime("+{$followup_in_days} days"));
                $f_subject = 'Follow-up: ' . $subject;
                $fsql = "INSERT INTO activities (
                            activity_type, subject, description,
                            related_to, related_id,
                            activity_date, reminder_date,
                            status, outcome,
                            assigned_to, created_by
                        ) VALUES (
                            'followup', :subject, NULL,
                            :related_to, :related_id,
                            :activity_date, NULL,
                            'scheduled', NULL,
                            :assigned_to, :created_by
                        )";
                $fstmt = $conn->prepare($fsql);
                $fstmt->bindParam(':subject', $f_subject);
                $fstmt->bindValue(':related_to', $related_to === '' ? null : $related_to, $related_to === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $fstmt->bindValue(':related_id', $related_id ?? null, $related_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $fstmt->bindParam(':activity_date', $f_date);
                $fstmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);
                $fstmt->bindParam(':created_by', $created_by, PDO::PARAM_INT);
                $fstmt->execute();
            }

            header('Location: /activities?msg=logged');
            exit();
        } catch (Exception $e) {
            $error = 'Error logging touch: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Sales Touch - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <h2>Log Sales Touch</h2>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header"><strong>Touch Details</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Base Type</label>
                                    <select name="activity_type" class="form-select">
                                        <option value="call">Phone Call</option>
                                        <option value="email">Email</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" name="subject" class="form-control" required placeholder="e.g., Call with ABC Corp">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Touch Category</label>
                                <select name="touch_category" class="form-select">
                                    <option value="">None</option>
                                    <option value="phone">Phone Call</option>
                                    <option value="email">Email</option>
                                    <option value="linkedin">LinkedIn Message</option>
                                    <option value="walkin">Walk-in Visit</option>
                                    <option value="followup">Follow-up Message</option>
                                    <option value="invite">Webinar Invite</option>
                                    <option value="proposal">Proposal</option>
                                    <option value="quote">Quote</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Touch Date/Time</label>
                                    <input type="datetime-local" name="activity_date" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Create Follow-up</label>
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <input class="form-check-input mt-0" type="checkbox" name="create_followup" value="1">
                                        </div>
                                        <input type="number" min="1" max="60" name="followup_in_days" class="form-control" placeholder="Days later">
                                    </div>
                                    <small class="text-muted">Optional: automatically schedule a follow-up activity.</small>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <a href="/activities" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Log Touch</button>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>