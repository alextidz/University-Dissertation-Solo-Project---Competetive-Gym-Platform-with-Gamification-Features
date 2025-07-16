<?php

session_start();

// --- ADD DAILY REWARD XP AND COINS TO USER'S ACCOUNT

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data   

if (isset($_SESSION["user_id"])) {                  // Check user is set

    $mysqli = require __DIR__ . "/database.php";    // Database connection

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";    // Fetch current user from database 

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();                 // Store current user

    if (isset($data["dailyXp"]) && isset($data["dailyCoins"])) {            // Check xp value and coins value from daily reward are set

        $newXp = ((int) $data["dailyXp"]) + $user["current_xp"];            // Calculate user's new xp total
        $newBalance = ((int) $data["dailyCoins"]) + $user["balance"];       // Calculate user's new balance
        $newTimestamp = date("Y-m-d H:i:s");                        // Set new timsetamp as current time (user will not be able to claim daily reward again until 24 hours after this)
        $userId = $user["user_id"];                                         // Store user id
        
        // Update user's xp total, balance and reward claim timestamp
        $sql = "UPDATE users SET current_xp = ?, balance = ?, daily_claimed_time = ? WHERE user_id = ?";
    
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iisi", $newXp, $newBalance, $newTimestamp, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);      // Return json indicating rewarc claimed successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }
         
    }
}

// ---