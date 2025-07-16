<?php

session_start();

// FETCH ALL LEADERBOARD ENTRIES AND THE USERNAME ASSOCIATED WITH EACH ENTRY FOR A SPECIFIED PUBLIC LEADERBOARD

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {               // Check user is set

    if (isset($data["exercise"]) && isset($data["reps"])) {     // Check exercise and number of reps for leaderboard are set

        $exercise = $data["exercise"];           // Store them
        $reps = (int) $data["reps"];  

        $mysqli = require __DIR__ . "/database.php";            // Database connection

        // Fetch all relevant entries from database
        $sql = "SELECT public_leaderboards.*, users.username FROM public_leaderboards
                JOIN users ON public_leaderboards.user_id = users.user_id
                WHERE public_leaderboards.exercise = ? AND public_leaderboards.num_reps = ?
                ORDER BY public_leaderboards.score DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("si", $exercise, $reps); 
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entries = [];

        if ($result->num_rows > 0) {                          
            while ($row = $result->fetch_assoc()) {         // Store result        
                $entries[] = $row;
            }
        }

        echo json_encode(["success" => true, "data" => $entries]);      // Return json containing list of fetched entries

    }

}

// ---