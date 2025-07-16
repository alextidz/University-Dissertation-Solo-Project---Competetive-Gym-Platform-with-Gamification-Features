<?php

session_start();

// --- FETCH LIST OF MEMBERS FOR SPECIFIED PRIVATE LEADERBOARD

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    if (isset($data["leaderboardId"])) {                // Check leaderboard id is set
        
        $leaderboardId = $data["leaderboardId"];        // Variable containing leaderboard id

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Select all user's from specified leaderboard
        $sql = "SELECT users.user_id, users.username
                FROM private_leaderboards_entries
                JOIN users ON private_leaderboards_entries.user_id = users.user_id
                WHERE private_leaderboards_entries.leaderboard_id = ?";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $leaderboardId);
        $stmt->execute();
        $result = $stmt->get_result();

        $members = [];                                  // Store fetched users in members array
        while ($row = $result->fetch_assoc()) {
            $members[] = [$row["user_id"], $row["username"]];
        }

        echo json_encode(["success" => true, "data" => $members]);      // Return json containing list of leaderboard members
         
    }
}

// ---