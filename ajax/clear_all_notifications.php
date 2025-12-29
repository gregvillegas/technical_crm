<?php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

header('Content-Type: application/json');

if(!Auth::isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$userId = Auth::getUserID();

$stmt = $conn->prepare("UPDATE system_notifications SET is_read = 1 WHERE user_id = :uid");
$stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();

echo json_encode(['success' => true]);
?>
