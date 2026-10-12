<?php
// logout.php - Destroy user session
require_once __DIR__ . '/config.php';

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
sync_session_cookie(true);

header("Location: login.php?logout=1");
exit();
?>
