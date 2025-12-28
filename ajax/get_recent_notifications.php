<?php
// ajax/get_recent_notifications.php
require_once '../config/database.php';
require_once '../app/core/Auth.php';

if(!Auth::isLoggedIn()) {
    echo '<li class="px-3 py-2 text-danger">Please login</li>';
    exit();
}

$userId = Auth::getUserID();
$db = new Database();
$conn = $db->getConnection();

// Get recent notifications
$query = "SELECT * FROM system_notifications 
          WHERE (user_id = ? OR user_id IS NULL) 
          AND is_read = 0 
          ORDER BY created_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userId);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(empty($notifications)) {
    echo '<li class="px-3 py-2 text-center text-muted">No new notifications</li>';
    exit();
}

foreach($notifications as $notification) {
    $timeAgo = timeAgo($notification['created_at']);
    $iconClass = match($notification['priority']) {
        'high' => 'text-danger',
        'medium' => 'text-warning',
        default => 'text-primary'
    };
    
    echo '<li>';
echo '<a class="dropdown-item" href="/notifications">';
    echo '<div class="d-flex">';
    echo '<div class="flex-shrink-0">';
    echo '<i class="fas fa-' . ($notification['icon'] ?? 'bell') . ' ' . $iconClass . '"></i>';
    echo '</div>';
    echo '<div class="flex-grow-1 ms-3">';
    echo '<div class="fw-bold">' . htmlspecialchars($notification['title']) . '</div>';
    echo '<div class="small">' . htmlspecialchars(substr($notification['message'], 0, 50)) . '...</div>';
    echo '<div class="text-muted"><small>' . $timeAgo . '</small></div>';
    echo '</div>';
    echo '</div>';
    echo '</a>';
    echo '</li>';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    elseif ($diff < 3600) return floor($diff / 60) . 'm ago';
    elseif ($diff < 86400) return floor($diff / 3600) . 'h ago';
    elseif ($diff < 604800) return floor($diff / 86400) . 'd ago';
    else return date('M d', $time);
}
?>