<?php
session_start();
require_once '../php/db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin_login.html");
    exit();
}

// Only super admin can manage other admins
if ($_SESSION['admin_role'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

// Get all admins
$stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        $stmt = $pdo->prepare("INSERT INTO admins (email, full_name, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$email, $full_name, $password, $role])) {
            $_SESSION['success'] = "Admin added successfully";
            header("Location: admins.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to add admin";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard - Manage Admins</h1>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?> (<?php echo $_SESSION['admin_role']; ?>)</span>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="past_questions.php">Past Questions</a></li>
                    <li><a href="admins.php" class="active">Admins</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Add Admin Form -->
            <div class="admin-form">
                <h2>Add New Admin</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                </form>
            </div>

            <!-- Admins Table -->
            <div class="admin-table">
                <div class="table-header">
                    <h2>System Administrators (<?php echo count($admins); ?>)</h2>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $admin['role'] === 'super_admin' ? 'status-active' : 'status-pending'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $admin['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <button class="btn-action btn-edit" onclick="editAdmin(<?php echo $admin['id']; ?>)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="deleteAdmin(<?php echo $admin['id']; ?>)">Delete</button>
                                    <?php else: ?>
                                    <span class="current-user">Current</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2023 School Past Questions Archive. Admin Panel</p>
        </div>
    </footer>

    <script>
    function editAdmin(adminId) {
        if (confirm('Edit admin #' + adminId + '?')) {
            // In real implementation, show edit form
            alert('Edit functionality would open here for admin: ' + adminId);
        }
    }
    
    function deleteAdmin(adminId) {
        if (confirm('Are you sure you want to delete this admin? This action cannot be undone.')) {
            // In real implementation, send AJAX request
            alert('Delete functionality would execute here for admin: ' + adminId);
        }
    }
    </script>
</body>
</html>