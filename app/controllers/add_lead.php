<?php
// add_lead.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & normalize inputs
    $first_name = Helpers::escape($_POST['first_name'] ?? '');
    $last_name = Helpers::escape($_POST['last_name'] ?? '');
    $company = Helpers::escape($_POST['company'] ?? '');
    $email = Helpers::escape($_POST['email'] ?? '');
    $phone = Helpers::escape($_POST['phone'] ?? '');
    $lead_status = Helpers::escape($_POST['lead_status'] ?? 'new');
    $lead_source = Helpers::escape($_POST['lead_source'] ?? 'website');
    $budget = Helpers::escape($_POST['budget'] ?? 'unknown');
    $timeline = Helpers::escape($_POST['timeline'] ?? 'unknown');
    $authority = Helpers::escape($_POST['authority'] ?? 'unknown');
    $need_level = Helpers::escape($_POST['need_level'] ?? 'unknown');
    $notes = ($_POST['notes'] ?? '') ? Helpers::escape($_POST['notes']) : null;
    $assigned_to = (int)(Auth::getUserID());

    if ($company === '') {
        $error = 'Company is required.';
    } else {
        try {
            $lead_code = Helpers::generateCode('LEAD');
            $sql = "INSERT INTO leads 
                    (lead_code, first_name, last_name, company, email, phone, 
                     lead_score, lead_status, lead_source, budget, timeline, authority, need_level, 
                     notes, assigned_to)
                    VALUES
                    (:lead_code, :first_name, :last_name, :company, :email, :phone,
                     :lead_score, :lead_status, :lead_source, :budget, :timeline, :authority, :need_level,
                     :notes, :assigned_to)";

            $stmt = $conn->prepare($sql);
            $lead_score = 0; // default; could compute later
            $stmt->bindParam(':lead_code', $lead_code);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':company', $company);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':lead_score', $lead_score, PDO::PARAM_INT);
            $stmt->bindParam(':lead_status', $lead_status);
            $stmt->bindParam(':lead_source', $lead_source);
            $stmt->bindParam(':budget', $budget);
            $stmt->bindParam(':timeline', $timeline);
            $stmt->bindParam(':authority', $authority);
            $stmt->bindParam(':need_level', $need_level);
            $stmt->bindValue(':notes', $notes, $notes === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':assigned_to', $assigned_to, PDO::PARAM_INT);

            $stmt->execute();
            header('Location: /leads?msg=added');
            exit();
        } catch (Exception $e) {
            $error = 'Error adding lead: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lead - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <h2>Add New Lead</h2>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header"><strong>Contact</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company *</label>
                                <input type="text" name="company" class="form-control" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header"><strong>Qualification</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Lead Status</label>
                                <select name="lead_status" class="form-select">
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="unqualified">Unqualified</option>
                                    <option value="converted">Converted</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Source</label>
                                <select name="lead_source" class="form-select">
                                    <option value="website">Website</option>
                                    <option value="referral">Referral</option>
                                    <option value="social">Social</option>
                                    <option value="event">Event</option>
                                    <option value="cold_call">Cold Call</option>
                                    <option value="email">Email</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Budget</label>
                                    <select name="budget" class="form-select">
                                        <option value="unknown">Unknown</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Timeline</label>
                                    <select name="timeline" class="form-select">
                                        <option value="unknown">Unknown</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="1-3_months">1-3 months</option>
                                        <option value="3-6_months">3-6 months</option>
                                        <option value="6+_months">6+ months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Authority</label>
                                    <select name="authority" class="form-select">
                                        <option value="unknown">Unknown</option>
                                        <option value="decision_maker">Decision maker</option>
                                        <option value="influencer">Influencer</option>
                                        <option value="end_user">End user</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Need Level</label>
                                    <select name="need_level" class="form-select">
                                        <option value="unknown">Unknown</option>
                                        <option value="high">High</option>
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-end">
                <a href="/leads" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Lead</button>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>