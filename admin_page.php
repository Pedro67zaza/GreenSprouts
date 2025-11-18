<?php
session_start();

if (!isset($_SESSION['userId']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: login.php");
    exit();
}

$currentUserRole = $_SESSION['role'];
$isSuperAdmin = ($currentUserRole === 'superadmin');

// Database Configuration
$host = 'localhost';
$dbname = 'greensproutsdb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            // Only superadmin can create new users
            if (!$isSuperAdmin) {
                $message = "Access denied! Only superadmins can add new users.";
                $messageType = "error";
            } else {
                $username = $_POST['username'];
                $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $email = $_POST['email'];
                $role = $_POST['role'];
                
                // Validate role
                if (!in_array($role, ['user', 'admin', 'superadmin'])) {
                    $message = "Invalid role selected!";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$username, $pass, $email, $role])) {
                        $message = ucfirst($role) . " created successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error creating user!";
                        $messageType = "error";
                    }
                }
            }
        } elseif ($_POST['action'] === 'update') {
            $userId = $_POST['userId'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            
            // Check if user being edited is a superadmin
            $checkStmt = $pdo->prepare("SELECT role FROM users WHERE userId = ?");
            $checkStmt->execute([$userId]);
            $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Only superadmin can edit superadmin accounts or change roles to superadmin
            if (($targetUser['role'] === 'superadmin' || $role === 'superadmin') && !$isSuperAdmin) {
                $message = "Access denied! Only superadmins can manage superadmin accounts.";
                $messageType = "error";
            } else {
                // Validate role
                if (!in_array($role, ['user', 'admin', 'superadmin'])) {
                    $message = "Invalid role selected!";
                    $messageType = "error";
                } else {
                    if (!empty($_POST['password'])) {
                        $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, email = ?, role = ? WHERE userId = ?");
                        $stmt->execute([$username, $pass, $email, $role, $userId]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE userId = ?");
                        $stmt->execute([$username, $email, $role, $userId]);
                    }
                    $message = "User updated successfully!";
                    $messageType = "success";
                }
            }
        }
    }
}

// Delete User
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Check if user being deleted is a superadmin
    $checkStmt = $pdo->prepare("SELECT role FROM users WHERE userId = ?");
    $checkStmt->execute([$userId]);
    $targetUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Only superadmin can delete superadmin accounts
    if ($targetUser['role'] === 'superadmin' && !$isSuperAdmin) {
        $message = "Access denied! Only superadmins can delete superadmin accounts.";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE userId = ?");
        if ($stmt->execute([$userId])) {
            $message = "User deleted successfully!";
            $messageType = "success";
        }
    }
    header("Location: admin_page.php");
    exit;
}

// Get filters and search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Build query based on filters
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter !== 'all') {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
}

$query .= " ORDER BY FIELD(role, 'superadmin', 'admin', 'user'), userId";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE userId = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar.css">
    <title>Admin Panel - User Management</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
         }
        
         .main-content {
             flex: 1;
             margin-left: 250px;
             padding: 20px;
             padding-bottom: 50px;
             min-height: 100vh;
             background-color: #f4f9f4;
             transition:  var(--tran-05);
             overflow-y: auto;
         }
        
         .sidebar.close ~ .main-content {
             margin-left: 88px;
         }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #f4f9f4;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: white;
            color: #2e7d32;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .role-indicator {
            position: absolute;
            top: 20px;
            right: 30px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .role-indicator.superadmin {
            background: #6f42c1;
            color: white;
        }

        .role-indicator.admin {
            background: #dc3545;
            color: white;
        }

        .alert {
            padding: 15px;
            margin: 20px 30px;
            border-radius: 8px;
            font-weight: 600;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .controls {
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #ddd;
        }

        .control-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-form {
            flex: 1;
            min-width: 300px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-search {
            background: #2e7d32;
            color: white;
        }

        .btn-search:hover {
            background: #4c8a4fff;
        }

        .btn-filter {
            background: white;
            color: #2e7d32;
            border: 2px solid #2e7d32;
        }

        .btn-filter:hover, .btn-filter.active {
            background: #2e7d32;
            color: white;
        }

        .btn-add {
            background: #28a745;
            color: white;
        }

        .btn-add:hover:not(:disabled) {
            background: #218838;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background: #2e7d32;
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-superadmin {
            background: #6f42c1;
            color: white;
        }

        .role-admin {
            background: #dc3545;
            color: white;
        }

        .role-user {
            background: #17a2b8;
            color: white;
        }

        .form-section {
            padding: 30px;
            background: #f8f9fa;
            border-top: 2px solid #ddd;
        }

        .form-section h2 {
            margin-bottom: 20px;
            color: #2e7d32;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2e7d32;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-submit {
            background: #2e7d32;
            color: white;
        }

        .btn-submit:hover {
            background: #1b5e20;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 18px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .permission-note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .permission-note i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="main-content">

        <div class="container">
        <div class="header">
            <div class="role-indicator <?php echo $currentUserRole; ?>">
                <?php echo strtoupper($currentUserRole); ?>
            </div>
            <h1>User Management Panel</h1>
            <p><?php echo $isSuperAdmin ? 'SuperAdmin Dashboard - Full Access' : 'Admin Dashboard - View Only'; ?></p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$isSuperAdmin): ?>
            <div class="permission-note" style="margin: 20px 30px;">
                <strong>⚠️ Limited Access:</strong> You are logged in as an Admin. Only SuperAdmins can add, edit, or delete users.
            </div>
        <?php endif; ?>

        <div class="controls">
            <form method="GET" class="control-row">
                <div class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-search">Search</button>
                </div>
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter); ?>">
            </form>

            <div class="control-row">
                <div class="filter-buttons">
                    <a href="?role=all&search=<?php echo urlencode($search); ?>" class="btn btn-filter <?php echo $roleFilter === 'all' ? 'active' : ''; ?>">All Users</a>
                    <a href="?role=superadmin&search=<?php echo urlencode($search); ?>" class="btn btn-filter <?php echo $roleFilter === 'superadmin' ? 'active' : ''; ?>">SuperAdmins</a>
                    <a href="?role=admin&search=<?php echo urlencode($search); ?>" class="btn btn-filter <?php echo $roleFilter === 'admin' ? 'active' : ''; ?>">Admins</a>
                    <a href="?role=user&search=<?php echo urlencode($search); ?>" class="btn btn-filter <?php echo $roleFilter === 'user' ? 'active' : ''; ?>">Users</a>
                </div>
                <a href="#form" class="btn btn-add <?php echo !$isSuperAdmin ? 'disabled' : ''; ?>" <?php echo !$isSuperAdmin ? 'onclick="alert(\'Only SuperAdmins can add users\'); return false;"' : ''; ?>>
                    Add New User
                </a>
            </div>
        </div>

        <div class="table-container">
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <?php if ($isSuperAdmin): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['userId']); ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo strtoupper($user['role']); ?></span></td>
                                <?php if ($isSuperAdmin): ?>
                                    <td>
                                        <a href="?edit=<?php echo $user['userId']; ?>&role=<?php echo $roleFilter; ?>&search=<?php echo urlencode($search); ?>#form" class="btn btn-edit">Edit</a>
                                        <a href="?delete=<?php echo $user['userId']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    No users found matching your search criteria
                </div>
            <?php endif; ?>
        </div>

        <?php if ($isSuperAdmin): ?>
        <div class="form-section" id="form">
            <h2><?php echo $editUser ? 'Edit User' : 'Add New User'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="userId" value="<?php echo $editUser['userId']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required value="<?php echo $editUser ? htmlspecialchars($editUser['username']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Password * <?php echo $editUser ? '(Leave blank to keep current)' : ''; ?></label>
                        <input type="password" name="password" <?php echo $editUser ? '' : 'required'; ?>>
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="user" <?php echo ($editUser && $editUser['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($editUser && $editUser['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="superadmin" <?php echo ($editUser && $editUser['role'] === 'superadmin') ? 'selected' : ''; ?>>SuperAdmin</option>
                        </select>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-submit">
                        <?php echo $editUser ? 'Update User' : 'Create User'; ?>
                    </button>
                    <?php if ($editUser): ?>
                        <a href="admin_page.php?role=<?php echo $roleFilter; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-cancel">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    </section>
</body>
</html>