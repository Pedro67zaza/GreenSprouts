<?php
    include 'db_connect.php';

    session_start();

    //Validate recordID and projectID
    if(!isset($_GET['recordId']) || !is_numeric($_GET['recordId']) || 
    !isset($_GET['projectID']) || !is_numeric($_GET['projectID'])) {
        die("Error: Invalid record or project ID.");
    }

    $recordId = intval($_GET['recordId']);
    $projectID = intval($_GET['projectID']);

    if(!isset($_SESSION['userId'])) {
        die("Error: User must be logged in.");
    }

    $query = $connect->prepare("SELECT projectID FROM projects WHERE projectID = ? AND userId = ?");
    $query->bind_param("ii", $projectID, $_SESSION['userId']);
    $query->execute();
    $query->store_result();

    if($query->num_rows === 0) {
        die("Error: Access denied or project not found");
    }

    $query->close();

    $recordName = "";
    $height = "";
    $width = "";
    $waterAmount = "";
    $fertilizerType = "";
    $fertilizerAmount = "";
    $pesticide = "";
    $remarks = "";
    $recordImage = "";

    $errorMessage = "";
    $successMessage = "";


    if($_SERVER['REQUEST_METHOD'] == 'GET') {

        $sql = $connect->prepare("SELECT * FROM records WHERE recordId = ? AND projectID = ?");
        $sql->bind_param("ii", $recordId, $projectID);
        $sql->execute();
        $result = $sql->get_result();

        if($result->num_rows === 0) {
            $sql->close(); 
            header("Location: /FYP/logbooktablepage.php?projectID=$projectID");
            exit;
        }

        $row = $result->fetch_assoc();

        $recordName = $row['recordName'];
        $height = $row['height'] === '-' ? '' : $row['height'];
        $width = $row['width'] === '-' ? '' : $row['width'];
        $waterAmount = $row['waterAmount'];
        $fertilizerType = $row['fertilizerType'] === '-' ? '' : $row['fertilizerType'];
        $fertilizerAmount = $row['fertilizerAmount'];
        $pesticide = $row['pesticide'] === '-' ? '' : $row['pesticide'];
        $remarks = $row['recordRemarks'] === '-' ? '' : $row['recordRemarks'];
        $recordImage = $row['recordImage'];

        $sql->close();
    }

    elseif($_SERVER['REQUEST_METHOD'] === 'POST') {

        $recordName = trim($_POST['recordName']);
        $height = !empty($_POST['height']) ? $_POST['height'] : "-";
        $width = !empty($_POST['width']) ? $_POST['width'] : "-";
        $waterAmount = $_POST['waterAmount'];
        $fertilizerType = !empty($_POST['fertilizerType']) ? trim($_POST['fertilizerType']) : "-";
        $fertilizerAmount = $_POST['fertilizerAmount'];
        $pesticide = !empty($_POST['pesticide']) ? trim($_POST['pesticide']) : "-";
        $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : "-";

        do {
            if(empty($recordName) || empty($height) || empty($width) || empty($waterAmount) || empty($fertilizerType
            || empty($fertilizerAmount) || empty($pesticide) || empty($remarks))) {
                $errorMessage = "All the fields must be entered";
                break;
            }

            $uploadDirectory = 'uploads/records/';
            if(!file_exists($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            if(isset($_FILES['recordImage']) && $_FILES['recordImage']['error'] == 0) {
                $allowedType = ['jpg', 'jpeg', 'png'];
                $fileName = $_FILES['recordImage']['name'];
                $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $fileSize = $_FILES['recordImage']['size'];

                if(!in_array($fileType, $allowedType) || $fileSize > 5000000) {
                    $errorMessage = "Invalid file. Only JPG, PNG, and JPEG allowed (max 5MB).";
                    break;
                }

                $newFileName = uniqid() . '_' . time() . '.' . $fileType;
                $destination = $uploadDirectory . $newFileName;

                if(move_uploaded_file($_FILES['recordImage']['tmp_name'], $destination)) {
                    $recordImage = $destination; 
                } else {
                    $errorMessage = "Failed to upload image.";
                    break;
                }
            }
            //if no new image keep old one
            else {
                    $stmt = $connect->prepare("SELECT recordImage FROM records WHERE recordId = ?");
                    $stmt->bind_param("i", $recordId);
                    $stmt->execute();
                    $stmt->bind_result($recordImage);
                    $stmt->fetch();
                    $stmt->close();
                }


            $stmt = $connect->prepare("UPDATE records SET 
            recordName = ?, height = ?, width = ?, waterAmount = ?, fertilizerType = ?, 
            fertilizerAmount = ?, pesticide = ?, recordRemarks = ?, recordImage = ? 
            WHERE recordId = ? AND projectID = ?");

            $stmt->bind_param("ssssssssssi", $recordName, $height, $width, $waterAmount, $fertilizerType,
                        $fertilizerAmount, $pesticide, $remarks, $recordImage, $recordId, $projectID);

            if(!$stmt->execute()) {
                $errorMessage = "Update failed: " . $connect->error;
                break;
            }

            $stmt->close();

            $successMessage = "Record updated successfully";

            header("Location: /FYP/logbooktablepage.php?projectID=$projectID");
            exit;

        } while(false);

    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook - Update Record</title>

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
            background-color: #2e7d32;
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
        <header>Modify Record</header>
        <form action="" class="form" method="POST" enctype="multipart/form-data">
            <div class="input-box">
                <label>Current Image</label>
                <?php if ($recordImage && $recordImage !== '-' && file_exists($recordImage)): ?>
                    <div style="margin: 10px 0;">
                        <img src="<?= htmlspecialchars($recordImage) ?>" alt="Current" style="max-height: 100px; border-radius: 6px;">
                    </div>
                <?php else: ?>
                    <p><em>No image</em></p>
                <?php endif; ?>
                <label for="">New Image (Optional)</label>
                <input type="file" name="recordImage" accept="image/*">
            </div>
            <div class="input-box">
                <label for="">Entry Name</label>
                <input type="text" placeholder="Enter the entry name" name="recordName" value="<?php echo $recordName; ?>" required>
            </div>
            <div class="column">
                <div class="input-box">
                    <label for="">Height (in cm)</label>
                    <input type="number" step="0.01" min="0" placeholder="Enter the height here (optional)" name="height" value="<?php echo $height; ?>">
                </div>

                <div class="input-box">
                    <label for="">  Width (in cm)</label>
                    <input type="number" step="0.01" min="0" placeholder="Enter the width here (optional)" name="width" value="<?php echo $width; ?>">
                </div>
            </div>
            <div class="column">
                <div class="input-box">
                    <label for="">Water Level</label>
                    <select name="waterAmount" id="waterAmount" value="<?php echo $waterAmount; ?>">
                        <option value="none">None</option>
                        <option value="veryLow">Very Low (100 - 200ml)</option>
                        <option value="low">Low (300 - 400ml)</option>
                        <option value="moderate">Moderate (500 - 700ml)</option>
                        <option value="high">High (800 - 100ml)</option>
                        <option value="veryHigh">Very High (1000ml+)</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="">Fertilizer Type</label>
                    <input type="text" placeholder="Enter the fertilizer type here (optional)" name="fertilizerType" value="<?php echo $fertilizerType; ?>">
                </div>
            </div>

            <div class="column">
                <div class="input-box">
                    <label for="">Fertilizer Amount</label>
                    <select name="fertilizerAmount" id="fertilizerAmount" value="<?php echo $fertilizerAmount; ?>">
                        <option value="none">None</option>
                        <option value="light">Light Feeding (10 - 15g)</option>
                        <option value="regular">Regular Feeding (20 - 25g)</option>
                        <option value="heavy">Heavy Feeding (30 - 40g)</option>
                        <option value="intensive">Intensive Feeding (40 - 60g)</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="">Pesticide</label>
                    <input type="text" placeholder="Enter the pesticide used here (optional)" name="pesticide" value="<?php echo $pesticide; ?>">
                </div>
            </div>
            <div class="input-box">
                <label for="">Additional Notes</label>
                <textarea class="textArea" placeholder="Enter any additional notes here..." name="remarks"><?php echo $remarks ?></textarea>
            </div>
            <button type="submit">Save</button>
            <a href="logbooktablepage.php?projectID=<?= $projectID ?>">Cancel</a>
        </form>
      </section>
    </div>
</body>
</html>