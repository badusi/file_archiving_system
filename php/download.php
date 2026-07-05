<?php
session_start();
require_once '../php/db.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
    header("Location: ../login.html");
    exit();
}

if (isset($_GET['id'])) {
    $question_id = $_GET['id'];
    
    // Get question details
    $stmt = $pdo->prepare("SELECT pq.*, d.name as department_name 
                          FROM past_questions pq
                          JOIN departments d ON pq.department_id = d.id
                          WHERE pq.id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($question && file_exists($question['file_path'])) {
        // Log the download (optional)
        logDownload($_SESSION['user_id'], $question_id);
        
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($question['file_path']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($question['file_path']));
        readfile($question['file_path']);
        exit;
    } else {
        $_SESSION['error'] = "File not found or unavailable";
        header("Location: ../user/dashboard.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request";
    header("Location: ../user/dashboard.php");
    exit();
}

function logDownload($user_id, $question_id) {
    // You can create a downloads_log table to track downloads
    // CREATE TABLE downloads_log (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, question_id INT, downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO downloads_log (user_id, question_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $question_id]);
    } catch (PDOException $e) {
        // Log error but don't interrupt download
        error_log("Download log error: " . $e->getMessage());
    }
}
?>