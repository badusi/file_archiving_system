<?php
session_start();
require_once '../php/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_number = $_POST['matric_number'];
    $full_name = $_POST['full_name'];
    $sex = $_POST['sex'];
    $department_id = $_POST['department'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if matric number already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE matric_number = ?");
    $stmt->execute([$matric_number]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Matric number already exists";
        header("Location: ../register.html");
        exit();
    }
    
    // Insert new user as student (role = 'user')
    $stmt = $pdo->prepare("INSERT INTO users (matric_number, full_name, sex, department_id, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
    
    if ($stmt->execute([$matric_number, $full_name, $sex, $department_id, $password])) {
        $_SESSION['success'] = "Registration successful. Please login.";
        header("Location: ../login.html");
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: ../register.html");
    }
}
?>