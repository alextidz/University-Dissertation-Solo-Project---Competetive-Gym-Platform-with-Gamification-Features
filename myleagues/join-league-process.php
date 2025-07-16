<?php

session_start();

// --- ADD CURRENT USER TO SPECIFIED LEAGUE

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {          // Check user is set

    if (isset($data["code"])) {             // Check join code is set

        $code = $data["code"];              // Store it   
        $userId = $_SESSION["user_id"];     // Store current user id

        $mysqli = require __DIR__ . "/database.php";        // Database connection

        $sql = "SELECT * FROM leagues WHERE code = ?";      // Fetch league with matching entry code from database

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if ($row) {                             // If a league with matching entry code was fetched

            $leagueId = $row["league_id"];      // Store league id

            // Fetch user's entry to this league (if there is one) from database
            $sql = "SELECT * FROM league_entries WHERE league_id = ? AND user_id = ?";      
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $leagueId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            $row = $result->fetch_assoc();

            if ($row) {         // If user has an entry for this league

                echo json_encode(["success" => true, "exists" => true, "member" => true]);       // Return json indicating user is already a member of this league

            } else {            // If user does not have entry for this league
                
                // Insert new entry for this league and for this user into league entries table in database
                $sql = "INSERT INTO league_entries (league_id, user_id) VALUES (?, ?)";      
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ii", $leagueId, $userId);

                if ($stmt->execute()) {

                    // Fetch leaderboard id of all leaderboards in this league
                    $sql = "SELECT league_leaderboard_id FROM league_leaderboards WHERE league_id = ?";      
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $leagueId);

                    if ($stmt->execute()) { 

                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {     // Loop through leaderboard ids of all leaderboards in this league

                            $leaderboardId = $row['league_leaderboard_id'];     // Store leaderboard id
                            $score = 0;                                         // Store default entry info
                            $video = "null";
                            
                            // Insert default leaderboard entry for this leaderboard for current user
                            $sql = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";      
                            $stmt = $mysqli->prepare($sql);
                            $stmt->bind_param("iids", $leaderboardId, $userId, $score, $video);
                            $stmt->execute();
                            $stmt->close();

                        }

                        echo json_encode(["success" => true, "exists" => true, "member" => false, "id" => $leagueId]);       // Return json indicating user has successfully joined league
                        
                    } else {
                        echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
                    }

                } else {
                    echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
                }

            }

        } else {

            echo json_encode(["success" => true, "exists" => false]);       // Return json indicating join code is invalid

        }
         
    }

}


// ---