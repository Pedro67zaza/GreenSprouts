<?php
    include 'db_connect.php';

    session_start();

    // for testing purposes only
    $projectID = $_GET['projectID'];

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

    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $recordName = $_POST['recordName'];
        $height = !empty($_POST['height']) ? $_POST['height'] : "-";
        $width = !empty($_POST['width']) ? $_POST['width'] : "-";
        $waterAmount = $_POST['waterAmount'];
        $fertilizerType = !empty($_POST['fertilizerType']) ? $_POST['fertilizerType'] : "-";
        $fertilizerAmount = $_POST['fertilizerAmount'];
        $pesticide = !empty($_POST['pesticide']) ? $_POST['pesticide'] : "-";
        $remarks = !empty($_POST['remarks']) ? $_POST['remarks'] : "-";

        // Handle image upload
        $recordImage = "-"; // Default value
        if(isset($_FILES['recordImage']) && $_FILES['recordImage']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['recordImage']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            $filesize = $_FILES['recordImage']['size'];

            // Validate file type and size (max 5MB)
            if(in_array(strtolower($filetype), $allowed) && $filesize < 5000000) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/records/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Generate unique filename
                $new_filename = uniqid() . '_' . time() . '.' . $filetype;
                $destination = $upload_dir . $new_filename;

                // Move uploaded file
                if(move_uploaded_file($_FILES['recordImage']['tmp_name'], $destination)) {
                    $recordImage = $destination;
                } else {
                    $errorMessage = "Failed to upload image.";
                }
            } else {
                $errorMessage = "Invalid file type or file too large. Only JPG, JPEG, PNG, GIF allowed (max 5MB).";
            }
        }

        do {
            if(!empty($errorMessage)) {
                break;
            }

            if(empty($recordName) || empty($waterAmount) || empty($fertilizerAmount)) {
                $errorMessage = "Name, Water Amount, and Fertilizer Amount are required fields.";
                break;
            }

            // Use prepared statement to prevent SQL injection
            $sql = $connect->prepare("INSERT INTO records (recordName, height, width, waterAmount, fertilizerType, 
                    fertilizerAmount, pesticide, recordImage, recordRemarks, projectID) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $sql->bind_param("sssssssssi", $recordName, $height, $width, $waterAmount, $fertilizerType, 
                            $fertilizerAmount, $pesticide, $recordImage, $remarks, $projectID);

            if(!$sql->execute()) {
                $errorMessage = "Invalid query: " . $connect->error;
                break;
            }

            $sql->close();

            $successMessage = "Record added successfully";

            header("location: /FYP/logbooktablepage.php?projectID=$projectID");
            exit();

        } while(false);
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook - Create Page</title>

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
            background: rgba(32, 85, 23, 1);

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
            background-color: rgba(32, 85, 23, 1);
        }

        .form button:hover {
            background-color: #f4f9f4;
            color: rgba(32, 85, 23, 1);
            border: 2px solid rgba(32, 85, 23, 1);
            transition: all 0.3s ease;
        }
        .form a {
            display: flex;
            text-align: center;
            justify-content: center;
            text-decoration: none;
            margin-top: 10px;
            color: rgba(32, 85, 23, 1);
            border: 2px solid rgba(32, 85, 23, 1);
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
        <header>New Record</header>
        <form  action="" class="form" method="POST" enctype="multipart/form-data">
            <div class="input-box">
                <label for="">Image (Optional)</label>
                <input type="file" placeholder="Upload your file here" name="recordImage" accept="image/jpeg,image/png,image/gif,image/jpg">
            </div>
            <div class="input-box">
                <label for="">Entry Name</label>
                <input type="text" placeholder="Enter the entry name" name="recordName" required>
            </div>
            <div class="column">
                <div class="input-box">
                    <label for="">Height (in cm)</label>
                    <input type="number" step="0.01" min="0" placeholder="Enter the height here (optional)" name="height">
                </div>

                <div class="input-box">
                    <label for="">  Width (in cm)</label>
                    <input type="number" step="0.01" min="0" placeholder="Enter the width here (optional)" name="width">
                </div>
            </div>
            <div class="column">
                <div class="input-box">
                    <label for="">Water Level</label>
                    <select name="waterAmount" id="waterAmount" required>
                        <option value="">-- Select Water Level --</option>
                        <option value="none">None</option>
                        <option value="veryLow">Very Low (100 - 200ml)</option>
                        <option value="low">Low (300 - 400ml)</option>
                        <option value="moderate">Moderate (500 - 700ml)</option>
                        <option value="high">High (800 - 1000ml)</option>
                        <option value="veryHigh">Very High (1000ml+)</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="">Fertilizer Type</label>
                    <input type="text" placeholder="Enter the fertilizer type here (optional)" name="fertilizerType">
                </div>
            </div>

            <div class="column">
                <div class="input-box">
                    <label for="">Fertilizer Amount</label>
                    <select name="fertilizerAmount" id="fertilizerAmount" required>
                        <option value="">-- Select Fertilizer Amount --</option>
                        <option value="none">None</option>
                        <option value="light">Light Feeding (10 - 15g)</option>
                        <option value="regular">Regular Feeding (20 - 25g)</option>
                        <option value="heavy">Heavy Feeding (30 - 40g)</option>
                        <option value="intensive">Intensive Feeding (40 - 60g)</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="">Pesticide</label>
                    <input type="text" placeholder="Enter the pesticide used here (optional)" name="pesticide">
                </div>
            </div>
            <div class="input-box">
                <label for="">Additional Notes</label>
                <textarea class="textArea" placeholder="Enter any additional notes here..." name="remarks"></textarea>
            </div>
            <button type="submit">Save</button>
            <a href="logbooktablepage.php?projectID=<?php echo $projectID ?>">Cancel</a>
        </form>
      </section>
    </div>
</body>
</html>