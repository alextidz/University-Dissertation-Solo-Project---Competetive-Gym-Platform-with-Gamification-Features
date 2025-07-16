<?php

session_start();

// --- ADD LEAGUE REWARDS TO USER ACCOUNT AND UPDATE LEAGUE ENTRY AS FINAL REWARD CLAIMED

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data   

if (isset($_SESSION["user_id"])) {                  // Check user is set

    $mysqli = require __DIR__ . "/database.php";    // Database connection

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";    // Fetch current user from database 

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();                 // Store current user

    if (isset($data["xpToAdd"]) && isset($data["coinsToAdd"]) && isset($data["leagueId"])) {            // Check xp value, coins value and league id are set

        $newXp = ((int) $data["xpToAdd"]) + $user["current_xp"];            // Calculate user's new xp total
        $newBalance = ((int) $data["coinsToAdd"]) + $user["balance"];       // Calculate user's new balance
        $userId = $user["user_id"];                                         // Store user id
        $leagueId = $data["leagueId"];                                      // Store league id
        
        $sql = "UPDATE users SET current_xp = ?, balance = ? WHERE user_id = ?";    // Update user's xp total and balance
    
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iii", $newXp, $newBalance, $userId);

        if ($stmt->execute()) {

            $finalRewardClaimed = 1;        // Store new value for final reward claimed column (true)

            // Set final reward claimed as true for user's league entry, so that league is no longer displayed for them
            $sql = "UPDATE league_entries SET final_reward_claimed = ? WHERE league_id = ? AND user_id = ?";

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iii", $finalRewardClaimed, $leagueId, $userId);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);      // Return json indicating reward claimed successfully
            } else {
                echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
            }

        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }
         
    }
}

// ---