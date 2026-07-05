<?php
require_once 'db.php';

if (isset($_POST['course'])) {
    $course = $_POST['course'];
    $stmt = $pdo->prepare("SELECT * FROM past_questions WHERE title = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$course]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($files) {
        echo "<h3>Files for <strong>" . htmlspecialchars($course) . "</strong></h3>";
        echo "<div style='display:flex; flex-wrap:wrap; gap:20px;'>";

        foreach ($files as $file) {
            $filePath = '../uploads/' . htmlspecialchars($file['file_path']);
            $fileName = htmlspecialchars($file['title']);
            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            echo "<div style='border:1px solid #ddd; border-radius:8px; padding:10px; width:230px; text-align:center; box-shadow:0 0 6px rgba(0,0,0,0.1);'>";
            if (in_array($fileExt, ['jpg','jpeg','png','gif'])) {
                echo "<img src='$filePath' style='width:100%; height:150px; object-fit:cover; border-radius:6px;'><br>";
            } elseif ($fileExt === 'pdf') {
                echo "<iframe src='$filePath' style='width:100%; height:150px; border:none;'></iframe><br>";
            } else {
                echo "<img src='../assets/file-icon.png' alt='File' style='width:80px; margin-top:20px;'><br>";
            }

            echo "<p><strong>$fileName</strong></p>";
            echo "<a href='$filePath' download class='btn btn-primary' style='background:#007bff; color:#fff; padding:6px 12px; border-radius:5px; text-decoration:none;'>Download</a>";
            echo "</div>";
        }

        echo "</div>";
    } else {
        echo "<p style='color:#777;'>No files uploaded for this course yet.</p>";
    }
}
?>
