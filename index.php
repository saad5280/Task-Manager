<?php
// index.php - Entry point redirector
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>
