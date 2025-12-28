<?php
// ajax/delete_notification.php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();

$query = "DELETE FROM system_notifications WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $notificationId);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>