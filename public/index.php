<?php
// public/index.php
session_start();

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('CONTROLLERS_PATH', APP_PATH . '/controllers');
define('MODELS_PATH', APP_PATH . '/models');
define('CORE_PATH', APP_PATH . '/core');

// Auto-load classes
spl_autoload_register(function ($class) {
    $paths = [
        CORE_PATH . "/{$class}.php",
        MODELS_PATH . "/{$class}.php",
        CONTROLLERS_PATH . "/{$class}.php"
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Simple routing
// Normalize request path and strip /public prefix added by .htaccess rewrite
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = preg_replace('#^/public#', '', $request); // ensure routes like /login match
$request = rtrim($request, '/');
if ($request === '') { $request = '/'; }

// Route mapping
$routes = [
    '/' => 'dashboard.php',
    '/dashboard' => 'dashboard.php',
    '/customers' => 'customers.php',
    '/deals' => 'deals.php',
    '/add_deal' => 'add_deal.php',
    '/leads' => 'leads.php',
    '/activities' => 'activities.php',
    '/calendar' => 'calendar.php',
    '/email' => 'email.php',
    '/reports' => 'reports.php',
    '/notifications' => 'notifications.php',
    '/tasks' => 'tasks.php',
    '/team' => 'team.php',
    '/login' => 'login.php',
    '/logout' => 'logout.php',
    '/add_customer' => 'add_customer.php',
    '/add_lead' => 'add_lead.php'
    ,'/schedule_activity' => 'schedule_activity.php'
    ,'/log_touch' => 'log_touch.php'
];

// Find the route (exact match first). Avoid '/' matching all paths.
$controllerFile = $routes[$request] ?? null;

// Optional: support routes with extra path segments (e.g., /customers/123)
if (!$controllerFile) {
    foreach ($routes as $route => $file) {
        if ($route !== '/' && strpos($request, $route . '/') === 0) {
            $controllerFile = $file;
            break;
        }
    }
}

// If no route found, show 404
if (!$controllerFile) {
    http_response_code(404);
    echo "Page not found";
    exit();
}

// Include the controller
$controllerPath = CONTROLLERS_PATH . '/' . $controllerFile;
if (file_exists($controllerPath)) {
    require_once $controllerPath;
} else {
    http_response_code(404);
    echo "Controller not found";
}
?>