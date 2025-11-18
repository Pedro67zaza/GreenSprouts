<?php
session_start();

include 'db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['userId']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

// Get total users count
$totalUsersQuery = $connect->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $totalUsersQuery->fetch_assoc()['count'];

// Get total posts count
$totalPostsQuery = $connect->query("SELECT COUNT(*) as count FROM posts");
$totalPosts = $totalPostsQuery->fetch_assoc()['count'];

// Get total resources count
$totalResourcesQuery = $connect->query("SELECT COUNT(*) as count FROM resources");
$totalResources = $totalResourcesQuery->fetch_assoc()['count'];


// Get posts this month
$postsThisMonthQuery = $connect->query("SELECT COUNT(*) as count FROM posts WHERE MONTH(createdAt) = MONTH(CURRENT_DATE()) AND YEAR(createdAt) = YEAR(CURRENT_DATE())");
$postsThisMonth = $postsThisMonthQuery->fetch_assoc()['count'];

// Get user distribution (users vs admins)
$usersCountQuery = $connect->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$usersCount = $usersCountQuery->fetch_assoc()['count'];

$adminsCountQuery = $connect->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$adminsCount = $adminsCountQuery->fetch_assoc()['count'];

// Get resource distribution (videos vs articles)
$videosQuery = $connect->query("SELECT COUNT(*) as count FROM resources WHERE content_type = 'video'");
$videosCount = $videosQuery->fetch_assoc()['count'];

$articlesQuery = $connect->query("SELECT COUNT(*) as count FROM resources WHERE content_type = 'article'");
$articlesCount = $articlesQuery->fetch_assoc()['count'];

// Get most active users (top 5)
$activeUsersQuery = $connect->query("
    SELECT u.username, COUNT(p.postId) as post_count 
    FROM users u 
    LEFT JOIN posts p ON u.userId = p.userId 
    GROUP BY u.userId 
    ORDER BY post_count DESC 
    LIMIT 5
");
$activeUsers = $activeUsersQuery->fetch_all(MYSQLI_ASSOC);

// Get recent posts (last 5)
$recentPostsQuery = $connect->query("
    SELECT p.postId, p.postCaption, p.createdAt, u.username 
    FROM posts p 
    LEFT JOIN users u ON p.userId = u.userId 
    ORDER BY p.createdAt DESC 
    LIMIT 5
");
$recentPosts = $recentPostsQuery->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href='sidebar.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

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
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2e7d32;
            font-size: 32px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(46, 125, 50, 0.05);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stat-icon.users { background: #e3f2fd; color: #1976d2; }
        .stat-icon.posts { background: #f3e5f5; color: #7b1fa2; }
        .stat-icon.resources { background: #fff3e0; color: #f57c00; }
        .stat-icon.new-users { background: #e8f5e9; color: #388e3c; }
        .stat-icon.monthly { background: #fce4ec; color: #c2185b; }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 15px;
        }

        .card h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-container {
            margin-top: 20px;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            height: 200px;
            padding: 10px 0;
        }

        .bar {
            flex: 1;
            background: linear-gradient(to top, #2e7d32, #66bb6a);
            border-radius: 8px 8px 0 0;
            position: relative;
            transition: all 0.3s;
            min-height: 20px;
        }

        .bar:hover {
            opacity: 0.8;
        }

        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #666;
            white-space: nowrap;
        }

        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item-info {
            flex: 1;
        }

        .list-item-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 3px;
        }

        .list-item-meta {
            font-size: 12px;
            color: #999;
        }

        .list-item-value {
            font-weight: 600;
            color: #2e7d32;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge.admin {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge.user {
            background: #e8f5e9;
            color: #388e3c;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 10px;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <section class="main-content">
        <div class="header">
            <h1><i class='bx bxs-dashboard'></i> Dashboard Overview</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! Here's what's happening.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class='bx bxs-user'></i>
                </div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon posts">
                    <i class='bx bxs-message-square-detail'></i>
                </div>
                <div class="stat-value"><?php echo $totalPosts; ?></div>
                <div class="stat-label">Total Posts</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon resources">
                    <i class='bx bxs-book-content'></i>
                </div>
                <div class="stat-value"><?php echo $totalResources; ?></div>
                <div class="stat-label">Total Resources</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon monthly">
                    <i class='bx bxs-calendar'></i>
                </div>
                <div class="stat-value"><?php echo $postsThisMonth; ?></div>
                <div class="stat-label">Posts This Month</div>
            </div>
        </div>

        <!-- Charts and Lists -->
        <div class="content-grid">
            <!-- User Distribution -->
            <div class="card">
                <h2><i class='bx bxs-pie-chart-alt-2'></i> User Distribution</h2>
                <div class="chart-container">
                    <div class="bar-chart">
                        <div class="bar" style="height: <?php echo $totalUsers > 0 ? ($usersCount / $totalUsers * 100) : 0; ?>%;">
                            <div class="bar-value"><?php echo $usersCount; ?></div>
                            <div class="bar-label">Users</div>
                        </div>
                        <div class="bar" style="height: <?php echo $totalUsers > 0 ? ($adminsCount / $totalUsers * 100) : 0; ?>%;">
                            <div class="bar-value"><?php echo $adminsCount; ?></div>
                            <div class="bar-label">Admins</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Distribution -->
            <div class="card">
                <h2><i class='bx bxs-bar-chart-alt-2'></i> Resource Distribution</h2>
                <div class="chart-container">
                    <div class="bar-chart">
                        <div class="bar" style="height: <?php echo $totalResources > 0 ? ($videosCount / $totalResources * 100) : 0; ?>%;">
                            <div class="bar-value"><?php echo $videosCount; ?></div>
                            <div class="bar-label">Videos</div>
                        </div>
                        <div class="bar" style="height: <?php echo $totalResources > 0 ? ($articlesCount / $totalResources * 100) : 0; ?>%;">
                            <div class="bar-value"><?php echo $articlesCount; ?></div>
                            <div class="bar-label">Articles</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

         <!-- Most Active Users -->
            <div class="card">
                <h2><i class='bx bxs-star'></i> Most Active Users</h2>
                <?php if (!empty($activeUsers)): ?>
                    <?php foreach ($activeUsers as $user): ?>
                        <div class="list-item">
                            <div class="list-item-info">
                                <div class="list-item-title"><?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            <div class="list-item-meta"><?php echo $user['post_count']; ?> posts</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-user'></i>
                        <p>No user activity yet</p>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Recent Posts -->
        <div class="card">
            <h2><i class='bx bxs-message-dots'></i> Recent Posts</h2>
            <?php if (!empty($recentPosts)): ?>
                <?php foreach ($recentPosts as $post): ?>
                    <div class="list-item">
                        <div class="list-item-info">
                            <div class="list-item-title">
                                <?php 
                                $caption = $post['postCaption'] ?? 'No caption';
                                echo htmlspecialchars(strlen($caption) > 50 ? substr($caption, 0, 50) . '...' : $caption); 
                                ?>
                            </div>
                            <div class="list-item-meta">
                                by <?php echo htmlspecialchars($post['username'] ?? 'Unknown'); ?> • 
                                <?php echo date('M d, Y', strtotime($post['createdAt'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class='bx bx-message'></i>
                    <p>No posts yet</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>