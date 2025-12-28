<?php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$isAdmin = (Auth::hasRole('admin') || Auth::hasRole('sales_manager'));
$userId = Auth::getUserID();

if ($isAdmin) {
    $sql = "SELECT id, company_name, contact_person, email FROM customers ORDER BY company_name";
    $stmt = $conn->query($sql);
} else {
    $sql = "SELECT id, company_name, contact_person, email FROM customers WHERE assigned_to = ? ORDER BY company_name";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
}

$options = '<option value="">Select Customer</option>';
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $label = htmlspecialchars($row['company_name']);
    if (!empty($row['contact_person'])) {
        $label .= ' (' . htmlspecialchars($row['contact_person']) . ')';
    }
    if (!empty($row['email'])) {
        $label .= ' - ' . htmlspecialchars($row['email']);
    }
    $options .= '<option value="' . (int)$row['id'] . '">' . $label . '</option>';
}

header('Content-Type: text/html; charset=UTF-8');
echo $options;
?>
