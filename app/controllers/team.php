<?php
// team.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Helpers.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Handle admin actions: add user, update quota
$message = null;
if ((Auth::hasRole('admin')) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_user') {
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'sales_rep';
        $quota = isset($_POST['quota_profit']) ? (float)$_POST['quota_profit'] : 250000.00;
        $password = $_POST['password'] ?? '';
        if ($first && $last && $username && $email && $password) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, quota_profit, status) 
                        VALUES (:username, :email, :password, :first_name, :last_name, :role, :quota_profit, 'active')";
                $stmtIns = $conn->prepare($sql);
                $stmtIns->bindParam(':username', $username);
                $stmtIns->bindParam(':email', $email);
                $stmtIns->bindParam(':password', $hash);
                $stmtIns->bindParam(':first_name', $first);
                $stmtIns->bindParam(':last_name', $last);
                $stmtIns->bindParam(':role', $role);
                $stmtIns->bindParam(':quota_profit', $quota);
                $stmtIns->execute();
                $message = 'User added successfully';
            } catch (Exception $e) {
                $message = 'Error adding user: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $message = 'Missing required fields for new user';
        }
    } elseif ($action === 'update_quota') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $quota = isset($_POST['quota_profit']) ? (float)$_POST['quota_profit'] : null;
        if ($userId && $quota !== null) {
            try {
                $sql = "UPDATE users SET quota_profit = :quota WHERE id = :id";
                $stmtUpd = $conn->prepare($sql);
                $stmtUpd->bindParam(':quota', $quota);
                $stmtUpd->bindParam(':id', $userId);
                $stmtUpd->execute();
                $message = 'Quota updated';
            } catch (Exception $e) {
                $message = 'Error updating quota: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            $message = 'Invalid quota update request';
        }
    }
}

$query = "SELECT * FROM users ORDER BY role, first_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Team</h2>
            <span class="text-muted">Total members: <?php echo count($users); ?></span>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (Auth::hasRole('admin')): ?>
        <div class="card mb-4">
            <div class="card-header">
                <strong>Add User</strong>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="add_user">
                    <div class="col-md-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="sales_rep">Sales Rep</option>
                            <option value="sales_manager">Sales Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quota (Profit)</label>
                        <input type="number" step="0.01" name="quota_profit" class="form-control" value="250000.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach($users as $user): ?>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-user-circle fa-2x text-secondary"></i>
                            </div>
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'dark' : ($user['role'] === 'sales_manager' ? 'info' : 'primary'); ?>"><?php echo str_replace('_', ' ', ucfirst($user['role'])); ?></span>
                                <?php if(isset($user['quota_profit'])): ?>
                                    <div class="mt-2 small text-muted">Quota: <?php echo Helpers::formatCurrency($user['quota_profit']); ?></div>
                                <?php endif; ?>
                                <?php if (Auth::hasRole('admin')): ?>
                                    <form method="POST" class="mt-2 d-flex align-items-center" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="action" value="update_quota">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                        <input type="number" step="0.01" name="quota_profit" class="form-control form-control-sm me-2" style="max-width:160px" value="<?php echo htmlspecialchars($user['quota_profit'] ?? 250000.00); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Update Quota</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../views/footer.php'; ?>
</body>
</html>