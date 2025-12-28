<?php
// tasks.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

include __DIR__ . '/../views/sidebar.php';
?>

<div class="container-fluid">
    <h2>Tasks</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>My Tasks</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Follow up with ABC Corp
                            <span class="badge bg-warning">Due Today</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Send proposal to XYZ Inc
                            <span class="badge bg-primary">Due Tomorrow</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/footer.php'; ?>
