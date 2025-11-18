<?php
    include 'db_connect.php';

    if(isset($_GET['recordId'])) {
        $id = $_GET['recordId'];

        $sql = "DELETE FROM records WHERE recordId=$id";
        $connect->query($sql);
    }

    header("Location: /FYP/logbooktablepage.php?projectID=" . $_GET['projectID']);
    exit;
?>