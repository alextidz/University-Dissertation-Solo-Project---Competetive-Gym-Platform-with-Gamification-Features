<?php

session_start();

// --- DELETE A SPECIFIED PRIVATE LEADERBOARD ENTRY 

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    if (isset($data["leaderboardId"]) && isset($data["username"])) {        // Check leaderboard id and username for specified entry are set

        $leaderboardId = $data["leaderboardId"];        // Store them
        $username = $data["username"];
        $score = 0;
        $video = "null";

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Update private leaderboard entries table, resetting specified user's leaderboard entry in database
        $sql = "UPDATE private_leaderboards_entries
                JOIN users ON private_leaderboards_entries.user_id = users.user_id
                SET private_leaderboards_entries.score = ?, private_leaderboards_entries.video = ?
                WHERE private_leaderboards_entries.leaderboard_id = ? AND users.username = ?;";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isis", $score, $video, $leaderboardId, $username);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);      // Return json indicating leaderboard entry was deleted successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }   
         
    }

}

// ---