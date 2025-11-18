<?php
session_start();

require_once 'db_connect.php';

$isEdit = false;
$postData = null;
$error = "";

// Check if editing existing post
if (isset($_GET['id'])) {
    $isEdit = true;
    $postId = intval($_GET['id']);
    
    $stmt = $connect->prepare("SELECT * FROM posts WHERE postId = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $postData = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Post not found.";
        header("Location: admin_posts.php");
        exit;
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postCaption = trim($_POST['postCaption']);
    $userId = intval($_POST['userId']);
    
    if (empty($postCaption) || $userId <= 0) {
        $error = "Please fill in all required fields.";
    } else {
        if ($isEdit) {
            // Update existing post
            $postId = intval($_POST['postId']);
            $currentImage = $_POST['current_image'];
            $imagePath = $currentImage;
            
            // Check if new image is uploaded
            if (isset($_FILES['postImage']) && $_FILES['postImage']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['postImage'];
                
                // Validate image
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $error = "Only JPG, PNG, and GIF images are allowed.";
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $error = "Image size must not exceed 5MB.";
                } else {
                    // Delete old image
                    if (!empty($currentImage) && file_exists($currentImage)) {
                        unlink($currentImage);
                    }
                    
                    // Upload new image
                    $targetDirectory = "Images/posts/";
                    if (!is_dir($targetDirectory)) {
                        mkdir($targetDirectory, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $fileName = "post_" . time() . "_" . uniqid() . "." . $fileExtension;
                    $targetPath = $targetDirectory . $fileName;
                    
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $imagePath = $targetPath;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }
            
            if (empty($error)) {
                $stmt = $connect->prepare("UPDATE posts SET postCaption = ?, postImage = ?, userId = ? WHERE postId = ?");
                $stmt->bind_param("ssii", $postCaption, $imagePath, $userId, $postId);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Post updated successfully!";
                    header("Location: admin_posts.php");
                    exit;
                } else {
                    $error = "Error updating post.";
                }
                $stmt->close();
            }
        } 
    }
}

// Get all users for dropdown
$usersQuery = "SELECT userId, username FROM users ORDER BY username";
$usersResult = $connect->query($usersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit Post' : 'Create New Post'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
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

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .back-btn {
            background: #e0e0e0;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            font-size: 20px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #d0d0d0;
            transform: translateX(-3px);
        }

        h1 {
            color: #333;
            font-size: 28px;
            flex: 1;
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

        .required {
            color: #e74c3c;
        }

        input[type="text"],
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
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-label {
            display: block;
            padding: 12px 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .file-input-label:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        input[type="file"] {
            position: absolute;
            left: -9999px;
        }

        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }

        .current-image {
            margin-top: 10px;
            text-align: center;
        }

        .current-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .current-image p {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit {
            background: #2e7d32;
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .help-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }

            h1 {
                font-size: 24px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admin_posts.php" class="back-btn">←</a>
            <h1><?php echo 'Edit Post'; ?></h1>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="postForm">
            <?php if ($isEdit): ?>
                <input type="hidden" name="postId" value="<?php echo $postData['postId']; ?>">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($postData['postImage']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="userId">User <span class="required">*</span></label>
                <select name="userId" id="userId" required>
                    <option value="">Select User</option>
                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                        <option value="<?php echo $user['userId']; ?>"
                            <?php echo ($isEdit && $postData['userId'] == $user['userId']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="postCaption">Caption <span class="required">*</span></label>
                <textarea name="postCaption" id="postCaption" placeholder="What's on your mind?" required><?php echo $isEdit ? htmlspecialchars($postData['postCaption']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="postImage">
                    Post Image <span class="required">*</span>
                    <?php if ($isEdit): ?>
                        <span style="font-weight: normal; color: #888;">(Leave empty to keep current image)</span>
                    <?php endif; ?>
                </label>
                <div class="file-input-wrapper">
                    <label for="postImage" class="file-input-label">
                        <span id="fileText">📷 Click to select an image</span>
                    </label>
                    <input type="file" 
                           name="postImage" 
                           id="postImage" 
                           accept="image/jpeg,image/jpg,image/png,image/gif"
                           <?php echo !$isEdit ? 'required' : ''; ?>>
                </div>
                <div class="help-text">Accepted formats: JPG, PNG, GIF (Max 5MB)</div>
                
                <?php if ($isEdit && !empty($postData['postImage'])): ?>
                    <div class="current-image">
                        <p><strong>Current Image:</strong></p>
                        <img src="<?php echo htmlspecialchars($postData['postImage']); ?>" alt="Current post image">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-submit">
                    <?php echo 'Update Post'; ?>
                </button>
                <a href="admin_posts.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        // Display selected file name
        document.getElementById('postImage').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileText = document.getElementById('fileText');
            
            if (fileName) {
                fileText.textContent = '✅ ' + fileName;
            } else {
                fileText.textContent = '📷 Click to select an image';
            }
        });

        // Form validation
        document.getElementById('postForm').addEventListener('submit', function(e) {
            const userId = document.getElementById('userId').value;
            const caption = document.getElementById('postCaption').value.trim();
            const fileInput = document.getElementById('postImage');
            
            if (!userId) {
                e.preventDefault();
                alert('Please select a user');
                return false;
            }
            
            if (!caption) {
                e.preventDefault();
                alert('Please enter a caption');
                return false;
            }

            <?php if (!$isEdit): ?>
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please select an image');
                return false;
            }
            <?php endif; ?>

            if (fileInput.files.length) {
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (file.size > maxSize) {
                    e.preventDefault();
                    alert('Image size must not exceed 5MB');
                    return false;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Only JPG, PNG, and GIF images are allowed');
                    return false;
                }
            }
        });
    </script>
</body>
</html>

<?php
$connect->close();
?>