<?php
session_start();

require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    // User not logged in, redirect to login page
    header("Location: /FYP/login.php");
    exit();
}

$userId = $_SESSION['userId'];

$projectID = "";
$projectImage = "";
$projectName = "";
$projectTag = "";
$plantName = "";

$errorMessage = "";
$successMessage = "";

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    if(!isset($_GET['projectID'])) {
        header("Location: /FYP/logbookfirstpage.php");
        exit;
    }

    $projectID = $_GET['projectID'];

    $sql = $connect->prepare("SELECT * FROM projects WHERE projectID = ?");
    $sql->bind_param("i", $projectID);
    $sql->execute();
    $result = $sql->get_result();
    $row = $result->fetch_assoc();

    if(!$row) {
        header("Location: /FYP/logbookfirstpage.php");
        exit;
    }
    $projectImage = $row['projectImage'];
    $projectName = $row['projectName'];
    $projectTag = $row['projectTag'];
    $plantName = $row['plantName'];
}
else {
    $projectID = $_POST['projectID'];
    $projectName = $_POST['projectName'];
    $projectTag = $_POST['projectTag'];
    $plantName = $_POST['plantName'];

    do {
        if(empty($projectName) || empty($projectTag) || empty($plantName)) {
            $errorMessage = "All the fields must be entered";
            break;
        }

        $sql = $connect->prepare("UPDATE projects SET projectName = ?, projectTag = ?, plantName = ? WHERE projectID = ?");
        $sql->bind_param("sssi", $projectName, $projectTag, $plantName, $projectID);
        $result = $sql->execute();

        if(!$result) {
            $errorMessage = "Invalid query: " . $connect->error;
            break;
        }

        if(!empty($_FILES['projectImage']['name'])) {
            $projectImage = $_FILES['projectImage']['name'];
            move_uploaded_file($_FILES['projectImage']['tmp_name'], "Images/" . $projectImage);
            
            $imgSql = $connect->prepare("UPDATE projects SET projectImage = ? WHERE projectID = ?");
            $imagePath = "Images/" . $projectImage;
            $imgSql->bind_param("si", $imagePath, $projectID);
            $imgSql->execute();
        }

        $successMessage = "Project updated successfully";

        header("Location: /FYP/logbookfirstpage.php");
        exit;

    } while(false);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #2e7d32;

        }

        .container {
            position: relative;
            max-width: 700px;
            width: 100%;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .container header {
            font-size: 1.5rem;
            color: #333;
            font-weight: 500;
            text-align: center;
        }
        .container .form {
            margin-top: 20px;
        }
        .form .input-box {
            width: 100%;
            margin-top: 20px;
        }
        .input-box label {
            color: #333;

        }
        .form .input-box input {
            position: relative;
            height: 50px;
            width: 100%;
            outline: none;
            font-size: 1rem;
            color: #707070;
            margin-top: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0 15px;
        }
        .form .column {
            display: flex;
            column-gap: 15px;
        }

        .form .column select {
            position: relative;
            height: 50px;
            width: 100%;
            outline: none;
            font-size: 1rem;
            color: #707070;
            margin-top: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 0 15px;
        }
        .form .input-box textArea {
            position: relative;
            width: 100%;
            height: 100px;
            outline: none;
            font-size: 1rem;
            color: #707070;
            margin-top: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 15px;
        }

        .form button {
            height: 55px;
            width: 100%;
            color: #fff;
            font-size: 1rem;
            border: none;
            margin-top: 20px;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 400;
            background-color: #2e7d32;
        }

        .form button:hover {
            background-color: #1b5e20;
            transition: all 0.3s ease;
        }
        .form a {
            display: flex;
            text-align: center;
            justify-content: center;
            text-decoration: none;
            margin-top: 10px;
            color: #2e7d32;
            border: 2px solid #2e7d32;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1rem;
            font-weight: 400;
        }

        .suggestions {
            border: 1px solid #ccc;
            max-width: 300px;
            background: white;
            position: absolute;
            z-index: 1000;
        }

        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background: #f0f0f0;
        }

        .suggestion-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        /* Make it responsive */
        @media screen and (max-width: 500px) {
            .form .column {
                flex-wrap: wrap;
            }
        }
    </style>



</head>
<body>

    <div class="container my-5">

        <?php
        if(!(empty($errorMessage))) {
            echo "
            <div class='alert alert-warning alert-dismissable fade show' role='alert'>
                <strong>$errorMessage</strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>

            </div>
            ";
        }
        else if (!(empty($successMessage))) {
             echo "
            <div class='alert alert-success alert-dismissable fade show' role='alert'>
                <strong>$successMessage</strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>

            </div>
            ";
        }
        ?>

      <section class="container">
        <header>Edit Project</header>
        <form action="" class="form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="projectID" value="<?php echo $projectID; ?>">
            <div class="input-box">
                <label for="">Image (Optional)</label>
                <input type="file" placeholder="Upload your file here" name="projectImage">
            </div>
            <div class="input-box">
                <label for="">Plant Name: </label>
                <input type="text" id="plantName" placeholder="Enter the plant name" value="<?php echo $plantName; ?>" name="plantName" autocomplete="off" required>
                <div id="suggestions" class="suggestions">

                </div>
            </div>
            <div class="column">
                <div class="input-box">
                    <label for="">Project Name:</label>
                    <input type="text" name="projectName" value="<?php echo $projectName; ?>" placeholder="Enter the project name" required>
                </div>
                <div class="input-box">
                    <label for="">Category</label>
                    <select name="projectTag" id="category">
                        <option value="edible" <?php echo ($projectTag == 'edible') ? 'selected' : ''; ?>>Edible Farming</option>
                        <option value="herb" <?php echo ($projectTag == 'herb') ? 'selected' : ''; ?>>Medicinal or Herbal Farming</option>
                        <option value="sustainable" <?php echo ($projectTag == 'sustainable') ? 'selected' : ''; ?>>Sustainable Farming</option>
                        <option value="experiment" <?php echo ($projectTag == 'experiment') ? 'selected' : ''; ?>>Experimental Farming</option>
                        <option value="urban" <?php echo ($projectTag == 'urban') ? 'selected' : ''; ?>>Urban or Container Farming</option>
                    </select>
                </div>
            </div>
            <button type="submit">Save</button>
            <a href="logbookfirstpage.php">Cancel</a>
        </form>
      </section>
    </div>

    <script>
        const input = document.getElementById("plantName");
        const suggestionBox = document.getElementById("suggestions");
        let debounceTimeout;

        input.addEventListener("input", () => {
            clearTimeout(debounceTimeout);
            const query = input.value.trim();

            if(query.length < 2) {
                suggestionBox.innerHTML = "";
                return;
            }
            debounceTimeout = setTimeout(async() => {
                const response = await fetch(`get_plants.php?q=${encodeURIComponent(query)}`);
                const plants = await response.json();

                suggestionBox.innerHTML = plants.map(p => `
                    <div class="suggestion-item">
                        <span>${p.name}</span>
                    </div>
                `).join("");

                document.querySelectorAll(".suggestion-item").forEach(item => {
                    item.addEventListener("click", () => {
                        const name = item.querySelector("span").textContent;
                        input.value = name;
                        suggestionBox.innerHTML = "";
                    });
                });
            }, 300);
        });
    </script>
    
</body>
</html>