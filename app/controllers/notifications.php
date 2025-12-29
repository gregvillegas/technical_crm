<?php
// app/views/notifications.php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';

if(!Auth::isLoggedIn()) {
    header("Location: /login");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get unread notifications for current user
$userId = Auth::getUserID();

// Get system notifications
$query = "SELECT * FROM system_notifications 
          WHERE (user_id = ? OR user_id IS NULL) 
          AND is_read = 0 
          ORDER BY created_at DESC 
          LIMIT 50";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userId);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending follow-ups
$followupQuery = "SELECT f.*, d.deal_name, c.company_name
                 FROM followups f
                 JOIN deals d ON f.deal_id = d.id
                 JOIN customers c ON d.customer_id = c.id
                 WHERE f.assigned_to = ? 
                 AND f.status = 'pending'
                 AND f.reminder_date <= CURDATE() + INTERVAL 3 DAY
                 ORDER BY f.reminder_date ASC";
$followupStmt = $conn->prepare($followupQuery);
$followupStmt->bindParam(1, $userId);
$followupStmt->execute();
$pendingFollowups = $followupStmt->fetchAll(PDO::FETCH_ASSOC);

// Get overdue activities
$activityQuery = "SELECT a.*, c.company_name
                 FROM activities a
                 LEFT JOIN customers c ON a.related_id = c.id AND a.related_to = 'customer'
                 WHERE a.assigned_to = ? 
                 AND a.status = 'scheduled'
                 AND a.activity_date < NOW()
                 ORDER BY a.activity_date ASC";
$activityStmt = $conn->prepare($activityQuery);
$activityStmt->bindParam(1, $userId);
$activityStmt->execute();
$overdueActivities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

// Mark notifications as read when page is loaded
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_all_read'])) {
    $updateQuery = "UPDATE system_notifications SET is_read = 1 
                    WHERE user_id = ? AND is_read = 0";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(1, $userId);
    $updateStmt->execute();
    
 header("Location: /notifications?marked=read");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .notification-item {
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .notification-item.unread {
            border-left-color: #007bff;
            background-color: #f0f8ff;
        }
        .notification-item.urgent {
            border-left-color: #dc3545;
            background-color: #fff5f5;
        }
        .notification-item.warning {
            border-left-color: #ffc107;
            background-color: #fffdf5;
        }
        .notification-item.success {
            border-left-color: #28a745;
            background-color: #f5fff7;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
        .time-ago {
            font-size: 12px;
            color: #6c757d;
        }
        .notification-actions {
            opacity: 0;
            transition: opacity 0.3s;
        }
        .notification-item:hover .notification-actions {
            opacity: 1;
        }
        .notification-category {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../views/sidebar.php'; ?>
    
<div class="container-fluid py-4">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Notifications</h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-bell"></i> 
                        <?php echo count($notifications) + count($pendingFollowups) + count($overdueActivities); ?> total items
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" class="d-inline">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-primary">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </form>
                    <button class="btn btn-outline-secondary" onclick="refreshNotifications()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearAllNotifications()">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </div>
            </div>
            
            <?php if(isset($_GET['marked'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    All notifications marked as read!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="notificationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                        All
                        <span class="badge bg-primary ms-1"><?php echo count($notifications); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="followup-tab" data-bs-toggle="tab" data-bs-target="#followup" type="button">
                        <i class="fas fa-clock"></i> Follow-ups
                        <span class="badge bg-warning ms-1"><?php echo count($pendingFollowups); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button">
                        <i class="fas fa-tasks"></i> Activities
                        <span class="badge bg-danger ms-1"><?php echo count($overdueActivities); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button">
                        <i class="fas fa-cog"></i> System
                        <span class="badge bg-info ms-1"><?php echo count($notifications); ?></span>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="notificationTabsContent">
                <!-- All Notifications Tab -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <?php if(empty($notifications) && empty($pendingFollowups) && empty($overdueActivities)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                            <h4>No notifications</h4>
                            <p class="text-muted">You're all caught up!</p>
                        </div>
                    <?php else: ?>
                        <!-- System Notifications -->
                        <?php if(!empty($notifications)): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">System Notifications</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach($notifications as $notification): ?>
                                        <div class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                            <div class="d-flex align-items-start">
                                                <div class="notification-icon bg-<?php 
                                                    switch($notification['priority']) {
                                                        case 'high': echo 'danger'; break;
                                                        case 'medium': echo 'warning'; break;
                                                        default: echo 'info';
                                                    }
                                                ?> text-white">
                                                    <i class="fas fa-<?php echo $notification['icon'] ?? 'bell'; ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                        <div class="time-ago">
                                                            <?php echo timeAgo($notification['created_at']); ?>
                                                        </div>
                                                    </div>
                                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <?php if($notification['related_type']): ?>
                                                        <small class="text-muted">
                                                            Related to: <?php echo ucfirst($notification['related_type']); ?> #<?php echo $notification['related_id']; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                                        <span class="notification-category bg-<?php 
                                                            switch($notification['category']) {
                                                                case 'deal': echo 'primary'; break;
                                                                case 'customer': echo 'success'; break;
                                                                case 'lead': echo 'warning'; break;
                                                                case 'system': echo 'secondary'; break;
                                                                default: echo 'info';
                                                            }
                                                        ?> text-white">
                                                            <?php echo ucfirst($notification['category']); ?>
                                                        </span>
                                                        <div class="notification-actions">
                                                            <?php if(!$notification['is_read']): ?>
                                                                <button class="btn btn-sm btn-outline-success" 
                                                                        onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                                    <i class="fas fa-check"></i> Mark Read
                                                                </button>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pending Follow-ups -->
                        <?php if(!empty($pendingFollowups)): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Follow-ups</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach($pendingFollowups as $followup): 
                                        $isToday = date('Y-m-d') == $followup['reminder_date'];
                                        $isTomorrow = date('Y-m-d', strtotime('+1 day')) == $followup['reminder_date'];
                                    ?>
                                        <div class="list-group-item notification-item <?php echo $isToday ? 'urgent' : ($isTomorrow ? 'warning' : ''); ?>">
                                            <div class="d-flex align-items-start">
                                                <div class="notification-icon <?php echo $isToday ? 'bg-danger' : ($isTomorrow ? 'bg-warning' : 'bg-info'); ?> text-white">
                                                    <i class="fas fa-calendar-check"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1">Follow-up Required</h6>
                                                        <div class="time-ago">
                                                            <?php 
                                                            if($isToday) echo '<span class="badge bg-danger">Today</span>';
                                                            elseif($isTomorrow) echo '<span class="badge bg-warning">Tomorrow</span>';
                                                            else echo 'Due: ' . date('M d', strtotime($followup['reminder_date']));
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <p class="mb-1">
                                                        <strong><?php echo htmlspecialchars($followup['deal_name']); ?></strong> - 
                                                        <?php echo htmlspecialchars($followup['company_name']); ?>
                                                    </p>
                                                    <?php if($followup['notes']): ?>
                                                        <p class="mb-1 small"><?php echo htmlspecialchars($followup['notes']); ?></p>
                                                    <?php endif; ?>
                                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                                        <span class="notification-category bg-warning text-dark">
                                                            <i class="fas fa-funnel-dollar"></i> Deal Follow-up
                                                        </span>
                                                        <div class="notification-actions">
                                                            <a href="deal_detail.php?id=<?php echo $followup['deal_id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> View Deal
                                                            </a>
                                                            <button class="btn btn-sm btn-outline-success" 
                                                                    onclick="completeFollowup(<?php echo $followup['id']; ?>)">
                                                                <i class="fas fa-check"></i> Complete
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Overdue Activities -->
                        <?php if(!empty($overdueActivities)): ?>
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Overdue Activities</h5>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach($overdueActivities as $activity): 
                                        $daysOverdue = floor((time() - strtotime($activity['activity_date'])) / (60 * 60 * 24));
                                    ?>
                                        <div class="list-group-item notification-item urgent">
                                            <div class="d-flex align-items-start">
                                                <div class="notification-icon bg-danger text-white">
                                                    <i class="fas fa-exclamation"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($activity['subject']); ?></h6>
                                                        <div class="time-ago">
                                                            <span class="badge bg-danger">
                                                                <?php echo $daysOverdue; ?> day<?php echo $daysOverdue != 1 ? 's' : ''; ?> overdue
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                    <?php if($activity['company_name']): ?>
                                                        <p class="mb-1 small">
                                                            Customer: <?php echo htmlspecialchars($activity['company_name']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                                        <span class="notification-category bg-danger text-white">
                                                            <i class="fas fa-tasks"></i> Activity
                                                        </span>
                                                        <div class="notification-actions">
                                                            <button class="btn btn-sm btn-outline-success" 
                                                                    onclick="completeActivity(<?php echo $activity['id']; ?>)">
                                                                <i class="fas fa-check"></i> Mark Complete
                                                            </button>
                                                            <a href="/reschedule_activity?id=<?php echo $activity['id']; ?>" 
                                                               class="btn btn-sm btn-outline-warning">
                                                                <i class="fas fa-calendar-alt"></i> Reschedule
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Follow-ups Tab -->
                <div class="tab-pane fade" id="followup" role="tabpanel">
                    <?php if(empty($pendingFollowups)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4>No pending follow-ups</h4>
                            <p class="text-muted">You're all caught up with follow-ups!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($pendingFollowups as $followup): ?>
                                <!-- Same follow-up items as above -->
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Activities Tab -->
                <div class="tab-pane fade" id="activity" role="tabpanel">
                    <?php if(empty($overdueActivities)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-check fa-4x text-success mb-3"></i>
                            <h4>No overdue activities</h4>
                            <p class="text-muted">All activities are up to date!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($overdueActivities as $activity): ?>
                                <!-- Same activity items as above -->
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- System Tab -->
                <div class="tab-pane fade" id="system" role="tabpanel">
                    <?php if(empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                            <h4>No system notifications</h4>
                            <p class="text-muted">No system notifications at this time.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($notifications as $notification): ?>
                                <!-- Same system notification items as above -->
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    include __DIR__ . '/../views/footer.php'; 
    ?>
    
    <!-- Time Ago Function -->
    <?php
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks != 1 ? 's' : '') . ' ago';
        } else {
            return date('M d, Y', $time);
        }
    }
    ?>
    
    <script>
    // AJAX Functions for Notifications
    function markAsRead(notificationId) {
        fetch('/ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.querySelector(`[onclick="markAsRead(${notificationId})"]`).closest('.notification-item').classList.remove('unread');
                updateNotificationCount();
            }
        });
    }
    
    function deleteNotification(notificationId) {
        if(!confirm('Are you sure you want to delete this notification?')) return;
        
        fetch('/ajax/delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.querySelector(`[onclick="deleteNotification(${notificationId})"]`).closest('.notification-item').remove();
                updateNotificationCount();
            }
        });
    }
    
    function completeFollowup(followupId) {
        fetch('/ajax/complete_followup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: followupId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            }
        });
    }
    
    function completeActivity(activityId) {
        fetch('/ajax/complete_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: activityId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            }
        });
    }
    
    function refreshNotifications() {
        location.reload();
    }
    
    function clearAllNotifications() {
        if(!confirm('Are you sure you want to clear all notifications? This cannot be undone.')) return;
        
        fetch('/ajax/clear_all_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            }
        });
    }
    
    function updateNotificationCount() {
        // Update badge counts
        fetch('/ajax/get_notification_counts.php')
        .then(response => response.json())
        .then(data => {
            // Update badges in tabs
            document.querySelector('#all-tab .badge').textContent = data.total;
            document.querySelector('#followup-tab .badge').textContent = data.followups;
            document.querySelector('#activity-tab .badge').textContent = data.activities;
            document.querySelector('#system-tab .badge').textContent = data.system;
            
            // Update top bar notification badge
            const topBadge = document.querySelector('.top-bar .badge');
            if(topBadge) {
                topBadge.textContent = data.total;
                if(data.total === 0) {
                    topBadge.style.display = 'none';
                } else {
                    topBadge.style.display = 'block';
                }
            }
        });
    }
    
    // Auto-refresh notifications every 60 seconds
    setInterval(refreshNotifications, 60000);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // R to refresh
        if(e.key === 'r' && e.ctrlKey) {
            e.preventDefault();
            refreshNotifications();
        }
        // M to mark all as read
        if(e.key === 'm' && e.ctrlKey) {
            e.preventDefault();
            document.querySelector('button[name="mark_all_read"]').click();
        }
        // Number keys to switch tabs
        if(e.key >= '1' && e.key <= '4' && e.ctrlKey) {
            e.preventDefault();
            const tabIndex = parseInt(e.key) - 1;
            const tabs = document.querySelectorAll('.nav-link');
            if(tabs[tabIndex]) {
                tabs[tabIndex].click();
            }
        }
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    </script>
</body>
</html>
