<?php

session_start();

// --- REPORT A SPECIFIED PUBLIC LEADERBOARD ENTRY

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {                  // Check user is set

    if (isset($data["username"]) && isset($data["exercise"]) && isset($data["reps"])) {          // Check username, exercise and number of reps for entry are set

        $username = $data["username"];              // Store them
        $exercise = $data["exercise"];
        $numReps = $data["reps"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Update relevant entry in database, incrementing its flag counter
        $sql = "UPDATE public_leaderboards
                JOIN users ON public_leaderboards.user_id = users.user_id
                SET public_leaderboards.flags = public_leaderboards.flags + 1
                WHERE public_leaderboards.exercise = ? AND public_leaderboards.num_reps = ? AND users.username = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sis", $exercise, $numReps, $username,);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);      // Return json indicating entry has been reported successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
        }

    }
    
}

// ---