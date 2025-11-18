<?php
session_start();

include('db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    // User not logged in, redirect to login page
    header("Location: /FYP/login.php");
    exit();
}

$userId = $_SESSION['userId'];
$username = htmlspecialchars($_SESSION['username'] ?? ''); 
$role = htmlspecialchars($_SESSION['role'] ?? 'user');

    $typeFilter = isset($_GET['type']) ? $_GET['type'] : 'all';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $query = "SELECT * FROM resources WHERE 1";

    if($typeFilter === 'video') {
        $query .= " AND content_type='video'";
    }
    elseif($typeFilter === 'article') {
        $query .= " AND content_type='article'";
    }

    if($search !== '') {
        $searchEscaped = $connect->real_escape_string($search);
        $query .= " AND (resourceTitle LIKE '%$searchEscaped%' OR category LIKE '%$searchEscaped%')";
    }

    $query .= " ORDER BY uploaded_at DESC";
    $result = $connect->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - GreenSprouts</title>

    <link href="sidebar.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            display: flex;
            flex-direction: row;
            background-color: #f4f9f4;
        }

        .main-content-repository {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background-color: #f4f9f4;
            transition: var(--tran-05);
            overflow-y: auto;
            height: 100vh;
        }

        .sidebar.close ~ .main-content-repository {
            margin-left: 88px;
        }

        .main-content-repository .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .main-content-repository .filter-bar a {
            padding: 10px 15px;
            background: #e8f5e9;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            color: #2e7d32;
        }

        .main-content-repository .filter-bar a:hover {
            background: #c8e6c9;
            color: #1b5e20;
        }
        .main-content-repository .filter-bar a.active {
            background: #2e7d32;
            color: #fff;
        }

        .main-content-repository .search-bar input[type="text"] {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            min-width: 200px;
        }

        .main-content-repository .resource-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            padding-bottom: 40px;
        }
        .main-content-repository .resource-item {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 15px;
        }
        iframe {
            width: 100%;
            height: 250px;
            border-radius: 10px;
        }
        .main-content-repository .search-bar button {
            padding: 10px 15px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 6px;
            border: 2px solid transparent;
            background: #fff;
        }
        .main-content-repository .search-bar button:hover {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 2px solid #2e7d32;
        }
        .main-content-repository .header {
            text-align: center;
            padding: 20px 10px;
            background-color: #2e7d32;
            border-radius: 8px;
            color: #e8f5e9;
            margin-bottom: 20px;
        }


    </style>
</head>
<body>

        <?php
            include 'sidebar.php';
        ?>

    <div class="main-content-repository">
        <h2 class="header">GreenSprouts Repository</h2>

    <div class="filter-bar">
        <a href="resources.php" class="<?= $typeFilter === 'all' ? 'active' : '' ?>">All</a>
        <a href="resources.php?type=video" class="<?= $typeFilter === 'video' ? 'active' : '' ?>">Video</a>
        <a href="resources.php?type=article" class="<?= $typeFilter === 'article' ? 'active' : '' ?>">Article</a>

        <form action="resources.php" method="GET" class="search-bar">
            <input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>">
            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="resource-container">
        <?php while($row = $result->fetch_assoc()) { ?>
        <div class="resource-item">
            <h3><?= htmlspecialchars($row['resourceTitle']); ?></h3>
            <p><?= htmlspecialchars($row['resourceDesc']); ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($row['category']); ?></p>

            <?php if($row['content_type']==='video') {
                $link = str_replace("watch?v=", "embed/", $row['file_path']);
            ?>
            <iframe src="<?= htmlspecialchars($link); ?>" allowfullscreen></iframe>
           <?php  }  else { ?>
            <a href="<?= htmlspecialchars($row['file_path']); ?>" target="_blank">View Article</a>
            <?php } ?>
        </div>
    <?php } ?>
    </div>
    </div>
</body>
</html>