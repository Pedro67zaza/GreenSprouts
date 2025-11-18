<?php
    include 'db_connect.php';

    session_start();

    $recordId = $_GET['recordId'];
    $projectId = $_GET['projectID'];
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
        .container .info-box {
            margin-top: 20px;
        }
        .info-box {
            width: 100%;
            margin-top: 20px;
        }
        .info-box label {
            color: #333;
        }
        .info-box input {
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
         .column {
            display: flex;
            column-gap: 15px;
        }

        /* Only change: Limit image size */
        .info-box img {
            max-width: 400px;        /* Adjust this number as needed */
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 10px 0;
            object-fit: contain;
        }

         a {
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

        @media screen and (max-width: 500px) {
             .column {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container my-5">

        <?php
        $query = "SELECT * FROM records WHERE recordId = " . $recordId;
        $result = $connect->query($query);
        
        while($row = $result->fetch_assoc()) {
            echo '
            <section class="container">
            <header>Record Details</header>
        
            <div class="info-box">
                <img src="'. $row['recordImage'] .'" alt="No Image was uploaded">
            </div>
            <div class="info-box">
                <label for="">Entry Name: '. $row['recordName'] .'</label>
            </div>
            <div class="info-box">
                <label for="">Record Date: '. $row['recordDate'] .'</label>
            </div>
            <div class="column">
                <div class="info-box">
                <label for="">Height (in cm): '. $row['height'] .'</label>
                </div>

                <div class="info-box">
                    <label for="">Width (in cm): '. $row['width'] .'</label>
                </div>
            </div>

            <div class="column">
                <div class="info-box">
                <label for="">Water Level: '. $row['waterAmount'] .'</label>
                </div>

                <div class="info-box">
                    <label for="">Pesticide: '. $row['pesticide'] .'</label>
                </div>
            </div>
            
            <div class="column">
                <div class="info-box">
                <label for="">Fertilizer Type: '. $row['fertilizerType'] .'</label>
                </div>

                <div class="info-box">
                    <label for="">Fertilizer Amount: '. $row['fertilizerAmount'] .'</label>
                </div>
            </div>
            <div class="column">
                <div class="info-box">
                <label for="">Additional Notes:  '. $row['recordRemarks'] .'</label>
                </div>
            </div>
      </section>';
        }?>
        <a href="logbooktablepage.php?projectID=<?php echo $projectId ?>">Back</a>
    </div>
</body>
</html>