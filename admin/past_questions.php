<?php
session_start();
require_once '../php/db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin_login.html");
    exit();
}

// Get all past questions with related data
$stmt = $pdo->query("SELECT pq.*, d.name as department_name, l.name as level_name, 
                     s.name as semester_name, a.full_name as uploaded_by_name,
                     (SELECT COUNT(*) FROM past_question_files WHERE past_question_id = pq.id) as file_count
                     FROM past_questions pq
                     JOIN departments d ON pq.department_id = d.id
                     JOIN levels l ON pq.level_id = l.id
                     JOIN semesters s ON pq.semester_id = s.id
                     JOIN admins a ON pq.uploaded_by = a.id
                     ORDER BY pq.uploaded_at DESC");
$past_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get departments, levels, semesters for form
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$levels = $pdo->query("SELECT * FROM levels")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Past Questions - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Admin Dashboard - Past Questions</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="past_questions.php" class="active">Past Questions</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Upload Form -->
            <div class="admin-form">
                <h2>Upload New Past Question</h2>
                <form id="uploadForm" action="../php/upload_question.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" required 
                                placeholder="e.g., Computer Programming Past Questions 2023">
                        </div>
                        <div class="form-group">
                            <label for="department">Department *</label>
                            <select id="department" name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="level">Level *</label>
                            <select id="level" name="level" required>
                                <option value="">Select Level</option>
                                <?php foreach ($levels as $level): ?>
                                <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester">Semester *</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($semesters as $semester): ?>
                                <option value="<?php echo $semester['id']; ?>"><?php echo htmlspecialchars($semester['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year">Year *</label>
                            <input type="number" id="year" name="year" min="2000" max="<?php echo date('Y'); ?>" 
                                required placeholder="e.g., 2023">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                placeholder="Optional description of the past question"></textarea>
                    </div>
                    
                    <!-- Step-by-Step File Upload Section -->
                    <div class="file-upload">
                        <div id="fileUploadArea">
                            <input type="file" id="file" name="files[]" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" style="display: none;">
                            <button type="button" id="addFileBtn" class="btn btn-secondary">Add First File</button>
                            <div class="file-info" style="margin-top: 1rem;">No files added yet</div>
                            <small>Allowed formats: PDF, DOC, DOCX, TXT, JPG, PNG (Max: 10MB each)</small>
                        </div>
                        
                        <!-- Selected files preview -->
                        <div id="selectedFiles" class="selected-files" style="display: none; margin-top: 1rem;">
                            <h4>Selected Files:</h4>
                            <div id="fileList" class="file-list"></div>
                            <button type="button" id="addAnotherBtn" class="btn btn-secondary" style="margin-top: 1rem;">Add Another File</button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Upload Past Question</button>
                </form>
            </div>

            <!-- Past Questions Table -->
            <div class="admin-table">
                <div class="table-header">
                    <h2>All Past Questions (<?php echo count($past_questions); ?>)</h2>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="exportQuestions()">Export</button>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Level</th>
                                <th>Semester</th>
                                <th>Year</th>
                                <th>Files</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($past_questions as $question): 
                                // Get all files for this question
                                $file_stmt = $pdo->prepare("SELECT * FROM past_question_files WHERE past_question_id = ?");
                                $file_stmt->execute([$question['id']]);
                                $files = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                $has_multiple_files = count($files) > 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($question['title']); ?></td>
                                <td><?php echo htmlspecialchars($question['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($question['level_name']); ?></td>
                                <td><?php echo htmlspecialchars($question['semester_name']); ?></td>
                                <td><?php echo htmlspecialchars($question['year']); ?></td>
                                <td>
                                    <?php if ($has_multiple_files): ?>
                                        <div class="file-preview">
                                            <?php foreach ($files as $file): 
                                                $file_extension = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                                            ?>
                                                <div class="file-preview-item">
                                                    <span class="file-badge"><?php echo strtoupper($file_extension); ?></span>
                                                    <small><?php echo htmlspecialchars($file['file_name']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <small><?php echo count($files); ?> file(s)</small>
                                    <?php else: ?>
                                        <?php 
                                        // Fallback to single file from main table
                                        $file_name = basename($question['file_path']);
                                        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                                        ?>
                                        <span class="file-badge"><?php echo strtoupper($file_extension); ?></span>
                                        <small><?php echo htmlspecialchars($file_name); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($question['uploaded_by_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($question['uploaded_at'])); ?></td>
                                <td>
                                    <?php if ($has_multiple_files): ?>
                                        <a href="#" class="btn-action btn-view" onclick="downloadAllFiles(<?php echo $question['id']; ?>)" 
                                        title="Download All Files">
                                            Download All
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo $question['file_path']; ?>" class="btn-action btn-view" download 
                                        title="Download <?php echo htmlspecialchars($question['title']); ?>">
                                            Download
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn-action btn-edit" onclick="editQuestion(<?php echo $question['id']; ?>)" 
                                            title="Edit <?php echo htmlspecialchars($question['title']); ?>">
                                        Edit
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteQuestion(<?php echo $question['id']; ?>)" 
                                            title="Delete <?php echo htmlspecialchars($question['title']); ?>">
                                        Delete
                                    </button>
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
        // Step-by-step file upload handling
        let fileCounter = 0;
        const selectedFiles = [];

        document.getElementById('addFileBtn').addEventListener('click', function() {
            document.getElementById('file').click();
        });

        document.getElementById('addAnotherBtn').addEventListener('click', function() {
            document.getElementById('file').click();
        });

        document.getElementById('file').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                
                // Validate file type
                const allowedTypes = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png'];
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                
                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File too large: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB (Max: 10MB)');
                    this.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(fileExtension)) {
                    alert('Invalid file type. Allowed types: PDF, DOC, DOCX, TXT, JPG, PNG');
                    this.value = '';
                    return;
                }
                
                // Add file to selected files
                const fileId = fileCounter++;
                selectedFiles.push({
                    id: fileId,
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: fileExtension
                });
                
                updateFileList();
                this.value = ''; // Reset file input
                
                // Enable submit button if we have at least one file
                if (selectedFiles.length > 0) {
                    document.getElementById('submitBtn').disabled = false;
                }
            }
        });

        function updateFileList() {
            const fileList = document.getElementById('fileList');
            const selectedFilesDiv = document.getElementById('selectedFiles');
            const fileInfo = document.querySelector('.file-info');
            
            fileList.innerHTML = '';
            let totalSize = 0;
            
            selectedFiles.forEach((fileData, index) => {
                const fileSize = (fileData.size / 1024 / 1024).toFixed(2);
                totalSize += fileData.size;
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <span class="file-name">${fileData.name}</span>
                    <span class="file-size">(${fileSize} MB)</span>
                    <span class="file-type">${fileData.type.toUpperCase()}</span>
                    <button type="button" class="btn-remove-file" data-id="${fileData.id}">×</button>
                `;
                fileList.appendChild(fileItem);
            });
            
            // Show total size
            const totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `${selectedFiles.length} file(s) selected - Total: ${totalSizeMB} MB`;
            fileInfo.style.color = '#27ae60';
            
            selectedFilesDiv.style.display = 'block';
            
            // Add remove file functionality
            document.querySelectorAll('.btn-remove-file').forEach(btn => {
                btn.addEventListener('click', function() {
                    const fileId = parseInt(this.getAttribute('data-id'));
                    removeFile(fileId);
                });
            });
        }

        function removeFile(fileId) {
            const index = selectedFiles.findIndex(file => file.id === fileId);
            if (index !== -1) {
                selectedFiles.splice(index, 1);
                updateFileList();
                
                // Disable submit button if no files left
                if (selectedFiles.length === 0) {
                    document.getElementById('submitBtn').disabled = true;
                    document.getElementById('selectedFiles').style.display = 'none';
                    document.querySelector('.file-info').textContent = 'No files added yet';
                    document.querySelector('.file-info').style.color = '';
                }
            }
        }

        // Update form submission to include all selected files
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const department = document.getElementById('department').value;
            const level = document.getElementById('level').value;
            const semester = document.getElementById('semester').value;
            const year = document.getElementById('year').value;
            
            if (!title || !department || !level || !semester || !year || selectedFiles.length === 0) {
                e.preventDefault();
                alert('Please fill in all required fields (*) and add at least one file');
                return false;
            }
            
            // Create a new FormData and append all files
            const formData = new FormData(this);
            
            // Remove existing files from form data
            formData.delete('files[]');
            
            // Append all selected files
            selectedFiles.forEach(fileData => {
                formData.append('files[]', fileData.file);
            });
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = 'Uploading... <span class="loading"></span>';
            submitBtn.disabled = true;
            
            // Submit via fetch instead of normal form submission
            e.preventDefault();
            
            fetch('../php/upload_question.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Past question uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = 'Upload Past Question';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error uploading files. Please try again.');
                submitBtn.innerHTML = 'Upload Past Question';
                submitBtn.disabled = false;
            });
        });

        // Download single file
        function downloadFile(filePath, fileName) {
            // Create a temporary link to trigger download
            const link = document.createElement('a');
            link.href = filePath;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Download all files for a question
        function downloadAllFiles(questionId) {
            // Show loading message
            const originalText = event.target.innerHTML;
            event.target.innerHTML = 'Preparing download...';
            event.target.disabled = true;
            
            // Create a zip file on the server and download it
            window.location.href = `../php/download_zip.php?id=${questionId}`;
            
            // Reset button after a delay
            setTimeout(() => {
                event.target.innerHTML = originalText;
                event.target.disabled = false;
            }, 3000);
        }



        // Edit modal file handling
        let editSelectedFiles = [];

        // Function to initialize edit modal event listeners
        function initEditModalEvents() {
            // Remove existing event listeners to prevent duplicates
            const newAddFileBtn = document.getElementById('editAddFileBtn').cloneNode(true);
            document.getElementById('editAddFileBtn').parentNode.replaceChild(newAddFileBtn, document.getElementById('editAddFileBtn'));
            
            const newFileInput = document.getElementById('edit_new_files').cloneNode(true);
            document.getElementById('edit_new_files').parentNode.replaceChild(newFileInput, document.getElementById('edit_new_files'));
            
            // Re-attach event listeners
            document.getElementById('editAddFileBtn').addEventListener('click', function() {
                document.getElementById('edit_new_files').click();
            });

            document.getElementById('edit_new_files').addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    Array.from(this.files).forEach(file => {
                        // Validate file type
                        const allowedTypes = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png'];
                        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                        
                        // Validate file size (10MB max)
                        if (file.size > 10 * 1024 * 1024) {
                            alert('File too large: ' + (file.size / 1024 / 1024).toFixed(2) + ' MB (Max: 10MB)');
                            return;
                        }
                        
                        if (!allowedTypes.includes(fileExtension)) {
                            alert('Invalid file type. Allowed types: PDF, DOC, DOCX, TXT, JPG, PNG');
                            return;
                        }
                        
                        // Check if file already exists in selected files
                        const isDuplicate = editSelectedFiles.some(selectedFile => 
                            selectedFile.name === file.name && selectedFile.size === file.size
                        );
                        
                        if (!isDuplicate) {
                            // Add file to selected files
                            editSelectedFiles.push(file);
                        } else {
                            alert('File "' + file.name + '" is already selected.');
                        }
                    });
                    
                    updateEditFileList();
                    this.value = ''; // Reset file input
                }
            });
        }

        // Edit Question Function
        function editQuestion(questionId) {
            // Reset edit files when opening modal
            editSelectedFiles = [];
            document.getElementById('editSelectedFiles').style.display = 'none';
            document.getElementById('editFileInfo').textContent = 'No new files selected';
            document.getElementById('editFileInfo').style.color = '';
            
            // Initialize event listeners for the edit modal
            initEditModalEvents();
            
            // Fetch question details via AJAX
            fetch(`../php/get_question_admin.php?id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const question = data.question;
                        
                        // Populate form fields
                        document.getElementById('edit_id').value = question.id;
                        document.getElementById('edit_title').value = question.title;
                        document.getElementById('edit_department').value = question.department_id;
                        document.getElementById('edit_level').value = question.level_id;
                        document.getElementById('edit_semester').value = question.semester_id;
                        document.getElementById('edit_year').value = question.year;
                        document.getElementById('edit_description').value = question.description || '';
                        
                        // Load existing files
                        loadExistingFiles(questionId);
                        
                        // Show modal
                        document.getElementById('editModal').style.display = 'block';
                    } else {
                        alert('Error loading question details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading question details. Please try again.');
                });
        }

        // Load existing files for editing
        function loadExistingFiles(questionId) {
            fetch(`../php/get_question_files.php?id=${questionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const fileList = document.getElementById('editFileList');
                        fileList.innerHTML = '';
                        
                        if (data.files.length > 0) {
                            data.files.forEach(file => {
                                const fileItem = document.createElement('div');
                                fileItem.className = 'file-item';
                                fileItem.innerHTML = `
                                    <span class="file-name">${file.file_name}</span>
                                    <span class="file-size">(${(file.file_size / 1024 / 1024).toFixed(2)} MB)</span>
                                    <span class="file-type">${file.file_type.toUpperCase()}</span>
                                    <button type="button" class="btn-remove-existing-file" data-id="${file.id}">×</button>
                                `;
                                fileList.appendChild(fileItem);
                            });
                            
                            // Add remove functionality for existing files
                            document.querySelectorAll('.btn-remove-existing-file').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const fileId = this.getAttribute('data-id');
                                    const questionId = document.getElementById('edit_id').value;
                                    removeExistingFile(fileId, questionId);
                                });
                            });
                        } else {
                            fileList.innerHTML = '<div class="no-files">No files found</div>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading files:', error);
                });
        }

        function updateEditFileList() {
            const fileList = document.getElementById('editNewFileList');
            const selectedFilesDiv = document.getElementById('editSelectedFiles');
            const fileInfo = document.getElementById('editFileInfo');
            
            fileList.innerHTML = '';
            let totalSize = 0;
            
            editSelectedFiles.forEach((file, index) => {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                totalSize += file.size;
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">(${fileSize} MB)</span>
                    <span class="file-type">${file.name.split('.').pop().toUpperCase()}</span>
                    <button type="button" class="btn-remove-file" data-index="${index}">×</button>
                `;
                fileList.appendChild(fileItem);
            });
            
            // Show total size
            const totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);
            fileInfo.innerHTML = `${editSelectedFiles.length} new file(s) selected - Total: ${totalSizeMB} MB`;
            fileInfo.style.color = '#27ae60';
            
            if (editSelectedFiles.length > 0) {
                selectedFilesDiv.style.display = 'block';
            } else {
                selectedFilesDiv.style.display = 'none';
            }
            
            // Add remove file functionality
            document.querySelectorAll('#editNewFileList .btn-remove-file').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    editSelectedFiles.splice(index, 1);
                    updateEditFileList();
                });
            });
        }


        // Update edit form submission - FIXED VERSION
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Edit form submitted');
            
            const questionId = document.getElementById('edit_id').value;
            const formData = new FormData();
            
            // Add all form fields
            formData.append('id', questionId);
            formData.append('title', document.getElementById('edit_title').value);
            formData.append('description', document.getElementById('edit_description').value);
            formData.append('department', document.getElementById('edit_department').value);
            formData.append('level', document.getElementById('edit_level').value);
            formData.append('semester', document.getElementById('edit_semester').value);
            formData.append('year', document.getElementById('edit_year').value);
            
            // Append new files
            console.log('New files to upload:', editSelectedFiles.length);
            editSelectedFiles.forEach((file, index) => {
                formData.append('new_files[]', file);
                console.log('Adding file:', file.name);
            });
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Updating... <span class="loading"></span>';
            submitBtn.disabled = true;
            
            // Send the request
            fetch('../php/update_question.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received');
                return response.json();
            })
            .then(data => {
                console.log('Data parsed:', data);
                if (data.success) {
                    alert('Past question updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating question. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
            
            return false;
        });

        // Reset edit files when modal closes
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            editSelectedFiles = [];
            document.getElementById('editSelectedFiles').style.display = 'none';
            document.getElementById('editFileInfo').textContent = 'No new files selected';
            document.getElementById('editFileInfo').style.color = '';
        }

        // Delete Question Function
        function deleteQuestion(questionId) {
            if (confirm('Are you sure you want to delete this past question? This action cannot be undone.')) {
                // Send delete request
                fetch(`../php/delete_question.php?id=${questionId}`, {
                    method: 'DELETE',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Question deleted successfully!');
                        location.reload(); // Reload page to reflect changes
                    } else {
                        alert('Error deleting question: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting question. Please try again.');
                });
            }
        }

        // Modal close functionality
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const closeBtn = modal.querySelector('.close');
            closeBtn.addEventListener('click', function() {
                if (modal.id === 'editModal') {
                    closeEditModal();
                } else {
                    modal.style.display = 'none';
                }
            });
        });

        window.addEventListener('click', function(e) {
            modals.forEach(modal => {
                if (e.target === modal) {
                    if (modal.id === 'editModal') {
                        closeEditModal();
                    } else {
                        modal.style.display = 'none';
                    }
                }
            });
        });

        function exportQuestions() {
            alert('Exporting past questions...');
            // In real implementation, generate export
        }
    </script>

    <!-- Enhanced Edit Question Modal with Multiple File Support -->
   <!-- Enhanced Edit Question Modal with Multiple File Support -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Past Question</h2>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="edit_id" name="id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit_title">Title *</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="edit_department">Department *</label>
                    <select id="edit_department" name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_level">Level *</label>
                    <select id="edit_level" name="level" required>
                        <option value="">Select Level</option>
                        <?php foreach ($levels as $level): ?>
                        <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_semester">Semester *</label>
                    <select id="edit_semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $semester): ?>
                        <option value="<?php echo $semester['id']; ?>"><?php echo htmlspecialchars($semester['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_year">Year *</label>
                    <input type="number" id="edit_year" name="year" min="2000" max="<?php echo date('Y'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>
            
            <!-- Existing Files Section -->
            <div class="form-group">
                <label>Existing Files:</label>
                <div id="editFileList" class="selected-files">
                    <!-- Existing files will be loaded here -->
                </div>
            </div>
            
            <!-- Add New Files Section -->
            <div class="file-upload">
                <div id="editFileUploadArea">
                    <input type="file" id="edit_new_files" name="new_files[]" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" multiple style="display: none;">
                    <button type="button" id="editAddFileBtn" class="btn btn-secondary">Add New Files</button>
                    <div class="file-info" id="editFileInfo" style="margin-top: 1rem;">No new files selected</div>
                    <small>Allowed formats: PDF, DOC, DOCX, TXT, JPG, PNG (Max: 10MB each)</small>
                </div>
                
                <!-- New files preview -->
                <div id="editSelectedFiles" class="selected-files" style="display: none; margin-top: 1rem;">
                    <h4>New Files to Add:</h4>
                    <div id="editNewFileList" class="file-list"></div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Question</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>