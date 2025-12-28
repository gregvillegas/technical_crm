<?php
// customers.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Customer.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$customerModel = new Customer($conn);

// Handle actions
if(isset($_GET['action'])) {
    if($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $customerModel->id = $_GET['id'];
        if($customerModel->delete()) {
            header("Location: customers.php?msg=deleted");
            exit();
        }
    }
}

// Handle transfer action (admin or sales_manager only)
$message = null;
if (($isAdmin = (Auth::hasRole('admin') || Auth::hasRole('sales_manager'))) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'transfer_customer') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $newAssigned = (int)($_POST['new_assigned_to'] ?? 0);
        if ($customerId && $newAssigned) {
            try {
                $customerModel->transferAssignedTo($customerId, $newAssigned);
                $message = 'Customer transferred successfully';
            } catch (Exception $e) {
                $message = 'Transfer failed: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $message = 'Invalid transfer request';
        }
    }
}

// Search functionality
$customers = [];
$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
$assignedUserId = $isAdmin ? null : Auth::getUserID();
if(isset($_GET['search'])) {
    $customers = $customerModel->search($_GET['search'], $assignedUserId);
} else {
    $customers = $customerModel->read(null, $assignedUserId);
}

// Fetch active sales reps for transfer dropdown
$salesStmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'sales_rep' AND status = 'active' ORDER BY first_name");
$salesStmt->execute();
$salesReps = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .customer-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .customer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customers</h2>
        <a href="/add_customer" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Customer
        </a>
        </div>
        
        <!-- Search Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by company name, contact person, or email..."
                               value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="customers.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Customers Grid -->
        <div class="row">
            <?php foreach($customers as $customer): ?>
                <div class="col-md-4 mb-4">
                    <div class="card customer-card h-100" 
                         onclick="window.location='customer_detail.php?id=<?php echo $customer['id']; ?>'">
                        <div class="card-body">
                            <span class="badge bg-<?php 
                                switch($customer['customer_status']) {
                                    case 'vip': echo 'danger'; break;
                                    case 'active': echo 'success'; break;
                                    case 'qualified': echo 'warning'; break;
                                    default: echo 'secondary';
                                }
                            ?> status-badge">
                                <?php echo ucfirst($customer['customer_status']); ?>
                            </span>
                            
                            <h5 class="card-title"><?php echo $customer['company_name']; ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <i class="fas fa-user"></i> <?php echo $customer['contact_person']; ?>
                            </h6>
                            
                            <p class="card-text">
                                <i class="fas fa-envelope"></i> <?php echo $customer['email']; ?><br>
                                <i class="fas fa-phone"></i> <?php echo $customer['phone']; ?><br>
                                <i class="fas fa-industry"></i> <?php echo $customer['industry']; ?>
                            </p>
                            
                            <?php if($customer['tech_stack']): ?>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <strong>Tech Stack:</strong> <?php echo substr($customer['tech_stack'], 0, 50); ?>...
                                    </small>
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <span class="badge bg-info">
                                    <?php echo $customer['budget_range']; ?> Budget
                                </span>
                                <span class="badge bg-secondary">
                                    <?php echo $customer['lead_source']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    Last Contact: <?php echo Helpers::formatDate($customer['last_contact']); ?>
                                    <?php if($customer['next_followup']): ?>
                                        | Next: <?php echo Helpers::formatDate($customer['next_followup']); ?>
                                    <?php endif; ?>
                                </small>
                                <?php if($isAdmin): ?>
                                <form method="POST" class="d-flex align-items-center mb-0" onsubmit="event.stopPropagation();" onclick="event.stopPropagation();">
                                    <input type="hidden" name="action" value="transfer_customer">
                                    <input type="hidden" name="customer_id" value="<?php echo (int)$customer['id']; ?>">
                                    <select name="new_assigned_to" class="form-select form-select-sm me-2" style="max-width: 180px;">
                                        <?php foreach($salesReps as $rep): ?>
                                            <option value="<?php echo (int)$rep['id']; ?>" <?php echo ($customer['assigned_to'] == $rep['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($rep['first_name'] . ' ' . $rep['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Transfer</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php if($message): ?>
                                <div class="mt-2 small text-info"><?php echo htmlspecialchars($message); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if(empty($customers)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <h4>No customers found</h4>
                <p>Add your first customer to get started!</p>
                <a href="/add_customer" class="btn btn-primary">Add Customer</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>
