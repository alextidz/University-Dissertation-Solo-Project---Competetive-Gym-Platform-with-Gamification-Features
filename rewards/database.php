<?php

    // Database information
    $db_server = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "fyp";

    // Create connection to database
    $mysqli = new mysqli($db_server, 
                        $db_user, 
                        $db_pass, 
                        $db_name);    

    if ($mysqli-> connect_errno) {
        die("Connection error: ". mysqli_connect_error());
    }

    return $mysqli;
?>
