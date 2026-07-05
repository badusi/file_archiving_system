<?php
session_start();
require_once '../php/db.php';

header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $question_id = $_GET['id'];
    
    // Get question details
    $stmt = $pdo->prepare("SELECT pq.*, d.name as department_name, l.name as level_name, 
                                  s.name as semester_name, a.full_name as uploaded_by_name 
                           FROM past_questions pq
                           JOIN departments d ON pq.department_id = d.id
                           JOIN levels l ON pq.level_id = l.id
                           JOIN semesters s ON pq.semester_id = s.id
                           JOIN admins a ON pq.uploaded_by = a.id
                           WHERE pq.id = ?");
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