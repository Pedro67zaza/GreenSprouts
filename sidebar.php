<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php'; // Ensure this file correctly establishes the $connect variable

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    // User not logged in, redirect to login page
    header("Location: /FYP/login.php");
    exit();
}

// If you reach here, session exists
$userId = $_SESSION['userId'];
$username = htmlspecialchars($_SESSION['username'] ?? ''); 
$email = filter_var(trim($_SESSION['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$role = $_SESSION['role'] ?? 'user';

$isAdmin = ($role === 'admin');
$isSuperAdmin = ($role === 'superadmin');
?>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
     <link rel="stylesheet" href="sidebar.css">
     <script src="https://kit.fontawesome.com/67a65874b9.js" crossorigin="anonymous"></script>

    <nav class="sidebar close">
        <header>
            <div class="image-text">
                <span class="image">
                    <!--Insert the user's img here-->
                    <?php if($isAdmin || $isSuperAdmin): ?>
                    <img src="Images/adminprofile.png" alt="admin profile">
                    <?php else: ?>
                    <img src="Images/userprofile.png" alt="User profile">
                    <?php endif; ?>
                </span>
                <div class="text header-text">
                    <!--Insert the username here-->
                    <span class="name"><?= $username ?></span>
                    <!--Insert the user's role here-->
                    <span class="role"><?= htmlspecialchars($email) ?></span>
                </div>
            </div>

            <i class='bx bx-chevron-right toggle'></i>
        </header>

        <div class="menu-bar">
            <div class="menu">
                <ul class="menu-links">
                    <?php if($isAdmin || $isSuperAdmin): ?>
                     <li class="nav-link">
                        <a href="dashboard.php">
                            <i class='bx bx-line-chart icon'></i>
                            <span class="text nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="admin_helpCenter.php">
                            <i class='bx bxs-message-alt-error icon'></i>
                            <span class="text nav-text">Inquiries</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="admin_posts.php">
                            <i class='bx bxs-image icon'></i>
                            <span class="text nav-text">Posts</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="manage_resources.php">
                            <i class='bx bxs-folder icon'></i>
                            <span class="text nav-text">Repository</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="admin_page.php">
                            <i class='bx bxs-user-circle icon'></i>
                            <span class="text nav-text">Users</span>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-link">
                        <a href="logbookfirstpage.php">
                            <i class='bx bx-book-content icon'></i>
                            <span class="text nav-text">Logbook</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="plant_search.php">
                            <i class='bx bx-search icon'></i>
                            <span class="text nav-text">Search Plant</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="feed.php">
                            <i class='bx bx-message-alt-detail icon'></i>
                            <span class="text nav-text">Forum</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="resources.php">
                            <i class="fa-solid fa-lightbulb icon"></i>
                            <span class="text nav-text">Resources</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="profile.php">
                            <i class='bx bxs-user-circle icon'></i>
                            <span class="text nav-text">User Profile</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="helpCenter.php">
                            <i class='bx bxs-help-circle icon' ></i>
                            <span class="text nav-text">Help Center</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
            </div>
            <div class="bottom-content">
                <li class="nav-link">
                    <a href="logout.php">
                        <i class='bx bx-log-out icon'></i>
                        <span class="text nav-text">Logout</span>
                    </a>
                </li>
            </div>


        </div>

    </nav>


    <script>
        const body = document.querySelector("body"),
              sidebar = body.querySelector(".sidebar"),
              toggle = body.querySelector(".toggle"),
              searchBtn = body.querySelector(".search-box"),
              modeSwitch = body.querySelector(".toggle-switch"),
              modetext = body.querySelector(".mode-text");

              toggle.addEventListener("click", () =>{
                sidebar.classList.toggle("close");
              });

              searchBtn.addEventListener("click", () =>{
                sidebar.classList.remove("close");
              });

              modeSwitch.addEventListener("click", () =>{
                body.classList.toggle("dark");

                if(body.classList.contains("dark")) {
                    modetext.innerHTML = "Light Mode"
                } else {
                    modetext.innerHTML = "Dark Mode"
                }

              });


    </script>