<?php
// api/index.php - Serverless Router for Vercel

$base_dir = dirname(__DIR__);

// Retrieve and normalize the request URI path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Static asset handler for style.css
if ($path === 'style.css') {
    header("Content-Type: text/css");
    readfile($base_dir . '/style.css');
    exit();
}

// Route mapping for clean URLs and standard .php extensions
$routes = [
    '' => 'index.php',
    'index' => 'index.php',
    'index.php' => 'index.php',
    'login' => 'login.php',
    'login.php' => 'login.php',
    'register' => 'register.php',
    'register.php' => 'register.php',
    'dashboard' => 'dashboard.php',
    'dashboard.php' => 'dashboard.php',
    'task_action' => 'task_action.php',
    'task_action.php' => 'task_action.php',
    'logout' => 'logout.php',
    'logout.php' => 'logout.php'
];

$file = $routes[$path] ?? null;

if ($file && file_exists($base_dir . '/' . $file)) {
    chdir($base_dir);
    require $base_dir . '/' . $file;
    exit();
}

// Fallback to index.php
chdir($base_dir);
require $base_dir . '/index.php';
