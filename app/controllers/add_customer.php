<?php
// add_customer.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Customer.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();
$customerModel = new Customer($conn);

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form
    $customerModel->company_name = Helpers::escape($_POST['company_name']);
    $customerModel->contact_person = Helpers::escape($_POST['contact_person'] ?? '');
    $customerModel->email = Helpers::escape($_POST['email'] ?? '');
    $customerModel->phone = Helpers::escape($_POST['phone'] ?? '');
    $customerModel->industry = Helpers::escape($_POST['industry'] ?? '');
    $customerModel->address = Helpers::escape($_POST['address'] ?? '');
    $customerModel->city = Helpers::escape($_POST['city'] ?? '');
    $customerModel->state = Helpers::escape($_POST['state'] ?? '');
    $customerModel->country = Helpers::escape($_POST['country'] ?? '');
    $customerModel->tech_stack = ($_POST['tech_stack'] ?? '') ? Helpers::escape($_POST['tech_stack']) : null;
    $customerModel->current_solutions = ($_POST['current_solutions'] ?? '') ? Helpers::escape($_POST['current_solutions']) : null;
    $customerModel->pain_points = ($_POST['pain_points'] ?? '') ? Helpers::escape($_POST['pain_points']) : null;
    $customerModel->budget_range = Helpers::escape($_POST['budget_range'] ?? '');
    $customerModel->lead_source = Helpers::escape($_POST['lead_source'] ?? '');
    $customerModel->customer_status = Helpers::escape($_POST['customer_status'] ?? 'prospect');
    $customerModel->assigned_to = (int)($_SESSION['user_id'] ?? 0);
    $customerModel->notes = ($_POST['notes'] ?? '') ? Helpers::escape($_POST['notes']) : null;
    $customerModel->last_contact = $_POST['last_contact'] ?: date('Y-m-d');
    $customerModel->next_followup = ($_POST['next_followup'] ?? '') ?: null;

    try {
        if($customerModel->create()) {
            $success = "Customer added successfully!";
            header("Location: /customers?msg=added");
            exit();
        } else {
            $error = "Failed to add customer. Please try again.";
        }
    } catch (Exception $e) {
        $error = "Error adding customer: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Add New Customer</h2>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="mt-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Company Name *</label>
                                <input type="text" class="form-control" name="company_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Industry</label>
                                <select class="form-select" name="industry">
                                    <option value="">Select Industry</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Education">Education</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Technical Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Current Tech Stack</label>
                                <textarea class="form-control" name="tech_stack" rows="3" 
                                          placeholder="e.g., PHP, MySQL, React, AWS"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Current Solutions</label>
                                <textarea class="form-control" name="current_solutions" rows="3"
                                          placeholder="Current tools/systems in use"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pain Points</label>
                                <textarea class="form-control" name="pain_points" rows="3"
                                          placeholder="Challenges they're facing"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Sales Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Budget Range</label>
                                <select class="form-select" name="budget_range">
                                    <option value="">Select Budget</option>
                                    <option value="Under ₱10k">Under ₱10,000</option>
                                    <option value="₱10k - ₱50k">₱10,000 - ₱50,000</option>
                                    <option value="₱50k - ₱100k">₱50,000 - ₱100,000</option>
                                    <option value="₱100k - ₱500k">₱100,000 - ₱500,000</option>
                                    <option value="Over ₱500k">Over ₱500,000</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lead Source</label>
                                <select class="form-select" name="lead_source">
                                    <option value="">Select Source</option>
                                    <option value="Website">Website</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Social Media">Social Media</option>
                                    <option value="Cold Call">Cold Call</option>
                                    <option value="Email Campaign">Email Campaign</option>
                                    <option value="Event">Event</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Customer Status</label>
                                <select class="form-select" name="customer_status">
                                    <option value="prospect">Prospect</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="active">Active</option>
                                    <option value="vip">VIP</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Contact Date</label>
                                    <input type="date" class="form-control" name="last_contact" 
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Next Follow-up Date</label>
                                    <input type="date" class="form-control" name="next_followup">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Address Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">State</label>
                                    <input type="text" class="form-control" name="state">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>Additional Notes</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <textarea class="form-control" name="notes" rows="4" 
                                          placeholder="Additional notes about this customer..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <a href="customers.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Customer
                </button>
            </div>
        </form>
    </div>
    
    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>