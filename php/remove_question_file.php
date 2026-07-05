<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['file_id']) && isset($input['question_id'])) {
    $fileId = $input['file_id'];
    $questionId = $input['question_id'];
    
    try {
        // Get file path before deleting
        $stmt = $pdo->prepare("SELECT file_path FROM past_question_files WHERE id = ? AND past_question_id = ?");
        $stmt->execute([$fileId, $questionId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            // Delete from database
            $deleteStmt = $pdo->prepare("DELETE FROM past_question_files WHERE id = ? AND past_question_id = ?");
            $deleteStmt->execute([$fileId, $questionId]);
            
            // Delete physical file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'File removed successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'File not found']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>