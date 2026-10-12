<?php
// config.php - Session management and core security helper functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session resilience for serverless/multi-instance environments (e.g. Vercel)
$session_secret = getenv('SESSION_SECRET') ?: 'tm_sec_key_9941a823b1238947';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['tm_session'])) {
    $parts = explode('.', $_COOKIE['tm_session'], 2);
    if (count($parts) === 2) {
        $payload = base64_decode($parts[0]);
        $sig = $parts[1];
        if (hash_hmac('sha256', $payload, $session_secret) === $sig) {
            $user_data = json_decode($payload, true);
            if (is_array($user_data) && !empty($user_data['user_id'])) {
                $_SESSION['user_id'] = $user_data['user_id'];
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['email'] = $user_data['email'] ?? '';
                $_SESSION['role'] = $user_data['role'];
            }
        }
    }
}

/**
 * Synchronize session data to signed HTTP cookie
 */
function sync_session_cookie($clear = false) {
    global $session_secret;
    if ($clear || empty($_SESSION['user_id'])) {
        setcookie('tm_session', '', time() - 3600, '/', '', false, true);
    } else {
        $payload = json_encode([
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role']
        ]);
        $sig = hash_hmac('sha256', $payload, $session_secret);
        setcookie('tm_session', base64_encode($payload) . '.' . $sig, time() + 86400 * 7, '/', '', false, true);
    }
}

require_once __DIR__ . '/db.php';

/**
 * Sanitize string input for safe HTML output
 */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged-in user is an Admin
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login to access a page
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Get current user details from session
 */
function get_current_user_data() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role']
    ];
}

/**
 * Check if current user has permission to modify a specific task
 * Admin can modify any task.
 * User can ONLY modify their own task.
 */
function can_manage_task($pdo, $task_id, $user_id, $is_admin) {
    if ($is_admin) return true;

    $stmt = $pdo->prepare("SELECT user_id FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if ($task && (int)$task['user_id'] === (int)$user_id) {
        return true;
    }

    return false;
}
?>
