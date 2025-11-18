<?php
   session_start();
    include 'db_connect.php';

    if(isset($_GET['projectID'])) {
        $id = $_GET['projectID'];

        $sql = "DELETE FROM projects WHERE projectID=$id";
        $connect->query($sql);
    }

    header("Location: /FYP/logbookfirstpage.php");
    exit;
?>