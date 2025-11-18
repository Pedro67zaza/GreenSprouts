<?php
session_start();

    require_once 'db_connect.php';

    if(!isset($_SESSION['userId'])) {
        header("Location: /FYP/login.php");
        exit();
    }

    $userId = $_SESSION['userId'];
    $username = htmlspecialchars($_SESSION['username'] ?? '');
    $email = filter_var(trim($_SESSION['email'] ?? ''), FILTER_SANITIZE_EMAIL);

// Fetch all posts with user information
$query = $connect->prepare("
    SELECT 
        p.postId, 
        p.postImage, 
        p.postCaption, 
        p.createdAt,
        u.username
    FROM posts p
    LEFT JOIN users u ON p.userId = u.userId
    ORDER BY p.createdAt DESC
");
$query->execute();
$result = $query->get_result();

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    if ($diff < 2592000) return floor($diff / 604800) . 'w ago';
    return date('M j, Y', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - GreenSprouts</title>
    <link rel="stylesheet" href="sidebar.css">
    <script src="https://kit.fontawesome.com/67a65874b9.js" crossorigin="anonymous"></script>
    <style>
        * {
            padding: 0;
            margin: 0;
            font-family: 'Poppins','sans-serif';
            box-sizing: border-box;
        }
        
        body {
            background: #fafafa;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: margin-left var(--tran-05);
            flex: 1;
            padding-bottom: 50px;
            background-color: #f4f9f4;
            transition:  var(--tran-05);
            overflow-y: auto;
        }
        
        .sidebar.close ~ .main-content {
            margin-left: 88px;
        }
        
        .feed-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Feed Section - Middle */
        .feed-section {
            width: 100%;
        }
        
        .feed-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .feed-header h1 {
            color: #2e7d32;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .feed-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .post-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #2e7d32;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-info {
            flex: 1;
        }
        
        .username {
            font-weight: 600;
            font-size: 0.95rem;
            color: #262626;
        }
        
        .post-time {
            font-size: 0.8rem;
            color: #8e8e8e;
        }
        
        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            display: block;
        }
        
        .post-caption {
            padding: 20px;
            line-height: 1.6;
            color: #262626;
            font-size: 0.95rem;
        }
        
        .no-posts {
            background: white;
            padding: 60px 20px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .no-posts i {
            font-size: 4rem;
            color: #c8e6c9;
            margin-bottom: 20px;
        }
        
        .no-posts h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-posts p {
            color: #999;
        }
        
        /* Create Post Section - Right */
        .create-post-section {
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .create-post-card {
            background: #345c34ff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .create-post-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .create-post-header h2 {
            color: white;
            font-size: 1.4rem;
            margin-bottom: 5px;
        }
        
        .create-post-header p {
            color: rgba(255,255,255,0.9);
            font-size: 0.85rem;
        }
        
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
            font-size: 0.85rem;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .upload-area {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            transform: scale(1.02);
        }
        
        #input-file {
            display: none;
        }
        
        #img-view {
            height: 200px;
            border-radius: 12px;
            border: 2px dashed #bbb5ff;
            background: #f7f8ff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        #img-view img {
            width: 60px;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        #img-view p {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            margin-bottom: 5px;
        }
        
        #img-view span {
            font-size: 0.75rem;
            color: #999;
        }
        
        .form-caption {
            width: 100%;
            height: 120px;
            border: none;
            outline: none;
            border-radius: 10px;
            padding: 15px;
            background: white;
            resize: vertical;
            margin-bottom: 15px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }
        
        .form-caption::placeholder {
            color: #999;
        }
        
        .btnSubmit {
            width: 100%;
            background: #2e7d32;
            color: white;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btnSubmit:hover {
            background: #1b5e20;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btnSubmit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .feed-container {
                grid-template-columns: 1fr 350px;
                gap: 20px;
            }
        }
        
        @media (max-width: 900px) {
            .main-content {
                margin-left: 108px;
            }
            
            .feed-container {
                grid-template-columns: 1fr;
            }
            
            .create-post-section {
                position: static;
                order: -1;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <section class="main-content">
        <div class="feed-container">
            <!-- Feed Section - Middle -->
            <div class="feed-section">
                <div class="feed-header">
                    <h1>🌱 Agricultural Journey Feed</h1>
                    <p>Share your farming experiences, crops, and agricultural insights with the community</p>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                    <?php while($post = $result->fetch_assoc()): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <div class="user-avatar">
                                <img src="Images/userprofile.png" alt="user">
                            </div>
                            <div class="user-info">
                                <div class="username"><?= htmlspecialchars($post['username'] ?? 'Anonymous') ?></div>
                                <div class="post-time"><?= timeAgo($post['createdAt']) ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($post['postImage'])): ?>
                        <img src="<?= htmlspecialchars($post['postImage']) ?>" alt="post" class="post-image">
                        <?php endif; ?>
                        
                        <?php if (!empty($post['postCaption'])): ?>
                        <div class="post-caption">
                            <?= nl2br(htmlspecialchars($post['postCaption'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-posts">
                        <i class="fas fa-seedling"></i>
                        <h3>No posts yet</h3>
                        <p>Be the first to share your agricultural journey!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Create Post Section - Right -->
            <div class="create-post-section">
                <div class="create-post-card">
                    <div class="create-post-header">
                        <h2>Share Your Journey</h2>
                        <p>Post updates about your crops and farm</p>
                    </div>
                    
                    <div class="success-message message" id="successMessage"></div>
                    <div class="error-message message" id="errorMessage"></div>
                    
                    <form id="postForm" enctype="multipart/form-data">
                        <label for="input-file" class="upload-area">
                            <input type="file" accept="image/*" name="image" id="input-file" required>
                            <div id="img-view">
                                <img src="Images/icon.png" alt="upload">
                                <p>Click or drag to upload image</p>
                                <span>JPG, PNG, GIF or WebP (Max 5MB)</span>
                            </div>
                        </label>
                        
                        <textarea 
                            class="form-caption" 
                            name="caption" 
                            placeholder="Share your agricultural story... What are you growing? Any tips or experiences to share?"
                            maxlength="1000"
                        ></textarea>
                        
                        <button type="submit" class="btnSubmit" id="btnSubmit">
                            <i class="fas fa-paper-plane"></i> Share Post
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Image upload preview
        const inputFile = document.getElementById("input-file");
        const imageView = document.getElementById("img-view");
        const postForm = document.getElementById("postForm");
        const submitBtn = document.getElementById("btnSubmit");
        const successMessage = document.getElementById("successMessage");
        const errorMessage = document.getElementById("errorMessage");

        inputFile.addEventListener("change", uploadImage);

        function uploadImage() {
            if (inputFile.files && inputFile.files[0]) {
                const imgLink = URL.createObjectURL(inputFile.files[0]);
                imageView.innerHTML = "";
                imageView.style.backgroundImage = `url(${imgLink})`;
                imageView.style.backgroundSize = "cover";
                imageView.style.backgroundPosition = "center";
                imageView.style.border = "none";
            }
        }

        // Drag and drop
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener("dragover", (e) => {
            e.preventDefault();
            uploadArea.style.transform = "scale(1.02)";
        });
        
        uploadArea.addEventListener("dragleave", () => {
            uploadArea.style.transform = "scale(1)";
        });
        
        uploadArea.addEventListener("drop", function(e) {
            e.preventDefault();
            uploadArea.style.transform = "scale(1)";
            inputFile.files = e.dataTransfer.files;
            uploadImage();
        });

        // Post form submission
        postForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

            const formData = new FormData(postForm);

            fetch('post_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    successMessage.textContent = '✓ Post shared successfully!';
                    successMessage.style.display = 'block';
                    postForm.reset();
                    imageView.style.backgroundImage = '';
                    imageView.style.border = '2px dashed #bbb5ff';
                    imageView.innerHTML = `
                        <img src="Images/icon.png" alt="upload">
                        <p>Click or drag to upload image</p>
                        <span>JPG, PNG, GIF or WebP (Max 5MB)</span>
                    `;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    errorMessage.textContent = '✗ ' + (data.message || 'Failed to create post');
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                errorMessage.textContent = '✗ Network error: ' + error.message;
                errorMessage.style.display = 'block';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Share Post';
            });
        });
    </script>
</body>
</html>