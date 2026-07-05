<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Debug logging
error_log("=== UPDATE QUESTION REQUEST START ===");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = $_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $department_id = $_POST['department'];
    $level_id = $_POST['level'];
    $semester_id = $_POST['semester'];
    $year = $_POST['year'];

    error_log("Processing question ID: $question_id");
    error_log("Title: $title, Department: $department_id, Level: $level_id, Semester: $semester_id, Year: $year");

    // Validate required fields
    if (empty($title) || empty($department_id) || empty($level_id) || empty($semester_id) || empty($year)) {
        error_log("Validation failed: Required fields missing");
        echo json_encode(['success' => false, 'message' => "Please fill in all required fields"]);
        exit();
    }

    // Validate year
    $current_year = date('Y');
    if ($year < 2000 || $year > $current_year) {
        error_log("Validation failed: Invalid year $year");
        echo json_encode(['success' => false, 'message' => "Please enter a valid year between 2000 and $current_year"]);
        exit();
    }

    try {
        // Check if question exists
        $stmt = $pdo->prepare("SELECT * FROM past_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $existing_question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing_question) {
            error_log("Question not found: $question_id");
            echo json_encode(['success' => false, 'message' => "Question not found"]);
            exit();
        }

        error_log("Question found: " . $existing_question['title']);

        // Handle new file uploads
        $new_uploaded_files = [];
        if (isset($_FILES['new_files']) && is_array($_FILES['new_files']['name'])) {
            $file_count = count($_FILES['new_files']['name']);
            error_log("Files found in request: $file_count");
            
            $upload_dir = '../uploads/past_questions/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
                error_log("Created upload directory: $upload_dir");
            }

            for ($i = 0; $i < $file_count; $i++) {
                // Skip empty file entries
                if (empty($_FILES['new_files']['name'][$i]) || $_FILES['new_files']['error'][$i] !== UPLOAD_ERR_OK) {
                    error_log("Skipping file index $i - empty or error");
                    continue;
                }

                $file_name = $_FILES['new_files']['name'][$i];
                $file_tmp_name = $_FILES['new_files']['tmp_name'][$i];
                $file_size = $_FILES['new_files']['size'][$i];
                $file_error = $_FILES['new_files']['error'][$i];

                error_log("Processing file: $file_name, size: $file_size, tmp_name: $file_tmp_name, error: $file_error");

                // Check if file was actually uploaded
                if (!is_uploaded_file($file_tmp_name)) {
                    error_log("File not uploaded properly: $file_tmp_name");
                    continue;
                }

                $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
                $max_file_size = 10 * 1024 * 1024;
                
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    error_log("Invalid file type: $file_extension for $file_name");
                    echo json_encode(['success' => false, 'message' => "Invalid file type for '$file_name'. Allowed types: PDF, DOC, DOCX, TXT, JPG, PNG"]);
                    exit();
                }
                
                if ($file_size > $max_file_size) {
                    error_log("File too large: $file_size bytes for $file_name");
                    echo json_encode(['success' => false, 'message' => "File '$file_name' is too large. Maximum size is 10MB"]);
                    exit();
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file_tmp_name, $file_path)) {
                    $new_uploaded_files[] = [
                        'path' => $file_path,
                        'name' => $file_name,
                        'size' => $file_size,
                        'type' => $file_extension
                    ];
                    error_log("File uploaded successfully: $file_name -> $file_path");
                } else {
                    error_log("Failed to move uploaded file: $file_tmp_name to $file_path");
                    echo json_encode(['success' => false, 'message' => "Failed to upload file '$file_name'. Please try again."]);
                    exit();
                }
            }
        } else {
            error_log("No files array found in request or files array is empty");
        }

        error_log("Files to be inserted into database: " . count($new_uploaded_files));

        // Start transaction
        $pdo->beginTransaction();
        error_log("Database transaction started");

        // Update question in database
        $stmt = $pdo->prepare("UPDATE past_questions SET 
                              title = ?, description = ?, 
                              department_id = ?, level_id = ?, semester_id = ?, year = ?
                              WHERE id = ?");
        
        $update_result = $stmt->execute([$title, $description, $department_id, $level_id, $semester_id, $year, $question_id]);
        
        if ($update_result) {
            error_log("Question updated successfully in database");
            
            // Add new files to past_question_files table
            if (!empty($new_uploaded_files)) {
                $file_stmt = $pdo->prepare("INSERT INTO past_question_files 
                    (past_question_id, file_path, file_name, file_size, file_type) 
                    VALUES (?, ?, ?, ?, ?)");
                
                $files_inserted = 0;
                foreach ($new_uploaded_files as $file) {
                    $insert_result = $file_stmt->execute([
                        $question_id, 
                        $file['path'], 
                        $file['name'], 
                        $file['size'], 
                        $file['type']
                    ]);
                    
                    if ($insert_result) {
                        $files_inserted++;
                        error_log("File inserted into database: " . $file['name']);
                    } else {
                        error_log("Failed to insert file into database: " . $file['name']);
                    }
                }
                error_log("Total files inserted: $files_inserted");
            } else {
                error_log("No new files to insert into database");
            }
            
            $pdo->commit();
            error_log("Database transaction committed");
            
            $new_files_count = count($new_uploaded_files);
            $message = "Past question updated successfully" . ($new_files_count > 0 ? " with $new_files_count new file(s)!" : "!");
            error_log("Success: $message");
            
            echo json_encode(['success' => true, 'message' => $message]);
            
        } else {
            $pdo->rollBack();
            error_log("Failed to update question in database");
            
            // Remove uploaded files if database update failed
            foreach ($new_uploaded_files as $file) {
                if (file_exists($file['path'])) {
                    unlink($file['path']);
                    error_log("Removed file after database failure: " . $file['path']);
                }
            }
            echo json_encode(['success' => false, 'message' => "Failed to update past question in database"]);
        }
        
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Database transaction rolled back due to error");
        }
        
        // Remove uploaded files if database error occurred
        foreach ($new_uploaded_files as $file) {
            if (file_exists($file['path'])) {
                unlink($file['path']);
                error_log("Removed file after database error: " . $file['path']);
            }
        }
        
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Invalid request method"]);
}

error_log("=== UPDATE QUESTION REQUEST END ===");

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
?>