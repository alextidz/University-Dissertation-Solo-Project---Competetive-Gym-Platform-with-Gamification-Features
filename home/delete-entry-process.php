<?php

session_start();

// --- DELETE CURRENT USER'S ENTRY FROM SPECIFIED PUBLIC LEADERBOARD

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {              // Check user is set

    if (isset($data["userId"]) && isset($data["exercise"]) && isset($data["reps"])) {            // Check user id, exercise and number of reps for the entry are set

        $userId = $data["userId"];              // Store them
        $exercise = $data["exercise"];          
        $numReps = $data["reps"];               

        $mysqli = require __DIR__ . "/database.php";        // Database connection

        $sql = "DELETE FROM public_leaderboards WHERE user_id = ? AND exercise = ? AND num_reps = ?";       // Delete relevant entry from database

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isi", $userId, $exercise, $numReps);

        if ($stmt->execute()) {                                
            echo json_encode(["success" => true]);      // Return json indicating entry was deleted successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }

    }
    
}

// ---