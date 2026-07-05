<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['is_admin']) && !isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if (isset($_GET['id'])) {
    $question_id = $_GET['id'];
    
    try {
        // Get question details
        $stmt = $pdo->prepare("SELECT pq.*, d.name as department_name 
                              FROM past_questions pq 
                              JOIN departments d ON pq.department_id = d.id 
                              WHERE pq.id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$question) {
            die('Question not found');
        }
        
        // Get all files for this question
        $file_stmt = $pdo->prepare("SELECT * FROM past_question_files WHERE past_question_id = ?");
        $file_stmt->execute([$question_id]);
        $files = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no files in the new table, use the single file from main table
        if (empty($files)) {
            $files = [[
                'file_path' => $question['file_path'],
                'file_name' => basename($question['file_path'])
            ]];
        }
        
        // If only one file, download it directly
        if (count($files) === 1) {
            $file = $files[0];
            if (file_exists($file['file_path'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
                header('Content-Length: ' . filesize($file['file_path']));
                readfile($file['file_path']);
                exit;
            } else {
                die('File not found');
            }
        } else {
            // Multiple files - show download page
            showDownloadPage($question, $files);
        }
        
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }
} else {
    die('No question ID provided');
}

function showDownloadPage($question, $files) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Download Files - <?php echo htmlspecialchars($question['title']); ?></title>
        <link rel="stylesheet" href="../css/style.css">
        <style>
            .download-container {
                max-width: 800px;
                margin: 2rem auto;
                padding: 2rem;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .file-list {
                margin: 1rem 0;
            }
            .file-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
                border: 1px solid #ecf0f1;
                border-radius: 4px;
                margin-bottom: 0.5rem;
            }
            .file-info {
                flex: 1;
            }
            .file-actions {
                display: flex;
                gap: 0.5rem;
            }
            .back-button {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                background: #95a5a6;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 600;
                transition: all 0.3s;
                border: none;
                cursor: pointer;
                font-size: 1rem;
                margin-top: 1rem;
            }
            .back-button:hover {
                background: #7f8c8d;
                text-decoration: none;
                color: white;
            }
            .button-container {
                text-align: center;
                margin-top: 2rem;
            }
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <h1>Download Files</h1>
            </div>
        </header>
        
        <main>
            <div class="container">
                <div class="download-container">
                    <h2><?php echo htmlspecialchars($question['title']); ?></h2>
                    <p>This question contains <?php echo count($files); ?> file(s). You can download them individually using the buttons below.</p>
                    
                    <div class="file-list">
                        <?php foreach ($files as $index => $file): 
                            $file_extension = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                            $file_size = filesize($file['file_path']);
                        ?>
                        <div class="file-item">
                            <div class="file-info">
                                <strong><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                <br>
                                <small>Type: <?php echo strtoupper($file_extension); ?> | Size: <?php echo formatFileSize($file_size); ?></small>
                            </div>
                            <div class="file-actions">
                                <a href="<?php echo $file['file_path']; ?>" download class="btn btn-primary">Download</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="button-container">
                        <button onclick="window.history.back()" class="back-button">← Back to Previous Page</button>
                    </div>
                </div>
            </div>
        </main>

        <script>
            // Alternative back button functionality
            document.querySelector('.back-button').addEventListener('click', function() {
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.close(); // Close the tab if no history
                }
            });
        </script>
    </body>
    </html>
    <?php
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>