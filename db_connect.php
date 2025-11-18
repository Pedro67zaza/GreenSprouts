
    <?php
    //input all of the credentials
    $server = "localhost";
    $username = "localhost";
    $password = "GreenSprouts123";
    $database = "greensproutsdb";

    //establish the connection
    $connect = new mysqli($server, $username, $password, $database);

    //error handling 
    if($connect -> connect_errno) {
        //die will display the message and terminate the program
        die("Error! Could not connect to the database." . $connect -> connect_error);
    }

    //reminder to create dedicate error page
    ?>