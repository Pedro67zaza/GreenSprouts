<?php
session_start();

include 'db_connect.php';
// Check if user is logged in and is admin
if (!isset($_SESSION['userId']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle Delete Resource
if (isset($_POST['deleteResource'])) {
    $resourceId = intval($_POST['resourceId']);
    
    // Get file path before deleting
    $getFileStmt = $connect->prepare("SELECT file_path, content_type FROM resources WHERE resourceId = ?");
    $getFileStmt->bind_param("i", $resourceId);
    $getFileStmt->execute();
    $result = $getFileStmt->get_result();
    $resource = $result->fetch_assoc();
    
    if ($resource) {
        // Delete file if it's an article (not a YouTube link)
        if ($resource['content_type'] === 'article' && file_exists($resource['file_path'])) {
            unlink($resource['file_path']);
        }
        
        // Delete from database
        $deleteStmt = $connect->prepare("DELETE FROM resources WHERE resourceId = ?");
        $deleteStmt->bind_param("i", $resourceId);
        
        if ($deleteStmt->execute()) {
            $message = 'Resource deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete resource';
            $messageType = 'error';
        }
        $deleteStmt->close();
    }
    $getFileStmt->close();
}

// Handle Edit Resource
if (isset($_POST['editResource'])) {
    $resourceId = intval($_POST['resourceId']);
    $resourceTitle = trim($_POST['resourceTitle']);
    $resourceDesc = trim($_POST['resourceDesc']);
    $category = trim($_POST['category']);
    
    if (empty($resourceTitle) || empty($category)) {
        $message = 'Title and category are required';
        $messageType = 'error';
    } else {
        // Get current resource data
        $getStmt = $connect->prepare("SELECT content_type, file_path FROM resources WHERE resourceId = ?");
        $getStmt->bind_param("i", $resourceId);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $currentResource = $result->fetch_assoc();
        $getStmt->close();
        
        $newFilePath = $currentResource['file_path'];
        
        // Check if content is being updated
        if ($currentResource['content_type'] === 'video') {
            // Update YouTube link if provided
            $youtubeLink = trim($_POST['youtube_link']);
            if (!empty($youtubeLink)) {
                if (preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $youtubeLink)) {
                    $newFilePath = $youtubeLink;
                } else {
                    $message = 'Invalid YouTube URL';
                    $messageType = 'error';
                }
            }
        } elseif ($currentResource['content_type'] === 'article') {
            // Check if new file is uploaded
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file'];
                $maxSize = 10 * 1024 * 1024;
                
                if ($file['size'] > $maxSize) {
                    $message = 'File size must not exceed 10MB';
                    $messageType = 'error';
                } else {
                    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (in_array($mimeType, $allowedTypes)) {
                        $targetDirectory = "uploads/";
                        if (!is_dir($targetDirectory)) {
                            mkdir($targetDirectory, 0755, true);
                        }
                        
                        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $fileName = time() . "_" . uniqid() . "." . $fileExtension;
                        $targetPath = $targetDirectory . $fileName;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Delete old file
                            if (file_exists($currentResource['file_path'])) {
                                unlink($currentResource['file_path']);
                            }
                            $newFilePath = $targetPath;
                        } else {
                            $message = 'Failed to upload new file';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Only PDF, DOC, and DOCX files are allowed';
                        $messageType = 'error';
                    }
                }
            }
        }
        
        if (empty($message)) {
            $updateStmt = $connect->prepare("UPDATE resources SET resourceTitle = ?, resourceDesc = ?, category = ?, file_path = ? WHERE resourceId = ?");
            $updateStmt->bind_param("ssssi", $resourceTitle, $resourceDesc, $category, $newFilePath, $resourceId);
            
            if ($updateStmt->execute()) {
                $message = 'Resource updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update resource';
                $messageType = 'error';
            }
            $updateStmt->close();
        }
    }
}

// Fetch all resources
$resourcesStmt = $connect->prepare("
    SELECT r.*, u.username 
    FROM resources r 
    LEFT JOIN users u ON r.uploaded_by = u.userId 
    ORDER BY r.uploaded_at DESC
");
$resourcesStmt->execute();
$resourcesResult = $resourcesStmt->get_result();
$resources = $resourcesResult->fetch_all(MYSQLI_ASSOC);
$resourcesStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources - Admin</title>
    <link href='sidebar.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
            background: var(--body-color);
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            padding-bottom: 50px;
            min-height: 100vh;
            background-color: #f4f9f4;
            transition:  var(--tran-05);
            overflow-y: auto;
        }

        .sidebar.close ~ .main-content {
            margin-left: 88px;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2e7d32;
            font-size: 28px;
        }

        .header p {
            color: #666;
            margin-top: 5px;
        }

        .btn-upload {
            background: #2e7d32;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-upload:hover {
            background: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .resources-grid {
            display: grid;
            gap: 20px;
        }

        .resource-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .resource-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .resource-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .resource-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .resource-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        .resource-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .resource-desc {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .resource-type {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .type-video {
            background: #e3f2fd;
            color: #1976d2;
        }

        .type-article {
            background: #fff3e0;
            color: #f57c00;
        }

        .resource-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
        }

        .btn-edit:hover {
            background: #1976D2;
        }

        .btn-delete {
            background: #f44336;
            color: white;
        }

        .btn-delete:hover {
            background: #d32f2f;
        }

        .btn-view {
            background: #4CAF50;
            color: white;
        }

        .btn-view:hover {
            background: #388E3C;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d0d0d0;
        }

        .btn-save {
            background: #2e7d32;
            color: white;
        }

        .btn-save:hover {
            background: #1b5e20;
        }

        .empty-state {
            background: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .empty-state i {
            font-size: 64px;
            color: #c8e6c9;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <section class="main-content">
        <div class="header">
            <div>
                <h1><i class='bx bxs-book-content'></i> Manage Resources</h1>
                <p>Create, edit, and delete educational resources</p>
            </div>
            <a href="upload_resource.php" class="btn-upload">
                <i class='bx bx-plus-circle'></i> Upload New Resource
            </a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($resources)): ?>
            <div class="empty-state">
                <i class='bx bx-book-open'></i>
                <h3>No resources yet</h3>
                <p>Start by uploading your first educational resource</p>
                <a href="upload_resource.php" class="btn-upload">
                    <i class='bx bx-plus-circle'></i> Upload Resource
                </a>
            </div>
        <?php else: ?>
            <div class="resources-grid">
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card">
                        <div class="resource-header">
                            <div style="flex: 1;">
                                <div class="resource-title"><?php echo htmlspecialchars($resource['resourceTitle']); ?></div>
                                <div class="resource-meta">
                                    <span><i class='bx bx-category'></i> <?php echo htmlspecialchars($resource['category']); ?></span>
                                    <span><i class='bx bx-time'></i> <?php echo date('M d, Y', strtotime($resource['uploaded_at'])); ?></span>
                                </div>
                                <span class="resource-type <?php echo $resource['content_type'] === 'video' ? 'type-video' : 'type-article'; ?>">
                                    <?php echo $resource['content_type'] === 'video' ? '🎥 Video' : '📄 Article'; ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($resource['resourceDesc'])): ?>
                            <div class="resource-desc">
                                <?php echo nl2br(htmlspecialchars($resource['resourceDesc'])); ?>
                            </div>
                        <?php endif; ?>

                        <div class="resource-actions">
                            <?php if ($resource['content_type'] === 'video'): ?>
                                <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank" class="btn btn-view">
                                    <i class='bx bx-play-circle'></i> Watch
                                </a>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank" class="btn btn-view">
                                    <i class='bx bx-download'></i> Download
                                </a>
                            <?php endif; ?>
                            
                            <button class="btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($resource)); ?>)">
                                <i class='bx bx-edit'></i> Edit
                            </button>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                <input type="hidden" name="resourceId" value="<?php echo $resource['resourceId']; ?>">
                                <button type="submit" name="deleteResource" class="btn btn-delete">
                                    <i class='bx bx-trash'></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Edit Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2><i class='bx bx-edit'></i> Edit Resource</h2>
            <form method="POST" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="resourceId" id="editResourceId">
                <input type="hidden" name="contentType" id="editContentType">
                
                <div class="form-group">
                    <label>Resource Title *</label>
                    <input type="text" name="resourceTitle" id="editTitle" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="resourceDesc" id="editDesc"></textarea>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="category" id="editCategory" required>
                </div>

                <div class="form-group" id="editVideoField" style="display: none;">
                    <label>YouTube URL</label>
                    <input type="url" name="youtube_link" id="editYoutubeLink" placeholder="https://www.youtube.com/watch?v=...">
                    <small style="color: #888; font-size: 12px; display: block; margin-top: 5px;">Leave blank to keep current video</small>
                </div>

                <div class="form-group" id="editArticleField" style="display: none;">
                    <label>Upload New File (Optional)</label>
                    <input type="file" name="file" id="editFile" accept=".pdf,.doc,.docx">
                    <small style="color: #888; font-size: 12px; display: block; margin-top: 5px;">Leave blank to keep current file. Max 10MB</small>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="editResource" class="btn btn-save">
                        <i class='bx bx-save'></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(resource) {
            document.getElementById('editResourceId').value = resource.resourceId;
            document.getElementById('editTitle').value = resource.resourceTitle;
            document.getElementById('editDesc').value = resource.resourceDesc || '';
            document.getElementById('editCategory').value = resource.category;
            document.getElementById('editContentType').value = resource.content_type;
            
            // Show appropriate field based on content type
            const videoField = document.getElementById('editVideoField');
            const articleField = document.getElementById('editArticleField');
            const youtubeLink = document.getElementById('editYoutubeLink');
            const fileInput = document.getElementById('editFile');
            
            videoField.style.display = 'none';
            articleField.style.display = 'none';
            youtubeLink.value = '';
            fileInput.value = '';
            
            if (resource.content_type === 'video') {
                videoField.style.display = 'block';
                youtubeLink.value = resource.file_path;
            } else if (resource.content_type === 'article') {
                articleField.style.display = 'block';
            }
            
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Auto-hide success messages
        setTimeout(function() {
            const successMsg = document.querySelector('.message.success');
            if (successMsg) {
                successMsg.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>