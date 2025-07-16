<?php

session_start();

// --- FETCH DETAILS FOR SPECIFIED PRIVATE LEADERBOARD ENTRY 

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    if (isset($data["leaderboardId"]) && isset($data["userId"])) {          // Check leaderboard id and user id for selected entry are set

        $leaderboardId = $data["leaderboardId"];        // Store them
        $userId = $data["userId"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Fetch relevant entry from database
        $sql = "SELECT * FROM private_leaderboards_entries WHERE leaderboard_id = ? AND user_id = ?";          

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $leaderboardId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $entry = $result->fetch_assoc();        // Store result

        if ($entry) {
            echo json_encode(["success" => true, "data" => $entry]);        // Return json data containing the entry 
        } else {
            echo json_encode(["success" => false, "error" => "Entry not found"]);   // Return json indicating error
        }
    }

}