<?php
// api/router.php - Vercel serverless entry point
// All PHP app files live in the project root (one level up from /api)

$base_dir = dirname(__DIR__);

// Normalize request path
$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

// Route map: URL path => PHP file in root
$routes = [
    ''               => 'index.php',
    'index'          => 'index.php',
    'index.php'      => 'index.php',
    'login'          => 'login.php',
    'login.php'      => 'login.php',
    'register'       => 'register.php',
    'register.php'   => 'register.php',
    'dashboard'      => 'dashboard.php',
    'dashboard.php'  => 'dashboard.php',
    'task_action'    => 'task_action.php',
    'task_action.php'=> 'task_action.php',
    'logout'         => 'logout.php',
    'logout.php'     => 'logout.php',
];

$file = $routes[$path] ?? null;

if ($file && file_exists($base_dir . '/' . $file)) {
    chdir($base_dir);
    require $base_dir . '/' . $file;
    exit();
}

// Fallback
chdir($base_dir);
require $base_dir . '/index.php';
