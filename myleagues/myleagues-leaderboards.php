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

$currentUsername = $user["username"];       // Store username

// ---


// --- RETRIEVE LEAGUE PASSED FROM MY LEAGUES PAGE

$league = [];

if (isset($_SESSION['data'])) {
    $league = $_SESSION['data'];        // Store league from session variable in league variable
}

// ---


// --- FETCH ALL LEADERBOARDS FROM SELECTED LEAGUE 

$userId = $user["user_id"];             // Store user id
$leagueId = $league["league_id"];       // Store league id

$mysqli = require __DIR__ . "/database.php";    // Database connection

$sql = "SELECT * FROM league_leaderboards WHERE league_id = ?";     // Fetch all leaderboards belonging to current league from database

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $leagueId); 
$stmt->execute();
$result = $stmt->get_result();

$leaderboards = [];                             // Store records in leaderboards array
while ($row = $result->fetch_assoc()) {
    $leaderboards[] = $row;
}

// ---


// --- CALCULATE USER'S RANKING FOR EACH LEADERBOARD IN LEAGUE

$userLeaderboards = [];     // Initialise array to store leaderboards with user's rank

foreach ($leaderboards as $leaderboard) {           // Loop through leaderboards array

    $leaderboardId = $leaderboard["league_leaderboard_id"];     // Store leaderboard details
    $exercise = $leaderboard["exercise"];
    $numReps = $leaderboard["num_reps"];

    // Fetch all entries for leaderboard, from best to worst
    $sql = "SELECT * FROM league_leaderboards_entries WHERE leaderboard_id = ? ORDER BY score DESC";    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $leaderboardId);
    $stmt->execute();
    $result = $stmt->get_result();

    $entries = [];                                  // Store results in entries array
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }

    for ($i = 0; $i < count($entries); $i++) {      // Loop through entries array
        $entry = $entries[$i];
        if ($entry["user_id"] == $userId) {                // If entry is current user's entry, store relevant entry information in user leaderboards array                       

            $userLeaderboardsEntry = [$i + 1, $leaderboardId, $exercise, $numReps];
            $userLeaderboards[] = $userLeaderboardsEntry;
        }
    }
}

// ---


// --- PRODUCE OVERALL LEAGUE TABLE

$usernames = [];        // Array to store usernames of league members, with user id as pointer

// Select users and their usernames that are members of this league
$sql = "SELECT league_entries.user_id, users.username
        FROM league_entries
        JOIN users ON league_entries.user_id = users.user_id
        WHERE league_entries.league_id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $leagueId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {     // Loop through users

    $userId = $row["user_id"];
    $username = $row["username"];
    $usernames[$userId] = $username;        // Store user and their username in usernames array

}

$total = count($usernames);          // Store total number of user's in league (used to calculate scores)           

// Select leaderboard id's of all leaderboards in this league
$sql = "SELECT league_leaderboard_id FROM league_leaderboards WHERE league_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $leagueId);
$stmt->execute();
$result = $stmt->get_result();

$leaderboardIds = [];       // Array to store list of leaderboard arrays

while ($row = $result->fetch_assoc()) {                 // Loop through results and store leaderboard id
    $leaderboardIds[] = $row["league_leaderboard_id"];
}

$totalPoints = [];          // Array to store total points for each user  

foreach ($leaderboardIds as $leaderboardId) {           // Loop through leaderboard ids

    // Select all user's and their scores for this leaderboard, ordered highest to lowest
    $sql = "SELECT user_id, score FROM league_leaderboards_entries WHERE leaderboard_id = ? ORDER BY score DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $leaderboardId);
    $stmt->execute();
    $result = $stmt->get_result();

    $deduction = 0;         // Used to calculate points for user (as list of scores is looped through, deduction is increased as score is lower)

    while ($row = $result->fetch_assoc()) {     // Loop through results

        $userId = $row["user_id"];              // Store user id of this user
        $username = $usernames[$userId];        // Store username of this user
        $score = $row["score"];                 // Store user's score for this leaderboard

        $points = 0;

        if (!($score == 0)) {                   // If score isn't 0 (deafult)
            $points = $total - $deduction;      // Calculate their points for this leaderboard (total num of users - deduction)
        }

        if (!isset($totalPoints[$username])) {  // If this is first leaderboard in iteration, initialise total points for this user as 0
            $totalPoints[$username] = 0;
        }

        $totalPoints[$username] += $points;     // Add user's points for this leaderboard to their total points
        $deduction = $deduction + 1;            // Increment deduction, so next user in list (who is a rank lower) gets a lower number of points
    }
}

arsort($totalPoints);       // Sort array of user's total points in order (highest to lowest)

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

            </div>


            <!-- Card which displays selected league info -->
            <div class="card" style="background-color:rgb(68, 19, 113);" id="league-info-card">

                <div class="leaderboard-header">

                    <!-- Contains league name-->
                    <div class="leaderboard-info-container">
                        <form action="myleagues.php">
                            <button class="my-rankings-category-button" style="padding-right: 5px; padding-bottom: 2px;"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                        </form>
                        <div class="card-header" id="league-info-name" style="padding-left: 20px; color: white; font-size: 1.75rem;"></div>
                    </div>

                    <!-- Contains league join code -->
                    <div class="leaderboard-info-container">
                        <div class="card-header" style="padding-left: 20px; color: white;">Join Code:</div>
                        <div class="card-header" id="league-info-code" style="padding-left: 20px; padding-right:10px; color: white; font-size: 1.75rem; font-weight:lighter"></div>
                    </div>

                </div>

                <div class="leaderboard-header" style="padding-bottom: 0px;">

                    <!-- Contains time remaining until league ends -->
                    <div class="leaderboard-info-container">
                        <div class="card-header" style="padding-left: 20px; color: white; font-size: 2rem;"><i class="bi bi-hourglass-split"></i></div>
                        <div class="card-header" id="league-info-time-remaining" style="padding-left: 20px; color: white; font-weight: lighter;"></div>
                    </div>

                    <div class="leaderboard-info-container">
                        <div class="card-header" style="padding-right: 15px; padding-top: 10px; color: white;">Members</div>
                        <button class="my-rankings-category-button" id="show-members-button"><i class="bi bi-caret-down-fill"></i></button>
                    </div>

                </div>

                <hr class="my-rankings-hr">

                <!-- Contains list of league members -->
                <div class="my-rankings-scroll-container">
                    <ul id="league-members" style="padding-bottom: 20px;">
                    
                    </ul>
                </div>

            </div>


            <!-- Card which displays overall league table -->
            <div class="card" style="background:linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255));" id="league-table-card">

                <div class="leaderboard-header" style="padding-bottom: 0px; align-items:baseline">

                    <div class="card-header" style="padding: 10px; color: white;">League Table<i class="bi bi-trophy-fill" style="padding-left:8px"></i></div>

                </div>
                
                <hr>

                <!-- Contains list of league members ranked in league table -->
                <ul id="league-table-list" style="padding-bottom: 20px;">
                    
                </ul>

            </div>


            <!-- Card containing all leaderboards in the league -->
            <div class="card" id="leaderboards-card">
                <div class="card-header" style="padding-bottom: 10px;">Leaderboards<i class="bi bi-bar-chart-line-fill" style="padding-left:8px"></i></div>
                <hr>

                <!-- Contains list of leaderboards in the league -->
                <div class="leaderboard-scroll-container">
                    <ul id="leaderboards-list" style="padding-bottom: 10px;">

                    </ul>
                </div>

            </div>


            <!-- Modal for displaying end of league message -->
            <dialog class="modal" id="end-of-league-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254)); padding-bottom: 30px;">
                
                <div class="small-sub-header" style="padding-top: 10px;">FINAL RESULTS!</div>

                <div class="delete-account-text">
                    <p class="store-text">This league has come to an end. After a long journey of pushing, pulling and progress, here's your final Rank and Rewards:</p>
                </div>

                <!-- Contains user's final rank for league -->
                <div class="delete-account-text">
                    <p class="small-sub-header">YOUR RANK:</p>
                    <div class="join-code" id="end-of-league-rank" style="font-size: 4rem;"></div>
                </div>

                <!-- Contains user's end of league rewards -->
                <div class="delete-account-text" style="padding-bottom:20px;">
                    <p class="small-sub-header">YOUR REWARDS:</p>
                    <div class="end-of-league-reward"><p id="end-of-league-xp-reward"></p><i>xp</i></div>
                    <div class="end-of-league-reward" style="align-items: center;"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; font-size:1.5rem; color:goldenrod;"></i><p id="end-of-league-coins-reward"></p></div>
                </div>

                <!-- Button which claims rewards -->
                <div style="justify-content:center;" style="padding: 10px;">
                    <button class="friends-button" style="width:175px; color: white;" onclick="claimLeagueRewards()">Claim Rewards</button>
                </div>

            </dialog>


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


        </main>

    </div>

    <script>

        // --- STORE HTML ELEMENTS

        // League info

        const leagueInfoCard = document.getElementById("league-info-card");
        const leagueInfoName = document.getElementById("league-info-name");
        const leagueInfoCode = document.getElementById("league-info-code");
        const leagueInfoTimeRemaining = document.getElementById("league-info-time-remaining");
        const showMembersButton = document.getElementById("show-members-button");
        const leagueMembers = document.getElementById("league-members");

        // League table

        const leagueTableList = document.getElementById("league-table-list");

        // Leaderboards

        const leaderboardsList = document.getElementById("leaderboards-list");

        // End of league

        const endOfLeagueModal = document.getElementById("end-of-league-modal");
        const endOfLeagueRank = document.getElementById("end-of-league-rank");
        const endOfLeagueXpReward = document.getElementById("end-of-league-xp-reward");
        const endOfLeagueCoinsReward = document.getElementById("end-of-league-coins-reward");

        // --- 
        

        // --- DISPLAY ALL INFORMATION, RANKINGS AND LEADERBOARDS FOR LEAGUE

        const currentUserId = <?php echo json_encode($userId); ?>;                  // Fetch php variable containing current user's id
        const currentUsername = <?php echo json_encode($currentUsername); ?>;       // Fetch php variable containing current user's username
        const league = <?php echo json_encode($league); ?>;                         // Fetch php variable containing selected league
        const leaderboards = <?php echo json_encode($userLeaderboards); ?>;         // Fetch php variable containing leaderboards in selected league
        const totalPoints = <?php echo json_encode($totalPoints); ?>;               // Fetch php variable containing list of users' total points

        const items = Object.entries(totalPoints);          // Convert totalPoints to array

        for (let i = 0; i < items.length; i++) {            // Loop through array of users' total points

            const userRanking = items[i];           
            const rank = i+1;                       // Store user's rank in league table
            const username = userRanking[0];        // Store username
            const points = userRanking[1];          // Store user's total points

            const listItem = document.createElement("li");          // Create and display list containing user's league table rank, username and total points

            const leagueTableListContainer = document.createElement("div");         // Create HTML elements containing user's rank, username and total points
            const leagueTableRankContainer = document.createElement("div");
            const leagueTableUsernameContainer = document.createElement("div");
            const leagueTablePointsContainer = document.createElement("div");

            const leagueTableRankInnerContainer = document.createElement("div");
            const leagueTableRank = document.createElement("p");
            const leagueTableUsername = document.createElement("p");
            const leagueTablePoints = document.createElement("p");
            
            const hr = document.createElement("hr");

            leagueTableListContainer.classList.add("league-table-list-container");
            leagueTableRankContainer.classList.add("my-rankings-rank-container");
            leagueTableUsernameContainer.classList.add("my-rankings-exercise-container");
            leagueTablePointsContainer.classList.add("leaderboard-score-container");

            leagueTableRankInnerContainer.classList.add("my-rankings-rank-inner-container");
            leagueTableRank.classList.add("league-table-rank");
            leagueTableUsername.classList.add("league-table-username");
            leagueTablePoints.classList.add("leaderboard-score");

            hr.classList.add("league-table-hr");

            leagueTableRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${rank}`;      // User's rank in league table

            if ((rank) == 1) {                                      // If entry is 1st, 2nd or 3rd, make rank gold, silver or bronze
                leagueTableRank.style.color = "rgb(197, 177, 44)";      
                leagueTableRank.style.textShadow = "2px 2px 4px rgba(175, 149, 5, 0.6)";
            } else if ((rank) == 2) {
                leagueTableRank.style.color = "rgb(146, 145, 145)";
                leagueTableRank.style.textShadow = "2px 2px 4px rgba(126, 124, 124, 0.6)";
            } else if ((rank) == 3) {
                leagueTableRank.style.color = "rgb(161, 85, 30)";
                leagueTableRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
            }

            leagueTableUsername.innerHTML = username;               // Username
            leagueTableUsername.style.textShadow = "2px 2px 4px rgba(126, 124, 124, 0.6)";

            if (username == currentUsername) {              // If username is current user's, give username different colour so it's easily identifiable
                leagueTableUsername.style.color = "white";
            }

            leagueTablePoints.innerHTML = points;           // Display user's total points

            leagueTableRankInnerContainer.appendChild(leagueTableRank);
            leagueTableRankContainer.appendChild(leagueTableRankInnerContainer);
            leagueTableUsernameContainer.appendChild(leagueTableUsername);
            leagueTablePointsContainer.appendChild(leagueTablePoints);

            leagueTableListContainer.appendChild(leagueTableRankContainer);
            leagueTableListContainer.appendChild(leagueTableUsernameContainer);
            leagueTableListContainer.appendChild(leagueTablePointsContainer);

            listItem.appendChild(leagueTableListContainer);
            leagueTableList.appendChild(listItem);              // Add to league table
            leagueTableList.appendChild(hr);

        }

        const leagueId = league.league_id;              // Store league info 
        const leagueName = league.league_name;
        const code = league.code;
        const creatorId = league.creator_id;

        const endDate = new Date(league.end_date);      // Use league end time and current time to calculate remaining time
        const now = new Date();
        const timeRemainingMins = endDate - now;

        if (timeRemainingMins > 0) {                    // If there is still time remaining 

            const timeRemainingDays = Math.floor(timeRemainingMins / (1000 * 60 * 60 * 24));                        // Calculate time remaining in days (rounded down)
            const timeRemainingHours = Math.floor((timeRemainingMins % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));  // Calculate remainder of time remaining in days, in hours (rounded down)
            const timeRemainingMinutes = Math.floor((timeRemainingMins % (1000 * 60 * 60)) / (1000 * 60));          // Calculate remainder of time remaining in hours, in minutes (rounded down)

            const timeRemaining = `${timeRemainingDays} D : ${timeRemainingHours} H : ${timeRemainingMinutes} M remaining`;     // Display time remaining
            leagueInfoTimeRemaining.innerHTML = timeRemaining;

        } else {            // If there is no time remaining
            
            const timeRemaining = "0 D : 0 H : 0 M remaining";      // Display time remaining as 0
            leagueInfoTimeRemaining.innerHTML = timeRemaining;

            var currentUserRanking;

            for (let i=0; i < items.length; i++) {      // Loop through array of users' total points
                
                const userRanking = items[i];
                const rank = i+1;                       // Store user's rank in league table
                const username = userRanking[0];        // Store username
                const points = userRanking[1];          // Store user's total points

                if (username == currentUsername) {      // If username is current user's

                    var xpReward = 0;           // Initialise variables to store xp reward and coins reward
                    var coinsReward = 0;
                    
                    if (rank == 1) {            // If user ranks 1st

                        xpReward = 2250;        // Set xp and coins reward
                        coinsReward = 175;
                        endOfLeagueRank.style.color = "rgb(188, 167, 29)";                          // Colour rank as gold    
                        endOfLeagueRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                        endOfLeagueRank.innerHTML = `<i class="bi bi-trophy-fill" style="padding-right:8px; font-size: 2.5rem"></i>${rank}`;    // Display rank

                    } else if (rank == 2) {     // If user ranks 2nd

                        xpReward = 2000;        // Set xp and coins reward
                        coinsReward = 150;
                        endOfLeagueRank.style.color = "rgb(166, 166, 166)";                         // Colour rank as silver
                        endOfLeagueRank.style.textShadow = "2px 2px 4px rgb(126, 124, 124)";
                        endOfLeagueRank.innerHTML = `<i class="bi bi-award-fill" style="padding-right:8px; font-size: 2.5rem"></i>${rank}`;     // Display rank

                    } else if (rank == 3) {     // If user ranks 3rd

                        xpReward = 1750;        // Set xp and coins reward
                        coinsReward = 125;
                        endOfLeagueRank.style.color = "rgb(189, 105, 45)";                          // Colour rank as bronze
                        endOfLeagueRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                        endOfLeagueRank.innerHTML = `<i class="bi bi-award-fill" style="padding-right:8px; font-size: 2.5rem"></i>${rank}`;     // Display rank

                    } else {                    // If user isn't in top three

                        xpReward = 1500;        // Set xp and coins reward
                        coinsReward = 100;
                        endOfLeagueRank.style.color = "white";          // Colour rank as white
                        endOfLeagueRank.style.textShadow = "2px 2px 4px rgb(166, 165, 165)";
                        endOfLeagueRank.innerHTML = rank;               // Display rank
                        

                    }

                    
                    endOfLeagueXpReward.innerHTML = xpReward;           // Display xp reward
                    endOfLeagueCoinsReward.innerHTML = coinsReward;     // Display coins reward

                }
                
                endOfLeagueModal.showModal();       // Show modal for end of league

            }

        }

        for (let i = 0; i < leaderboards.length; i++) {         // Loop through leaderboards array
            const leaderboard = leaderboards[i];

            const listItem = document.createElement("li");      // Create and display list of buttons containing user's leaderboard rank and leaderboard exercise and number of reps
            const button = document.createElement("button");            
            button.addEventListener("click", () => {            // When button clicked
                
                fetch('pass-leaderboard.php', {                 // Post php request to store leaderboard in session variable
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ data:leaderboard })   // Pass in data for leaderboard
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = "myleagues-leaderboard-details.php";    // Relocate to page showing info about selected league
                    } else {
                        console.error("Failed to pass league");
                    }
                }); 

            })

            const leaderboardButtonContainer = document.createElement("div");          // Create HTML elements containing leaderboard with user's rank, exercise and number of reps
            const leaderboardRankContainer = document.createElement("div");
            const leaderboardExerciseContainer = document.createElement("div");
            const leaderboardRepsContainer = document.createElement("div");

            const leaderboardRankInnerContainer = document.createElement("div");
            const leaderboardRank = document.createElement("p");
            const leaderboardExercise = document.createElement("p");
            const leaderboardReps = document.createElement("p");
            
            const hr = document.createElement("hr");

            button.classList.add("search-result-button");

            leaderboardButtonContainer.classList.add("leaderboard-button-container");
            leaderboardRankContainer.classList.add("leaderboard-rank-container");
            leaderboardExerciseContainer.classList.add("leaderboard-username-container");
            leaderboardRepsContainer.classList.add("leaderboard-score-container");

            leaderboardRankInnerContainer.classList.add("leaderboard-rank-inner-container");
            leaderboardRank.classList.add("leaderboard-rank");
            leaderboardExercise.classList.add("leaderboard-username");
            leaderboardReps.classList.add("leaderboard-score");

            leaderboardRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${leaderboard[0]}`;         // User's rank on the leaderboard

            if (leaderboard[0] == 1) {                  // Make rank colour gold, silver or bronze if 1st, 2nd or 3rd
                leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";       
                leaderboardRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
            } else if (leaderboard[0] == 2) {
                leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                leaderboardRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
            } else if (leaderboard[0] == 3) {
                leaderboardRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                leaderboardRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
            }

            leaderboardExercise.innerHTML = leaderboard[2];         // Leaderboard exercise

            if (leaderboard[3] == 1) {              // Leaderboard number or reps, colour coded consistent to previous
                leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color: rgb(255, 82, 82); padding-right: 10px;"></i> ${leaderboard[3]} x Rep(s)`;
            } else if (leaderboard[3] == 3) {
                leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color: rgb(252, 252, 149); padding-right: 10px;"></i> ${leaderboard[3]} x Rep(s)`;
            } else if (leaderboard[3] == 5) {
                leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color: rgb(69, 246, 69); padding-right: 10px;"></i> ${leaderboard[3]} x Rep(s)`;
            } else if (leaderboard[3] == 10) {
                leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color: rgb(83, 173, 247); padding-right: 10px;"></i> ${leaderboard[3]} x Rep(s)`;
            }

            leaderboardRankInnerContainer.appendChild(leaderboardRank);
            leaderboardRankContainer.appendChild(leaderboardRankInnerContainer);
            leaderboardExerciseContainer.appendChild(leaderboardExercise);
            leaderboardRepsContainer.appendChild(leaderboardReps);

            leaderboardButtonContainer.appendChild(leaderboardRankContainer);
            leaderboardButtonContainer.appendChild(leaderboardExerciseContainer);
            leaderboardButtonContainer.appendChild(leaderboardRepsContainer);

            button.appendChild(leaderboardButtonContainer);
            listItem.appendChild(button);
            leaderboardsList.appendChild(listItem);             // Add to display list of leaderboards
            leaderboardsList.appendChild(hr);
        }

               
        leagueInfoName.innerHTML = leagueName;      // Display league name
        leagueInfoCode.innerHTML = code;            // Display join code for league

        // ---


        // --- SHOW/HIDE LEAGUE MEMBERS

        var membersShowing = false;         // Variable indicating if league members are currently being displayed

        showMembersButton.addEventListener("click", () => {         // When show members button clicked
            
            if (membersShowing == false) {                                                  // If members are not currently showing
                showMembers();                                                              // Show them 
                showMembersButton.innerHTML = `<i class="bi bi-caret-up-fill"></i>`;        // Change symbol on button to up, as list has now "dropped down"
                membersShowing = true;                                                      // Change varianle to indicate members are showing

            } else {                                                                        // Else members are currently showing                                  
                hideMembers();                                                              // Hide them
                showMembersButton.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;      // Change symbol on button to down, as list has now "gone back up"
                membersShowing = false;                                                     // Change variable to indicate members are not showing
            }
        })


        function showMembers() {            // Function for displaying league members

            leagueMembers.innerHTML = "";

            fetch("fetch-league-members-process.php", {         // Post request to php file to fetch league members
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leagueId:leagueId })     // Pass value for league id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                                     // If members successfully fetched
                    const members = data.data;

                    for (let i=0; i < members.length; i++) {            // Loop through list of members

                        const member = members[i];                      // Store member

                        const listItem = document.createElement("li");  // Create HTML elements to display member and icon showing if they are admin or normal member
                        const div = document.createElement("div");
                        const hr = document.createElement("hr");

                        listItem.classList.add("leaderboard-member-container");
                        div.classList.add("leaderboard-member");
                        hr.classList.add("my-rankings-hr");

                        const userId = member[0];           // Store user id of member
                        const username = member[1];         // Store username of member

                        if (userId == creatorId) {          // If member is league admin, display their username with admin symbol
                            div.innerHTML = `<i class="bi bi-star-fill" style="font-size:medium; background-image:linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B); background-clip: text; color: transparent; padding-right:8px;"></i> ${username}`;
                        } else {                            // If member is not league admin, display their name with normal member symbol
                            div.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:medium; background-image: linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255)); background-clip: text; color: transparent; padding-right:8px"></i> ${username}`;
                        }

                        listItem.appendChild(div);
                        leagueMembers.appendChild(listItem);        // Add to members display list
                        leagueMembers.appendChild(hr);

                    }
                } else {
                    console.log("Error creating leaderboard:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));            

        }

        function hideMembers() {                    // Function for hiding members
            leagueMembers.innerHTML = "";           // Set members list to empty
        }

        // ---


        // --- CLAIM END OF LEAGUE REWARDS

        function claimLeagueRewards() {             // Function for claiming end of league rewards

            const xpToAdd = endOfLeagueXpReward.innerHTML;
            const coinsToAdd = endOfLeagueCoinsReward.innerHTML;

            fetch("claim-league-rewards-process.php", {     // Post request to php file to add rewards to user account and update thier league entry
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leagueId:leagueId , xpToAdd:xpToAdd , coinsToAdd:coinsToAdd })   // Pass values for league id and xp and coin rewards
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                         // If rewards successfully added to user's account and their league entry updated

                    endOfLeagueModal.close();               // Close end of league modal           

                    const xpGained = document.getElementById("xp-gained");          
                    const coinsGained = document.getElementById("coins-gained");
                    
                    xpGained.innerHTML = `+${xpToAdd}xp` ;                  // Add xp gained and coins gained to display message 
                    coinsGained.innerHTML = `+<i class="bi bi-currency-exchange" style="padding-right:5px;padding-bottom:15px; font-weight:bold; font-size:2.5rem; color:goldenrod;"></i>${coinsToAdd}`;

                    var overlay = document.getElementById("xp-overlay");        
                    overlay.style.display = "flex";                         // Display xp and coins gained message
                    overlay.style.flexDirection = "column";
                    overlay.style.alignItems = "center";
                    overlay.style.justifyContent = "center";

                    setTimeout(() => {                          // Display message for 2 seconds    
                        overlay.style.display = "none";
                        window.location.href="myleagues.php";   // Relocate to my leagues page 
                    }, 2000);  
                    
                } else {
                    console.log("Error claiming rewards:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));    

        }

        // ---

    </script>

</body>
</html>