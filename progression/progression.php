<?php

session_start();

// --- CHECK IF THE USER IS LOGGED IN AND FETCH AND STORE THEIR DETAILS --- //

$user = null;       // Initialise variable to store user details

if (isset($_SESSION["user_id"])) {                  // Check if user is set

    $mysqli = require __DIR__ . "/database.php";    // Database connection

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";        // Fetch user details from database

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();                 // Store details in user variable 
}

if (!$user) {                               // If user isn't set, user isn't logged in  

    header("Location: ../login-signup/login.php");  // Redirect to login page
    exit;
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progression</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styling/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body> 
    
    <div class="min-h-screen flex">

        <!-- Sidebar containing buttons with links to all main pages -->
        <aside class="w-1/4 pt-6 shadow-lg flex flex-col justify-between transition duration-500 ease-in-out transform" id="sidebar"> 
            <div>
                <form action = "../home/home.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-house-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">Home</p></div></button>
                </form>
                <form action="../myleaderboards/myleaderboards.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-bar-chart-line-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">My Leaderboards</p></div></button>
                </form>
                <form action="../myleagues/myleagues.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-bar-chart-steps" style="padding-right:5px"></i><p style="padding-right: 5px;">My Leagues</p></div></button>
                </form>
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-graph-up-arrow" style="padding-right:5px"></i><p style="padding-right: 5px;">Progression</p></div></button>
                </form>
                <form action="../rewards/rewards.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-coin" style="padding-right:5px"></i><p style="padding-right: 5px;">Rewards</p></div></button>
                </form>
                <form action="../store/store.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-bag-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">Store</p></div></button>
                </form>
                <form action="../myaccount/myaccount.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-person-circle" style="padding-right:5px"></i><p style="padding-right: 5px;">My Account</p></div></button>
                </form>
            </div>
            
            <!-- Container with current user logged in and log out button -->
            <div class="p-6 transition duration-500 ease-in-out transform">

                <?php if (isset($user)) {?>

                <p class="mb-4 text-m" style="color:gray;text-align:center" id="user-logged-in"><?= htmlspecialchars($user["username"]); ?> logged in</p>

                <!-- Button that logs out user when pressed -->
                <form class="sidebar-form" action="../login-signup/logout.php">
                    <button type="submit" class="main-btn"><p class = "logout-text">Log Out</p></button>
                </form>

            </div>

        </aside>


        <!-- Main right hand side of page -->
        <main class="flex-1 p-6" id = "main">

            <div class="grid grid-cols-1 gap-6">

                <!-- Card containing page header and user level, username and balance -->
                <div class="card">
                    <div class="header-container">
                        <div class="card-header">Progression<i class="bi bi-graph-up-arrow" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); ?></div>
                        </div>
                    </div>
                </div>
            
                <!-- Card containing user's current level and level progress -->
                <div class="card">
                    <div class="card-header">My Level<i class="bi bi-award" style="padding-left:8px"></i></div>

                    <!-- User's current level -->
                    <div class="my-level-container">
                        <div class="card" style="background: linear-gradient(to right, rgb(3, 244, 107), cyan); width: 200px; height:210px; text-align:center">
                            <div class = "sub-header"><p><i>Level</i></p></div>
                            <div class="my-level"><p style="color:rgb(68, 19, 113)"><?= htmlspecialchars($user["current_level"]); ?></p></div>
                        </div>
                    </div>

                    <!-- Level progress -->
                    <div class="progress-container">

                        <!-- User's current level -->
                        <div class="card" style="background-color:rgb(68, 19, 113); padding:5px; padding-left:15px; padding-right:15px; width:60px; height:50px; text-align:center; align-items:center">
                            <p class="logout-text" style="font-weight: bolder; font-size: 1.5rem;"><?= htmlspecialchars($user["current_level"]); ?></p>
                        </div>

                        <!-- Level progress bar -->
                        <div class="progress-bar-container">
                            <?php 
                                $currentLevel = $user["current_level"];     // Store user's current level
                                $currentXp = $user["current_xp"];           // Store user's current xp

                                $x = 0.05;      // Constants for formula
                                $y = 1.6;
                                
                                $nextLevelXp = round(((($currentLevel + 1)/ $x) ** $y) , -1);   // Formula for total xp to hit next level
                                $lastLevelXp;

                                if ($currentLevel == 1) {       // If user is level 1, total xp required to hit user's current level was 0
                                    $lastLevelXp = 0;
                                } else {
                                    $lastLevelXp = round(((($currentLevel)/ $x) ** $y) , -1);   // Formula for total xp to hit user's current level
                                }

                                $requiredXp = $nextLevelXp - $currentXp;        // Calculate how far away (in terms of xp) user is from hitting next level

                                $progress = (($currentXp - $lastLevelXp) / ($nextLevelXp - $lastLevelXp)) * 100;    // Calculate percentage progress user has currently made from current level to next level
                            ?>
                            <progress class="progress-bar" value="<?= htmlspecialchars($progress);  // Set progress bar value as this percentage progress ?>" max="100"></progress> 
                            <div class="next-level-xp">
                                <i>Next Level:</i> 
                                <i><?= htmlspecialchars($requiredXp);   // Xp required to reach next level ?></i>       
                                <i>xp</i>
                            </div>
                        </div>

                        <!-- User's next level -->
                        <div class="card" style="background-color:rgb(68, 19, 113); padding:5px; padding-left:15px; padding-right:15px; width:60px; height:50px; text-align:center; align-items:center">
                            <?php if ($user["current_level"] == 100) {      // If user level 100 (max level), set next level as 100 ?>
                                <p class="logout-text" style="font-weight: bolder; font-size: 1.5rem;"><?= htmlspecialchars($user["current_level"]); ?></p>
                            <?php } else {          // If user level not max, set next level as current level + 1 ?>
                                <p class="logout-text" style="font-weight: bolder; font-size: 1.5rem;"><?= htmlspecialchars($user["current_level"] + 1); ?></p>
                            <?php } }?>    
                        </div>

                    </div>
                </div>

            </div>

        </main>

    </div>

</body>
</html>