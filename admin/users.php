<?php
session_start();
require_once '../php/db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin_login.html");
    exit();
}


// Get all users
$stmt = $pdo->query("SELECT u.*, d.name as department_name FROM users u 
                    JOIN departments d ON u.department_id = d.id 
                    ORDER BY u.created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard - Manage Users</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php" class="active">Manage Users</a></li>
                    <li><a href="past_questions.php">Past Questions</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="admin-table">
                <div class="table-header">
                    <h2>All Users (<?php echo count($users); ?>)</h2>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="exportUsers()">Export CSV</button>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Matric Number</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Sex</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['matric_number']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['sex']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['role'] === 'admin' ? 'status-active' : 'status-pending'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">View</button>
                                    <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">Edit</button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
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
            <p>&copy; 2025 School Past Questions Archive. All rights reserved.</p>
        </div>
    </footer>

    <script>
    function viewUser(userId) {
        alert('View user: ' + userId);
        // In real implementation, show user details in modal
    }
    
    function editUser(userId) {
        alert('Edit user: ' + userId);
        // In real implementation, open edit form
    }
    
    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
            alert('Delete user: ' + userId);
            // In real implementation, send AJAX request to delete user
        }
    }
    
    function exportUsers() {
        alert('Exporting users to CSV...');
        // In real implementation, generate and download CSV
    }
    </script>
</body>
</html>