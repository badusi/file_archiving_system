<?php
session_start();
require_once '../php/db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin_login.html");
    exit();
}

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM past_questions")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$recent_questions = $pdo->query("SELECT COUNT(*) FROM past_questions WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Past Questions Archive</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard</h1>
            <div class="admin-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?> (<?php echo $_SESSION['admin_role']; ?>)</span>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="past_questions.php">Past Questions</a></li>
                    <li><a href="admins.php">Admins</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Total Students</h3>
                        <div class="number"><?php echo $total_users; ?></div>
                        <div class="trend">All Time</div>
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
                        <div class="trend">Available</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🆕</div>
                    <div class="stat-info">
                        <h3>Recent Uploads</h3>
                        <div class="number"><?php echo $recent_questions; ?></div>
                        <div class="trend">Last 7 Days</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="actions-grid">
                    <a href="past_questions.php?action=upload" class="action-card">
                        <div class="action-icon">📤</div>
                        <h3>Upload Questions</h3>
                        <p>Add new past questions to the system</p>
                    </a>
                    
                    <a href="users.php" class="action-card">
                        <div class="action-icon">👥</div>
                        <h3>Manage Students</h3>
                        <p>View and manage student accounts</p>
                    </a>
                    
                    <a href="admins.php" class="action-card">
                        <div class="action-icon">🔧</div>
                        <h3>Admin Management</h3>
                        <p>Manage administrator accounts</p>
                    </a>
                    
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>View Reports</h3>
                        <p>System usage and analytics</p>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <div class="activity-header">
                    <h2>Recent Activity</h2>
                    <a href="activity.php" class="view-all">View All</a>
                </div>
                <div class="activity-list">
                    <?php
                    $recent_activity = $pdo->query("
                        SELECT 'question' as type, title as description, uploaded_at as date 
                        FROM past_questions 
                        ORDER BY uploaded_at DESC 
                        LIMIT 5
                    ")->fetchAll();
                    
                    if (empty($recent_activity)): ?>
                        <div class="empty-activity">
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php echo $activity['type'] === 'question' ? '📚' : '👥'; ?>
                            </div>
                            <div class="activity-content">
                                <p class="activity-text">New past question uploaded: <?php echo htmlspecialchars($activity['description']); ?></p>
                                <span class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 School Past Questions Archive. Admin Panel</p>
        </div>
    </footer>
</body>
</html>