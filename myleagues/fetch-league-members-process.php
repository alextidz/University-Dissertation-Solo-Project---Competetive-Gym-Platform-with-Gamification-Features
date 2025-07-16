<?php

session_start();

// --- FETCH LIST OF MEMBERS FOR SPECIFIED LEAGUE

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {              // Check user is set

    if (isset($data["leagueId"])) {             // Check league id is set
        
        $leagueId = $data["leagueId"];          // Store it

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Select all user's from specified league
        $sql = "SELECT users.user_id, users.username
                FROM league_entries
                JOIN users ON league_entries.user_id = users.user_id
                WHERE league_entries.league_id = ?";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $leagueId);
        $stmt->execute();
        $result = $stmt->get_result();

        $members = [];                                  // Store fetched users' id and username in members array
        while ($row = $result->fetch_assoc()) {
            $members[] = [$row["user_id"], $row["username"]];
        }

        echo json_encode(["success" => true, "data" => $members]);      // Return json containing list of league members
         
    }
}

// ---