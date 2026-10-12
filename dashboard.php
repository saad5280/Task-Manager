<?php
// dashboard.php - Main Task Management Interface
require_once __DIR__ . '/config.php';
require_login();

$current_user = get_current_user_data();
$user_id = $current_user['id'];
$is_admin = is_admin();

// Fetch filter & search parameters
$filter_status = sanitize($_GET['status'] ?? '');
$filter_priority = sanitize($_GET['priority'] ?? '');
$filter_user = sanitize($_GET['user_id'] ?? '');
$search_query = sanitize($_GET['q'] ?? '');

$msg = sanitize($_GET['msg'] ?? '');
$error = sanitize($_GET['error'] ?? '');

// ----------------------------------------------------
// FETCH STATS (ADMIN: GLOBAL | USER: OWN ONLY)
// ----------------------------------------------------
if ($is_admin) {
    $stats_total = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $stats_pending = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
    $stats_progress = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn();
    $stats_completed = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats_total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $stats_pending = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'in_progress'");
    $stmt->execute([$user_id]);
    $stats_progress = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $stats_completed = $stmt->fetchColumn();
}

// Fetch all users list for Admin selection dropdown
$all_users = [];
if ($is_admin) {
    $all_users = $pdo->query("SELECT id, username, email, role FROM users ORDER BY username ASC")->fetchAll();
}

// ----------------------------------------------------
// BUILD MAIN TASKS QUERY WITH FILTERS & AUTHORIZATION
// ----------------------------------------------------
$sql = "SELECT t.*, u.username as owner_name, u.role as owner_role 
        FROM tasks t 
        JOIN users u ON t.user_id = u.id 
        WHERE 1=1";
$params = [];

// ROLE SECURITY: Regular user CAN ONLY view their own tasks!
if (!$is_admin) {
    $sql .= " AND t.user_id = ?";
    $params[] = $user_id;
} else {
    // Admin optional user filter
    if (!empty($filter_user)) {
        $sql .= " AND t.user_id = ?";
        $params[] = $filter_user;
    }
}

// Filter by Status
if (!empty($filter_status)) {
    $sql .= " AND t.status = ?";
    $params[] = $filter_status;
}

// Filter by Priority
if (!empty($filter_priority)) {
    $sql .= " AND t.priority = ?";
    $params[] = $filter_priority;
}

// Search Query
if (!empty($search_query)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$sql .= " ORDER BY CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END, t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager Pro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="dashboard.php" class="brand">
                <div class="brand-icon">✓</div>
                Task Manager
            </a>

            <div class="user-profile">
                <div class="user-info">
                    <span style="font-weight: 600; font-size: 0.95rem;">
                        👤 <?php echo htmlspecialchars($current_user['username']); ?>
                    </span>
                    <span class="role-badge <?php echo $is_admin ? 'role-admin' : 'role-user'; ?>">
                        <?php echo htmlspecialchars(strtoupper($current_user['role'])); ?>
                    </span>
                </div>
                <a href="logout.php" class="btn btn-secondary btn-sm">Sign Out</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">

        <!-- Flash Notices -->
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success" style="margin-top: 1rem;">
                <span>✓</span> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-top: 1rem;">
                <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h2>
                    <?php echo $is_admin ? '🛡️ Admin Task Management Center' : '📋 My Tasks Dashboard'; ?>
                </h2>
                <p>
                    <?php echo $is_admin 
                        ? 'Manage, edit, create, and assign tasks across all registered users.' 
                        : 'Organize, create, update, and track your assigned tasks.'; ?>
                </p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <span>+</span> Add New Task
            </button>
        </div>

        <!-- Stats Overview Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span>Total Tasks</span>
                    <h3><?php echo $stats_total; ?></h3>
                </div>
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #818cf8;">📊</div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span>Pending</span>
                    <h3><?php echo $stats_pending; ?></h3>
                </div>
                <div class="stat-icon" style="background: var(--status-pending-bg); color: var(--status-pending-text);">⏳</div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span>In Progress</span>
                    <h3><?php echo $stats_progress; ?></h3>
                </div>
                <div class="stat-icon" style="background: var(--status-progress-bg); color: var(--status-progress-text);">⚡</div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span>Completed</span>
                    <h3><?php echo $stats_completed; ?></h3>
                </div>
                <div class="stat-icon" style="background: var(--status-completed-bg); color: var(--status-completed-text);">✓</div>
            </div>
        </div>

        <!-- Filters & Search Controls -->
        <form action="dashboard.php" method="GET" class="controls-bar">
            <div class="filter-group">
                <!-- Status Filter -->
                <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>

                <!-- Priority Filter -->
                <select name="priority" class="form-control" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="high" <?php echo $filter_priority === 'high' ? 'selected' : ''; ?>>High Priority</option>
                    <option value="medium" <?php echo $filter_priority === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                    <option value="low" <?php echo $filter_priority === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                </select>

                <!-- Admin Only: User Filter -->
                <?php if ($is_admin): ?>
                    <select name="user_id" class="form-control" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        <?php foreach ($all_users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $filter_user == $u['id'] ? 'selected' : ''; ?>>
                                User: <?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['role']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <!-- Search Input -->
                <input type="text" name="q" class="form-control search-input" placeholder="Search tasks by title..." value="<?php echo htmlspecialchars($search_query); ?>">
                
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>

                <?php if (!empty($filter_status) || !empty($filter_priority) || !empty($filter_user) || !empty($search_query)): ?>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm" style="background: transparent; border: 1px solid var(--border-color);">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tasks Table -->
        <div class="tasks-container">
            <?php if (count($tasks) > 0): ?>
                <div class="table-responsive">
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th>Task Title & Description</th>
                                <?php if ($is_admin): ?>
                                    <th>Owner / User</th>
                                <?php endif; ?>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <span class="task-title-cell"><?php echo htmlspecialchars($task['title']); ?></span>
                                        <?php if (!empty($task['description'])): ?>
                                            <span class="task-desc-cell" title="<?php echo htmlspecialchars($task['description']); ?>">
                                                <?php echo htmlspecialchars($task['description']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <?php if ($is_admin): ?>
                                        <td>
                                            <span class="user-pill">
                                                👤 <?php echo htmlspecialchars($task['owner_name']); ?>
                                                <small style="opacity: 0.7; font-size: 0.7rem;">(<?php echo $task['owner_role']; ?>)</small>
                                            </span>
                                        </td>
                                    <?php endif; ?>

                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($task['priority']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($task['priority'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($task['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $task['status']))); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?php echo $task['due_date'] ? htmlspecialchars($task['due_date']) : '—'; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions-cell">
                                            <!-- Quick Status Switcher Form -->
                                            <form action="task_action.php" method="POST" style="display: inline-block;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <select name="status" class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </form>

                                            <!-- Edit Button -->
                                            <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?php echo json_encode($task); ?>)'>
                                                ✏️ Edit
                                            </button>

                                            <!-- Delete Button -->
                                            <form action="task_action.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No tasks found</h3>
                    <p>Get started by creating a new task or resetting your search filters.</p>
                    <button class="btn btn-primary" style="margin-top: 1rem;" onclick="openCreateModal()">+ Add New Task</button>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- CREATE TASK MODAL -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Task</h3>
                <button class="close-btn" onclick="closeCreateModal()">&times;</button>
            </div>
            <form action="task_action.php" method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="create_title">Task Title *</label>
                    <input type="text" id="create_title" name="title" class="form-control" placeholder="e.g. Design homepage mockup" required>
                </div>

                <div class="form-group">
                    <label for="create_desc">Description</label>
                    <textarea id="create_desc" name="description" class="form-control" placeholder="Add additional details..."></textarea>
                </div>

                <?php if ($is_admin): ?>
                    <div class="form-group">
                        <label for="create_assigned_user">Assign Task To User (Admin Only)</label>
                        <select id="create_assigned_user" name="assigned_user_id" class="form-control">
                            <?php foreach ($all_users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $u['id'] == $user_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['email']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="create_priority">Priority</label>
                        <select id="create_priority" name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="create_status">Status</label>
                        <select id="create_status" name="status" class="form-control">
                            <option value="pending" selected>Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="create_due_date">Due Date</label>
                    <input type="date" id="create_due_date" name="due_date" class="form-control">
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-full">Create Task</button>
                    <button type="button" class="btn btn-secondary btn-full" onclick="closeCreateModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT TASK MODAL -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Task</h3>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form action="task_action.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="edit_task_id" name="task_id" value="">

                <div class="form-group">
                    <label for="edit_title">Task Title *</label>
                    <input type="text" id="edit_title" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_desc">Description</label>
                    <textarea id="edit_desc" name="description" class="form-control"></textarea>
                </div>

                <?php if ($is_admin): ?>
                    <div class="form-group">
                        <label for="edit_assigned_user">Assigned User / Owner (Admin Only)</label>
                        <select id="edit_assigned_user" name="assigned_user_id" class="form-control">
                            <?php foreach ($all_users as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['email']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_priority">Priority</label>
                        <select id="edit_priority" name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select id="edit_status" name="status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_due_date">Due Date</label>
                    <input type="date" id="edit_due_date" name="due_date" class="form-control">
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
                    <button type="button" class="btn btn-secondary btn-full" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal JavaScript Logic -->
    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        function openEditModal(task) {
            document.getElementById('edit_task_id').value = task.id;
            document.getElementById('edit_title').value = task.title;
            document.getElementById('edit_desc').value = task.description || '';
            document.getElementById('edit_priority').value = task.priority;
            document.getElementById('edit_status').value = task.status;
            document.getElementById('edit_due_date').value = task.due_date || '';

            const userSelect = document.getElementById('edit_assigned_user');
            if (userSelect) {
                userSelect.value = task.user_id;
            }

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside content area
        window.onclick = function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            if (event.target === createModal) closeCreateModal();
            if (event.target === editModal) closeEditModal();
        }
    </script>
</body>
</html>
