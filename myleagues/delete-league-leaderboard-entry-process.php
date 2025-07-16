<?php

session_start();

// --- DELETE A SPECIFIED LEAGUE LEADERBOARD ENTRY 

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    if (isset($data["leaderboardId"]) && isset($data["userId"])) {        // Check leaderboard id and user id for specified entry are set

        $leaderboardId = $data["leaderboardId"];        // Store them
        $userId = $data["userId"];
        $score = 0;                     // Store default entry values for score and video
        $video = "null";

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Update league leaderboard entries table, resetting specified user's leaderboard entry to default in database
        $sql = "UPDATE league_leaderboards_entries SET score = ?, video = ? WHERE leaderboard_id = ? AND user_id = ?;";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isii", $score, $video, $leaderboardId, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);      // Return json indicating leaderboard entry was deleted successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }   
         
    }

}

// ---