<?php
session_start();
require_once '../php/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_number = trim($_POST['matric_number']);
    $password = $_POST['password'];
    
    // Check if user exists in the users table (students)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE matric_number = ?");
    $stmt->execute([$matric_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Set student session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['matric_number'] = $user['matric_number'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_admin'] = false; // Explicitly set as not admin
        
        header("Location: ../user/dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid matric number or password";
        header("Location: ../login.html");
        exit();
    }
}
?>