<?php
session_start();
require_once '../php/db.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $question_id = $_GET['id'];
    
    // Get question details
    $stmt = $pdo->prepare("SELECT * FROM past_questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($question) {
        echo json_encode(['success' => true, 'question' => $question]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Question not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>