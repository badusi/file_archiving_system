<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set JSON header
header('Content-Type: application/json');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $department_id = $_POST['department'];
    $level_id = $_POST['level'];
    $semester_id = $_POST['semester'];
    $year = $_POST['year'];
    $uploaded_by = $_SESSION['admin_id'];

    // Validate required fields
    if (empty($title) || empty($department_id) || empty($level_id) || empty($semester_id) || empty($year)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }

    // Validate year
    $current_year = date('Y');
    if ($year < 2000 || $year > $current_year) {
        echo json_encode(['success' => false, 'message' => "Please enter a valid year between 2000 and $current_year"]);
        exit();
    }

    // Handle file uploads
    $uploaded_files = [];
    $has_errors = false;

    if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
        $file_count = count($_FILES['files']['name']);
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/past_questions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];

                // File validation
                $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
                $max_file_size = 10 * 1024 * 1024; // 10MB
                
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $file_size = $file['size'];
                
                // Check file extension
                if (!in_array($file_extension, $allowed_extensions)) {
                    echo json_encode(['success' => false, 'message' => "Invalid file type for '{$file['name']}'. Allowed types: " . implode(', ', $allowed_extensions)]);
                    exit();
                }
                
                // Check file size
                if ($file_size > $max_file_size) {
                    echo json_encode(['success' => false, 'message' => "File '{$file['name']}' is too large. Maximum size is 10MB"]);
                    exit();
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $file_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $uploaded_files[] = [
                        'path' => $file_path,
                        'name' => $file['name'],
                        'size' => $file_size,
                        'type' => $file_extension
                    ];
                } else {
                    echo json_encode(['success' => false, 'message' => "Failed to upload file '{$file['name']}'. Please try again."]);
                    exit();
                }
            } else {
                $error_code = $_FILES['files']['error'][$i];
                $error_message = getUploadErrorMessage($error_code);
                echo json_encode(['success' => false, 'message' => "File upload error for '{$_FILES['files']['name'][$i]}': $error_message"]);
                exit();
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Please select at least one file"]);
        exit();
    }

    if (!empty($uploaded_files)) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert into past_questions table
            $stmt = $pdo->prepare("INSERT INTO past_questions 
                (title, description, file_path, department_id, level_id, semester_id, year, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Store first file path in the main table for backward compatibility
            $main_file_path = $uploaded_files[0]['path'];
            
            if ($stmt->execute([$title, $description, $main_file_path, $department_id, $level_id, $semester_id, $year, $uploaded_by])) {
                $past_question_id = $pdo->lastInsertId();
                
                // Insert all files into past_question_files table
                $file_stmt = $pdo->prepare("INSERT INTO past_question_files 
                    (past_question_id, file_path, file_name, file_size, file_type) 
                    VALUES (?, ?, ?, ?, ?)");
                
                foreach ($uploaded_files as $file) {
                    $file_stmt->execute([$past_question_id, $file['path'], $file['name'], $file['size'], $file['type']]);
                }
                
                $pdo->commit();
                
                // Log the activity
                logActivity("Uploaded past question: $title with " . count($uploaded_files) . " files");
                
                echo json_encode(['success' => true, 'message' => "Past question with " . count($uploaded_files) . " file(s) uploaded successfully!"]);
                
            } else {
                $pdo->rollBack();
                // Remove uploaded files if database insert failed
                foreach ($uploaded_files as $file) {
                    if (file_exists($file['path'])) {
                        unlink($file['path']);
                    }
                }
                echo json_encode(['success' => false, 'message' => "Failed to save past question to database"]);
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Remove uploaded files if database error occurred
            foreach ($uploaded_files as $file) {
                if (file_exists($file['path'])) {
                    unlink($file['path']);
                }
            }
            echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => "Invalid request method"]);
}

/**
 * Get user-friendly upload error message
 */
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "File is too large";
        case UPLOAD_ERR_PARTIAL:
            return "File was only partially uploaded";
        case UPLOAD_ERR_NO_FILE:
            return "No file was selected";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing temporary folder";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension";
        default:
            return "Unknown upload error";
    }
}

/**
 * Log admin activity (optional enhancement)
 */
function logActivity($activity) {
    // You can implement an activity log system here
    error_log("Admin Activity: " . $activity);
}
?>