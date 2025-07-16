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


// --- CALCULATE IF USER'S LEVEL HAS CHANGED

$currentLevel = $user["current_level"];     // User's current level
$currentXp = $user["current_xp"];           // User's current xp total

$x = 0.05;      // Define values for constants in formula 
$y = 1.6;

$nextLevelXp = round(((($currentLevel + 1)/ $x) ** $y) , -1);       // Formula for calculating xp required to reach next level

$levelChanged = false;                      // If user's current xp total is more than xp total required for next level, set levelChanged as true
if ($currentXp >= $nextLevelXp) {           // (This variable will be used in js below to determine whether user level needs to be increased)
    $levelChanged = true;
}

// ---


// --- CALCULATE HOW LONG SINCE USER LAST CLAIMED DAILY REWARD

$currentDateTime = strtotime(date("Y-m-d H:i:s"));    // Current time
$dailyClaimedTime = strtotime($user["daily_claimed_time"]);   // Time user last claimed daily reward
$differenceInHours = ($currentDateTime - $dailyClaimedTime) / 3600;     // Difference between two in hours                

// ---


// --- CALCULATE DAILY REWARD BASED ON USER LEVEL

$dailyXp = ((2 * intdiv($currentLevel, 5)) + 5) * 20;       // Formula to calculate user's daily xp reward based on user's level
$dailyCoins = (2 * intdiv($currentLevel, 5)) + 5;           // Formula to calculate user's daily coins reward based on user's level

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards</title>
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
                <form action="../progression/progression.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-graph-up-arrow" style="padding-right:5px"></i><p style="padding-right: 5px;">Progression</p></div></button>
                </form>
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-coin" style="padding-right:5px"></i><p style="padding-right: 5px;">Rewards</p></div></button>
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
                        <div class="card-header">Rewards<i class="bi bi-coin" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); }?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Reward card for when daily reward is ready to claim -->
                <div class="card" style = "background:linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255))" id="daily-reward-card" <?php if ($differenceInHours < 24) { echo "hidden"; }?>>
                    <div class="card-header" style="color:white;">Daily Reward<i class="bi bi-calendar-week" style="padding-left:8px"></i></div>
                    <div class = "daily-rewards-container">

                        <!-- Daily XP Reward -->
                        <div class="reward-container">
                            <div class="card" style="height:100%; background: linear-gradient(135deg,rgb(90, 239, 249), rgb(161, 253, 145)); text-align:center">
                                <div class = "sub-header"><p class="logout-text"><i>XP Reward</i></p></div>
                                <div style="display:flex;align-items:baseline;justify-content:center;padding-top:10px">
                                    <div class="daily-reward"><p class="logout-text" id="daily-xp"></p></div>
                                    <p style="font-weight:bold; color: rgb(0, 149, 255);"><i>xp</i></p>
                                </div>
                            </div> 
                        </div>

                        <!-- Daily Coins Reward -->
                        <div class="reward-container">
                            <div class="card" style="height:100%; background: linear-gradient(135deg,rgb(90, 239, 249), rgb(161, 253, 145)); text-align:center">
                                <div class = "sub-header"><p class="logout-text"><i><i class="bi bi-currency-exchange" style="padding-right:5px;"></i> Reward</i></p></div>
                                <div style="display:flex;align-items:center;justify-content:center;padding-top:10px">
                                    <i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; font-size:1.5rem; color:goldenrod;"></i>
                                    <div class="daily-reward"><p class="logout-text" id="daily-coins"></p></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Button to claim daily rewards -->
                    <div class = "claim-rewards-container">
                        <form method="post" id="daily-rewards-form">
                            <button class="main-btn" style="width: 200px; color: white;" id="claim-rewards-button">Claim Rewards</button>
                        </form>
                    </div>
                </div>

                <!-- Daily Reward card for when daily reward is not ready to claim -->
                <div class="card" style = "background:linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255));height:300px" id="daily-reward-claimed-card" <?php if ($differenceInHours >= 24) { echo "hidden"; } ?>>
                    <div class="card-header" style="color:white;">Daily Reward<i class="bi bi-calendar-week" style="padding-left:8px"></i></div>
                    <div class="card-header" style="color:white; text-align: center; font-size:2.75rem;padding-top:40px"><i>Reward Claimed!</i></div>
                    <div class="card-header" style="color:white; text-align:center;">Come back in 

                        <!-- Time remaining until daily reward becomes available -->
                        <?php if ($differenceInHours < 24) { 
                            $currentTime = strtotime(date("Y-m-d H:i:s"));                  // Current time
                            $availableTime = strtotime($user["daily_claimed_time"] . " +1 day");    // 24 hours after last time daily reward was claimed
                            $diffInSeconds = $availableTime - $currentTime;                                   // Difference between them in seconds
                            $hoursRemaining = floor($diffInSeconds / 3600);                              // Difference between them in hours rounded down
                            $minutesRemaining = floor(($diffInSeconds % 3600) / 60);                     // Remainder of difference between them in hours, as minutes
                        ?>
                        <?= htmlspecialchars(" $hoursRemaining hours and $minutesRemaining minutes ");      // Display hours and minutes remaining ?>    
                        <?php } ?> 
                        for your next daily reward...
                    </div>
                </div>

                <!-- Card containing information about rewards -->
                <div class="card" style="padding-bottom: 30px;">
                    <div class="header-container">
                        <div class="card-header">Information<i class="bi bi-info-circle" style="padding-left:8px"></i></div>
                    </div>
                    <em style="font-size:large;font-weight:lighter; color:rgb(68, 19, 113);padding:10px">
                        As you Rank Up, your Daily Rewards will improve. Keep ranking up to unlock better rewards!
                    </em>
                </div>

            </div>


            <!-- Contains overlay displaying xp and coins gained message -->
            <div class="overlay" id="xp-overlay">
                <div class="level-up-container">
                    <i id="xp-gained" class="logout-text" style="font-weight: bold; font-size: 4rem;"></i>
                </div>
                <br>
                <div class="level-up-container" style="align-items: center;">
                    <i id="coins-gained" class="logout-text" style="font-weight: bold; font-size: 4rem;"></i>
                </div>
            </div>

            <!-- Contains overlay displaying level up message -->
            <div class="overlay" id="level-overlay">
                <div class="level-up-container">
                    <i class="logout-text" style="font-weight: bold; font-size: 4rem;">LEVEL UP!</i>
                    <div class="my-level-container">
                        <div class="card" style="background: linear-gradient(to right, rgb(3, 244, 107), cyan); width: 200px; height:210px; text-align:center">
                            <div class = "sub-header"><p><i>Level</i></p></div>
                            <div class="my-level"><p style="color:rgb(68, 19, 113)"><?= htmlspecialchars($user["current_level"]); ?></p></div>
                        </div>
                    </div>
                </div>
            </div>

        </main>

    </div>

    <script>

        // --- INCREASE USER'S LEVEL IF LEVEL CHANGE IS REQUIRED

        var levelChanged = <?php echo json_encode($levelChanged); ?>;   // Fetch php variable determining if level has changed
        var currentLevel = <?php echo json_encode($currentLevel); ?>;   // Fetch php variable containing current user's level
        currentLevel = parseInt(currentLevel, 10);                             // Set current level as Int 
        var newLevel = currentLevel + 1;                                       // Increment current level

        if (levelChanged == true) {              // If level has changed
            
            fetch("../progression/level-up-process.php", {      // Post new level to php file which updates user's level
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ newLevel:newLevel })     // Pass value for new level
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                                     // If level successfully updated
                    sessionStorage.setItem('levelSuccess', 'true');     // Create session variable levelSuccess and set as true 
                                                                        // (This will be used to determine if level up message should be displayed)
                    window.location.reload();                           // Reload the page
                } else {
                    console.log("Error claiming rewards:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));
        }

        window.addEventListener("load", function() {                    // When page is loaded/reloaded
            if (sessionStorage.getItem('levelSuccess') === 'true') {    // If levelSuccess is true
                var overlay = document.getElementById("level-overlay");
                overlay.style.display = "flex";                         // Display level up message

                setTimeout(() => {
                    overlay.style.display = "none";                     // Display message for 3 seconds
                }, 3000);
                sessionStorage.removeItem('levelSuccess');              // Remove session variable levelSuccess so on future page reloads, level up message isn't displayed
            }
        });

        // ---


        // --- CLAIM DAILY REWARD
        
        var dailyXp = <?php echo json_encode($dailyXp); ?>;             // Fetch php variable containing xp from daily reward
        var dailyCoins = <?php echo json_encode($dailyCoins); ?>;       // Fetch php variable containing coins from daily reward

        dailyXpContainer = document.getElementById("daily-xp");                // Display these rewards in daily reward secton
        dailyCoinsContainer = document.getElementById("daily-coins");

        dailyXpContainer.innerHTML = dailyXp;
        dailyCoinsContainer.innerHTML = dailyCoins;

        const claimRewardsButton = document.getElementById("claim-rewards-button");

        claimRewardsButton.addEventListener("click", () => {            // When claim rewards button clicked 
            event.preventDefault();

            const xpGained = document.getElementById("xp-gained");          
            const coinsGained = document.getElementById("coins-gained");
            
            xpGained.innerHTML = `+${dailyXp}xp` ;
            coinsGained.innerHTML = `+<i class="bi bi-currency-exchange" style="padding-right:5px;padding-bottom:15px; font-weight:bold; font-size:2.5rem; color:goldenrod;"></i>${dailyCoins}`;

            var overlay = document.getElementById("xp-overlay");        
            overlay.style.display = "flex";         // Display xp and coins gained message
            overlay.style.flexDirection = "column";
            overlay.style.alignItems = "center";
            overlay.style.justifyContent = "center";

            fetch("rewards-process.php", {          // Post request to php file to add rewards to user's account
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ dailyXp:dailyXp , dailyCoins:dailyCoins })   // Pass values for xp and coins from daily reward
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                 // If rewards claimed successfully
                    setTimeout(() => {              // Display message for 2 seconds    
                        overlay.style.display = "none";
                        window.location.reload();   // Reload page   
                    }, 2000);                       // This is essential as it allows to check if user has leveled up after the xp increase 
                                                                            
                } else {
                    console.log("Error claiming rewards:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));
       
        })

        // ---


    </script>

</body>
</html>