<?php
session_start();
require_once '../php/db.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Get question ID from URL parameters
    $input = file_get_contents('php://input');
    parse_str($input, $params);
    $question_id = $params['id'] ?? ($_GET['id'] ?? null);
    
    if (!$question_id) {
        echo json_encode(['success' => false, 'message' => 'Question ID required']);
        exit();
    }

    try {
        // Get question details to delete the file
        $stmt = $pdo->prepare("SELECT file_path FROM past_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$question) {
            echo json_encode(['success' => false, 'message' => 'Question not found']);
            exit();
        }

        // Delete the question from database
        $stmt = $pdo->prepare("DELETE FROM past_questions WHERE id = ?");
        
        if ($stmt->execute([$question_id])) {
            // Delete the file
            if (file_exists($question['file_path'])) {
                unlink($question['file_path']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete question']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>