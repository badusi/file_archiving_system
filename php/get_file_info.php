<?php
session_start();
require_once '../php/db.php';

header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_GET['path'])) {
    $filePath = $_GET['path'];
    
    // Security check - ensure the file is within allowed directories
    $allowedBasePath = realpath('../uploads/');
    $requestedPath = realpath($filePath);
    
    if ($requestedPath && strpos($requestedPath, $allowedBasePath) === 0 && file_exists($requestedPath)) {
        $fileSize = filesize($requestedPath);
        echo json_encode([
            'success' => true,
            'size' => $fileSize,
            'path' => $filePath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found or access denied']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File path required']);
}
?>