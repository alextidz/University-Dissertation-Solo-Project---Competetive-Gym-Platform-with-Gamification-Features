<?php

session_start();

// FETCH ALL LEADERBOARD ENTRIES AND THE USERNAME ASSOCIATED WITH EACH ENTRY FOR A SPECIFIED LEAGUE LEADERBOARD

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    if (isset($data["leaderboardId"])) {                // Check leaderboard id is set for selected leaderboard

        $leaderboardId = $data["leaderboardId"];        // Store it

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Fetch all relevant leaderboard entries and the username associated from database
        $sql = "SELECT league_leaderboards_entries.*, users.user_id, users.username FROM league_leaderboards_entries
                JOIN users ON league_leaderboards_entries.user_id = users.user_id
                WHERE league_leaderboards_entries.leaderboard_id = ?
                ORDER BY league_leaderboards_entries.score DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $leaderboardId); 
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entries = [];

        if ($result->num_rows > 0) {                    // Store fetched entries in entries array  
            while ($row = $result->fetch_assoc()) {              
                $entries[] = $row;
            }
        }
        
        echo json_encode(["success" => true, "data" => $entries]);      // Return json data containing list of the leaderboard's entries
        
    }

}

// ---