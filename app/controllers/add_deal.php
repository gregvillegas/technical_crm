<?php
// add_deal.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Deal.php';
require_once __DIR__ . '/../models/Customer.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();
$dealModel = new Deal($conn);
$customerModel = new Customer($conn);

$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
$assignedUserId = $isAdmin ? null : Auth::getUserID();
$customers = $customerModel->read(null, $assignedUserId);

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'customer_id' => $_POST['customer_id'],
        'deal_name' => Helpers::escape($_POST['deal_name']),
        'description' => Helpers::escape($_POST['description'] ?? ''),
        'deal_value' => (float)($_POST['deal_value'] ?? 0),
        'funnel_category' => Helpers::escape($_POST['funnel_category'] ?? 'pink'),
        'deal_status' => 'open',
        'probability' => (int)($_POST['probability'] ?? 10),
        'expected_close' => $_POST['expected_close'] ?? date('Y-m-d', strtotime('+30 days')),
        'quote_date' => $_POST['quote_date'] ?? date('Y-m-d'),
        'deal_type' => Helpers::escape($_POST['deal_type'] ?? 'product'),
        'requirements' => Helpers::escape($_POST['requirements'] ?? ''),
        'competitors' => Helpers::escape($_POST['competitors'] ?? ''),
        'owner_id' => Auth::getUserID()
    ];

    if(!$data['customer_id'] || !$data['deal_name']) {
        $error = 'Please select a customer and enter a deal name.';
    } else {
        $newId = $dealModel->create($data);
        if($newId) {
            header('Location: /deals?msg=created');
            exit();
        } else {
            $error = 'Failed to create deal. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Deal - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Create New Deal</h2>
            <a href="/deals" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Funnel</a>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header"><h5>Deal Details</h5></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Customer *</label>
                                <select name="customer_id" class="form-select" required>
                                    <option value="">Select customer</option>
                                    <?php foreach($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>">
                                            <?php echo htmlspecialchars($c['company_name'] . ' — ' . ($c['contact_person'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deal Name *</label>
                                <input type="text" name="deal_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Deal Value (₱)</label>
                                    <input type="number" step="0.01" name="deal_value" class="form-control" placeholder="0.00">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Probability (%)</label>
                                    <input type="number" name="probability" class="form-control" min="0" max="100" value="10">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expected Close Date</label>
                                    <input type="date" name="expected_close" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quote Date</label>
                                    <input type="date" name="quote_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Funnel Category</label>
                                <select name="funnel_category" class="form-select">
                                    <option value="yellow">Closable This Month</option>
                                    <option value="pink" selected>Newly Quoted</option>
                                    <option value="green">Project Based</option>
                                    <option value="blue">Services Offered</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deal Type</label>
                                <select name="deal_type" class="form-select">
                                    <option value="product">Product</option>
                                    <option value="service">Service</option>
                                    <option value="project">Project</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header"><h5>Additional Info</h5></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Requirements</label>
                                <textarea name="requirements" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Competitors</label>
                                <textarea name="competitors" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Deal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>