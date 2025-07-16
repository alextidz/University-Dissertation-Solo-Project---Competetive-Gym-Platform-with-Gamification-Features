<?php

session_start();

// --- ADD XP TO CURRENT USER'S ACCOUNT

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    $mysqli = require __DIR__ . "/database.php";        // Database connection

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";    // Fetch current user from database

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();         // Store current user

    if (isset($data["xpToAdd"])) {          // Check xp value is set

        $newXp = ((int) $data["xpToAdd"]) + $user["current_xp"];    // Calculate new xp total for user
        $userId = $user["user_id"];
        
        $sql = "UPDATE users SET current_xp = ? WHERE user_id = ?"; // Update new xp total for user in the database
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $newXp, $userId);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);       // Return json indicating xp was added successfully
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);    // Return json indicating error
        }
         
    }
}

// ---