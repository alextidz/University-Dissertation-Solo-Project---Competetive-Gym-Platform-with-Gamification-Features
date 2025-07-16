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


// --- FETCH ALL LEAGUES THE CURRENT USER IS REGISTERED TO 

$userId = $user["user_id"];         // Store user id
$rewardClaimed = 0;                 // Variable indicating league final reward not claimed

$mysqli = require __DIR__ . "/database.php";    // Database connection

// Fetch all leagues that user is registered to where they havent claimed end of league reward from database
$sql = "SELECT league_entries.*, leagues.*
        FROM league_entries
        JOIN leagues ON league_entries.league_id = leagues.league_id
        WHERE league_entries.user_id = ? AND league_entries.final_reward_claimed = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $userId, $rewardClaimed); 
$stmt->execute();
$result = $stmt->get_result();

$leagues = [];                                  // Store records in leagues array
while ($row = $result->fetch_assoc()) {
    $leagues[] = $row;
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leagues</title>
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
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-bar-chart-steps" style="padding-right:5px"></i><p style="padding-right: 5px;">My Leagues</p></div></button>
                </form>
                <form action="../progression/progression.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-graph-up-arrow" style="padding-right:5px"></i><p style="padding-right: 5px;">Progression</p></div></button>
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
                        <div class="card-header">My Leagues<i class="bi bi-bar-chart-steps" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); }?></div>
                        </div>
                    </div>
                </div>


                <!-- Container for create and join league cards -->
                <div class="create-join-container">

                    <!-- Card where user can create league -->
                    <div class="card" style="background:rgb(68, 19, 113); width: 40%;" id="create-league-card">

                        <div class="create-leaderboard-container">
                            <div class="card-header" style="color:white;">Create League<i class="bi bi-plus-circle" style="padding-left:8px"></i></div>

                            <!-- Button which opens modal for creating league -->
                            <div class="create-leaderboard-button-container">
                                <button class="my-rankings-category-button" style="padding-right:6.75px" onclick="openCreateLeagueModal()"><i class="bi bi-plus-circle" style="padding-left:8px"></i></button>
                            </div>
                        </div>

                    </div>

                    <!-- Card where user can join a league -->
                    <div class="card" style="background:linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255)); width: 58%;" id="join-league-card">

                        <div class="join-leaderboard-container">

                            <div class="card-header" style="color:white; padding-bottom: 10px;">Join League<i class="bi bi-person-fill-add" style="padding-left:5px"></i></div>

                            <div class="join-leaderboard-right-container">
                                <div class="join-leaderboard-inner-container">

                                    <!-- Input where user enters join code -->
                                    <input class="join-leaderboard-code" type="text" id="join-league-code" name="join-league-code" placeholder="Enter join code" spellcheck="false" required></input>

                                    <!-- Button where user submits code to join league when clicked -->
                                    <button class="main-btn" style="width: 100px; color:white" onclick="joinLeague()">Join</button>
                                    
                                </div>

                                <!-- Error message if user enters invalid join code -->
                                <em class="validation-text" style="padding: 10px; font-size: medium;" id="join-league-validation-text"></em>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Card containing all leagues current user is a part of -->
                <div class="card" id="my-leagues-card">
                    <div class="card-header" style="padding-bottom: 10px;">My Leagues<i class="bi bi-bar-chart-steps" style="padding-left:8px"></i></div>
                    <hr>

                    <!-- List of league's current user is part of -->
                    <div class="leaderboard-scroll-container">
                        <ul id="my-leagues-list" style="padding-bottom: 10px;">

                        </ul>
                    </div>
                </div>
            
            </div>


            <!-- Modal for creating league -->
            <dialog class="modal" id="create-league-modal" style="width:710px; overflow-x:hidden;">
                
                <div class="create-leaderboard-modal-header" style="padding-right: 270px;">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeCreateLeagueModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px;">CREATE LEAGUE</div>
                </div>

                <div class="modal-scroll-container">

                    <!-- Input for league name -->
                    <div class="new-entry-exercise-container" style="padding-left: 20px;">
                        <p class="new-entry-label" style="padding-bottom: 5px;">League Name</p>
                        <input class="create-leaderboard-name" style="width:306px" type="text" id="create-league-name" name="create-league-name" placeholder="League Name" spellcheck="false" required></input>
                        <br>
                        <em class="validation-text" style="padding: 10px; font-size: medium;" id="create-league-name-validation-text"></em>
                    </div>

                    <hr>

                    <!-- Input for league duration -->
                    <div class="new-entry-exercise-container" style="padding-left: 20px;">
                        <p class="new-entry-label" style="padding-bottom: 5px;">League Duration</p>
                        <div class="search-leaderboards-exercise-container" style="padding-left: 0px;">
                            <input class="create-leaderboard-name" style="width:260px" type="text" id="create-league-duration" name="create-league-duration" placeholder="League Duration" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick="showDurations()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Validation text if duration is empty on submit -->
                        <em class="validation-text" style="padding: 10px; font-size: medium;" id="create-league-duration-validation-text"></em>
                    </div>

                    <!-- Container which displays list of possible durations for league -->
                    <div class="search-leaderboards-results-container" style="padding-top: 5px; padding-left: 25px;">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="create-league-duration-list">
                            
                            </ul>
                        </div>           
                    </div> 

                    <hr>

                    <!-- Leaderboard 1 -->
                    <p class="new-entry-label" style="padding-top: 5px; color: rgb(68, 19, 113);">Leaderboard 1</p>
                    <div class="search-leaderboards-container" style="padding-top: 5px; padding-bottom: 0px;">

                        <!-- Input containing selected exercise for leaderboard 1 -->
                        <div class="search-leaderboards-exercise-container">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-exercise-1" name="create-leaderboards-exercise-1" placeholder="Select an Exercise" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick="showExercises1()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Input containing selected number of reps for leaderboard 1 -->
                        <div class="search-leaderboards-reps-container" style="padding-right: 0px; width: 325px;">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-reps-1" name="create-leaderboards-reps-1" placeholder="Select Number of Reps" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick = "showReps1()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                    </div>

                    <!-- Validation text for if exercise of number of reps for this leaderboard is empty on submit -->
                    <em class="validation-text" style="padding-left: 25px; font-size: medium;" id="create-league-validation-text-1"></em>

                    <!-- Container which displays list of exercises or list of options for number of reps -->
                    <div class="search-leaderboards-results-container" style="padding-top: 5px; padding-left: 25px;">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="create-league-list-1">
                            
                            </ul>
                        </div>           
                    </div> 

                    <hr>

                    <!-- Leaderboard 2 -->
                    <p class="new-entry-label" style="padding-top: 5px; color: rgb(68, 19, 113);">Leaderboard 2</p>
                    <div class="search-leaderboards-container" style="padding-top: 5px; padding-bottom: 0px;">

                        <!-- Input containing selected exercise for leaderboard 2 -->
                        <div class="search-leaderboards-exercise-container">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-exercise-2" name="create-leaderboards-exercise-2" placeholder="Select an Exercise" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick="showExercises2()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Input containing selected number of reps for leaderboard 2 -->
                        <div class="search-leaderboards-reps-container" style="padding-right: 0px; width: 325px;">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-reps-2" name="create-leaderboards-reps-2" placeholder="Select Number of Reps" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick = "showReps2()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                    </div>

                    <!-- Validation text for if exercise of number of reps for this leaderboard is empty on submit --> 
                    <em class="validation-text" style="padding-left: 25px; font-size: medium;" id="create-league-validation-text-2"></em>

                    <!-- Container which displays list of exercises or list of options for number of reps -->
                    <div class="search-leaderboards-results-container" style="padding-top: 5px;">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="create-league-list-2">
                            
                            </ul>
                        </div>           
                    </div> 

                    <hr>

                    <!-- Leaderboard 3 -->
                    <p class="new-entry-label" style="padding-top: 5px; color: rgb(68, 19, 113);">Leaderboard 3</p>
                    <div class="search-leaderboards-container" style="padding-top: 5px; padding-bottom: 0px;">

                        <!-- Input containing selected exercise for leaderboard 3 -->
                        <div class="search-leaderboards-exercise-container">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-exercise-3" name="create-leaderboards-exercise-3" placeholder="Select an Exercise" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick="showExercises3()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Input containing selected number of reps for leaderboard 3 -->
                        <div class="search-leaderboards-reps-container" style="padding-right: 0px; width: 325px;">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-reps-3" name="create-leaderboards-reps-3" placeholder="Select Number of Reps" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick = "showReps3()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                    </div>

                    <!-- Validation text for if exercise of number of reps for this leaderboard is empty on submit -->
                    <em class="validation-text" style="padding-left: 25px; font-size: medium;" id="create-league-validation-text-3"></em>

                    <!-- Container which displays list of exercises or list of options for number of reps -->
                    <div class="search-leaderboards-results-container" style="padding-top: 5px;">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="create-league-list-3">
                            
                            </ul>
                        </div>           
                    </div> 

                    <hr>

                    <!-- Leaderboard 4 -->
                    <p class="new-entry-label" style="padding-top: 5px; color: rgb(68, 19, 113);">Leaderboard 4</p>
                    <div class="search-leaderboards-container" style="padding-top: 5px; padding-bottom: 0px;">

                        <!-- Input containing selected exercise for leaderboard 4 -->
                        <div class="search-leaderboards-exercise-container">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-exercise-4" name="create-leaderboards-exercise-4" placeholder="Select an Exercise" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick="showExercises4()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Input containing selected number of reps for leaderboard 4 -->
                        <div class="search-leaderboards-reps-container" style="padding-right: 0px; width: 325px;">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-reps-4" name="create-leaderboards-reps-4" placeholder="Select Number of Reps" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick = "showReps4()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                    </div>

                    <!-- Validation text for if exercise of number of reps for this leaderboard is empty on submit -->
                    <em class="validation-text" style="padding-left: 25px; font-size: medium;" id="create-league-validation-text-4"></em>

                    <!-- Container which displays list of exercises or list of options for number of reps -->
                    <div class="search-leaderboards-results-container" style="padding-top: 5px;">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="create-league-list-4">
                            
                            </ul>
                        </div>           
                    </div> 

                    <hr>

                    <!-- Leaderboard 5 -->
                    <p class="new-entry-label" style="padding-top: 5px; color: rgb(68, 19, 113);">Leaderboard 5</p>
                    <div class="search-leaderboards-container" style="padding-top: 5px; padding-bottom: 0px;">

                        <!-- Input containing selected exercise for leaderboard 5 -->
                        <div class="search-leaderboards-exercise-container">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-exercise-5" name="create-leaderboards-exercise-5" placeholder="Select an Exercise" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick="showExercises5()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Input containing selected number of reps for leaderboard 5 -->
                        <div class="search-leaderboards-reps-container" style="padding-right: 0px; width: 325px;">
                            <input class="search-leaderboards-input" type="text" id="create-leaderboards-reps-5" name="create-leaderboards-reps-5" placeholder="Select Number of Reps" spellcheck="false" required></input>
                            <button class="my-codes-button" style="height:45px; width:45px;" onclick = "showReps5()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                    </div>

                    <!-- Validation text for if exercise of number of reps for this leaderboard is empty on submit -->
                    <em class="validation-text" style="padding-left: 25px; font-size: medium;" id="create-league-validation-text-5"></em>

                    <!-- Container which displays list of exercises or list of options for number of reps -->
                    <div class="search-leaderboards-results-container" style="padding-top: 5px;">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="create-league-list-5">
                            
                            </ul>
                        </div>           
                    </div> 

                    <hr>

                </div>

                <!-- Button which creates league when clicked -->
                <div class="new-entry-submit-container" style="padding-top: 20px;">
                    <button class="friends-button" style="width:125px;" onclick="createLeague()"><p class="logout-text">Create</p></button>
                </div>
            

            </dialog>  


            <!-- Modal for confirming league created successfully -->
            <dialog class="modal" id="create-league-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">League created successfully!</p>
                    <p class="store-text">Keep track of your league in the <br> <b class="small-sub-header">My Leagues</b> section</p>
                </div>

                <!-- Contains join code for league -->
                <div class="delete-account-text">
                    <p class="small-sub-header">JOIN CODE:</p>
                    <p class="join-code" id="create-league-join-code"></p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;" style="padding: 10px;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeCreateLeagueSuccessModal()">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming league joined successfully -->
            <dialog class="modal" id="join-league-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">League joined successfully!</p>
                    <p class="store-text">Keep track of this league in the <br> <b class="small-sub-header">My Leagues</b> section</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeJoinLeagueSuccessModal()">Dismiss</button>
                </div>

            </dialog>


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

        if (levelChanged == true) {             // If level has changed
            
            fetch("../progression/level-up-process.php", {     // Post new level to php file which updates user's level
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ newLevel:newLevel })     // Pass value for new level
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                                         // If level successfully updated
                    sessionStorage.setItem('levelSuccess', 'true');         // Create session variable levelSuccess and set as true 
                                                                            // (This will be used to determine if level up message should be displayed)
                    window.location.reload();                               // Reload the page
                } else {
                    console.log("Error claiming rewards:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));
        }

        window.addEventListener("load", function() {                        // When page is loaded/reloaded
            if (sessionStorage.getItem('levelSuccess') === 'true') {        // If levelSuccess is true
                var overlay = document.getElementById("level-overlay");
                overlay.style.display = "flex";                             // Display level up message

                setTimeout(() => {
                    overlay.style.display = "none";                         // Display message for 3 seconds
                }, 3000);
                sessionStorage.removeItem('levelSuccess');                  // Remove session variable levelSuccess so on future page reloads, level up message isn't displayed
            }
        });

        // ---


        // --- STORE HTML ELEMENTS

        // My leagues

        const myLeaguesCard = document.getElementById("my-leagues-card");
        const myLeaguesList = document.getElementById("my-leagues-list");

        // Create league

        const createLeagueCard = document.getElementById("create-league-card");
        const createLeagueModal = document.getElementById("create-league-modal");
        const createLeagueName = document.getElementById("create-league-name");
        const createLeagueDuration = document.getElementById("create-league-duration");
        const createLeaderboardsExercise1 = document.getElementById("create-leaderboards-exercise-1");
        const createLeaderboardsReps1 = document.getElementById("create-leaderboards-reps-1");
        const createLeaderboardsExercise2 = document.getElementById("create-leaderboards-exercise-2");
        const createLeaderboardsReps2 = document.getElementById("create-leaderboards-reps-2");
        const createLeaderboardsExercise3 = document.getElementById("create-leaderboards-exercise-3");
        const createLeaderboardsReps3 = document.getElementById("create-leaderboards-reps-3");
        const createLeaderboardsExercise4 = document.getElementById("create-leaderboards-exercise-4");
        const createLeaderboardsReps4 = document.getElementById("create-leaderboards-reps-4");
        const createLeaderboardsExercise5 = document.getElementById("create-leaderboards-exercise-5");
        const createLeaderboardsReps5 = document.getElementById("create-leaderboards-reps-5");

        const createLeagueNameValidationText = document.getElementById("create-league-name-validation-text");
        const createLeagueDurationValidationText = document.getElementById("create-league-duration-validation-text");
        const createLeagueValidationText1 = document.getElementById("create-league-validation-text-1");
        const createLeagueValidationText2 = document.getElementById("create-league-validation-text-2");
        const createLeagueValidationText3 = document.getElementById("create-league-validation-text-3");
        const createLeagueValidationText4 = document.getElementById("create-league-validation-text-4");
        const createLeagueValidationText5 = document.getElementById("create-league-validation-text-5");
        
        const createLeagueDurationList = document.getElementById("create-league-duration-list");
        const createLeagueList1 = document.getElementById("create-league-list-1");       
        const createLeagueList2 = document.getElementById("create-league-list-2");
        const createLeagueList3 = document.getElementById("create-league-list-3");
        const createLeagueList4 = document.getElementById("create-league-list-4");
        const createLeagueList5 = document.getElementById("create-league-list-5");

        // Create league success

        const createLeagueSuccessModal = document.getElementById("create-league-success-modal");
        const createLeagueJoinCode = document.getElementById("create-league-join-code");

        // Join league

        const joinLeagueCard = document.getElementById("join-league-card");
        const joinLeagueCode = document.getElementById("join-league-code");
        const joinLeagueValidationText = document.getElementById("join-league-validation-text");

        // Join league success

        const joinLeagueSuccessModal = document.getElementById("join-league-success-modal");

        // ---


        // --- DISPLAY LIST OF USER'S LEAGUES

        const leagues = <?php echo json_encode($leagues); ?>;           // Fetch php variable containing list of current user's leagues        

        if (leagues.length == 0) {        // If user isn't part of any leagues, dispay relevant message

            const firstMessage = document.createElement("li");
            firstMessage.classList.add("my-leaderboards-first-message");
            firstMessage.innerHTML = "You aren't currently a member of any leagues";

            const secondMessage = document.createElement("li");
            secondMessage.classList.add("my-leaderboards-second-message");
            secondMessage.innerHTML = "Join or Create your own leagues above!";

            myLeaguesList.appendChild(firstMessage);
            myLeaguesList.appendChild(secondMessage);

        } else {
            
            for (let i = 0; i < leagues.length; i++) {             // Loop through leagues array
                const league = leagues[i];

                const listItem = document.createElement("li");          // Create and display list of buttons containing user's league names
                const button = document.createElement("button");            
                button.addEventListener("click", () => {                // When button clicked
                    
                    fetch('pass-data.php', {            // Post php request to store league in session variable
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ data:league })   // Pass in data for league
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = "myleagues-leaderboards.php";    // Relocate to page showing info about selected league
                        } else {
                            console.error("Failed to pass league");
                        }
                    });     
                    
                })

                const leagueButtonContainer = document.createElement("div");       // Create HTML elements containing league's name
                const leagueRankContainer = document.createElement("div");
                const leagueNameContainer = document.createElement("div");

                const leagueRankInnerContainer = document.createElement("div");
                const leagueRank = document.createElement("p");
                const leagueName = document.createElement("p");
                
                const hr = document.createElement("hr");

                button.classList.add("search-result-button");

                leagueButtonContainer.classList.add("leaderboard-button-container");
                leagueRankContainer.classList.add("leaderboard-rank-container");
                leagueNameContainer.classList.add("leaderboard-username-container");

                leagueRankInnerContainer.classList.add("leaderboard-rank-inner-container");
                leagueRank.classList.add("leaderboard-rank");
                leagueName.classList.add("leaderboard-username"); 

                leagueRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i>`;
                leagueName.innerHTML = league.league_name;          // League name

                leagueRankInnerContainer.appendChild(leagueRank);
                leagueRankContainer.appendChild(leagueRankInnerContainer);
                leagueNameContainer.appendChild(leagueName);

                leagueButtonContainer.appendChild(leagueRankContainer);
                leagueButtonContainer.appendChild(leagueNameContainer);

                button.appendChild(leagueButtonContainer);
                listItem.appendChild(button);
                myLeaguesList.appendChild(listItem);
                myLeaguesList.appendChild(hr);
            }

        }

        // ---


        // --- ADD FUNCTIONALITY TO SHOW LIST OF DURATIONS WHEN CREATING LEAGUE

        const durations = [              // Array containing list of all durations
            1,
            3,
            6,
            12
        ];

        function showDurations() {              // Function for displaying the list of durations

            createLeagueDurationValidationText.innerHTML = "";      // Remove any displayed validation text
            createLeagueDurationList.innerHTML = "<hr>";            // Empty display list

            for (let i = 0; i < durations.length; i++) {            // Loop through list of durations

                const listItem = document.createElement("li");              // For each duration, add button for that duration to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = durations[i] + " month(s)"; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {               // When button clicked
                    createLeagueDuration.value = button.textContent;        // Set value of currently selected duration to this button's duration
                    createLeagueDurationList.innerHTML = "";                // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeagueDurationList.appendChild(listItem);
                createLeagueDurationList.appendChild(hr);

            }

        }

        // ---


        // --- ADD FUNCTIONALITY TO SHOW LIST OF EXERCISES / LIST OF NUMBER OF REPS WHEN CREATING LEAGUE

        const exercises = [                         // Array containing list of all exercises
            "Barbell Bench Press (Flat)",
            "Barbell Bench Press (Incline)",
            "Barbell Bicep Curls",
            "Barbell Rows",
            "Barbell Shoulder Press",
            "Barbell Squats",
            "Deadlifts",
            "Dips",
            "Dumbell Bench Press (Flat)",
            "Dumbell Bench Press (Incline)",
            "Dumbell Bicep Curls",
            "Dumbell Rows",
            "Dumbell Shoulder Press",
            "Pull ups"
        ];

        const reps = [              // Array containing list of all number of reps 
            1,
            3,
            5,
            10
        ];

        // LEADERBOARD 1

        function showExercises1() {                      // Function for displaying the list of exercises for leaderboard 1

            createLeagueValidationText1.innerHTML = "";         // Remove any displayed validation text
            createLeagueList1.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < exercises.length; i++) {                    // Loop through list of exercises

                const listItem = document.createElement("li");              // For each exercise, add button for that exercise to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {                   // When button clicked
                    createLeaderboardsExercise1.value = button.textContent;     // Set value of currently selected exercise to this button's exercise
                    createLeagueList1.innerHTML = "";                           // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeagueList1.appendChild(listItem);
                createLeagueList1.appendChild(hr);
            }

        }

        function showReps1() {           // Function for displaying the list of number of reps for leaderboard 1

            createLeagueValidationText1.innerHTML = "";         // Remove any displayed validation text
            createLeagueList1.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < reps.length; i++) {                     // Loop through the list of number of reps
                const listItem = document.createElement("li");          // For each number of reps, add button for that number of reps to the display list
                const button = document.createElement("button");
                const div = document.createElement("div");
                const num = document.createElement("p");
                const hr = document.createElement("hr");

                button.textContent = reps[i] + " x Rep(s)"; 
                listItem.classList.add("create-leaderboard-list-item")
                button.classList.add("create-leaderboard-button"); 

                if (reps[i] == "1") {                                   // Colour code each number of reps for clear distinction and easier identification for user
                    button.style.color = "rgb(255, 82, 82)";        
                } else if (reps[i] == "3") {
                    button.style.color = "rgb(199, 199, 43)";
                } else if (reps[i] == "5") {
                    button.style.color = "rgb(48, 235, 48)";
                } else if (reps[i] == "10") {
                    button.style.color = "rgb(83, 173, 247)";
                }

                button.addEventListener("click", function() {                   // When button clicked

                    createLeaderboardsReps1.value = button.textContent;           // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                             // Colour code currently selected number of reps to match previous 
                        createLeaderboardsReps1.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        createLeaderboardsReps1.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        createLeaderboardsReps1.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        createLeaderboardsReps1.style.color = "rgb(83, 173, 247)";
                    }

                    createLeagueList1.innerHTML = "";                   // Empty display list
                });
                            
                listItem.appendChild(button);
                createLeagueList1.appendChild(listItem);
                createLeagueList1.appendChild(hr);
            }
        }

        // LEADERBOARD 2

        function showExercises2() {                      // Function for displaying the list of exercises for leaderboard 2

            createLeagueValidationText2.innerHTML = "";         // Remove any displayed validation text
            createLeagueList2.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < exercises.length; i++) {                    // Loop through list of exercises

                const listItem = document.createElement("li");              // For each exercise, add button for that exercise to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {                   // When button clicked
                    createLeaderboardsExercise2.value = button.textContent;     // Set value of currently selected exercise to this button's exercise
                    createLeagueList2.innerHTML = "";                           // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeagueList2.appendChild(listItem);
                createLeagueList2.appendChild(hr);
            }

        }

        function showReps2() {           // Function for displaying the list of number of reps for leaderboard 2

            createLeagueValidationText2.innerHTML = "";         // Remove any displayed validation text
            createLeagueList2.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < reps.length; i++) {                     // Loop through the list of number of reps
                const listItem = document.createElement("li");          // For each number of reps, add button for that number of reps to the display list
                const button = document.createElement("button");
                const div = document.createElement("div");
                const num = document.createElement("p");
                const hr = document.createElement("hr");

                button.textContent = reps[i] + " x Rep(s)"; 
                listItem.classList.add("create-leaderboard-list-item")
                button.classList.add("create-leaderboard-button"); 

                if (reps[i] == "1") {                                   // Colour code each number of reps for clear distinction and easier identification for user
                    button.style.color = "rgb(255, 82, 82)";        
                } else if (reps[i] == "3") {
                    button.style.color = "rgb(199, 199, 43)";
                } else if (reps[i] == "5") {
                    button.style.color = "rgb(48, 235, 48)";
                } else if (reps[i] == "10") {
                    button.style.color = "rgb(83, 173, 247)";
                }

                button.addEventListener("click", function() {                   // When button clicked

                    createLeaderboardsReps2.value = button.textContent;           // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                             // Colour code currently selected number of reps to match previous 
                        createLeaderboardsReps2.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        createLeaderboardsReps2.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        createLeaderboardsReps2.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        createLeaderboardsReps2.style.color = "rgb(83, 173, 247)";
                    }

                    createLeagueList2.innerHTML = "";                   // Empty display list
                });
                            
                listItem.appendChild(button);
                createLeagueList2.appendChild(listItem);
                createLeagueList2.appendChild(hr);
            }
        }

        // LEADERBOARD 3

        function showExercises3() {                      // Function for displaying the list of exercises for leaderboard 3

            createLeagueValidationText3.innerHTML = "";         // Remove any displayed validation text
            createLeagueList3.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < exercises.length; i++) {                    // Loop through list of exercises

                const listItem = document.createElement("li");              // For each exercise, add button for that exercise to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {                   // When button clicked
                    createLeaderboardsExercise3.value = button.textContent;     // Set value of currently selected exercise to this button's exercise
                    createLeagueList3.innerHTML = "";                           // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeagueList3.appendChild(listItem);
                createLeagueList3.appendChild(hr);
            }

        }

        function showReps3() {           // Function for displaying the list of number of reps for leaderboard 3

            createLeagueValidationText3.innerHTML = "";         // Remove any displayed validation text
            createLeagueList3.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < reps.length; i++) {                     // Loop through the list of number of reps
                const listItem = document.createElement("li");          // For each number of reps, add button for that number of reps to the display list
                const button = document.createElement("button");
                const div = document.createElement("div");
                const num = document.createElement("p");
                const hr = document.createElement("hr");

                button.textContent = reps[i] + " x Rep(s)"; 
                listItem.classList.add("create-leaderboard-list-item")
                button.classList.add("create-leaderboard-button"); 

                if (reps[i] == "1") {                                   // Colour code each number of reps for clear distinction and easier identification for user
                    button.style.color = "rgb(255, 82, 82)";        
                } else if (reps[i] == "3") {
                    button.style.color = "rgb(199, 199, 43)";
                } else if (reps[i] == "5") {
                    button.style.color = "rgb(48, 235, 48)";
                } else if (reps[i] == "10") {
                    button.style.color = "rgb(83, 173, 247)";
                }

                button.addEventListener("click", function() {                   // When button clicked

                    createLeaderboardsReps3.value = button.textContent;           // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                             // Colour code currently selected number of reps to match previous 
                        createLeaderboardsReps3.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        createLeaderboardsReps3.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        createLeaderboardsReps3.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        createLeaderboardsReps3.style.color = "rgb(83, 173, 247)";
                    }

                    createLeagueList3.innerHTML = "";                   // Empty display list
                });
                            
                listItem.appendChild(button);
                createLeagueList3.appendChild(listItem);
                createLeagueList3.appendChild(hr);
            }
        }

        // LEADERBOARD 4

        function showExercises4() {                      // Function for displaying the list of exercises for leaderboard 4

            createLeagueValidationText4.innerHTML = "";         // Remove any displayed validation text
            createLeagueList4.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < exercises.length; i++) {                    // Loop through list of exercises

                const listItem = document.createElement("li");              // For each exercise, add button for that exercise to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {                   // When button clicked
                    createLeaderboardsExercise4.value = button.textContent;     // Set value of currently selected exercise to this button's exercise
                    createLeagueList4.innerHTML = "";                           // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeagueList4.appendChild(listItem);
                createLeagueList4.appendChild(hr);
            }

        }

        function showReps4() {           // Function for displaying the list of number of reps for leaderboard 4

            createLeagueValidationText4.innerHTML = "";         // Remove any displayed validation text
            createLeagueList4.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < reps.length; i++) {                     // Loop through the list of number of reps
                const listItem = document.createElement("li");          // For each number of reps, add button for that number of reps to the display list
                const button = document.createElement("button");
                const div = document.createElement("div");
                const num = document.createElement("p");
                const hr = document.createElement("hr");

                button.textContent = reps[i] + " x Rep(s)"; 
                listItem.classList.add("create-leaderboard-list-item")
                button.classList.add("create-leaderboard-button"); 

                if (reps[i] == "1") {                                   // Colour code each number of reps for clear distinction and easier identification for user
                    button.style.color = "rgb(255, 82, 82)";        
                } else if (reps[i] == "3") {
                    button.style.color = "rgb(199, 199, 43)";
                } else if (reps[i] == "5") {
                    button.style.color = "rgb(48, 235, 48)";
                } else if (reps[i] == "10") {
                    button.style.color = "rgb(83, 173, 247)";
                }

                button.addEventListener("click", function() {                   // When button clicked

                    createLeaderboardsReps4.value = button.textContent;           // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                             // Colour code currently selected number of reps to match previous 
                        createLeaderboardsReps4.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        createLeaderboardsReps4.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        createLeaderboardsReps4.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        createLeaderboardsReps4.style.color = "rgb(83, 173, 247)";
                    }

                    createLeagueList4.innerHTML = "";                   // Empty display list
                });
                            
                listItem.appendChild(button);
                createLeagueList4.appendChild(listItem);
                createLeagueList4.appendChild(hr);
            }
        }

        // LEADERBOARD 5

        function showExercises5() {                      // Function for displaying the list of exercises for leaderboard 5

            createLeagueValidationText5.innerHTML = "";         // Remove any displayed validation text
            createLeagueList5.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < exercises.length; i++) {                    // Loop through list of exercises

                const listItem = document.createElement("li");              // For each exercise, add button for that exercise to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {                   // When button clicked
                    createLeaderboardsExercise5.value = button.textContent;     // Set value of currently selected exercise to this button's exercise
                    createLeagueList5.innerHTML = "";                           // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeagueList5.appendChild(listItem);
                createLeagueList5.appendChild(hr);
            }

        }

        function showReps5() {           // Function for displaying the list of number of reps for leaderboard 5

            createLeagueValidationText5.innerHTML = "";         // Remove any displayed validation text
            createLeagueList5.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < reps.length; i++) {                     // Loop through the list of number of reps
                const listItem = document.createElement("li");          // For each number of reps, add button for that number of reps to the display list
                const button = document.createElement("button");
                const div = document.createElement("div");
                const num = document.createElement("p");
                const hr = document.createElement("hr");

                button.textContent = reps[i] + " x Rep(s)"; 
                listItem.classList.add("create-leaderboard-list-item")
                button.classList.add("create-leaderboard-button"); 

                if (reps[i] == "1") {                                   // Colour code each number of reps for clear distinction and easier identification for user
                    button.style.color = "rgb(255, 82, 82)";        
                } else if (reps[i] == "3") {
                    button.style.color = "rgb(199, 199, 43)";
                } else if (reps[i] == "5") {
                    button.style.color = "rgb(48, 235, 48)";
                } else if (reps[i] == "10") {
                    button.style.color = "rgb(83, 173, 247)";
                }

                button.addEventListener("click", function() {                   // When button clicked

                    createLeaderboardsReps5.value = button.textContent;           // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                             // Colour code currently selected number of reps to match previous 
                        createLeaderboardsReps5.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        createLeaderboardsReps5.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        createLeaderboardsReps5.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        createLeaderboardsReps5.style.color = "rgb(83, 173, 247)";
                    }

                    createLeagueList5.innerHTML = "";                   // Empty display list
                });
                            
                listItem.appendChild(button);
                createLeagueList5.appendChild(listItem);
                createLeagueList5.appendChild(hr);
            }
        }


        // ---


        // --- CREATE LEAGUE

        function createLeague() {                  // Function for creating league

            createLeagueNameValidationText.innerHTML = "";          // Set all validation text as empty
            createLeagueDurationValidationText.innerHTML = "";
            createLeagueValidationText1.innerHTML = "";
            createLeagueValidationText2.innerHTML = "";
            createLeagueValidationText3.innerHTML = "";
            createLeagueValidationText4.innerHTML = "";
            createLeagueValidationText5.innerHTML = "";

            const name = createLeagueName.value;                    // Store inputted league info
            const duration = createLeagueDuration.value;
            const exercise1 = createLeaderboardsExercise1.value;
            const reps1 = createLeaderboardsReps1.value
            const exercise2 = createLeaderboardsExercise2.value;
            const reps2 = createLeaderboardsReps2.value
            const exercise3 = createLeaderboardsExercise3.value;
            const reps3 = createLeaderboardsReps3.value
            const exercise4 = createLeaderboardsExercise4.value;
            const reps4 = createLeaderboardsReps4.value
            const exercise5 = createLeaderboardsExercise5.value;
            const reps5 = createLeaderboardsReps5.value

            var nameEmpty = false;              // Variables indicating if input fields are empty
            var durationEmpty = false;
            var leaderboard1Empty = false;
            var leaderboard2Empty = false;
            var leaderboard3Empty = false;
            var leaderboard4Empty = false;
            var leaderboard5Empty = false;

            if (name.trim() == "") {            // If league name empty, display relevant message
                nameEmpty = true;
                createLeagueNameValidationText.innerHTML = "Please enter league name";
            }

            if (duration == "") {               // If duration empty, display relevant message
                durationEmpty = true;
                createLeagueDurationValidationText.innerHTML = "Please select a duration";
            }

            if (exercise1 == "" || reps1 == "") {               // If exercise or number of reps for leaderboard 1 empty, display relevant message
                leaderboard1Empty = true;
                createLeagueValidationText1.innerHTML = "Please select exercise and number of reps for Leaderboard 1";
            }

            if (exercise2 == "" || reps2 == "") {               // If exercise or number of reps for leaderboard 2 empty, display relevant message
                leaderboard2Empty = true;
                createLeagueValidationText2.innerHTML = "Please select exercise and number of reps for Leaderboard 2";
            }

            if (exercise3 == "" || reps3 == "") {               // If exercise or number of reps for leaderboard 3 empty, display relevant message
                leaderboard3Empty = true;
                createLeagueValidationText3.innerHTML = "Please select exercise and number of reps for Leaderboard 3";
            }

            if (exercise4 == "" || reps4 == "") {               // If exercise or number of reps for leaderboard 4 empty, display relevant message
                leaderboard4Empty = true;
                createLeagueValidationText4.innerHTML = "Please select exercise and number of reps for Leaderboard 4";
            }

            if (exercise5 == "" || reps5 == "") {               // If exercise or number of reps for leaderboard 5 empty, display relevant message
                leaderboard5Empty = true;
                createLeagueValidationText5.innerHTML = "Please select exercise and number of reps for Leaderboard 5";
            }

            // If all fields have been filled
            if (!( nameEmpty || durationEmpty || leaderboard1Empty || leaderboard2Empty || leaderboard3Empty || leaderboard4Empty || leaderboard5Empty )) {         

                let joinCode = '';
                const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

                for (let i = 0; i < 8; i++) {               // Generate random 8 digit join code for league           

                    const randNum = Math.floor(Math.random() * characters.length);
                    joinCode += characters[randNum];
                }

                fetch("create-league-process.php", {        // Post request to php file to create new league
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    // Pass value for league name, duration, join code, and exercise and number of reps for the 5 leaderboards
                    body: JSON.stringify({ name:name , duration:duration, code:joinCode , exercise1:exercise1 , reps1:reps1 , exercise2:exercise2 , reps2:reps2 , exercise3:exercise3 , reps3:reps3 , exercise4:exercise4 , reps4:reps4 , exercise5:exercise5 , reps5:reps5 })     
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {                                     // If league successfully created
                        createLeagueModal.close();                          // Close modal for creating league
                        createLeagueJoinCode.innerHTML = joinCode;
                        createLeagueSuccessModal.showModal();               // Open modal confirming league created successfully, containing join code
                    } else {
                        console.log("Error creating league:", data.error);
                    }
                })
                .catch(error => console.log("Fetch error:", error));

            }

        }

        // ---


        // --- JOIN LEAGUE

        function joinLeague() {                    // Function for joining league 

            const code = joinLeagueCode.value;     // Store inputted join code

            fetch("join-league-process.php", {     // Post request to php file to join league
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ code:code })     // Pass value for inputted join code
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {                 // If data returned
                        const exists = data.exists;     // Variable indicating if league with this join code exists

                        if (exists == true) {               // If league with this join code exists        
                            const member = data.member;     // Variable indicating if user is already a member of this league

                            if (member == true) {           // If user is already a member, display relevant message    

                                joinLeagueValidationText.innerHTML = "Already a member of this league";

                            } else {                        // If user not already a member

                                joinLeagueValidationText.innerHTML = ""    // Set join code input field and validation text as empty
                                joinLeagueCode.value = "";
                                joinLeagueSuccessModal.showModal();        // Show modal confirming league joined successfully 
                            }

                        } else {        // If no league exists with inputted join code, display relevant message

                            joinLeagueValidationText.innerHTML = "Invalid join code";  
                        }
                    } else {
                        console.log("Error joining league:", data.error);
                    }
                })
                .catch(error => console.log("Fetch error:", error));

        }

        // ---


        // --- FUNCTIONS FOR OPENING MODALS

        function openCreateLeagueModal() {      // Function for opening modal to create league
            createLeagueModal.showModal();
        }

        // ---


        // --- FUNCTIONS FOR CLOSING MODALS

        function closeCreateLeagueModal() {    // Function for closing modal to create league
            createLeagueModal.close();

            createLeagueName.value = "";                    // Set all input fields, validation text and display lists as empty
            createLeagueDuration.value = "";
            createLeaderboardsExercise1.value = "";
            createLeaderboardsReps1.value = "";
            createLeaderboardsExercise2.value = "";
            createLeaderboardsReps2.value = "";
            createLeaderboardsExercise3.value = "";
            createLeaderboardsReps3.value = "";
            createLeaderboardsExercise4.value = "";
            createLeaderboardsReps4.value = "";
            createLeaderboardsExercise5.value = "";
            createLeaderboardsReps5.value = "";

            createLeagueNameValidationText.innerHTML = "";
            createLeagueDurationValidationText.innerHTML = "";
            createLeagueValidationText1.innerHTML = "";
            createLeagueValidationText2.innerHTML = "";
            createLeagueValidationText3.innerHTML = "";
            createLeagueValidationText4.innerHTML = "";
            createLeagueValidationText5.innerHTML = "";

            createLeagueDurationList.innerHTML = "";
            createLeagueList1.innerHTML = "";
            createLeagueList2.innerHTML = "";
            createLeagueList3.innerHTML = "";
            createLeagueList4.innerHTML = "";
            createLeagueList5.innerHTML = "";

        }

        function closeCreateLeagueSuccessModal() {      // Function for closing modal confirming league was created successfully
            createLeagueSuccessModal.close();
            window.location.reload(); 
        }

        function closeJoinLeagueSuccessModal() {      // Function for closing modal confirming league was joined successfully
            joinLeagueSuccessModal.close();
            window.location.reload(); 
        }

        // ---

    </script>

</body>
</html>