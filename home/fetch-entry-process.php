<?php

session_start();

// --- FETCH DETAILS FOR SPECIFIED PUBLIC LEADERBOARD ENTRY 

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {              // Check user is set

    if (isset($data["username"]) && isset($data["exercise"]) && isset($data["reps"])) {          // Check username, exercise and number of reps for entry are set 

        $username = $data["username"];          // Store them
        $exercise = $data["exercise"];
        $numReps = $data["reps"];

        $mysqli = require __DIR__ . "/database.php";        // Database connection

        // Fetch relevant entry from database
        $sql = "SELECT public_leaderboards.*, users.username  
                FROM public_leaderboards
                JOIN users ON public_leaderboards.user_id = users.user_id
                WHERE users.username = ? AND public_leaderboards.exercise = ? AND public_leaderboards.num_reps = ?";        
            
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssi", $username, $exercise, $numReps);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entry = $result->fetch_assoc();        // Store result

        if ($entry) {
            echo json_encode(["success" => true, "data" => $entry]);        // Return json containing entry, indicating entry was fetched successfully
        } else {
            echo json_encode(["success" => false, "error" => "Entry not found"]);       // Return json indicating error
        }

    }

}

// ---