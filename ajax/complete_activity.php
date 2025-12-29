<?php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

header('Content-Type: application/json');

if(!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if(!$id) { echo json_encode(['success' => false]); exit(); }

$db = new Database();
$conn = $db->getConnection();

$isAdmin = Auth::hasRole('admin') || Auth::hasRole('sales_manager');
$sql = "UPDATE activities SET status = 'completed', completed_date = NOW() WHERE id = :id" . ($isAdmin ? '' : " AND assigned_to = :uid");
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
if(!$isAdmin) { $stmt->bindParam(':uid', $_SESSION['user_id'], PDO::PARAM_INT); }
$ok = $stmt->execute();

echo json_encode(['success' => $ok]);
?>
