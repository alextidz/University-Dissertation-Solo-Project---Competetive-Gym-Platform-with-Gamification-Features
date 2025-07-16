<?php

session_start();

// --- REMOVE SELECTED MEMBER FROM PRIVATE LEADERBOARD

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data

if (isset($_SESSION["user_id"])) {          // Check user is set

    if (isset($data["leaderboardId"]) && isset($data["username"])) {        // Check leaderboard id and username of user to remove are set

        $leaderboardId = $data["leaderboardId"];        // Store them 
        $username = $data["username"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Delete user with specified username from private leaderboard entries table in database
        $sql = "DELETE private_leaderboards_entries FROM private_leaderboards_entries
                JOIN users ON private_leaderboards_entries.user_id = users.user_id
                WHERE private_leaderboards_entries.leaderboard_id = ? AND users.username = ?;";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("is", $leaderboardId, $username);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);      // Return json indicating user has been removed from leaderboard successfully 
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error 
        }
         
    }

}

// ---