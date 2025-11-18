<?php
session_start();

include 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['userId']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resourceTitle = trim($_POST['resourceTitle']);
    $resourceDesc = trim($_POST['resourceDesc']);
    $category = trim($_POST['category']);
    $type = $_POST['content_type'];
    $file_path = "";

    // Validate inputs
    if (empty($resourceTitle) || empty($category) || empty($type)) {
        $error = "Please fill in all required fields.";
    } else {
        if ($type === 'video') {
            $youtube_link = trim($_POST['youtube_link']);
            
            // Validate YouTube URL
            if (empty($youtube_link)) {
                $error = "Please provide a YouTube link.";
            } elseif (!preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $youtube_link)) {
                $error = "Please provide a valid YouTube URL.";
            } else {
                $file_path = $youtube_link;
            }
        } elseif ($type === 'article' && isset($_FILES['file'])) {
            $file = $_FILES['file'];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = "File upload error. Please try again.";
            } else {
                // Validate file size (10MB max)
                $maxSize = 10 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    $error = "File size must not exceed 10MB.";
                } else {
                    // Validate file type
                    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mimeType, $allowedTypes)) {
                        $error = "Only PDF, DOC, and DOCX files are allowed.";
                    } else {
                        // Create uploads directory if it doesn't exist
                        $targetDirectory = "uploads/";
                        if (!is_dir($targetDirectory)) {
                            mkdir($targetDirectory, 0755, true);
                        }
                        
                        // Generate safe filename
                        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = time() . "_" . uniqid() . "." . $fileExtension;
                        $targetPath = $targetDirectory . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $file_path = $targetPath;
                        } else {
                            $error = "Failed to upload file. Please try again.";
                        }
                    }
                }
            }
        } else {
            $error = "Please provide the required content.";
        }

        // Insert into database if no errors
        if (empty($error) && !empty($file_path)) {
            $query = $connect->prepare("INSERT INTO resources (resourceTitle, resourceDesc, category, file_path, content_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $query->bind_param("sssssi", $resourceTitle, $resourceDesc, $category, $file_path, $type, $_SESSION['userId']);
            
            if ($query->execute()) {
                $success = "Resource uploaded successfully!";
                header("Location: manage_resources.php");
                exit;
            } else {
                $error = "Database error. Please try again.";
            }
            $query->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resource</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #2e7d32;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #2e7d32;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="url"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #2e7d32;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
        }

        .upload-field {
            display: none;
        }

        .upload-field.active {
            display: block;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .required {
            color: #c33;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Resource</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="resourceTitle">Resource Title <span class="required">*</span></label>
                <input type="text" name="resourceTitle" id="resourceTitle" placeholder="Enter resource title" required>
            </div>

            <div class="form-group">
                <label for="resourceDesc">Resource Description</label>
                <textarea name="resourceDesc" id="resourceDesc" placeholder="Enter the resource description here..."></textarea>
            </div>

            <div class="form-group">
                <label for="category">Category <span class="required">*</span></label>
                <input type="text" name="category" id="category" placeholder="Enter resource category" required>
            </div>

            <div class="form-group">
                <label for="content_type">Content Type <span class="required">*</span></label>
                <select name="content_type" id="content_type" required>
                    <option value="">Select Type</option>
                    <option value="video">Video (YouTube)</option>
                    <option value="article">Article (PDF/DOC)</option>
                </select>
            </div>

            <div class="form-group upload-field" id="video_field">
                <label for="youtube_link">YouTube URL <span class="required">*</span></label>
                <input type="url" name="youtube_link" id="youtube_link" placeholder="https://www.youtube.com/watch?v=...">
            </div>

            <div class="form-group upload-field" id="article_field">
                <label for="file">Upload File <span class="required">*</span></label>
                <input type="file" name="file" id="file" accept=".pdf,.doc,.docx">
                <small style="color: #888; font-size: 12px; display: block; margin-top: 5px;">Accepted formats: PDF, DOC, DOCX (Max 10MB)</small>
            </div>

            <button type="submit">Upload Resource</button>
        </form>

        <div class="back-link">
            <a href="manage_resources.php">Cancel</a>
        </div>
    </div>

    <script>
        const contentType = document.getElementById('content_type');
        const videoField = document.getElementById('video_field');
        const articleField = document.getElementById('article_field');
        const youtubeLink = document.getElementById('youtube_link');
        const fileInput = document.getElementById('file');

        function toggleUploadFields() {
            const type = contentType.value;
            
            videoField.classList.remove('active');
            articleField.classList.remove('active');
            
            // Clear and remove required attribute from hidden fields
            youtubeLink.removeAttribute('required');
            fileInput.removeAttribute('required');
            
            if (type === 'video') {
                videoField.classList.add('active');
                youtubeLink.setAttribute('required', 'required');
            } else if (type === 'article') {
                articleField.classList.add('active');
                fileInput.setAttribute('required', 'required');
            }
        }

        contentType.addEventListener('change', toggleUploadFields);

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const type = contentType.value;
            
            if (type === 'video') {
                const url = youtubeLink.value;
                if (!url.match(/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/)) {
                    e.preventDefault();
                    alert('Please enter a valid YouTube URL');
                    return false;
                }
            } else if (type === 'article') {
                if (!fileInput.files.length) {
                    e.preventDefault();
                    alert('Please select a file to upload');
                    return false;
                }
                
                const file = fileInput.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('File size must not exceed 10MB');
                    return false;
                }
            }
        });
    </script>
</body>
</html>