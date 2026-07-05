<?php
session_start();
require_once '../php/db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin_login.html");
    exit();
}

// Get statistics for reports
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM past_questions")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM admins WHERE is_active = TRUE")->fetchColumn();

// Get questions by department
$questions_by_dept = $pdo->query("
    SELECT d.name, COUNT(pq.id) as question_count 
    FROM departments d 
    LEFT JOIN past_questions pq ON d.id = pq.department_id 
    GROUP BY d.id, d.name 
    ORDER BY question_count DESC
")->fetchAll();

// Get questions by level
$questions_by_level = $pdo->query("
    SELECT l.name, COUNT(pq.id) as question_count 
    FROM levels l 
    LEFT JOIN past_questions pq ON l.id = pq.level_id 
    GROUP BY l.id, l.name 
    ORDER BY question_count DESC
")->fetchAll();

// Get recent uploads (last 30 days)
$recent_uploads = $pdo->query("
    SELECT pq.title, d.name as department, l.name as level, pq.uploaded_at 
    FROM past_questions pq 
    JOIN departments d ON pq.department_id = d.id 
    JOIN levels l ON pq.level_id = l.id 
    WHERE pq.uploaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    ORDER BY pq.uploaded_at DESC 
    LIMIT 10
")->fetchAll();

// Get user registration trends (last 6 months)
$user_registrations = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as user_count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard - Reports & Analytics</h1>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?> (<?php echo $_SESSION['admin_role']; ?>)</span>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="past_questions.php">Past Questions</a></li>
                    <li><a href="admins.php">Admins</a></li>
                    <li><a href="reports.php" class="active">Reports</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Summary Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Students</h3>
                        <div class="number"><?php echo $total_users; ?></div>
                        <div class="trend">Registered Users</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-info">
                        <h3>Past Questions</h3>
                        <div class="number"><?php echo $total_questions; ?></div>
                        <div class="trend">Total Uploaded</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🏫</div>
                    <div class="stat-info">
                        <h3>Departments</h3>
                        <div class="number"><?php echo $total_departments; ?></div>
                        <div class="trend">Active Departments</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-info">
                        <h3>Administrators</h3>
                        <div class="number"><?php echo $total_admins; ?></div>
                        <div class="trend">System Admins</div>
                    </div>
                </div>
            </div>

            <!-- Reports Sections -->
            <div class="reports-grid">
                <!-- Questions by Department -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Questions by Department</h3>
                    </div>
                    <div class="report-content">
                        <?php if (!empty($questions_by_dept)): ?>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Question Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questions_by_dept as $dept): 
                                        $percentage = $total_questions > 0 ? round(($dept['question_count'] / $total_questions) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                        <td><?php echo $dept['question_count']; ?></td>
                                        <td>
                                            <div class="percentage-bar">
                                                <div class="bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                                <span class="percentage-text"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Questions by Level -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Questions by Level</h3>
                    </div>
                    <div class="report-content">
                        <?php if (!empty($questions_by_level)): ?>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>Question Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questions_by_level as $level): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($level['name']); ?></td>
                                        <td><?php echo $level['question_count']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Uploads -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>Recent Uploads (Last 30 Days)</h3>
                    </div>
                    <div class="report-content">
                        <?php if (!empty($recent_uploads)): ?>
                            <div class="recent-list">
                                <?php foreach ($recent_uploads as $upload): ?>
                                <div class="recent-item">
                                    <div class="recent-title"><?php echo htmlspecialchars($upload['title']); ?></div>
                                    <div class="recent-meta">
                                        <span><?php echo htmlspecialchars($upload['department']); ?></span> • 
                                        <span><?php echo htmlspecialchars($upload['level']); ?></span> • 
                                        <span><?php echo date('M j, Y', strtotime($upload['uploaded_at'])); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recent uploads</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Registration Trends -->
                <div class="report-card">
                    <div class="report-header">
                        <h3>User Registration Trends (Last 6 Months)</h3>
                    </div>
                    <div class="report-content">
                        <?php if (!empty($user_registrations)): ?>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>New Users</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_registrations as $registration): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($registration['month'] . '-01')); ?></td>
                                        <td><?php echo $registration['user_count']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">No registration data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="export-options">
                <h3>Export Reports</h3>
                <div class="export-buttons">
                    <button class="btn btn-primary" onclick="exportReport('users')">Export Users List</button>
                    <button class="btn btn-primary" onclick="exportReport('questions')">Export Questions List</button>
                    <button class="btn btn-primary" onclick="exportReport('statistics')">Export Statistics</button>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 School Past Questions Archive. Admin Panel</p>
        </div>
    </footer>

    <script>
        function exportReport(type) {
            alert(`Exporting ${type} report...`);
            // In real implementation, this would generate and download CSV/PDF reports
            // window.location.href = `../php/export_${type}.php`;
        }
    </script>
</body>
</html>