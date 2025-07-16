<?php

session_start();

// PASS DATA FROM ONE PAGE TO ANOTHER (USED FOR PASSING LEAGUE BETWEEN PAGES)

$data = json_decode(file_get_contents('php://input'), true);    // Receive json data

if (isset($_SESSION["user_id"])) {              // Check user is set

    if (isset($data['data'])) {                 // Check data is set

        $_SESSION['data'] = $data['data'];                  // Store data in session variable
        echo json_encode(['success' => true]);       // Return json indicating data successfully stored in session variable

    } else {

        echo json_encode(["success" => false, "error" => "Session storage error"]);        // Return json indicating error

    }

}

// ---