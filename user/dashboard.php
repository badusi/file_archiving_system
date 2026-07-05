<?php
session_start();
require_once '../php/db.php';

// Check if user is logged in as student (not admin)
if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
    header("Location: ../login.html");
    exit();
}

// Get user info
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name, d.code as department_code 
                      FROM users u 
                      JOIN departments d ON u.department_id = d.id 
                      WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get levels and semesters for filters
$levels = $pdo->query("SELECT * FROM levels")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();

// Initialize variables
$search_results = [];
$course_titles = [];
$selected_course = null;
$course_files = [];
$show_course_dropdown = false;

// Handle search/filter
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['search'])) {
    $department_id = $_POST['department'] ?? $_GET['department'] ?? '';
    $level_id = $_POST['level'] ?? $_GET['level'] ?? '';
    $semester_id = $_POST['semester'] ?? $_GET['semester'] ?? '';
    $year = $_POST['year'] ?? $_GET['year'] ?? '';
    
    // Build query based on filters
    $query = "SELECT pq.*, d.name as department_name, l.name as level_name, 
                     s.name as semester_name, a.full_name as uploaded_by_name 
              FROM past_questions pq
              JOIN departments d ON pq.department_id = d.id
              JOIN levels l ON pq.level_id = l.id
              JOIN semesters s ON pq.semester_id = s.id
              JOIN admins a ON pq.uploaded_by = a.id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($department_id)) {
        $query .= " AND pq.department_id = ?";
        $params[] = $department_id;
    }
    
    if (!empty($level_id)) {
        $query .= " AND pq.level_id = ?";
        $params[] = $level_id;
    }
    
    if (!empty($semester_id)) {
        $query .= " AND pq.semester_id = ?";
        $params[] = $semester_id;
    }
    
    if (!empty($year)) {
        $query .= " AND pq.year = ?";
        $params[] = $year;
    }
    
    $query .= " ORDER BY pq.year DESC, pq.uploaded_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If we have search results and a department is selected, get course titles for dropdown
    if (!empty($search_results) && !empty($department_id)) {
        $show_course_dropdown = true;
        $course_stmt = $pdo->prepare("SELECT DISTINCT title, id FROM past_questions WHERE department_id = ? ORDER BY title");
        $course_stmt->execute([$department_id]);
        $course_titles = $course_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle course selection (after search)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    
    // Get selected course details
    $stmt = $pdo->prepare("SELECT pq.*, d.name as department_name, l.name as level_name, 
                           s.name as semester_name, a.full_name as uploaded_by_name 
                           FROM past_questions pq
                           JOIN departments d ON pq.department_id = d.id
                           JOIN levels l ON pq.level_id = l.id
                           JOIN semesters s ON pq.semester_id = s.id
                           JOIN admins a ON pq.uploaded_by = a.id
                           WHERE pq.id = ?");
    $stmt->execute([$course_id]);
    $selected_course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_course) {
        // Get all files for this course
        $file_stmt = $pdo->prepare("SELECT * FROM past_question_files WHERE past_question_id = ?");
        $file_stmt->execute([$course_id]);
        $course_files = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no files in the new table, use the single file from main table
        if (empty($course_files)) {
            $course_files = [[
                'file_path' => $selected_course['file_path'],
                'file_name' => basename($selected_course['file_path']),
                'file_type' => pathinfo($selected_course['file_path'], PATHINFO_EXTENSION),
                'file_size' => filesize($selected_course['file_path'])
            ]];
        }
        
        // Also get course titles for dropdown (to keep it visible)
        $show_course_dropdown = true;
        $course_stmt = $pdo->prepare("SELECT DISTINCT title, id FROM past_questions WHERE department_id = ? ORDER BY title");
        $course_stmt->execute([$selected_course['department_id']]);
        $course_titles = $course_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Past Questions Archive</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <style>
        .info-value{
            color: #000;
        }
        
        /* Course Dropdown Styles */
        .course-dropdown-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: none;
        }
        
        .course-dropdown-section.show {
            display: block;
        }
        
        .course-dropdown {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        /* File badges in course card */
        .file-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        
        .file-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #3498db;
            color: white;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .files-count {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .close:hover {
            color: #333;
        }
        
        /* Preview Modal Styles */
        .preview-image {
            max-width: 100%;
            max-height: 400px;
            display: block;
            margin: 0 auto;
            border-radius: 4px;
        }
        
        .preview-placeholder {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }
        
        /* Download Modal Styles */
        .download-list {
            max-height: 300px;
            overflow-y: auto;
            margin: 1rem 0;
        }
        
        .download-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #ecf0f1;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .file-details {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .download-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .download-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="user-info">
                <h2>Your Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Matric Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['matric_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Department</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['department_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Program Type</span>
                        <span class="info-value">
                            <?php 
                            $program_type = substr($user['matric_number'], 6, 2);
                            echo $program_type == '01' ? 'Full Time' : ($program_type == '02' ? 'Part Time' : 'Unknown');
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="search-filters">
                <h2>Find Past Questions</h2>
                <form id="searchForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                        <?php echo (isset($_POST['department']) && $_POST['department'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="level">Level</label>
                            <select id="level" name="level">
                                <option value="">All Levels</option>
                                <?php foreach ($levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>"
                                        <?php echo (isset($_POST['level']) && $_POST['level'] == $level['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select id="semester" name="semester">
                                <option value="">All Semesters</option>
                                <?php foreach ($semesters as $semester): ?>
                                    <option value="<?php echo $semester['id']; ?>"
                                        <?php echo (isset($_POST['semester']) && $_POST['semester'] == $semester['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year">Year</label>
                            <input type="number" id="year" name="year" 
                                   min="2000" max="<?php echo date('Y'); ?>" 
                                   placeholder="e.g., 2023"
                                   value="<?php echo $_POST['year'] ?? ''; ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Search Past Questions</button>
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset Filters</button>
                </form>
            </div>
            
            <!-- Course Dropdown Section (shown after search) -->
            <?php if ($show_course_dropdown && !empty($course_titles)): ?>
            <div class="course-dropdown-section show">
                <h2>Select Course</h2>
                <form id="courseForm" method="POST">
                    <!-- Keep search filters as hidden fields -->
                    <input type="hidden" name="department" value="<?php echo $_POST['department'] ?? ''; ?>">
                    <input type="hidden" name="level" value="<?php echo $_POST['level'] ?? ''; ?>">
                    <input type="hidden" name="semester" value="<?php echo $_POST['semester'] ?? ''; ?>">
                    <input type="hidden" name="year" value="<?php echo $_POST['year'] ?? ''; ?>">
                    
                    <select name="course_id" id="courseSelect" class="course-dropdown" required onchange="this.form.submit()">
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($course_titles as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="results" id="results">
                <h2>
                    Past Questions 
                    <?php if ($selected_course): ?>
                        <span class="results-count">(Selected Course)</span>
                    <?php elseif (!empty($search_results)): ?>
                        <span class="results-count">(<?php echo count($search_results); ?> courses found - Select from dropdown)</span>
                    <?php endif; ?>
                </h2>
                
                <?php if ($selected_course): ?>
                    <!-- Display selected course as single card -->
                    <div class="past-questions-grid">
                        <div class="question-card">
                            <div class="question-header">
                                <h3 class="question-title" style="color: #fff;"><?php echo htmlspecialchars($selected_course['title']); ?></h3>
                                <span class="question-badge"><?php echo count($course_files); ?> Files</span>
                            </div>
                            
                            <div class="question-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Department</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($selected_course['department_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Level</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($selected_course['level_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Semester</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($selected_course['semester_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Year</span>
                                    <span class="meta-value"><?php echo htmlspecialchars($selected_course['year']); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($selected_course['description'])): ?>
                                <div class="question-description">
                                    <?php echo htmlspecialchars($selected_course['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- File badges showing file types -->
                            <?php if (!empty($course_files)): ?>
                                <div class="file-badges">
                                    <?php 
                                    $file_types = [];
                                    foreach ($course_files as $file) {
                                        $file_extension = strtoupper($file['file_type']);
                                        if (!in_array($file_extension, $file_types)) {
                                            $file_types[] = $file_extension;
                                            echo '<span class="file-badge">' . $file_extension . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div class="files-count">
                                    Total <?php echo count($course_files); ?> file(s) available
                                </div>
                            <?php endif; ?>
                            
                            <div class="question-actions">
                                <!-- Preview button - shows images in modal -->
                                <button class="btn btn-primary btn-sm" 
                                        onclick="showPreviewModal()">
                                    Preview Images
                                </button>
                                <!-- Download button - shows file list in modal -->
                                <button class="btn btn-secondary btn-sm" 
                                        onclick="showDownloadModal()">
                                    Download Files
                                </button>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($search_results)): ?>
                    <!-- Show message to select from dropdown instead of showing all courses -->
                    <div class="empty-state">
                        <p>Search completed! Please select a course from the dropdown above to view its files.</p>
                        <p>Found <?php echo count($search_results); ?> course(s) matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <!-- Default empty state -->
                    <div class="empty-state">
                        <p>Use the search filters above to find past questions.</p>
                        <p>You can filter by department, level, semester, and year.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Preview Images Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePreviewModal()">&times;</span>
            <h2>Image Preview - <?php echo $selected_course ? htmlspecialchars($selected_course['title']) : ''; ?></h2>
            <div id="previewContent">
                <!-- Image previews will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Download Files Modal -->
    <div id="downloadModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDownloadModal()">&times;</span>
            <h2>Download Files - <?php echo $selected_course ? htmlspecialchars($selected_course['title']) : ''; ?></h2>
            <div class="download-list" id="downloadList">
                <!-- File download list will be loaded here -->
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 School Past Questions Archive. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Store course files data for JavaScript
        const courseFiles = <?php echo json_encode($course_files); ?>;
        
        function showPreviewModal() {
            const modal = document.getElementById('previewModal');
            const previewContent = document.getElementById('previewContent');
            
            // Filter only image files
            const imageFiles = courseFiles.filter(file => 
                ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(file.file_type.toLowerCase())
            );
            
            if (imageFiles.length > 0) {
                let html = '';
                imageFiles.forEach(file => {
                    html += `
                        <div style="margin-bottom: 2rem; text-align: center;">
                            <h4>${file.file_name}</h4>
                            <img src="${file.file_path}" alt="${file.file_name}" class="preview-image">
                            <div style="margin-top: 1rem;">
                                <button class="download-btn" onclick="downloadFile('${file.file_path}', '${file.file_name}')">
                                    Download ${file.file_type.toUpperCase()}
                                </button>
                            </div>
                        </div>
                        <hr>
                    `;
                });
                previewContent.innerHTML = html;
            } else {
                previewContent.innerHTML = `
                    <div class="preview-placeholder">
                        <p>No image files available for preview.</p>
                        <p>This course contains only document files.</p>
                    </div>
                `;
            }
            
            modal.style.display = 'block';
        }
        
        function showDownloadModal() {
            const modal = document.getElementById('downloadModal');
            const downloadList = document.getElementById('downloadList');
            
            let html = '';
            courseFiles.forEach(file => {
                const fileSize = formatFileSize(file.file_size);
                html += `
                    <div class="download-item">
                        <div class="file-info">
                            <div class="file-name">${file.file_name}</div>
                            <div class="file-details">
                                ${file.file_type.toUpperCase()} • ${fileSize}
                            </div>
                        </div>
                        <button class="download-btn" onclick="downloadFile('${file.file_path}', '${file.file_name}')">
                            Download
                        </button>
                    </div>
                `;
            });
            
            downloadList.innerHTML = html;
            modal.style.display = 'block';
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }
        
        function closeDownloadModal() {
            document.getElementById('downloadModal').style.display = 'none';
        }
        
        function downloadFile(filePath, fileName) {
            // Create a temporary link to trigger download
            const link = document.createElement('a');
            link.href = filePath;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function resetFilters() {
            document.getElementById('department').value = '';
            document.getElementById('level').value = '';
            document.getElementById('semester').value = '';
            document.getElementById('year').value = '';
            document.getElementById('searchForm').submit();
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const previewModal = document.getElementById('previewModal');
            const downloadModal = document.getElementById('downloadModal');
            
            if (e.target === previewModal) {
                closePreviewModal();
            }
            if (e.target === downloadModal) {
                closeDownloadModal();
            }
        });
    </script>
</body>
</html>