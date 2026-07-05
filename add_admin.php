<?php
$mysqli = new mysqli('localhost', 'root', '', 'past_questions_archive');


if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// New admin credentials
$email = "adminEdu@mail.com";  // Change this to the new admin's email
$full_name = "System Administrator";
$password = "admin123";    // Change this to a strong password
$role = "admin";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into the database
$stmt = $mysqli->prepare("INSERT INTO admins (email, full_name, password,  role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $email, $full_name, $hashed_password,  $role);

if ($stmt->execute()) {
    echo "✅ New admin added successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
?>
