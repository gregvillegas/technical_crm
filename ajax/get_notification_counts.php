<?php
// ajax/get_notification_counts.php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = Auth::getUserID();
$db = new Database();
$conn = $db->getConnection();

$counts = [];

// System notifications
$query = "SELECT COUNT(*) as count FROM system_notifications 
          WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userId);
$stmt->execute();
$counts['system'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending follow-ups
$query = "SELECT COUNT(*) as count FROM followups 
          WHERE assigned_to = ? AND status = 'pending' 
          AND reminder_date <= CURDATE() + INTERVAL 3 DAY";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userId);
$stmt->execute();
$counts['followups'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Overdue activities
$query = "SELECT COUNT(*) as count FROM activities 
          WHERE assigned_to = ? AND status = 'scheduled' 
          AND activity_date < NOW()";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userId);
$stmt->execute();
$counts['activities'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$counts['total'] = $counts['system'] + $counts['followups'] + $counts['activities'];

echo json_encode($counts);
?>