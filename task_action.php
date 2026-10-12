<?php
// task_action.php - Handles Create, Update, Delete, and Status Change for Tasks
require_once __DIR__ . '/config.php';
require_login();

$current_user = get_current_user_data();
$user_id = $current_user['id'];
$is_admin = is_admin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper for redirecting with notice messages
function redirect_dashboard($msg = '', $type = 'success') {
    $param = ($type === 'error') ? 'error' : 'msg';
    header("Location: dashboard.php?" . $param . "=" . urlencode($msg));
    exit();
}

// ----------------------------------------------------
// ACTION 1: CREATE TASK
// ----------------------------------------------------
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $status = sanitize($_POST['status'] ?? 'pending');
    $due_date = sanitize($_POST['due_date'] ?? '');

    // Determine task owner
    // Admin can assign task to any valid user, regular user assigns to self
    $target_user_id = $user_id;
    if ($is_admin && isset($_POST['assigned_user_id']) && !empty($_POST['assigned_user_id'])) {
        $target_user_id = (int)$_POST['assigned_user_id'];
    }

    if (empty($title)) {
        redirect_dashboard("Task title cannot be empty.", "error");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, priority, status, due_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$target_user_id, $title, $description, $priority, $status, $due_date ?: null]);
        redirect_dashboard("Task created successfully!");
    } catch (PDOException $e) {
        redirect_dashboard("Error creating task: " . $e->getMessage(), "error");
    }
}

// ----------------------------------------------------
// ACTION 2: UPDATE TASK (EDIT)
// ----------------------------------------------------
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $status = sanitize($_POST['status'] ?? 'pending');
    $due_date = sanitize($_POST['due_date'] ?? '');

    if ($task_id <= 0 || empty($title)) {
        redirect_dashboard("Invalid parameters for updating task.", "error");
    }

    // STRICT ROLE PERMISSION CHECK:
    // Admin can update any task; User can ONLY update their own task
    if (!can_manage_task($pdo, $task_id, $user_id, $is_admin)) {
        redirect_dashboard("Access Denied: You can only edit your own tasks!", "error");
    }

    try {
        if ($is_admin && isset($_POST['assigned_user_id']) && !empty($_POST['assigned_user_id'])) {
            $new_owner_id = (int)$_POST['assigned_user_id'];
            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ?, user_id = ? WHERE id = ?");
            $stmt->execute([$title, $description, $priority, $status, $due_date ?: null, $new_owner_id, $task_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$title, $description, $priority, $status, $due_date ?: null, $task_id]);
        }
        redirect_dashboard("Task updated successfully!");
    } catch (PDOException $e) {
        redirect_dashboard("Error updating task: " . $e->getMessage(), "error");
    }
}

// ----------------------------------------------------
// ACTION 3: QUICK UPDATE STATUS
// ----------------------------------------------------
if ($action === 'update_status') {
    $task_id = (int)($_POST['task_id'] ?? $_GET['task_id'] ?? 0);
    $new_status = sanitize($_POST['status'] ?? $_GET['status'] ?? '');

    $allowed_statuses = ['pending', 'in_progress', 'completed'];

    if ($task_id <= 0 || !in_array($new_status, $allowed_statuses)) {
        redirect_dashboard("Invalid status update request.", "error");
    }

    // STRICT ROLE PERMISSION CHECK
    if (!can_manage_task($pdo, $task_id, $user_id, $is_admin)) {
        redirect_dashboard("Access Denied: You can only update your own tasks!", "error");
    }

    try {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $task_id]);
        redirect_dashboard("Task status updated to " . ucfirst(str_replace('_', ' ', $new_status)));
    } catch (PDOException $e) {
        redirect_dashboard("Error updating status: " . $e->getMessage(), "error");
    }
}

// ----------------------------------------------------
// ACTION 4: DELETE TASK
// ----------------------------------------------------
if ($action === 'delete') {
    $task_id = (int)($_POST['task_id'] ?? $_GET['task_id'] ?? 0);

    if ($task_id <= 0) {
        redirect_dashboard("Invalid task ID.", "error");
    }

    // STRICT ROLE PERMISSION CHECK:
    // Admin can delete any task; User can ONLY delete their own task
    if (!can_manage_task($pdo, $task_id, $user_id, $is_admin)) {
        redirect_dashboard("Access Denied: You can only delete your own tasks!", "error");
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        redirect_dashboard("Task deleted successfully.");
    } catch (PDOException $e) {
        redirect_dashboard("Error deleting task: " . $e->getMessage(), "error");
    }
}

// Fallback redirect if no valid action matched
redirect_dashboard();
?>
