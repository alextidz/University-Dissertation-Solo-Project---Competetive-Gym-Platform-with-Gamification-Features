<?php

session_start();

// --- REMOVE CURRENT USER FROM SPECIFIED PRIVATE LEADERBOARD (AND DELETE LEADERBOARD IF THEY ARE ADMIN)

$data = json_decode(file_get_contents("php://input"), true);            // Receive json data

if (isset($_SESSION["user_id"])) {          // Check user is set

    if (isset($data["leaderboardId"]) && isset($data["userId"]) && isset($data["creatorId"])) {      // Check leaderboard id, current user's id and creator id are set

        $leaderboardId = $data["leaderboardId"];        // Store them
        $userId = $data["userId"];
        $creatorId = $data["creatorId"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        if ($userId == $creatorId) {            // If current user is creator for this leaderboard

            // Delete user's entry for this leaderboard from database
            $sql = "DELETE FROM private_leaderboards_entries WHERE leaderboard_id = ?";     

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $leaderboardId);

            if ($stmt->execute()) {

                // Delete this leaderboard from database
                $sql = "DELETE FROM private_leaderboards WHERE private_leaderboard_id = ?";     

                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $leaderboardId);

                if ($stmt->execute()) {
                    echo json_encode(["success" => true]);      // Return json indicating leaderboard entry and leaderboard were successfully deleted
                } else {
                    echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
                }

            } else {
                echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
            }

        } else  {
        
            // Delete user's entry for this leaderboard from database
            $sql = "DELETE FROM private_leaderboards_entries WHERE leaderboard_id = ? AND user_id = ?";

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $leaderboardId, $userId);

            if ($stmt->execute()) {
                echo json_encode(["success" => true]);      // Return json indicating user's leaderboard entry was deleted successfully 
            } else {
                echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
            }
        }
         
    }
}

// ---