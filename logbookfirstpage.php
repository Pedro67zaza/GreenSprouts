<?php 
session_start();

include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    // User not logged in, redirect to login page
    header("Location: /FYP/login.php");
    exit();
}

$userId = $_SESSION['userId'];
$username = htmlspecialchars($_SESSION['username'] ?? ''); 
$email = htmlspecialchars($_SESSION['email'] ?? '');
$role = htmlspecialchars($_SESSION['role'] ?? 'user');

// Fetch projects for the logged-in user
$stmt = $connect->prepare("SELECT projectID, projectImage, projectName, projectTag FROM projects WHERE userId = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook - Project Page</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=arrow_forward" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/67a65874b9.js" crossorigin="anonymous"></script>
    
    <!-- N8N Chat Widget - Single Import -->
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
        font-weight: bold;
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

    .card-wrapper {
        max-width: 1100px;
        margin: 0 60px 35px;
        padding: 20px 10px;
    }

    .card-list .card-item {
        list-style: none;
    }

    .card-link {
        user-select: none;
        display: block;
        background: var(--sidebar-color);
        padding: 18px;
        border-radius: 12px;
        text-decoration: none;
        border: 2px solid transparent;
        box-shadow: 0 10px 10px rgba(0, 0, 0, 0.05);
        transition: var(--tran-03);
    }

    .card-list .card-item .card-link:active {
        cursor: grabbing;
    }

    .card-link:hover {
        border-color: #2e7d32;
    }

    .card-list .card-link .card-image {
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
        border-radius: 10px;
    }

    .card-list .card-link .badge {
        color: var(--sidebar-color);
        padding: 8px 16px;
        font-size: 0.95rem;
        font-weight: 500;
        margin: 16px 0 18px;
        background: var(--primary-color);
        width: fit-content;
        border-radius: 50px;
    }

    .card-list .card-link .badge.edible {
        color: white;
        background: #4f0606ff;
    }

    .card-list .card-link .badge.herb {
        color: white;
        background: #335f2fff;
    }

    .card-list .card-link .badge.sustainable {
        color: white;
        background: #c15229ff;
    }
    .card-list .card-link .badge.experiment {
        color: white;
        background: #6f2348ff;
    }
    .card-list .card-link .badge.urban {
        color: white;
        background: #393586ff;
    }

    .card-list .card-link .card-title {
        font-size: 1.19rem;
        color: black;
        font-weight: 600;
    }

    .card-list .card-link .card-button {
        height: auto;
        width: auto;
        border-radius: 0.5rem;
        margin: 30px 0 5px;
        border: 2px solid #e8f5e9;
        color: #e8f5e9;
        background: #2e7d32;
        cursor: pointer;
        display:flex;
        transition: var(--tran-04);
        align-items: center;
    }

    .card-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 2px solid #2e7d32;
        color: #2e7d32;
        background: none;
        border-radius: 8px;
        padding: 8px 16px;
        cursor: pointer;
        transition: var(--tran-04);
    }

    .card-button a {
        color: inherit;
        text-decoration: none;
        height: 100%;
    }

    .card-button i {
        margin-right: 10px;
    }


    .card-list .card-link .card-button:hover {
        background: #e8f5e9;
        color: #2e7d32;
        border: 2px solid #2e7d32;
    }

    .card-wrapper .swiper-pagination-bullet {
        height: 13px;
        width: 13px;
        opacity: 0.5;
        background: var(--primary-color);
    }

    .card-wrapper .swiper-pagination-bullet-active {
        opacity: 1;
    }

    .card-wrapper .swiper-slide-button {
        color: var(--primary-color);
        margin-top: -35px;
    }

    @media screen and (max-width: 768px) {
        .card-wrapper {
            margin: 0 10px 25px;
        }

        .card-wrapper .swiper-slide-button {
            display: none;
        }
    }

    .container {
        padding: 10px 15px;
        transition: var(--tran-03);
        right: 25px;
        width: 100%;
        min-height: 100vh;
    }

    .container h2 {
        color: #e8f5e9;
        background: #2e7d32;
        width: 100%;
        height: auto;
        padding: 20px 10px;
        border-radius: 0.5rem;
        font-weight: bold;
    }

    .createBtn {
        margin: 20px;
        padding: 10px 15px; 
    }

    .icon-bottom {
        display: flex;
        justify-content: flex-end;
        margin-left: 10px;
        align-items: center;
    }
    .icon-bottom a {
        text-decoration: none;
        color: gray;
    }
    .icon-bottom a:hover {
        color: #2e7d32;
    }

        /* Ensure chat widget is accessible and visible */
        /* N8N Chat Widget Custom Styling */
    #n8n-chat {
        z-index: 9999 !important;
    }



    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="main-content" id="mainContent">
        <div class="container swiper">
            <h2>GreenSprouts Projects</h2>
               <a href="/FYP/createProject.php" class='btn btn-success btn-sm createBtn'>Create New Project</a>
        <div class="card-wrapper">
            <ul class="card-list swiper-wrapper">
             <?php 

             if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) { 
                // Check and sanitize projectImage path
                $projectImage = (!empty($row['projectImage'])) ? htmlspecialchars($row['projectImage']) : 'Images/imageNotFound.png';
                $projectID = htmlspecialchars($row['projectID']);
                $projectTag = htmlspecialchars($row['projectTag']);
                $projectName = htmlspecialchars($row['projectName']);
                ?>
                <li class="card-item swiper-slide">
                    <div class="card-link">
                        <img src="<?php echo $projectImage; ?>" alt="Card Sample" class="card-image">
                        <p class="badge <?php echo $projectTag; ?>"><?php echo $projectTag; ?></p>
                        <h3 class="card-title"><?php echo $projectName; ?></h3>
                        <button class="card-button">
                            <a href='/FYP/logbooktablepage.php?projectID=<?php echo $projectID;?>'><i class='bx bxs-right-arrow'></i>See the project details</a>
                        </button>
                        <div class="icon-bottom">
                            <a href="/FYP/editProject.php?projectID=<?php echo $projectID; ?>"><i class="fa-solid fa-pencil"></i></a>
                            <a href="/FYP/deleteProject.php?projectID=<?php echo $projectID; ?>"><i class="fa-solid fa-trash"></i></a>
                        </div>
                    </div>
                </li>
            <?php } 
             } else {
                ?> <li class="swiper-slide text-center p-5">
                    <div style="
                    border: 2px solid #e9ecef;
                    border-radius: 12px;
                    padding: 40px 20px;
                    min-height: 250px;
                    width: 800px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    ">
                    <p style="
                    color: #6c757d;
                    font-size: 1.1rem;
                    margin: 0;">
                    <i class="fa-solid fa-circle-question"></i>You have no project to track, create one now
                    </p>
                    </div>
                </li>
        <?php  } ?>
             
            </ul>

            <div class="swiper-pagination"></div>
            <div class="swiper-slide-button swiper-button-next"></div>
            <div class="swiper-slide-button swiper-button-prev"></div>

        </div>
        </div>

    </section>

    <!-- Initialize Swiper -->
    <script>
        new Swiper('.card-wrapper', {
            loop: true,
            spaceBetween: 30,

            // Pagination bullets
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true
            },

            // Navigation arrows
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },

            //responsive breakpoints
            breakpoints: {
                0: {
                    slidesPerView: 1
                },
                768: {
                    slidesPerView: 2
                },
                1024: {
                    slidesPerView: 3
                },
            }
        });
    </script>

    <!-- N8N Chat Widget - Initialize after page loads -->
    <script type="module">
        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

        // Wait for DOM to be ready
        window.addEventListener('DOMContentLoaded', () => {
            createChat({
                webhookUrl: 'http://localhost:5677/webhook/0f6e0f89-8586-4b2b-afca-40e411f00bcf/chat',
                initialMessages: [
                    'Hello! How can I help you with your agricultural journey today?'
                ],
                i18n: {
                    en: {
                        title: 'GreenSprouts Assistant',
                        subtitle: 'Ask me anything about agriculture',
                        footer: '',
                        getStarted: 'Start Chat',
                        inputPlaceholder: 'Type your message...',
                    }
                }

            });
        });
    </script>
</body>
</html>

<?php
// Close connections at the end of the script
$stmt->close();
$connect->close();
?>