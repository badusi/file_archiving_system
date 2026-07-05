<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    exit("Unauthorized");
}

$title = trim($_POST['title']);
$description = trim($_POST['description']);
$department_id = $_POST['department'];
$level_id = $_POST['level'];
$semester_id = $_POST['semester'];
$year = $_POST['year'];
$uploaded_by = $_SESSION['admin_id'];

if (empty($title) || empty($department_id) || empty($level_id) || empty($semester_id) || empty($year)) {
    exit("Please fill all fields.");
}

$upload_dir = '../uploads/past_questions/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$file_paths = [];
$allowed = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];

foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
    $name = $_FILES['files']['name'][$key];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) continue;

    $new_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    $path = $upload_dir . $new_name;

    if (move_uploaded_file($tmp_name, $path)) {
        $file_paths[] = $path;
    }
}

if (count($file_paths) === 0) exit("No valid files uploaded.");

$json_files = json_encode($file_paths);

$stmt = $pdo->prepare("INSERT INTO past_questions (title, description, file_path, department_id, level_id, semester_id, year, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if ($stmt->execute([$title, $description, $json_files, $department_id, $level_id, $semester_id, $year, $uploaded_by])) {
    echo "Files uploaded successfully!";
} else {
    echo "Failed to save to database.";
}
?>
