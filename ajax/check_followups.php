<?php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['count' => 0, 'error' => 'unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$userId = Auth::getUserID();

// Count pending follow-ups due today for current user
$sql = "SELECT COUNT(*) AS cnt
        FROM activities
        WHERE assigned_to = :uid
          AND status = 'scheduled'
          AND activity_type = 'followup'
          AND (
              DATE(reminder_date) = CURDATE()
              OR (reminder_date IS NULL AND DATE(activity_date) = CURDATE())
          )";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$count = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
