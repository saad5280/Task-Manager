# 📋 Role-Based PHP Task Manager

A modern, secure Task Management Web Application built using **HTML, CSS, and PHP only** (with auto-provisioned SQLite database via PDO).

---

## ✨ Features & Role Permissions

### 🔐 1. Admin Role (`admin`)
* **Global Overview**: Views statistics and tasks for **ALL** users in the system.
* **Full CRUD Control**: Can **Add**, **Edit**, **Update**, and **Delete** tasks belonging to **any** user.
* **Task Assignment**: Can assign new or existing tasks to any user in the system.
* **User Filter**: Easily filter the task dashboard by individual users.

### 👤 2. Standard User Role (`user`)
* **Personal Dashboard**: Views statistics and tasks created by / assigned to **themselves only**.
* **Isolated CRUD Control**: Can **Add**, **Edit**, **Update**, and **Delete** their **own tasks only**.
* **Strict Security Enforcement**: Server-side role checks (`can_manage_task()`) block any attempt to modify or delete tasks owned by other users.

---

## 🔑 Pre-Configured Demo Accounts

| Role | Username | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Can view, edit, reassign, & delete tasks for ALL users |
| **Standard User** | `john_doe` | `user123` | Can only manage their own tasks |
| **Standard User** | `sarah_connor` | `user123` | Can only manage their own tasks |

---

## 🚀 How to Run the Application

### Option A: Built-in PHP Development Server (Recommended)
1. Open your terminal or command prompt in the project root directory.
2. Run the following command:
   ```bash
   php -S localhost:8000
   ```
3. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

### Option B: Local Web Server (XAMPP / WampServer / MAMP)
1. Copy or move this folder into your web server document root (e.g. `C:\xampp\htdocs\task-manager`).
2. Open your browser and navigate to:
   ```
   http://localhost/task-manager
   ```

---

## 📁 File Structure

* `config.php` - Session control, database connection import, and security permission helpers.
* `db.php` - Auto-creates SQLite database (`task_manager.sqlite`) and seeds sample users & tasks on initial run.
* `login.php` - Sign-in authentication with password hashing (`password_verify`).
* `register.php` - Account creation page (defaults to standard `user` role).
* `dashboard.php` - Main dashboard UI with stats widgets, search/filters, tasks table, and modal forms.
* `task_action.php` - Backend processor for Create, Read, Update, Delete, and Status updates.
* `logout.php` - Securely terminates user session.
* `style.css` - Custom design system with glassmorphism UI, badges, modals, and responsive layout.
