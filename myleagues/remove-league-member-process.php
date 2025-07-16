<?php

session_start();

// --- REMOVE SELECTED MEMBER FROM LEAGUE

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {          // Check user is set

    if (isset($data["leagueId"]) && isset($data["userId"])) {        // Check league id and user id of user to remove are set

        $leagueId = $data["leagueId"];                  // Store them     
        $userId = $data["userId"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Fetch leaderboard id of all leaderboard that belong to this league from database
        $sql = "SELECT league_leaderboard_id FROM league_leaderboards WHERE league_id = ?;";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $leagueId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {         // Loop through results

            $leaderboardId = $row["league_leaderboard_id"];     // Store leaderboard id
            
            // Delete all entries from this user to this leaderboard from league leaderboard entries table in database
            $sql = "DELETE FROM league_leaderboards_entries WHERE leaderboard_id = ? AND user_id = ?;";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $leaderboardId, $userId);
            $stmt->execute();
            $stmt->close();

        }

        // Delete this user's entry to this league from league entries table in database
        $sql = "DELETE FROM league_entries WHERE league_id = ? AND user_id = ?;";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $leagueId, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);      // Return json indicating user has been removed from league successfully 
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error 
        }
         
    }

}

// ---