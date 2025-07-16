<?php

session_start();

// --- LEVEL UP CURRENT USER

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {          // Check user is set

    if (isset($data["newLevel"])) {         // Check new level is set

        $newLevel = $data["newLevel"];
        $userId = $_SESSION["user_id"];

        $mysqli = require __DIR__ . "/database.php";            // Database connection

        $sql = "UPDATE users SET current_level = ? WHERE user_id = ?";      // Update new level for user in database 
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $newLevel, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);        // Return json indicating user levelled up successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }

    }
}

// ---