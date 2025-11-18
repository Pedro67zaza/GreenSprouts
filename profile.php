<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$userId = $_SESSION['userId'];
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Reset Password
    if (isset($_POST['resetPassword'])) {
        $currentPassword = trim($_POST['currentPassword']);
        $newPassword = trim($_POST['newPassword']);
        $confirmPassword = trim($_POST['confirmPassword']);
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'All fields are required';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 6) {
            $message = 'Password must be at least 6 characters';
            $messageType = 'error';
        } else {
            // Verify current password
            $stmt = $connect->prepare("SELECT password FROM users WHERE userId = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($currentPassword, $user['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $connect->prepare("UPDATE users SET password = ? WHERE userId = ?");
                $updateStmt->bind_param("si", $hashedPassword, $userId);
                
                if ($updateStmt->execute()) {
                    $message = 'Password updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update password';
                    $messageType = 'error';
                }
                $updateStmt->close();
            } else {
                $message = 'Current password is incorrect';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
    
    // Delete Account
    if (isset($_POST['deleteAccount'])) {
        $password = trim($_POST['confirmDeletePassword']);
        
        if (empty($password)) {
            $message = 'Password is required to delete account';
            $messageType = 'error';
        } else {
            // Verify password
            $stmt = $connect->prepare("SELECT password FROM users WHERE userId = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Delete user's posts first
                $deletePostsStmt = $connect->prepare("DELETE FROM posts WHERE userId = ?");
                $deletePostsStmt->bind_param("i", $userId);
                $deletePostsStmt->execute();
                $deletePostsStmt->close();
                
                // Delete user account
                $deleteUserStmt = $connect->prepare("DELETE FROM users WHERE userId = ?");
                $deleteUserStmt->bind_param("i", $userId);
                
                if ($deleteUserStmt->execute()) {
                    $deleteUserStmt->close();
                    session_destroy();
                    header("Location: login.php?deleted=1");
                    exit();
                } else {
                    $message = 'Failed to delete account';
                    $messageType = 'error';
                }
            } else {
                $message = 'Incorrect password';
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
    
    // Delete Post
    if (isset($_POST['deletePost'])) {
        $postId = intval($_POST['postId']);
        
        // Verify post belongs to user
        $verifyStmt = $connect->prepare("SELECT postId FROM posts WHERE postId = ? AND userId = ?");
        $verifyStmt->bind_param("ii", $postId, $userId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        
        if ($verifyResult->num_rows === 1) {
            $deleteStmt = $connect->prepare("DELETE FROM posts WHERE postId = ? AND userId = ?");
            $deleteStmt->bind_param("ii", $postId, $userId);
            
            if ($deleteStmt->execute()) {
                $message = 'Post deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete post';
                $messageType = 'error';
            }
            $deleteStmt->close();
        } else {
            $message = 'Post not found';
            $messageType = 'error';
        }
        $verifyStmt->close();
    }
}

// Fetch user information
$userStmt = $connect->prepare("SELECT username, email, role FROM users WHERE userId = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

// Fetch user's posts
$postsStmt = $connect->prepare("SELECT postId, postImage, postCaption, createdAt FROM posts WHERE userId = ? ORDER BY createdAt DESC");
$postsStmt->bind_param("i", $userId);
$postsStmt->execute();
$postsResult = $postsStmt->get_result();
$posts = $postsResult->fetch_all(MYSQLI_ASSOC);
$postsStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
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
            background: #f4f9f4;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: #2e7d32;
            z-index: 0;
        }

        .profile-header {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-img img{
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e0e0e0;
            border: 4px solid white;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #666;
        }

        .profile-info h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: #666;
            font-size: 14px;
        }

        .section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .section h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #2e7d32;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2e7d32;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2e7d32;
            color: white;
        }

        .btn-primary:hover {
            background: #1b5e20;
        }

        .btn-danger {
            background: #f5576c;
            color: white;
        }

        .btn-danger:hover {
            background: #d63447;
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .post-item {
            position: relative;
            aspect-ratio: 1/1;
            border-radius: 12px;
            overflow: hidden;
            background: #e0e0e0;
        }

        .post-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-item form {
            position: absolute;
            top: 8px;
            right: 8px;
        }

        .post-item .btn-delete {
            padding: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s;
        }

        .post-item:hover .btn-delete {
            opacity: 1;
        }

        .post-item .btn-delete:hover {
            background: #f5576c;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 10px;
            opacity: 0.5;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="logbookfirstpage.php" class="back-btn">
            <i class='bx bx-arrow-back'></i> Back to Home
        </a>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-img">
                    <img src="Images/userprofile.png" alt="user profile">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="text-transform: capitalize; color: #2e7d32; font-weight: 500;">
                        <?php echo htmlspecialchars($user['role']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Reset Password Section -->
        <div class="section">
            <h3><i class='bx bx-lock-alt'></i> Reset Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="newPassword" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirmPassword" required>
                </div>
                <button type="submit" name="resetPassword" class="btn btn-primary">
                    Update Password
                </button>
            </form>
        </div>

        <!-- My Posts Section -->
        <div class="section">
            <h3><i class='bx bx-image'></i> My Posts (<?php echo count($posts); ?>)</h3>
            
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class='bx bx-image'></i>
                    <p>No posts yet</p>
                </div>
            <?php else: ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-item">
                            <img src="<?php echo htmlspecialchars($post['postImage']); ?>" alt="Post">
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <input type="hidden" name="postId" value="<?php echo $post['postId']; ?>">
                                <button type="submit" name="deletePost" class="btn-delete">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delete Account Section -->
        <div class="section">
            <h3><i class='bx bx-trash'></i> Delete Account</h3>
            <p style="color: #666; margin-bottom: 20px;">
                Warning: This action cannot be undone. All your posts and data will be permanently deleted.
            </p>
            <form method="POST" onsubmit="return confirm('Are you ABSOLUTELY sure? This cannot be undone!');">
                <div class="form-group">
                    <label>Enter your password to confirm</label>
                    <input type="password" name="confirmDeletePassword" required>
                </div>
                <button type="submit" name="deleteAccount" class="btn btn-danger">
                    Delete My Account
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successMsg = document.querySelector('.message.success');
            if (successMsg) {
                successMsg.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>