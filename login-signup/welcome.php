<?php

session_start();

// --- CHECK IF THE USER IS LOGGED IN AND FETCH AND STORE THEIR DETAILS

$user = null;       // Initialise variable to store user details

if (isset($_SESSION["user_id"])) {                  // Check if user is set

    $mysqli = require __DIR__ . "/database.php";    // Database connection

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";    // Fetch user details from database 

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();                 // Store details in user variable 
}

if (!$user) {       // If user isn't set, user isn't logged in

    header("Location: ../login-signup/login.php");      // Redirect to login page
    exit;
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="../styling/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>    

    <div class = "login-box-container">

            <!-- Welcome card -->
            <div class = "card" style = "width:400px; height:480px; padding-bottom: 50px; text-align:center">
                <div class = "login-header">WELCOME</div>

                <?php if (isset($user)) {?>

                <!-- Personalised welcome message -->
                <div class = "welcome-text">
                    Great news <b style="color:rgb(68, 19, 113); font-weight:bold"><?= htmlspecialchars($user["first_name"]); ?></b>, you're all ready to go!
                </div>

                <!-- Display user's username, starting level and balance -->
                <div class="welcome-username"><?= htmlspecialchars($user["username"]); ?></div>                        

                <div class="welcome-stats">
                    <div class="welcome-level">Level <b class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></b></div>
                    <div class="welcome-balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); }?></div>
                </div>

                <div class="welcome-list">
                    <p class = "welcome-list-item"><i class="bi bi-star-half" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>Compete with others worldwide!</p>
                    <p class = "welcome-list-item"><i class="bi bi-star-half" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>Compete amongst your friends!</p>
                    <p class = "welcome-list-item"><i class="bi bi-star-half" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>Join or create leagues with people you know!</p>
                    <p class = "welcome-list-item"><i class="bi bi-star-half" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>Level up and earn lots of amazing rewards!</p>
                </div>

                <!-- Button to take user to home page -->
                <button class = "login-button" onclick="window.location.href='../home/home.php'"><p class = "logout-text">Get Started</p></button>

            </div>
    </div>

</body>
</html>