<?php
// sidebar.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

if(!Auth::isLoggedIn()) {
    header("Location: /login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            min-height: 100vh;
            padding: 0;
            position: fixed;
            width: 250px;
            z-index: 1000;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        .dropdown-menu { z-index: 2000; }
        .sidebar-brand {
            padding: 20px 15px;
            color: white;
            border-bottom: 1px solid #34495e;
            text-align: center;
        }
        .sidebar-brand h4 {
            margin: 0;
            font-weight: 600;
        }
        .sidebar-brand small {
            color: #bdc3c7;
            font-size: 12px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
        }
        .sidebar-menu li {
            padding: 0;
            margin: 5px 0;
        }
        .sidebar-menu a {
            color: #bdc3c7;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 14px;
            border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid #1abc9c;
        }
        .sidebar-menu a.active {
            background: rgba(26, 188, 156, 0.1);
            color: #1abc9c;
            border-left: 3px solid #1abc9c;
        }
        .sidebar-menu a i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
            text-align: center;
        }
        .user-profile {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            color: white;
            border-top: 1px solid #34495e;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #1abc9c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-brand span, 
            .sidebar-menu span {
                display: none;
            }
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 18px;
            }
            .main-content {
                margin-left: 70px;
            }
            .user-profile span {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Notifications dropdown -->
<div class="dropdown me-3">
    <button class="btn btn-outline-secondary position-relative" type="button" 
            data-bs-toggle="dropdown" id="notificationDropdown">
        <i class="fas fa-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
              id="notificationBadge" style="display: none;">
            0
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" style="width: 350px;">
        <li><h6 class="dropdown-header">Recent Notifications</h6></li>
        <div id="notificationDropdownContent">
            <!-- Will be loaded via AJAX -->
            <li class="px-3 py-2 text-center">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </li>
        </div>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-center" href="/notifications">
            <i class="fas fa-eye"></i> View All Notifications
        </a></li>
    </ul>
</div>

    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="sidebar-brand">
            <h4><i class="fas fa-chart-line"></i> <span>TechCRM</span></h4>
            <small>Technical Sales CRM</small>
        </div>
        
        <ul class="sidebar-menu">
            <?php
            // Get current page for active state
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <li>
                <a href="/dashboard" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/customers" class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span>Customers</span>
                </a>
            </li>
            <li>
                <a href="/deals" class="<?php echo $current_page == 'deals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-funnel-dollar"></i> <span>Sales Funnel</span>
                </a>
            </li>
            <li>
                <a href="/leads" class="<?php echo $current_page == 'leads.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullseye"></i> <span>Leads</span>
                </a>
            </li>
            <li>
                <a href="/activities" class="<?php echo $current_page == 'activities.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i> <span>Activities</span>
                </a>
            </li>
            <li>
                <a href="/email" class="<?php echo $current_page == 'email.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> <span>Email Templates</span>
                </a>
            </li>
            <li>
                <a href="/reports" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="/calendar" class="<?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> <span>Calendar</span>
                </a>
            </li>
            <li>
                <a href="/tasks" class="<?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> <span>Tasks</span>
                </a>
            </li>
            <?php if(Auth::hasRole('admin') || Auth::hasRole('sales_manager')): ?>
            <li>
        <a href="/team" class="<?php echo $current_page == 'team.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-friends"></i> <span>Team</span>
        </a>
            </li>
            <li>
                <a href="/notifications" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> <span>Notifications</span>
                    <?php
                    // Show notification count badge
                    $notificationCount = 0; // You can calculate this from database
                    if($notificationCount > 0): ?>
                        <span class="badge bg-danger float-end"><?php echo $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>


            <?php endif; ?>
        </ul>
        
        <div class="user-profile d-flex align-items-center">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
            <div>
                <div class="fw-bold"><?php echo $_SESSION['full_name']; ?></div>
                <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
            </div>
        </div>
    </div>

    <!-- Mobile Navbar -->
    <nav class="navbar navbar-dark bg-dark d-md-none fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line"></i> TechCRM
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="offcanvas offcanvas-end bg-dark text-white" tabindex="-1" id="mobileSidebar">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title">
                        <i class="fas fa-chart-line"></i> TechCRM
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="/dashboard">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>" href="/customers">
                                <i class="fas fa-users"></i> Customers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'deals.php' ? 'active' : ''; ?>" href="/deals">
                                <i class="fas fa-funnel-dollar"></i> Sales Funnel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'leads.php' ? 'active' : ''; ?>" href="/leads">
                                <i class="fas fa-bullseye"></i> Leads
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'activities.php' ? 'active' : ''; ?>" href="/activities">
                                <i class="fas fa-tasks"></i> Activities
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'email.php' ? 'active' : ''; ?>" href="/email">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="/reports">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>" href="/calendar">
                                <i class="fas fa-calendar-alt"></i> Calendar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>" href="/tasks">
                                <i class="fas fa-check-circle"></i> Tasks
                            </a>
                        </li>
                        <?php if(Auth::hasRole('admin') || Auth::hasRole('sales_manager')): ?>
                        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'team.php' ? 'active' : ''; ?>" href="/team">
                <i class="fas fa-user-friends"></i> Team
            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mt-3">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                                </div>
                                <div class="ms-3">
                                    <div class="fw-bold"><?php echo $_SESSION['full_name']; ?></div>
                                    <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="/logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Bar for Desktop -->
        <div class="top-bar d-none d-md-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">
                    <?php
                    $page_titles = [
                        'dashboard.php' => 'Dashboard',
                        'customers.php' => 'Customer Management',
                        'deals.php' => 'Sales Funnel',
                        'leads.php' => 'Lead Management',
                        'activities.php' => 'Activities',
                        'email.php' => 'Email Templates',
                        'reports.php' => 'Reports & Analytics',
                        'calendar.php' => 'Calendar',
                        'tasks.php' => 'Tasks',
                        'team.php' => 'Team Management'
                    ];
                    echo $page_titles[$current_page] ?? 'Technical CRM';
                    ?>
                </h4>
                <small class="text-muted">
                    <?php echo date('l, F j, Y'); ?>
                </small>
            </div>
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-outline-secondary position-relative" type="button" 
                            data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-envelope text-primary"></i> Follow-up due tomorrow
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-calendar text-success"></i> Meeting in 30 minutes
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-check-circle text-warning"></i> Deal updated
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="/notifications">View All</a></li>
                    </ul>
                </div>
                
                <!-- Quick Actions -->
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown">
                        <i class="fas fa-plus"></i> Quick Action
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/add_customer">
                            <i class="fas fa-user-plus"></i> Add Customer
                        </a></li>
                <li><a class="dropdown-item" href="/add_deal">
                    <i class="fas fa-plus-circle"></i> Create Deal
                </a></li>
                        <li><a class="dropdown-item" href="/add_lead">
                            <i class="fas fa-bullseye"></i> Add Lead
                        </a></li>
                        <li><a class="dropdown-item" href="/log_touch">
                            <i class="fas fa-hand-pointer"></i> Log Sales Touch
                        </a></li>
                        <li><a class="dropdown-item" href="/schedule_activity">
                            <i class="fas fa-calendar-plus"></i> Schedule Activity
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Content will be inserted here by individual pages -->
