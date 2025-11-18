<?php
session_start();

require_once 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['userId']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}


// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $postId = intval($_GET['id']);
    
    // Get image path before deleting
    $stmt = $connect->prepare("SELECT postImage FROM posts WHERE postId = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete the image file if it exists
        if (!empty($row['postImage']) && file_exists($row['postImage'])) {
            unlink($row['postImage']);
        }
        
        // Delete the post
        $deleteStmt = $connect->prepare("DELETE FROM posts WHERE postId = ?");
        $deleteStmt->bind_param("i", $postId);
        
        if ($deleteStmt->execute()) {
            $_SESSION['success_message'] = "Post deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting post.";
        }
        $deleteStmt->close();
    }
    $stmt->close();
    
    header("Location: admin_posts.php" . (isset($_GET['filter']) ? "?filter=" . urlencode($_GET['filter']) : ""));
    exit;
}

// Get filter parameters
$filterUser = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
$filterCaption = isset($_GET['filter_caption']) ? trim($_GET['filter_caption']) : '';
$filterDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Build query with filters
$query = "SELECT p.*, u.username FROM posts p 
          LEFT JOIN users u ON p.userId = u.userId 
          WHERE 1=1";

$params = [];
$types = "";

if ($filterUser > 0) {
    $query .= " AND p.userId = ?";
    $params[] = $filterUser;
    $types .= "i";
}

if (!empty($filterCaption)) {
    $query .= " AND p.postCaption LIKE ?";
    $params[] = "%$filterCaption%";
    $types .= "s";
}

if (!empty($filterDate)) {
    $query .= " AND DATE(p.createdAt) = ?";
    $params[] = $filterDate;
    $types .= "s";
}

$query .= " ORDER BY p.createdAt DESC";

$stmt = $connect->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params); //expands the array into individual arguments 
}

$stmt->execute();
$result = $stmt->get_result();

// Get all users for filter dropdown
$usersQuery = "SELECT userId, username FROM users ORDER BY username";
$usersResult = $connect->query($usersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="sidebar.css" rel="stylesheet">
    <title>Admin - Manage Posts</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2e7d32;
            font-size: 28px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #3498db;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .filter-section {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .filter-section h2 {
            color: #2e7d32;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #27ae60;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            background: #27ae60;
            color: white;
            flex: 1;
        }

        .btn-filter:hover {
            background: #229954;
        }

        .btn-reset {
            background: #95a5a6;
            color: white;
            flex: 1;
        }

        .btn-reset:hover {
            background: #7f8c8d;
        }

        .table-section {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            padding: 15px;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            color: #495057;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .post-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .post-image:hover {
            transform: scale(1.05);
        }

        .post-caption {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .no-posts {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }

        .stats {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-badge {
            background: #e8f4f8;
            color: #3498db;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }

            .post-image {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="main-content">
        <div class="container">
        <div class="header">
            <h1><i class='bx bx-paper-plane'></i> Post Management</h1>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="filter-section">
            <h2>Filter Posts</h2>
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>User</label>
                    <select name="filter_user">
                        <option value="0">All Users</option>
                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                            <option value="<?php echo $user['userId']; ?>" 
                                <?php echo $filterUser == $user['userId'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Caption Contains</label>
                    <input type="text" name="filter_caption" 
                           placeholder="Search caption..." 
                           value="<?php echo htmlspecialchars($filterCaption); ?>">
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="filter_date" 
                           value="<?php echo htmlspecialchars($filterDate); ?>">
                </div>

                <div class="form-group">
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-filter">Apply Filter</button>
                        <a href="admin_posts.php" class="btn btn-reset">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-section">
            <div class="stats">
                <span class="stat-badge">Total Posts: <?php echo $result->num_rows; ?></span>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>User</th>
                            <th>Caption</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['postId']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($row['postImage']); ?>" 
                                         alt="Post" 
                                         class="post-image"
                                         onclick="openModal('<?php echo htmlspecialchars($row['postImage']); ?>')">
                                </td>
                                <td><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></td>
                                <td>
                                    <div class="post-caption" title="<?php echo htmlspecialchars($row['postCaption']); ?>">
                                        <?php echo htmlspecialchars($row['postCaption']); ?>
                                    </div>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['createdAt'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="admin_post_form.php?id=<?php echo $row['postId']; ?>" 
                                           class="btn btn-edit">Edit</a>
                                        <a href="?action=delete&id=<?php echo $row['postId']; ?><?php echo $filterUser ? '&filter_user=' . $filterUser : ''; ?><?php echo $filterCaption ? '&filter_caption=' . urlencode($filterCaption) : ''; ?><?php echo $filterDate ? '&filter_date=' . $filterDate : ''; ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-posts">
                    <p>No posts found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </section>

    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        function openModal(imageSrc) {
            document.getElementById('imageModal').classList.add('active');
            document.getElementById('modalImage').src = imageSrc;
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$connect->close();
?>