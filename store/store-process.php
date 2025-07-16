<?php

session_start();

// --- UPDATE USER'S BALANCE AND ADD PRODUCT CODE TO THEIR ACCOUNT

$data = json_decode(file_get_contents("php://input"), true);        // Receive json data 

if (isset($_SESSION["user_id"])) {              // Check user is set

    if (isset($data["newBalance"])) {           // Checks new balance for user is set

        $userId = $_SESSION["user_id"];                 // Store them
        $newBalance = (int) $data["newBalance"];

        $mysqli = require __DIR__ . "/database.php";    // Database connection
        
        $sql = "UPDATE users SET balance = ? WHERE user_id = ?";        // Update user's balance in database
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $newBalance, $userId);

        if ($stmt->execute()) {

            if (isset($data["itemName"]) && isset($data["itemCode"])) {     // Check item name and code are set

                $itemName = $data["itemName"];              // Store them
                $itemCode = $data["itemCode"];
                $datePurchased = date("Y-m-d");     // Store date purchased (today)

                // Add product code associated with current user to database
                $sql = "INSERT INTO codes (user_id, code_string, item_name, date_purchased) VALUES (?, ?, ?, ?)";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("isss", $userId, $itemCode, $itemName, $datePurchased);
            }

            if ($stmt->execute()) {
                echo json_encode(["success" => true]);     // Return json indicating balance updated and product code added to user's account successfully
            } else {
                echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
            }
        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
        }
         
    }
}

// ---