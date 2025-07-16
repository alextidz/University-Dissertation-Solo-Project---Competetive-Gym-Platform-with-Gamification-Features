<?php

session_start();

// --- CREATE A NEW PRIVATE LEADERBOARD BASED ON INFORMATION ENTERED BY USER

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    if (isset($data["name"]) && isset($data["code"]) && isset($data["exercise"]) && isset($data["reps"])) {     // Check name, join code, exercise and number of reps are set  
        
        $name = $data["name"];                          // Variables containing leaderboard info
        $code = $data["code"];
        $exercise = $data["exercise"];
        $reps = (int) $data["reps"];
        $creatorId = (int) $_SESSION["user_id"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        $sql = "INSERT INTO private_leaderboards (leaderboard_name, code, exercise, num_reps, creator_id) VALUES (?, ?, ?, ?, ?)";      // Insert new private leaderboard info into private leaderboards table
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssii", $name, $code, $exercise, $reps, $creatorId);

        if ($stmt->execute()) {

            $privateLeaderboardId = $mysqli->insert_id;         // Set details for initially empty leaderboard entry for the leaderboard creator (this user)
            $score = 0;
            $video = "null";

            $sql = "INSERT INTO private_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";      // Insert user's entry into private leaderboard entries table
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iids", $privateLeaderboardId, $creatorId, $score, $video);

            if ($stmt->execute()) {
                echo json_encode(["success" => true]);       // Return json indicating leaderboard was created successfully
            } else {
                echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
            }

        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
        }
         
    }

}

// ---