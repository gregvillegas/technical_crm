<?php
// index.php - Main entry point (legacy router)
// Use absolute paths and load controllers correctly to avoid require errors

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('BASE_PATH', __DIR__);
define('CONTROLLERS_PATH', BASE_PATH . '/app/controllers');

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/core/Auth.php';

// Simple query-string routing (e.g., /index.php?url=dashboard)
$url = $_GET['url'] ?? 'login';

// Map URLs to controller files
$routes = [
    'login' => 'login.php',
    'dashboard' => 'dashboard.php',
    'customers' => 'customers.php',
    'deals' => 'deals.php',
    'leads' => 'leads.php',
    'activities' => 'activities.php',
    'email' => 'email.php',
    'reports' => 'reports.php',
    'logout' => 'logout.php'
];

if (isset($routes[$url])) {
    $target = CONTROLLERS_PATH . '/' . $routes[$url];
    if (file_exists($target)) {
        require_once $target;
    } else {
        http_response_code(500);
        echo "Controller not found: " . htmlspecialchars($routes[$url]);
    }
} else {
    // 404 error
    http_response_code(404);
    echo "Page not found";
}
?>