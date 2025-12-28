<?php
// leads.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Example static leads (replace with DB queries later)
$leads = [
    ['company' => 'ACME Co.', 'contact' => 'Jane Doe', 'status' => 'new', 'source' => 'Website'],
    ['company' => 'Widgets LLC', 'contact' => 'John Smith', 'status' => 'qualified', 'source' => 'Referral'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Leads</h2>
            <a href="/add_lead" class="btn btn-primary">
                <i class="fas fa-bullseye"></i> Add Lead
            </a>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($leads as $lead): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lead['company']); ?></td>
                                <td><?php echo htmlspecialchars($lead['contact']); ?></td>
                                <td><span class="badge bg-<?php echo $lead['status'] === 'qualified' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($lead['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($lead['source']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>