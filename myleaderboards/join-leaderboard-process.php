<?php

session_start();

// --- ADD CURRENT USER TO SPECIFIED PRIVATE LEADERBOARD

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {          // Check user is set

    if (isset($data["code"])) {             // Check join code is set

        $code = $data["code"];              // Store it   

        $userId = $_SESSION["user_id"];     // Variables containing leaderboard entry info
        $score = 0;
        $video = "null";

        $mysqli = require __DIR__ . "/database.php";        // Database connection

        $sql = "SELECT * FROM private_leaderboards WHERE code = ?";      // Fetch leaderboard with matching entry code from database

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if ($row) {                         // If there is a leaderboard with matching entry code

            $leaderboardId = $row["private_leaderboard_id"];        // Store leaderboard id

            // Fetch user's entry to this leaderboard (if there is one) from database
            $sql = "SELECT * FROM private_leaderboards_entries WHERE leaderboard_id = ? AND user_id = ?";      
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $leaderboardId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            $row = $result->fetch_assoc();

            if ($row) {         // If user has an entry for this leaderboard

                echo json_encode(["success" => true, "exists" => true, "member" => true]);       // Return json indicating user is already a member of this leaderboard

            } else {
                
                // Insert new entry for this leaderboard and for this user into private leaderboard entries table in database
                $sql = "INSERT INTO private_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";      
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("iids", $leaderboardId, $userId, $score, $video);

                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "exists" => true, "member" => false, "id" => $leaderboardId]);       // Return json indicating user has successfully joined leaderboard
                } else {
                    echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
                }

            }

        } else {

            echo json_encode(["success" => true, "exists" => false]);       // Return json indicating join code is invalid

        }
         
    }

}