<?php
// db.php - Database connection & automatic setup using SQLite PDO

// Determine SQLite database path
// In serverless environments (Vercel/Lambda), the filesystem is read-only except /tmp
$is_serverless = getenv('VERCEL') || !is_writable(__DIR__) || (file_exists(__DIR__ . '/task_manager.sqlite') && !is_writable(__DIR__ . '/task_manager.sqlite'));

if ($is_serverless) {
    $db_file = sys_get_temp_dir() . '/task_manager.sqlite';
    $seed_file = __DIR__ . '/task_manager.sqlite';
    if (!file_exists($db_file) && file_exists($seed_file)) {
        copy($seed_file, $db_file);
    }
} else {
    $db_file = __DIR__ . '/task_manager.sqlite';
}

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Enable foreign keys in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create tasks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        priority TEXT DEFAULT 'medium',
        status TEXT DEFAULT 'pending',
        due_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Pre-seed initial database if no users exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $userPassword = password_hash('user123', PASSWORD_BCRYPT);

        // Insert Admin and standard Users
        $stmtUser = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmtUser->execute(['admin', 'admin@taskmanager.com', $adminPassword, 'admin']);
        $stmtUser->execute(['john_doe', 'john@example.com', $userPassword, 'user']);
        $stmtUser->execute(['sarah_connor', 'sarah@example.com', $userPassword, 'user']);

        // Insert default sample tasks
        $stmtTask = $pdo->prepare("INSERT INTO tasks (user_id, title, description, priority, status, due_date) VALUES (?, ?, ?, ?, ?, ?)");
        
        // Tasks for john_doe (ID: 2)
        $stmtTask->execute([2, 'Review UI Mockups', 'Evaluate final desktop & mobile dashboard designs', 'high', 'in_progress', '2026-10-10']);
        $stmtTask->execute([2, 'Database Backup Script', 'Automate daily backups for task database', 'medium', 'pending', '2026-10-15']);
        
        // Tasks for sarah_connor (ID: 3)
        $stmtTask->execute([3, 'Quarterly Financial Report', 'Compile user stats and server usage logs', 'high', 'completed', '2026-10-08']);
        $stmtTask->execute([3, 'Update Security Protocols', 'Audit user role permissions and session timeouts', 'low', 'pending', '2026-10-20']);

        // Task created for Admin (ID: 1)
        $stmtTask->execute([1, 'System Maintenance', 'Perform PHP extension updates and server reboots', 'high', 'pending', '2026-10-12']);
    }

} catch (PDOException $e) {
    die("Database initialization error: " . htmlspecialchars($e->getMessage()));
}
?>
