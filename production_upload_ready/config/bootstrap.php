<?php
/**
 * Bootstrap/Initialize Services
 * Add this to the top of your main application files or include it in a central location
 * 
 * Usage:
 *   require_once 'constant/connect.php';      // DB connection
 *   require_once 'config/bootstrap.php';      // This file
 *   
 *   // Now you can use:
 *   $services = getServices();
 *   $stock_service = $services->getStockService();
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set session variables if not already set
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path for includes
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/../');
}

// Autoloader for namespace-based classes
spl_autoload_register(function($class) {
    // Example: Config\ServiceContainer -> libraries/Services/...
    $parts = explode('\\', $class);
    $namespace = array_shift($parts);
    $class_name = array_pop($parts);
    
    // Map namespaces to directories
    $namespace_map = [
        'Services'   => BASE_PATH . 'libraries/Services/',
        'Middleware' => BASE_PATH . 'libraries/Middleware/',
        'Helpers'    => BASE_PATH . 'libraries/Helpers/',
        'Controllers' => BASE_PATH . 'libraries/Controllers/',
        'Config'     => BASE_PATH . 'config/',
    ];
    
    if (isset($namespace_map[$namespace])) {
        $file = $namespace_map[$namespace] . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Database connection should be available before this
// Typically from constant/connect.php
if (!isset($connect) || !$connect) {
    die("Database connection not initialized. Include constant/connect.php first.");
}

// Initialize service container (singleton)
require_once BASE_PATH . 'config/services.php';
use Config\ServiceContainer;

/**
 * Global function to get services easily
 * Usage: $services = getServices();
 */
function getServices($db = null)
{
    global $connect;
    $db = $db ?? $connect;
    
    $user_id = $_SESSION['user_id'] ?? $_SESSION['user'] ?? 1;
    $user_role = $_SESSION['user_role'] ?? $_SESSION['user_type'] ?? 'USER';
    
    return ServiceContainer::getInstance($db, $user_id, $user_role);
}

// Log initialization
error_log("Services bootstrap loaded successfully at " . date('Y-m-d H:i:s'));

?>
