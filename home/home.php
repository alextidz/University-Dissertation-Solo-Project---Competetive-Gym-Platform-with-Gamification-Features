<?php

session_start();

// --- CHECK IF THERE HAS BEEN A SUCCESSFUL UPLOAD, WHICH DETERMINES WHETHER UPLOAD SUCCESS MODAL SHOULD BE DISPLAYED 

if (!isset($_SESSION["upload_success"])) {      // If page loaded and session variable "upload_success" is not set, set as false
    $_SESSION["upload_success"] = false;
}

$uploadSuccess = false;     // Variable for upload success that is initially set as false when page is loaded

if(isset($_SESSION["upload_success"]) && $_SESSION["upload_success"] === true) {    // Check if session variable "upload_success" is true
    $uploadSuccess = true;      // If true, then there has been a successful upload, so uploadSuccess set to true 
}                               // (This variable will be used in js below to control whether an upload success modal is displayed)

// ---


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

$userId = $user["user_id"];

//---


// --- FETCH ALL LEADERBOARDS THE CURRENT USER IS REGISTERED TO

$username = $user["username"];

$mysqli = require __DIR__ . "/database.php";    // Database connection

$sql = "SELECT * FROM public_leaderboards WHERE user_id = {$user["user_id"]}";      // Fetch all leaderboard entries for current user
$result = $mysqli->query($sql);

$leaderboards = [];                             // Store records in leaderboards array
while ($row = $result->fetch_assoc()) {
    $leaderboards[] = $row;
}

// ---


// --- CALCULATE USER'S RANKING FOR EACH LEADERBOARD THEY'RE REGISTERED TO

$userLeaderboards = [];     // Initialise array to store user's leaderboards with their rank

foreach ($leaderboards as $leaderboard) {       // Loop through leaderboards array

    $exercise = $leaderboard["exercise"];
    $numReps = $leaderboard["num_reps"];

    $sql = "SELECT * FROM public_leaderboards WHERE exercise = '{$exercise}' AND num_reps = {$numReps} ORDER BY score DESC";    // Fetch all records for leaderboard, from best

    $result = $mysqli->query($sql);

    $entries = [];                              // Store results in entries array
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }

    for ($i = 0; $i < count($entries); $i++) {      // Loop through entries array
        $entry = $entries[$i];
        if ($entry["user_id"] == $user["user_id"]) {       // If entry is current user's entry, store rank, exercise and number of reps in userLeaderboards array                         

            $userLeaderboardsEntry = [$i + 1, $entry["exercise"], $entry["num_reps"]];
            $userLeaderboards[] = $userLeaderboardsEntry;
        }
    }
}

// ---


// --- HANDLE USER SUBMITTING A LEADERBOARD ENTRY

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $user["user_id"];                            // Define information relevant for entry submission
    $exercise = $_POST["new-entry-exercise"];
    $num_reps = (int) $_POST["new-entry-reps"];
    $scoreNum = $_POST["new-entry-score-num"];
    $scoreDecimal = $_POST["new-entry-score-decimal"];
    $score = (float) $scoreNum . "." . $scoreDecimal;
    $flags = 0;

    $video = $_FILES["video"];                              // Preparing video for upload
    $videoName = basename($video["name"]);
    $videoTmpPath = $video["tmp_name"];
    $videoSize = $video["size"];
    $videoExt = strtolower(pathinfo($videoName, PATHINFO_EXTENSION));
    $newVideoName = uniqid("video_", true) . "." . $videoExt;
    $uploadPath = "../uploads/" . $newVideoName;

    if (move_uploaded_file($videoTmpPath, $uploadPath)) {       // Move video file to upload directory

        $sql = "DELETE FROM public_leaderboards WHERE user_id = ? AND exercise = ? AND num_reps = ?";       // Delete previous entry for current user
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isi", $user_id, $exercise, $num_reps);

        if ($stmt->execute()) {

            $sql = "INSERT INTO public_leaderboards (user_id, exercise, num_reps, score, video, flags) VALUES (?, ?, ?, ?, ?, ?)";      // Insert new entry record into public leaderboards table
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("isidsi", $user_id, $exercise, $num_reps, $score, $uploadPath, $flags);
            
            if ($stmt->execute()) {
                $_SESSION["upload_success"] = true;         // Set session variable upload_success to true, so that upload success modal is shown
                header("Location: home.php");       // Relocate to home page, reloading page
                exit;
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Failed to upload video.";
    }
   
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
    <title>Home</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styling/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body> 
    
    <div class="min-h-screen flex">

        <!-- Sidebar containing buttons with links to all main pages -->
        <aside class="w-1/4 pt-6 shadow-lg flex flex-col justify-between transition duration-500 ease-in-out transform" id="sidebar"> 
            <div>
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-house-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">Home</p></div></button>
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
                        <div class="card-header">Home<i class="bi bi-house-fill" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); }?></div>
                        </div>
                    </div>
                </div>

                <!-- Card containing all current user's leaderboard entries -->
                <div class="card" style="background-color:rgb(68, 19, 113);" id="my-rankings-card">
                    <div class="card-header" style="color:white; padding-bottom: 10px;">My Global Rankings<i class="bi bi-globe-americas" style="padding-left:5px"></i></div>
                    <hr class="my-rankings-hr">

                    <!-- Container for 1 rep leaderboard entries -->
                    <div class="my-rankings-category-container">
                        <div class="my-rankings-category-header">
                            <p><i class="bi bi-diamond-fill" style="font-size:small; color: rgb(255, 82, 82); padding-right: 10px;"></i>1 x Rep(s)</p>
                            <button class="my-rankings-category-button" id="my-rankings-category-button-1" style="height:45px; width:45px;"><i class="bi bi-caret-down-fill"></i></button>
                        </div>
                        <hr class="my-rankings-hr">
                        <div class="my-rankings-scroll-container">
                            <ul id="my-rankings-list-1">

                            </ul>
                        </div>
                        
                    </div>

                    <!-- Container for 3 rep leaderboard entries -->
                    <div class="my-rankings-category-container">
                        <div class="my-rankings-category-header">
                            <p><i class="bi bi-diamond-fill" style="font-size:small; color: rgb(252, 252, 149); padding-right: 10px;"></i>3 x Rep(s)</p>
                            <button class="my-rankings-category-button" id="my-rankings-category-button-3" style="height:45px; width:45px;"><i class="bi bi-caret-down-fill"></i></button>
                        </div>
                        <hr class="my-rankings-hr">
                        <div class="my-rankings-scroll-container">
                            <ul id="my-rankings-list-3">

                            </ul>
                        </div>
                    </div>

                    <!-- Container for 5 rep leaderboard entries -->
                    <div class="my-rankings-category-container">
                        <div class="my-rankings-category-header">
                            <p><i class="bi bi-diamond-fill" style="font-size:small; color: rgb(69, 246, 69); padding-right: 10px;"></i>5 x Rep(s)</p>
                            <button class="my-rankings-category-button" id="my-rankings-category-button-5" style="height:45px; width:45px;"><i class="bi bi-caret-down-fill"></i></button>
                        </div>
                        <hr class="my-rankings-hr">
                        <div class="my-rankings-scroll-container">
                            <ul id="my-rankings-list-5">

                            </ul>
                        </div>
                    </div>

                    <!-- Container for 10 rep leaderboard entries -->
                    <div class="my-rankings-category-container">
                        <div class="my-rankings-category-header">
                            <p><i class="bi bi-diamond-fill" style="font-size:small; color: rgb(83, 173, 247); padding-right: 10px;"></i>10 x Rep(s)</p>
                            <button class="my-rankings-category-button" id="my-rankings-category-button-10" style="height:45px; width:45px;"><i class="bi bi-caret-down-fill"></i></button>
                        </div>
                        <hr class="my-rankings-hr">
                        <div class="my-rankings-scroll-container">
                            <ul id="my-rankings-list-10">

                            </ul>
                        </div>
                    </div>

                </div>


                <!-- Card where user can search for leaderboard -->
                <div class="card" id="search-leaderboards-card">
                    <div class="card-header">Find Leaderboards<i class="bi bi-search" style="padding-left:5px"></i></div>

                    <div class="search-leaderboards-container">

                        <!-- Input containing selected exercise -->
                        <div class="search-leaderboards-exercise-container">
                            <input class="search-leaderboards-input" type="text" id="search-leaderboards-exercise" name="search-leaderboards-exercise" placeholder="Select an Exercise" spellcheck="false" required></input>
                            <button class="my-codes-button" id="show-exercises-button" style="height:45px; width:45px;" onclick="showExercises()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Input containing selected number of reps -->
                        <div class="search-leaderboards-reps-container">
                            <input class="search-leaderboards-input" type="text" id="search-leaderboards-reps" name="search-leaderboards-reps" placeholder="Select Number of Reps" spellcheck="false" required></input>
                            <button class="my-codes-button" id="show-reps-button" style="height:45px; width:45px;" onclick = "showReps()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>

                        <!-- Button which displays leaderboard for selected exercise and number of reps when clicked -->
                        <button class="main-btn" style="width: 70px;" onclick="showSearchLeaderboard()"><p class=logout-text>Go</p></button>

                    </div>

                    <em class="validation-text" style="padding-left: 25px; font-size: medium;" id="search-leaderboards-validation-text"></em>

                    <!-- Container which displays list of exercises or list of options for number of reps -->
                    <div class="search-leaderboards-results-container">
                        <div class="search-leaderboards-scroll-container">
                            <ul id="search-leaderboards-results">
                            
                            </ul>
                        </div>           
                    </div>
                </div>

                <!-- Card which displays selected leaderboard info, as well as current user's rank on leaderboard -->
                <div class="card" style="background-color:rgb(68, 19, 113);" id="leaderboard-card">

                    <div class="leaderboard-header">

                        <div class="leaderboard-info-container">
                            <button class="my-rankings-category-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeLeaderboard()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                            <div class="card-header" id="leaderboard-name" style="padding-left: 20px; color: white;"></div>
                            <div ><p style="color:rgb(95, 38, 149); padding-left:20px; padding-bottom: 10px; font-size: 1.75rem;">|</p></div>
                            <div class="card-header" id="leaderboard-reps" style="padding-left: 15px; color: white;"></div>
                        </div>

                        <!-- Button which opens modal for adding an entry to the leaderboard -->
                        <div class="add-entry-container">
                            <button class="my-rankings-category-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="openNewEntryModal()"><p class="logout-text"><i class="bi bi-plus-circle" style="padding-left:5px"></i></p></button>
                        </div>

                    </div>

                    <div class="leaderboard-header" style="padding-bottom: 5px;">
                        <div class="small-sub-header" style="padding: 10px; color: white;">Your Rank<i class="bi bi-trophy-fill" style="padding-left:8px"></i></div>
                        
                        <!-- Button which opens modal for deleting your entry from the leaderboard -->
                        <button id="delete-entry-button" class="my-rankings-category-button" style="padding-right: 5px; padding-bottom: 2px; color: red;" onclick="openDeleteEntryModal()"><i class="bi bi-trash3" style="padding-left:5px"></i></button>
                    </div>
                    <hr class="my-rankings-hr">

                    <!-- Contains current user's leaderboard entry if they have one -->
                    <ul id="user-leaderboard-entry" style="padding-bottom: 20px;">
                        <div style="text-align:center; padding-top: 25px;">
                            <i class="small-sub-header" style=" color: white;">You aren't registered on this leaderboard yet</i>
                        </div>
                    </ul>

                </div>

                <!-- Card containing all leaderboard entries -->
                <div class="card" id="leaderboard-display-card">

                    <div class="card-header" style="padding-bottom: 10px; padding-left: 10px;">Leaderboard<i class="bi bi-bar-chart-line-fill" style="padding-left:5px"></i></div>
                    <hr>

                    <div class="leaderboard-scroll-container">
                        <ul id="leaderboard-entries" style="padding-bottom: 20px;">
                            
                        </ul>
                    </div>
                    
                </div>

            </div>


            <!-- Modal for adding new entry to leaderboard -->
            <dialog class="modal" id="new-entry-modal">
                
                <div class="new-entry-modal-header">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeNewEntryModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px;">ADD ENTRY</div>
                </div>

                <em style="font-size: small; color:rgb(68, 19, 113); font-weight: bold;">NOTE: IF YOU ALREADY HAVE A SUBMISSION ON THIS LEADERBOARD, IT WILL BE REPLACED WITH THIS</em>

                <form method="post" id="add-entry-form" enctype="multipart/form-data" novalidate>

                    <!-- Field containing exercise -->
                    <div class="new-entry-exercise-container">
                        <p class="new-entry-label">Exercise</p>
                        <input class="new-entry-exercise" type="text" id="new-entry-exercise" name="new-entry-exercise" spellcheck="false" required></input>
                    </div>

                    <!-- Field containing number of reps -->
                    <div class="new-entry-reps-container">
                        <p class="new-entry-label">Number of Reps</p>
                        <input class="new-entry-reps" type="text" id="new-entry-reps" name="new-entry-reps" spellcheck="false" required></input>
                    </div>

                    <!-- Input field for weight achieved by the user -->
                    <div class="new-entry-score-container">
                        <p class="new-entry-label">Weight(kg)</p>
                        <div class="new-entry-score-inner-container">
                            <input class="new-entry-score-num" type="text" id="new-entry-score-num" name="new-entry-score-num" onkeydown="return validateScoreNum(event, this)" placeholder="000" required></input>
                            <p class="new-entry-score-text">.</p>
                            <input class="new-entry-score-decimal" type="text" id="new-entry-score-decimal" name="new-entry-score-decimal" onkeydown="return validateScoreDecimal(event, this)" placeholder="00" required></input>
                            <p class="new-entry-score-text">kg</p>
                        </div>

                        <!-- Error message for when field is empty on submit -->
                        <em class="validation-text" id="new-entry-score-validation-text"></em>
                    </div>

                    <!-- Contains input for video file -->
                    <div class="new-entry-video-container">
                        <p class="new-entry-label" style="font-weight: bold; padding-left: 0px;">Video</p>
                        <em style="font-size:medium;">Please attatch a video of you performing this exercise for the above weight and reps as proof of completion:</em>
                        <input style="padding-top: 15px;" type="file" id="video" name="video" accept="video/*" required></input>
                        <br>

                        <!-- Error message for when no video is selected on submity -->
                        <em class="validation-text" id="new-entry-video-validation-text"></em>
                    </div>

                    <!-- Button which submits entry when clicked -->
                    <div class="new-entry-submit-container">
                        <button class="friends-button" style="width:125px;" type="submit"><p class="logout-text">Submit</p></button>
                    </div>

                </form>

            </dialog>


            <!-- Modal for deleting your entry from leaderboard -->
            <dialog class="modal" style="width:450px" id="delete-entry-modal">
                
                <div class="new-entry-modal-header" style="padding-right:35%;">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeDeleteEntryModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px; color: red;">DELETE ENTRY</div>
                </div>
                
                <div class="delete-account-text">
                    <p class="store-text">Are you sure you want to delete your entry from this leaderboard?</p>
                </div>  

                <!-- Button which deletes entry when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="deleteEntry()">Delete</button>
                </div>

            </dialog>


            <!-- Modal for displaying a selected entry -->
            <dialog class="modal" id="show-entry-modal">
                
                <div class="new-entry-modal-header" style="padding-right: 0px;">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeShowEntryModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="show-entry-button-container" id="report-button-container">
                        
                        <!-- Button which displays modal for reporting an entry -->
                        <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px; color:red" onclick = "openReportModal()"><i class="bi bi-flag-fill" style="padding-left:5px;"></i></button>
                    </div>
                </div>

                <!-- Contains the username associated with selected leaderboard entry -->
                <div class="new-entry-exercise-container" style="padding-bottom:5px">
                    <p class="new-entry-label">User</p>
                    <p class="card-header" id="show-entry-user" name="show-entry-user"></p>
                </div>

                <!-- Contains the weight associated with selected leaderboard entry -->
                <div class="new-entry-exercise-container">
                    <p class="new-entry-label">Weight</p>
                    <p class="card-header" style="color:black" id="show-entry-score" name="show-entry-score"></p>
                </div>

                <!-- Contains the video associated with selected leaderboard entry -->
                <div class="video-container" id="show-entry-video-container"></div>

            </dialog>


            <!-- Modal for reporting an entry -->
            <dialog class="modal" style="width:450px" id="report-modal">
                
                <div class="new-entry-modal-header" style="padding-right:38%;">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeReportModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px; color:red">REPORT<i class="bi bi-exclamation-triangle-fill" style="padding-left:5px; color:rgb(237, 208, 66)"></i></div>
                </div>
                
                <div class="delete-account-text">
                    <p class="store-text">Are you sure you want to report this as an invalid/illegitimate entry?</p>
                </div>  

                <!-- Button which submits report when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="reportEntry()">Report</button>
                </div>

            </dialog>


            <!-- Modal for confirming report was submitted -->
            <dialog class="modal" id="report-confirmation-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">THANK YOU</div>
                
                <div class="delete-account-text">
                    <p class="store-text">Your report has been submitted successfully</p>
                </div>  

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color:white" onclick="closeReportConfirmationModal()">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming user's leaderboard entry has been uploaded -->
            <dialog class="modal" id="upload-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">Your entry has successfully been added to the leaderboard!</p>
                    <p class="store-text">Keep track of your position in the<br> <b class="small-sub-header">My Rankings</b> section</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button id="close-upload-success-modal-button" class="friends-button" style="width:125px; color: white;">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming user's leaderboard entry has been deleted -->
            <dialog class="modal" id="delete-entry-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">Your entry has been successfully deleted</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeDeleteEntrySuccessModal()">Dismiss</button>
                </div>

            </dialog>

            
            <!-- Contains overlay displaying xp gained message -->
            <div class="overlay" id="xp-overlay">
                <div class="level-up-container">
                    <i class="logout-text" style="font-weight: bold; font-size: 4rem;">+250xp</i>
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


        // --- STORE ALL REQUIRED HTML PAGE ELEMENTS 

        const myRankingsCard = document.getElementById("my-rankings-card");
        const myRankingsList1 = document.getElementById("my-rankings-list-1");
        const myRankingsList3 = document.getElementById("my-rankings-list-3");
        const myRankingsList5 = document.getElementById("my-rankings-list-5");
        const myRankingsList10 = document.getElementById("my-rankings-list-10");

        const searchLeaderboardsCard = document.getElementById("search-leaderboards-card");
        const searchLeaderboardsValidationText = document.getElementById("search-leaderboards-validation-text");
        const searchResultsList = document.getElementById("search-leaderboards-results");
        const searchLeaderboardsExercise = document.getElementById("search-leaderboards-exercise");
        const searchLeaderboardsReps = document.getElementById("search-leaderboards-reps");

        const leaderboardCard = document.getElementById("leaderboard-card");
        const leaderboardDisplayCard = document.getElementById("leaderboard-display-card");
        const leaderboardName = document.getElementById("leaderboard-name");
        const leaderboardReps = document.getElementById("leaderboard-reps");
        const leaderboardEntries = document.getElementById("leaderboard-entries");
        const userLeaderboardEntry = document.getElementById("user-leaderboard-entry");

        const showEntryModal = document.getElementById("show-entry-modal");
        const showEntryUser = document.getElementById("show-entry-user");
        const showEntryScore = document.getElementById("show-entry-score");
        const videoContainer = document.getElementById("show-entry-video-container");

        const newEntryModal = document.getElementById("new-entry-modal");
        const newEntryExercise = document.getElementById("new-entry-exercise");
        const newEntryReps = document.getElementById("new-entry-reps");

        const reportModal = document.getElementById("report-modal");
        const reportConfirmationModal = document.getElementById("report-confirmation-modal");

        const deleteEntryModal = document.getElementById("delete-entry-modal");
        const deleteEntrySuccessModal = document.getElementById("delete-entry-success-modal");
        const deleteEntryButton = document.getElementById("delete-entry-button");

        // ---


        var currentUserId = <?php echo json_encode($userId); ?>;                // Fetch php variable containing current user's user id
        var currentUsername = <?php echo json_encode($username); ?>;            // Fetch php variable containing current user's username


        // --- SPLIT USER'S LEADERBOARDS INTO CATEGORIES BASED ON NUMBER OF REPS

        var userLeaderboards = <?php echo json_encode($userLeaderboards); ?>;   // Fetch php variable containing list of current user's leaderboard rankings

        let userLeaderboards1 = [];         // Initialise array for current user's 1 rep leaderboard rankings
        let userLeaderboards3 = [];         // Initialise array for current user's 3 rep leaderboard rankings
        let userLeaderboards5 = [];         // Initialise array for current user's 5 rep leaderboard rankings
        let userLeaderboards10 = [];        // Initialise array for current user's 10 rep leaderboard rankings

        for (let i = 0; i < userLeaderboards.length; i++) {     // Loop through userLeaderboards array
            const userLeaderboard = userLeaderboards[i];
            
            if (userLeaderboard[2] == 1) {                      // Add each item to the relevant array based on value for number of reps
                userLeaderboards1.push(userLeaderboard);
            } else if (userLeaderboard[2] == 3) {
                userLeaderboards3.push(userLeaderboard);
            } else if (userLeaderboard[2] == 5) {
                userLeaderboards5.push(userLeaderboard);
            } else if (userLeaderboard[2] == 10) {
                userLeaderboards10.push(userLeaderboard);
            }
        }

        // ---


        // --- DISPLAY LIST OF USER'S 1 REP LEADERBOARDS

        myRankingsList1.innerHTML = "";     // Initially set empty all lists in the My Rankings section 
        myRankingsList3.innerHTML = "";
        myRankingsList5.innerHTML = "";
        myRankingsList10.innerHTML = "";

        if (userLeaderboards1.length == 0) {        // If user has no entries for 1 rep leaderboards, dispay relevant message

            const firstMessage = document.createElement("li");
            firstMessage.classList.add("my-rankings-first-message");
            firstMessage.innerHTML = "You aren't currently registered on any leaderboards of this category";

            const secondMessage = document.createElement("li");
            secondMessage.classList.add("my-rankings-second-message");
            secondMessage.innerHTML = "Find leaderboards to join below!";

            const hr = document.createElement("hr");
            hr.classList.add("my-rankings-hr");

            myRankingsList1.appendChild(firstMessage);
            myRankingsList1.appendChild(secondMessage);
            myRankingsList1.appendChild(hr);

        } else {

            for (let i = 0; i < userLeaderboards1.length; i++) {           // Loop through userLeaderboards1 array
                const userLeaderboard = userLeaderboards1[i];

                const listItem = document.createElement("li");             // Create and display list of buttons containing user's leaderboard rank and leaderboard exercise
                const button = document.createElement("button");            
                button.addEventListener("click", () => {                            // When button clicked
                    let hidden1 = leaderboardCard.getAttribute("hidden");           // Show card containing leaderboard information and current user's entry
                    if (hidden1) {
                        leaderboardCard.removeAttribute("hidden");
                    }

                    let hidden2 = leaderboardDisplayCard.getAttribute("hidden");    // Show card containing all leaderboard entries
                    if (hidden2) {
                        leaderboardDisplayCard.removeAttribute("hidden");
                    }

                    myRankingsCard.setAttribute("hidden", "hidden");                // Hide card containing list of current user's leaderboards
                    searchLeaderboardsCard.setAttribute("hidden", "hidden");        // Hide card for searching for a leaderboard

                    const exercise = userLeaderboard[1];                    // Variable containing leaderboard exercise
                    const reps = userLeaderboard[2] + " x Rep(s)";          // Variable containing leaderboard number of reps (for display)

                    showLeaderboard(exercise, reps);        // Call function to display leaderboard information and entries          
                })

                const myRankingsButtonContainer = document.createElement("div");        // Create HTML elements containing user's entry with their rank and the exercise
                const myRankingsRankContainer = document.createElement("div");
                const myRankingsExerciseContainer = document.createElement("div");

                const myRankingsRankInnerContainer = document.createElement("div");
                const myRankingsRank = document.createElement("p");
                const myRankingsExercise = document.createElement("p");
                
                const hr = document.createElement("hr");

                button.classList.add("my-rankings-button");

                myRankingsButtonContainer.classList.add("my-rankings-button-container");
                myRankingsRankContainer.classList.add("my-rankings-rank-container");
                myRankingsExerciseContainer.classList.add("my-rankings-exercise-container");

                myRankingsRankInnerContainer.classList.add("my-rankings-rank-inner-container");
                myRankingsRank.classList.add("my-rankings-rank");
                myRankingsExercise.classList.add("my-rankings-exercise");

                hr.classList.add("my-rankings-hr");

                myRankingsRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${userLeaderboard[0]}`;         // User's rank on the leaderboard

                if (userLeaderboard[0] == 1) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";       // Make rank colour gold, silver or bronze if 1st, 2nd or 3rd
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                } else if (userLeaderboard[0] == 2) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
                } else if (userLeaderboard[0] == 3) {
                    myRankingsRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                }

                myRankingsExercise.innerHTML = userLeaderboard[1];          // Leaderboard exercise

                myRankingsRankInnerContainer.appendChild(myRankingsRank);
                myRankingsRankContainer.appendChild(myRankingsRankInnerContainer);
                myRankingsExerciseContainer.appendChild(myRankingsExercise);

                myRankingsButtonContainer.appendChild(myRankingsRankContainer);
                myRankingsButtonContainer.appendChild(myRankingsExerciseContainer);

                button.appendChild(myRankingsButtonContainer);
                listItem.appendChild(button);
                myRankingsList1.appendChild(listItem);
                myRankingsList1.appendChild(hr);
            }
        }

        // ---


        // --- DISPLAY LIST OF USER'S 3 REP LEADERBOARDS

        if (userLeaderboards3.length == 0) {        // If user has no entries for 3 rep leaderboards, dispay relevant message

            const firstMessage = document.createElement("li");
            firstMessage.classList.add("my-rankings-first-message");
            firstMessage.innerHTML = "You aren't currently registered on any leaderboards of this category";

            const secondMessage = document.createElement("li");
            secondMessage.classList.add("my-rankings-second-message");
            secondMessage.innerHTML = "Find leaderboards to join below!";

            const hr = document.createElement("hr");
            hr.classList.add("my-rankings-hr");

            myRankingsList3.appendChild(firstMessage);
            myRankingsList3.appendChild(secondMessage);
            myRankingsList3.appendChild(hr);

        } else {

            for (let i = 0; i < userLeaderboards3.length; i++) {            // Loop through userLeaderboards3 array
                const userLeaderboard = userLeaderboards3[i];

                const listItem = document.createElement("li");              // Create and display list of buttons containing user's leaderboard rank and leaderboard exercise
                const button = document.createElement("button");
                button.addEventListener("click", () => {                            // When button clicked
                    let hidden1 = leaderboardCard.getAttribute("hidden");           // Show card containing leaderboard information and current user's entry
                    if (hidden1) {
                        leaderboardCard.removeAttribute("hidden");
                    }

                    let hidden2 = leaderboardDisplayCard.getAttribute("hidden");    // Show card containing all leaderboard entries
                    if (hidden2) {
                        leaderboardDisplayCard.removeAttribute("hidden");
                    }

                    myRankingsCard.setAttribute("hidden", "hidden");                // Hide card containing list of current user's leaderboards
                    searchLeaderboardsCard.setAttribute("hidden", "hidden");        // Hide card for searching for a leaderboard

                    const exercise = userLeaderboard[1];                    // Variable containing leaderboard exercise
                    const reps = userLeaderboard[2] + " x Rep(s)";          // Variable containing leaderboard number of reps (for display)

                    showLeaderboard(exercise, reps);        // Call function to display leaderboard information and entries 
                })

                const myRankingsButtonContainer = document.createElement("div");        // Create HTML elements containing user's entry with their rank and the exercise
                const myRankingsRankContainer = document.createElement("div");
                const myRankingsExerciseContainer = document.createElement("div");

                const myRankingsRankInnerContainer = document.createElement("div");
                const myRankingsRank = document.createElement("p");
                const myRankingsExercise = document.createElement("p");
                
                const hr = document.createElement("hr");

                button.classList.add("my-rankings-button");

                myRankingsButtonContainer.classList.add("my-rankings-button-container");
                myRankingsRankContainer.classList.add("my-rankings-rank-container");
                myRankingsExerciseContainer.classList.add("my-rankings-exercise-container");

                myRankingsRankInnerContainer.classList.add("my-rankings-rank-inner-container");
                myRankingsRank.classList.add("my-rankings-rank");
                myRankingsExercise.classList.add("my-rankings-exercise");

                hr.classList.add("my-rankings-hr");

                myRankingsRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${userLeaderboard[0]}`;         // User's rank on the leaderboard

                if (userLeaderboard[0] == 1) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";       // Make rank colour gold, silver or bronze if 1st, 2nd or 3rd
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                } else if (userLeaderboard[0] == 2) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
                } else if (userLeaderboard[0] == 3) {
                    myRankingsRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                }

                myRankingsExercise.innerHTML = userLeaderboard[1];          // Leaderboard exercise

                myRankingsRankInnerContainer.appendChild(myRankingsRank);
                myRankingsRankContainer.appendChild(myRankingsRankInnerContainer);
                myRankingsExerciseContainer.appendChild(myRankingsExercise);

                myRankingsButtonContainer.appendChild(myRankingsRankContainer);
                myRankingsButtonContainer.appendChild(myRankingsExerciseContainer);

                button.appendChild(myRankingsButtonContainer);
                listItem.appendChild(button);
                myRankingsList3.appendChild(listItem);
                myRankingsList3.appendChild(hr);
            }
        }

        // ---


        // --- DISPLAY LIST OF USER'S 5 REP LEADERBOARDS

        if (userLeaderboards5.length == 0) {        // If user has no entries for 5 rep leaderboards, dispay relevant message

            const firstMessage = document.createElement("li");
            firstMessage.classList.add("my-rankings-first-message");
            firstMessage.innerHTML = "You aren't currently registered on any leaderboards of this category";

            const secondMessage = document.createElement("li");
            secondMessage.classList.add("my-rankings-second-message");
            secondMessage.innerHTML = "Find leaderboards to join below!";

            const hr = document.createElement("hr");
            hr.classList.add("my-rankings-hr");

            myRankingsList5.appendChild(firstMessage);
            myRankingsList5.appendChild(secondMessage);
            myRankingsList5.appendChild(hr);

        } else {

            for (let i = 0; i < userLeaderboards5.length; i++) {            // Loop through userLeaderboards5 array
                const userLeaderboard = userLeaderboards5[i];

                const listItem = document.createElement("li");              // Create and display list of buttons containing user's leaderboard rank and leaderboard exercise
                const button = document.createElement("button");
                button.addEventListener("click", () => {                            // When button clicked
                    let hidden1 = leaderboardCard.getAttribute("hidden");           // Show card containing leaderboard information and current user's entry
                    if (hidden1) {
                        leaderboardCard.removeAttribute("hidden");
                    }

                    let hidden2 = leaderboardDisplayCard.getAttribute("hidden");    // Show card containing all leaderboard entries
                    if (hidden2) {
                        leaderboardDisplayCard.removeAttribute("hidden");
                    }

                    myRankingsCard.setAttribute("hidden", "hidden");                // Hide card containing list of current user's leaderboards
                    searchLeaderboardsCard.setAttribute("hidden", "hidden");        // Hide card for searching for a leaderboard

                    const exercise = userLeaderboard[1];                    // Variable containing leaderboard exercise
                    const reps = userLeaderboard[2] + " x Rep(s)";          // Variable containing leaderboard number of reps (for display)

                    showLeaderboard(exercise, reps);        // Call function to display leaderboard information and entries 
                })

                const myRankingsButtonContainer = document.createElement("div");        // Create HTML elements containing user's entry with their rank and the exercise
                const myRankingsRankContainer = document.createElement("div");
                const myRankingsExerciseContainer = document.createElement("div");

                const myRankingsRankInnerContainer = document.createElement("div");
                const myRankingsRank = document.createElement("p");
                const myRankingsExercise = document.createElement("p");
                
                const hr = document.createElement("hr");

                button.classList.add("my-rankings-button");

                myRankingsButtonContainer.classList.add("my-rankings-button-container");
                myRankingsRankContainer.classList.add("my-rankings-rank-container");
                myRankingsExerciseContainer.classList.add("my-rankings-exercise-container");

                myRankingsRankInnerContainer.classList.add("my-rankings-rank-inner-container");
                myRankingsRank.classList.add("my-rankings-rank");
                myRankingsExercise.classList.add("my-rankings-exercise");

                hr.classList.add("my-rankings-hr");

                myRankingsRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${userLeaderboard[0]}`;         // User's rank on the leaderboard

                if (userLeaderboard[0] == 1) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";       // Make rank colour gold, silver or bronze if 1st, 2nd or 3rd
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                } else if (userLeaderboard[0] == 2) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
                } else if (userLeaderboard[0] == 3) {
                    myRankingsRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                }

                myRankingsExercise.innerHTML = userLeaderboard[1];          // Leaderboard exercise

                myRankingsRankInnerContainer.appendChild(myRankingsRank);
                myRankingsRankContainer.appendChild(myRankingsRankInnerContainer);
                myRankingsExerciseContainer.appendChild(myRankingsExercise);

                myRankingsButtonContainer.appendChild(myRankingsRankContainer);
                myRankingsButtonContainer.appendChild(myRankingsExerciseContainer);

                button.appendChild(myRankingsButtonContainer);
                listItem.appendChild(button);
                myRankingsList5.appendChild(listItem);
                myRankingsList5.appendChild(hr);
            }
        }

        // ---


        // --- DISPLAY LIST OF USER'S 10 REP LEADERBOARDS

        if (userLeaderboards10.length == 0) {       // If user has no entries for 10 rep leaderboards, dispay relevant message

            const firstMessage = document.createElement("li");
            firstMessage.classList.add("my-rankings-first-message");
            firstMessage.innerHTML = "You aren't currently registered on any leaderboards of this category";

            const secondMessage = document.createElement("li");
            secondMessage.classList.add("my-rankings-second-message");
            secondMessage.innerHTML = "Find leaderboards to join below!";

            const hr = document.createElement("hr");
            hr.classList.add("my-rankings-hr");

            myRankingsList10.appendChild(firstMessage);
            myRankingsList10.appendChild(secondMessage);
            myRankingsList10.appendChild(hr);

        } else {

            for (let i = 0; i < userLeaderboards10.length; i++) {           // Loop through userLeaderboards10 array
                const userLeaderboard = userLeaderboards10[i];

                const listItem = document.createElement("li");              // Create and display list of buttons containing user's leaderboard rank and leaderboard exercise
                const button = document.createElement("button");
                button.addEventListener("click", () => {                            // When button clicked
                    let hidden1 = leaderboardCard.getAttribute("hidden");           // Show card containing leaderboard information and current user's entry
                    if (hidden1) {
                        leaderboardCard.removeAttribute("hidden");
                    }

                    let hidden2 = leaderboardDisplayCard.getAttribute("hidden");    // Show card containing all leaderboard entries
                    if (hidden2) {
                        leaderboardDisplayCard.removeAttribute("hidden");
                    }

                    myRankingsCard.setAttribute("hidden", "hidden");                // Hide card containing list of current user's leaderboards
                    searchLeaderboardsCard.setAttribute("hidden", "hidden");        // Hide card for searching for a leaderboard

                    const exercise = userLeaderboard[1];                    // Variable containing leaderboard exercise
                    const reps = userLeaderboard[2] + " x Rep(s)";          // Variable containing leaderboard number of reps (for display)

                    showLeaderboard(exercise, reps);        // Call function to display leaderboard information and entries 
                })

                const myRankingsButtonContainer = document.createElement("div");        // Create HTML elements containing user's entry with their rank and the exercise
                const myRankingsRankContainer = document.createElement("div");
                const myRankingsExerciseContainer = document.createElement("div");

                const myRankingsRankInnerContainer = document.createElement("div");
                const myRankingsRank = document.createElement("p");
                const myRankingsExercise = document.createElement("p");
                
                const hr = document.createElement("hr");

                button.classList.add("my-rankings-button");

                myRankingsButtonContainer.classList.add("my-rankings-button-container");
                myRankingsRankContainer.classList.add("my-rankings-rank-container");
                myRankingsExerciseContainer.classList.add("my-rankings-exercise-container");

                myRankingsRankInnerContainer.classList.add("my-rankings-rank-inner-container");
                myRankingsRank.classList.add("my-rankings-rank");
                myRankingsExercise.classList.add("my-rankings-exercise");

                hr.classList.add("my-rankings-hr");

                myRankingsRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${userLeaderboard[0]}`;         // User's rank on the leaderboard

                if (userLeaderboard[0] == 1) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";       // Make rank colour gold, silver or bronze if 1st, 2nd or 3rd
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                } else if (userLeaderboard[0] == 2) {
                    myRankingsRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
                } else if (userLeaderboard[0] == 3) {
                    myRankingsRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                    myRankingsRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                }

                myRankingsExercise.innerHTML = userLeaderboard[1];          // Leaderboard exercise

                myRankingsRankInnerContainer.appendChild(myRankingsRank);
                myRankingsRankContainer.appendChild(myRankingsRankInnerContainer);
                myRankingsExerciseContainer.appendChild(myRankingsExercise);

                myRankingsButtonContainer.appendChild(myRankingsRankContainer);
                myRankingsButtonContainer.appendChild(myRankingsExerciseContainer);

                button.appendChild(myRankingsButtonContainer);
                listItem.appendChild(button);
                myRankingsList10.appendChild(listItem);
                myRankingsList10.appendChild(hr);
            }
        }

        // ---


        // --- ADD FUNCTIONALITY TO DROPDOWN BUTTONS TO SHOW/HIDE USER'S LEADERBOARD RANKINGS

        const myRankingsCategoryButton1 = document.getElementById("my-rankings-category-button-1");         // Dropdown button for user's 1 rep leaderboard rankings
        const myRankingsCategoryButton3 = document.getElementById("my-rankings-category-button-3");         // Dropdown button for user's 3 rep leaderboard rankings
        const myRankingsCategoryButton5 = document.getElementById("my-rankings-category-button-5");         // Dropdown button for user's 5 rep leaderboard rankings
        const myRankingsCategoryButton10 = document.getElementById("my-rankings-category-button-10");       // Dropdown button for user's 10 rep leaderboard rankings

        function showMyRankingsCategory(list) {             // Function for showing a given list
            let hidden = list.getAttribute("hidden");
                if (hidden) {
                    list.removeAttribute("hidden");
                }
        }

        function hideMyRankingsCategory(list) {             // Function for hiding a given list
            list.setAttribute("hidden", "hidden");
        }

        var showReps1 = false;          // Variable that indicates if user's 1 rep leaderboard rankings are currently showing
        var showReps3 = false;          // Variable that indicates if user's 3 rep leaderboard rankings are currently showing
        var showReps5 = false;          // Variable that indicates if user's 5 rep leaderboard rankings are currently showing
        var showReps10 = false;         // Variable that indicates if user's 10 rep leaderboard rankings are currently showing

        hideMyRankingsCategory(myRankingsList1);        // Initially hide all lists of user leaderboard rankings
        hideMyRankingsCategory(myRankingsList3);
        hideMyRankingsCategory(myRankingsList5);
        hideMyRankingsCategory(myRankingsList10);


        myRankingsCategoryButton1.addEventListener("click", () => {                             // When dropdown button for 1 rep leaderboards is clicked
            
            if (showReps1 == false) {                                                           // If user's 1 rep leaderboard rankings are not currently showing
                showMyRankingsCategory(myRankingsList1);                                        // Show them 
                myRankingsCategoryButton1.innerHTML = `<i class="bi bi-caret-up-fill"></i>`;    // Change symbol on button to up, as list has now "dropped down"
                showReps1 = true;                                                               // Change varianle to indicate user's 1 rep leaderboard rankings are showing

            } else {                                                                            // Else user's 1 rep leaderboard rankings are currently showing                                  
                hideMyRankingsCategory(myRankingsList1);                                        // Hide them
                myRankingsCategoryButton1.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;  // Change symbol on button to down, as list has now "gone back up"
                showReps1 = false;                                                              // Change variable to indicate user's 1 rep leaderboard rankings are not showing
            }
        })

        myRankingsCategoryButton3.addEventListener("click", () => {                             // When dropdown button for 3 rep leaderboards is clicked
            
            if (showReps3 == false) {                                                           // If user's 3 rep leaderboard rankings are not currently showing
                showMyRankingsCategory(myRankingsList3);                                        // Show them 
                myRankingsCategoryButton3.innerHTML = `<i class="bi bi-caret-up-fill"></i>`;    // Change symbol on button to up, as list has now "dropped down"
                showReps3 = true;                                                               // Change varianle to indicate user's 3 rep leaderboard rankings are showing
            
            } else {                                                                            // Else user's 3 rep leaderboard rankings are currently showing       
                hideMyRankingsCategory(myRankingsList3);                                        // Hide them
                myRankingsCategoryButton3.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;  // Change symbol on button to down, as list has now "gone back up"
                showReps3 = false;                                                              // Change variable to indicate user's 3 rep leaderboard rankings are not showing
            }
        })

        myRankingsCategoryButton5.addEventListener("click", () => {                             // When dropdown button for 5 rep leaderboards is clicked
            
            if (showReps5 == false) {                                                           // If user's 5 rep leaderboard rankings are not currently showing
                showMyRankingsCategory(myRankingsList5);                                        // Show them 
                myRankingsCategoryButton5.innerHTML = `<i class="bi bi-caret-up-fill"></i>`;    // Change symbol on button to up, as list has now "dropped down"
                showReps5 = true;                                                               // Change varianle to indicate user's 5 rep leaderboard rankings are showing
            
            } else {                                                                            // Else user's 5 rep leaderboard rankings are currently showing 
                hideMyRankingsCategory(myRankingsList5);                                        // Hide them
                myRankingsCategoryButton5.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;  // Change symbol on button to down, as list has now "gone back up"
                showReps5 = false;                                                              // Change variable to indicate user's 5 rep leaderboard rankings are not showing
            }
        })

        myRankingsCategoryButton10.addEventListener("click", () => {                            // When dropdown button for 10 rep leaderboards is clicked

            if (showReps10 == false) {                                                          // If user's 10 rep leaderboard rankings are not currently showing
                showMyRankingsCategory(myRankingsList10);                                       // Show them 
                myRankingsCategoryButton10.innerHTML = `<i class="bi bi-caret-up-fill"></i>`;   // Change symbol on button to up, as list has now "dropped down"
                showReps10 = true;                                                              // Change varianle to indicate user's 10 rep leaderboard rankings are showing

            } else {                                                                            // Else user's 10 rep leaderboard rankings are currently showing 
                hideMyRankingsCategory(myRankingsList10);                                       // Hide them
                myRankingsCategoryButton10.innerHTML = `<i class="bi bi-caret-down-fill"></i>`; // Change symbol on button to down, as list has now "gone back up"
                showReps10 = false;                                                             // Change variable to indicate user's 10 rep leaderboard rankings are not showing
            }
        })

        // ---


        // --- ADD FUNCTIONALITY TO SHOW LIST OF EXERCISES / LIST OF NUMBER OF REPS WHEN SEARCHING FOR LEADERBOARD

        function showExercises() {                      // Function for displaying the list of exercises

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

            searchLeaderboardsValidationText.innerHTML = "";        // Remove any displayed validation text
            searchResultsList.innerHTML = "<hr>";                   // Empty display list

            for (let i = 0; i < exercises.length; i++) {                // Loop through list of exercises
                const listItem = document.createElement("li");          // For each exercise, add button for that exercise to display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("search-result-list-item");
                button.classList.add("search-result-button"); 

                button.addEventListener("click", function() {                   // When button clicked
                    searchLeaderboardsExercise.value = button.textContent;      // Set value of currently selected exercise to this button's exercise
                    searchResultsList.innerHTML = "";                           // Empty display list 
                });
                
                listItem.appendChild(button);
                searchResultsList.appendChild(listItem);
                searchResultsList.appendChild(hr);
            }

        }

        function showReps() {           // Function for displaying the list of number of reps

            const reps = [              // Array containing list of all number of reps 
                1,
                3,
                5,
                10
            ];

            searchLeaderboardsValidationText.innerHTML = "";        // Remove any displayed validation text
            searchResultsList.innerHTML = "<hr>";                   // Empty display list

            for (let i = 0; i < reps.length; i++) {                     // Loop through the list of number of reps
                const listItem = document.createElement("li");          // For each number of reps, add button for that number of reps to display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = reps[i] + " x Rep(s)"; 
                listItem.classList.add("search-result-list-item")
                button.classList.add("search-result-button"); 

                if (reps[i] == "1") {                                       // Colour code each number of reps for clear distinction and easier identification for user
                    button.style.color = "rgb(255, 82, 82)";        
                } else if (reps[i] == "3") {
                    button.style.color = "rgb(199, 199, 43)";
                } else if (reps[i] == "5") {
                    button.style.color = "rgb(48, 235, 48)";
                } else if (reps[i] == "10") {
                    button.style.color = "rgb(83, 173, 247)";
                }

                button.addEventListener("click", function() {                   // When button clicked
                    searchLeaderboardsReps.value = button.textContent;          // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                                 // Colour code currently selected number of reps to match previous 
                        searchLeaderboardsReps.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        searchLeaderboardsReps.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        searchLeaderboardsReps.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        searchLeaderboardsReps.style.color = "rgb(83, 173, 247)";
                    }

                    searchResultsList.innerHTML = "";                            // Empty display list
                });
                            
                listItem.appendChild(button);
                searchResultsList.appendChild(listItem);
                searchResultsList.appendChild(hr);
            }
        }

        // ---


        // --- HANDLE WHEN USER SEARCHES FOR LEADERBOARD

        function showSearchLeaderboard() {                          

            const exercise = searchLeaderboardsExercise.value;      // Exercise selected by user
            const reps = searchLeaderboardsReps.value;              // Number of reps selected by user

            if (exercise == "" || reps == "") {                     // If exercise of number of reps not selected
                searchLeaderboardsValidationText.innerHTML = "Please select an Exercise and a Number of Reps";      // Display error message telling user to enter both
            } else {
                searchLeaderboardsValidationText.innerHTML = "";                // Else if both selected

                let hidden1 = leaderboardCard.getAttribute("hidden");           // Show card containing leaderboard information and current user's entry
                if (hidden1) {
                    leaderboardCard.removeAttribute("hidden");
                }

                let hidden2 = leaderboardDisplayCard.getAttribute("hidden");    // Show card containing all leaderboard entries 
                if (hidden2) {
                    leaderboardDisplayCard.removeAttribute("hidden");
                }

                myRankingsCard.setAttribute("hidden", "hidden");                // Hide card containing list of current user's leaderboards
                searchLeaderboardsCard.setAttribute("hidden", "hidden");        // Hide card for searching for a leaderboard
                
                showLeaderboard(exercise, reps);                // Call function for displaying leaderboard entries with selected exercise and number of reps
            }

        }

        // ---


        // --- DISPLAY ALL INFORMATION AND ENTRIES FOR A LEADERBOARD

        function showLeaderboard(exercise, reps) {

            deleteEntryButton.setAttribute("hidden", "hidden");  

            const repsValue = reps.split(" ")[0];       // Extract number of reps from text

                leaderboardName.innerHTML = exercise;   // Display leaderboard name as exercise

                if (repsValue == "1") {                                                                                                                                     // Display number of reps, with colour coding consistent to prevoious
                    leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:medium; color:rgb(255, 82, 82); padding-right:8px"></i> ${reps}`;
                } else if (repsValue == "3") {
                    leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(199, 199, 43); padding-right:8px"></i> ${reps}`;
                } else if (repsValue == "5") {
                    leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(48, 235, 48); padding-right:8px"></i> ${reps}`;
                } else if (repsValue == "10") {
                    leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(83, 173, 247); padding-right:8px"></i> ${reps}`;
                }
                
                fetch("fetch-leaderboard-process.php", {                        // Post request to php file to fetch leaderboard entries
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ exercise:exercise , reps:repsValue })    // Pass values for exercise and number of reps so that only relevant entries are fetched
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {                                         // If entries fetched successfully

                        const entries = data.data;

                        leaderboardEntries.innerHTML = "";                      // Set display list as empty 

                        if (entries.length == 0) {                              // If no entries exist for this leaderboard
                            
                            const firstMessage = document.createElement("li");              // Display relevant message
                            firstMessage.classList.add("leaderboard-first-message");
                            firstMessage.innerHTML = "No Entries Yet";

                            const secondMessage = document.createElement("li");
                            secondMessage.classList.add("leaderboard-second-message");
                            secondMessage.innerHTML = "Be the first to add an Entry!";

                            leaderboardEntries.appendChild(firstMessage);
                            leaderboardEntries.appendChild(secondMessage);

                        } else {                                                // Else if entries exist for this leaderboard
                
                            for (let i = 0; i < entries.length; i++) {          // Loop through list of entries

                                const entry = entries[i];
                                
                                const listItem = document.createElement("li");          // Create and display list of buttons, each containing a leaderboard entry
                                const button = document.createElement("button");
                                button.addEventListener("click", function() {           // When button containing entry is clicked

                                    const username = entry.username;                    
                                    const score = entry.score;

                                    showEntryUser.innerHTML = username;                 // Display username associated with entry in the show entry modal
                                    showEntryScore.innerHTML = score + "kg";            // Display the weight achieved in this entry in the show entry modal

                                    const reportButtonContainer = document.getElementById("report-button-container");

                                    if(username == currentUsername) {                                   // If selected entry belongs to the current user
                                        reportButtonContainer.setAttribute("hidden", "hidden");         // Hide the report button in the show entry modal (User cannot report themself)
                                    
                                    } else {                                                            // If selected entry does not belong to the current user
                                        let hidden = reportButtonContainer.getAttribute("hidden");      // Ensure report button is not hidden
                                        if (hidden) {
                                            reportButtonContainer.removeAttribute("hidden");
                                        }
                                    }

                                    fetch("fetch-entry-process.php", {              // Post request to php file to fetch details about selected entry
                                    method: "POST",     
                                    headers: {
                                        "Content-Type": "application/json"
                                    },
                                    body: JSON.stringify({ username:username , exercise:exercise , reps:repsValue })        // Pass values for username, exercise and number of reps, so the correct entry is fetched
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {                         // If entry is fetched successfully 

                                            videoContainer.innerHTML = "";          // Set video container as empty
                                            const entryVideo = data.data.video;

                                            const video = document.createElement("video");              // Create and display video element containing video associated with selected entry
                                            video.setAttribute("controls", "controls");
                                            video.setAttribute("width", "600");
                                            video.innerHTML = `                                         
                                                <source src="${entryVideo}" type="video/mp4">
                                                <source src="${entryVideo}" type="video/quicktime">
                                                <source src="${entryVideo}" type="video/x-msvideo">
                                                <source src="${entryVideo}" type="video/webm">
                                                Your browser does not support the video tag.
                                            `;                                                           // Ensure all valid video file formats can be displayed
                                            videoContainer.appendChild(video);

                                        } else {
                                            console.log("Error:", data.error);
                                        }
                                    })
                                    .catch(error => console.log("Fetch error:", error));
            

                                    showEntryModal.showModal();                                           // Display the show entry modal
                                });

                                const leaderboardButtonContainer = document.createElement("div");
                                const leaderboardRankContainer = document.createElement("div");
                                const leaderboardUsernameContainer = document.createElement("div");
                                const leaderboardScoreContainer = document.createElement("div");

                                const leaderboardRankInnerContainer = document.createElement("div");
                                const leaderboardRank = document.createElement("p");
                                const leaderboardUsername = document.createElement("p");
                                const leaderboardScore = document.createElement("p");
                                
                                const hr = document.createElement("hr");

                                button.classList.add("search-result-button");

                                leaderboardButtonContainer.classList.add("leaderboard-button-container");
                                leaderboardRankContainer.classList.add("leaderboard-rank-container");
                                leaderboardUsernameContainer.classList.add("leaderboard-username-container");
                                leaderboardScoreContainer.classList.add("leaderboard-score-container");

                                leaderboardRankInnerContainer.classList.add("leaderboard-rank-inner-container");
                                leaderboardRank.classList.add("leaderboard-rank");
                                leaderboardUsername.classList.add("leaderboard-username");
                                leaderboardScore.classList.add("leaderboard-score");

                                leaderboardRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${i + 1}`;                     // Use index of list of entries (ordered by weight achieved) to determine entry rank

                                if ((i + 1) == 1) {
                                    leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";      // If entry is 1st, 2nd or 3rd, make rank gold, silver or bronze
                                    leaderboardRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                                } else if ((i + 1) == 2) {
                                    leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                                    leaderboardRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
                                } else if ((i + 1) == 3) {
                                    leaderboardRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                                    leaderboardRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                                }

                                leaderboardUsername.innerHTML = entry.username;             // Display username associated with entry
                                leaderboardScore.innerHTML = entry.score + "kg";            // Display weight achieved for this entry

                                leaderboardRankInnerContainer.appendChild(leaderboardRank);
                                leaderboardRankContainer.appendChild(leaderboardRankInnerContainer);
                                leaderboardUsernameContainer.appendChild(leaderboardUsername);
                                leaderboardScoreContainer.appendChild(leaderboardScore);

                                leaderboardButtonContainer.appendChild(leaderboardRankContainer);
                                leaderboardButtonContainer.appendChild(leaderboardUsernameContainer);
                                leaderboardButtonContainer.appendChild(leaderboardScoreContainer);

                                button.appendChild(leaderboardButtonContainer);
                                listItem.appendChild(button);
                                leaderboardEntries.appendChild(listItem);
                                leaderboardEntries.appendChild(hr);

                                if (entry.username == currentUsername) {                    // If this entry belongs to the current user

                                    let hidden = deleteEntryButton.getAttribute("hidden");    // Show card containing all leaderboard entries
                                    if (hidden) {
                                        deleteEntryButton.removeAttribute("hidden");
                                    }

                                    let clonedListItem = listItem.cloneNode(true);          // Clone HTML elements to be displayed in the "Your Rank" section
                                    let hr2 = document.createElement("hr");

                                    hr2.classList.add("my-rankings-hr");

                                    let clonedButton = clonedListItem.querySelector(".search-result-button");
                                    let clonedUsername = clonedListItem.querySelector(".leaderboard-username");
                                    let clonedScore = clonedListItem.querySelector(".leaderboard-score");

                                    if (clonedButton) {                                                             // Give button appropriate styling for different coloured card
                                        clonedButton.addEventListener("mouseenter", () => {
                                            clonedButton.style.backgroundColor = "rgb(126, 108, 143)";
                                        });

                                        clonedButton.addEventListener("mouseleave", () => {
                                            clonedButton.style.backgroundColor = "rgb(68, 19, 113)";
                                        });

                                        clonedButton.addEventListener("click", function() {                          // When user's entry button clicked

                                            const username = entry.username;
                                            const score = entry.score;

                                            showEntryUser.innerHTML = username;                 // Display username associated with entry in the show entry modal
                                            showEntryScore.innerHTML = score + "kg";            // Display weight achieved in this entry in the show entry modal

                                            const reportButtonContainer = document.getElementById("report-button-container");

                                            if(username == currentUsername) {                                   // If selected entry belongs to the current user
                                                reportButtonContainer.setAttribute("hidden", "hidden");         // Hide the report button in the show entry modal (User cannot report themself)
                                            
                                            } else {                                                            // If selected entry does not belong to the current user
                                                let hidden = reportButtonContainer.getAttribute("hidden");      // Ensure report button is not hidden
                                                if (hidden) {
                                                    reportButtonContainer.removeAttribute("hidden");
                                                }
                                            }

                                            fetch("fetch-entry-process.php", {                  // Post request to php file to fetch details about selected entry
                                            method: "POST",
                                            headers: {
                                                "Content-Type": "application/json"
                                            },
                                            body: JSON.stringify({ username:username , exercise:exercise , reps:repsValue })        // Pass values for username, exercise and number of reps, so the correct entry is fetched
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {                             // If entry is fetched successfully 

                                                    videoContainer.innerHTML = "";              // Set video container as empty
                                                    const entryVideo = data.data.video;

                                                    const video = document.createElement("video");              // Create and display video element containing video associated with selected entry
                                                    video.setAttribute("controls", "controls");
                                                    video.setAttribute("width", "600");
                                                    video.innerHTML = `
                                                        <source src="${entryVideo}" type="video/mp4">
                                                        <source src="${entryVideo}" type="video/quicktime">
                                                        <source src="${entryVideo}" type="video/x-msvideo">
                                                        <source src="${entryVideo}" type="video/webm">
                                                        Your browser does not support the video tag.
                                                    `;                                                          // Ensure all valid video file formats can be displayed
                                                    videoContainer.appendChild(video);

                                                } else {
                                                    console.log("Error:", data.error);
                                                }
                                            })
                                            .catch(error => console.log("Fetch error:", error));


                                            showEntryModal.showModal();                                         // Display the show entry modal
                                        });
                                    }

                                    if (clonedUsername) {                                                       // Give username appropriate styling for different coloured card
                                        clonedUsername.style.color = "white";
                                    }

                                    if (clonedScore) {                                                          // Give score appropriate styling for different coloured card
                                        clonedScore.style.backgroundImage = "linear-gradient(90deg, rgb(3, 244, 51), rgb(0, 149, 255))";
                                        clonedScore.style.webkitBackgroundClip = "text";
                                        clonedScore.style.backgroundClip = "text";
                                        clonedScore.style.color = "transparent";
                                    }

                                    userLeaderboardEntry.innerHTML = "";                                         // Set user entry display as empty
                                    userLeaderboardEntry.appendChild(clonedListItem);                            // Display user's entry
                                    userLeaderboardEntry.appendChild(hr2);
                                }

                            }

                        }
                        
                    } else {
                        console.log("Error claiming rewards:", data.error);
                    }
                })
                .catch(error => console.log("Fetch error:", error));
        }

        // ---


        // --- CLOSE LEADERBOARD

        function closeLeaderboard() {
            let hidden1 = myRankingsCard.getAttribute("hidden");            // Show card containing list of current user's leaderboards
            if (hidden1) {
                myRankingsCard.removeAttribute("hidden");
            }

            let hidden2 = searchLeaderboardsCard.getAttribute("hidden");    // Show card for searching for a leaderboard
            if (hidden2) {
                searchLeaderboardsCard.removeAttribute("hidden");
            }

            leaderboardCard.setAttribute("hidden", "hidden");               // Hide card containing leaderboard information and current user's entry
            leaderboardDisplayCard.setAttribute("hidden", "hidden");        // Hide card containing all leaderboard entries 

            userLeaderboardEntry.innerHTML = `<div style="text-align:center; padding-top: 25px;">                                                         
                                                <i class="small-sub-header" style=" color: white;">You aren't registered on this leaderboard yet</i>
                                            </div>`                                                                                                         
                                            // Display default message for when user does't have an entry for selected userboard
        }

        // ---


        // --- DELETE AN ENTRY

        function deleteEntry() {
            const exercise = leaderboardName.innerHTML;         // Store entry exercise
            const reps = leaderboardReps.textContent;           // Store entry number of reps (text display form)
            const repsValue = parseInt(reps);                   // Extract entry number of reps (Integer value)

            fetch("delete-entry-process.php", {                 // Post request to php file to delete this entry
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ userId:currentUserId , exercise:exercise , reps:repsValue })        // Pass values for user id, exercise and number of reps so correct entry is deleted
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                         // If entry was successfully deleted
                    deleteEntryModal.close();               // Close the modal for deleting an entry                    
                    deleteEntrySuccessModal.showModal();    // Show the modal to confirm entry was deleted successfully
                } else {
                    console.log("Error:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));

        }

        // ---


        // --- REPORT AN ENTRY

        function reportEntry() {
            const exercise = leaderboardName.innerHTML;         // Store entry exercise
            const reps = leaderboardReps.textContent;           // Store entry number of reps (text display form)
            const repsValue = parseInt(reps);                   // Extract entry number of reps (Integer value)
            const username = showEntryUser.innerHTML;           // Store entry username
            
            fetch("report-entry-process.php", {                 // Post request to php file to update flags value for this entry
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ username:username , exercise:exercise , reps:repsValue })        // Pass values for username, exercise and number of reps so correct entry is updated
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                         // If entry was successfully updated
                    reportModal.close();                    // Close the modal for reporting an entry
                    showEntryModal.close();                 // Close the modal for showing the details of a selected entry
                    
                    reportConfirmationModal.showModal();    // Show the modal to confirm report was submitted successfully
                } else {
                    console.log("Error:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));
        }

        // ---

        
        // --- VALIDATE KEY PRESSES FOR WEIGHT INPUT

        function validateScoreNum(event, input) {               // Function that only allows certain key presses when user inputs whole number part of weight for a new entry

            const key = event.key;
            const allowedKeys = ["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab"];      // Array containing allowed key presses

            if (!/^\d$/.test(key) && !allowedKeys.includes(key)) {                  // If key press is not a number or one of the allowed keys
                return false;                                                       // Key press is invalid and ignored
            }

            if (input.value.length >= 3 && !allowedKeys.includes(event.key)) {      // If value is 3 digits or more and key press is not one of the allowed keys     
                return false;                                                       // Key press is invalid and ignored
            }

        }

        function validateScoreDecimal(event, input) {           // Function that only allows certain key presses when user inputs decimal part of weight for a new entry

            const key = event.key;
            const allowedKeys = ["Backspace", "Delete", "ArrowLeft", "ArrowRight", "Tab"];      // Array containing allowed key presses

            if (!/^\d$/.test(key) && !allowedKeys.includes(key)) {                  // If key press is not a number or one of the allowed keys
                return false;                                                       // Key press is invalid and ignored
            }

            if (input.value.length >= 2 && !allowedKeys.includes(event.key)) {      // If value is 2 digits or more and key press is not one of the allowed keys
                return false;                                                       // Key press is invalid and ignored
            }

        }

        // ---


        // --- VALIDATE NEW ENTRY SUBMISSION 

        document.getElementById("add-entry-form").addEventListener("submit", function (event) {         // When form for new entry is submitted
            
            event.preventDefault();                                                                     // Don't submit yet

            maxVideoSize = 50 * 1024 * 1024;                                                            // Define max video size as 50MB
            validFormats = ["video/mp4", "video/quicktime", "video/x-msvideo", "video/webm"];           // Array containing valid video formats

            let isValid = true;             // Variable indicating if entry is valid

            const scoreNum = document.getElementById("new-entry-score-num").value.trim();               // Inputted weight (whole number part) value
            const scoreDecimal = document.getElementById("new-entry-score-decimal").value.trim();       // Inputted weight (decimal part) value
            const score = scoreNum + "." + "scoreDecimal";                                              // Combine these inputs to create score value
            const video = document.getElementById("video").files[0];                                    // Inputted video file

            const scoreValidationText = document.getElementById("new-entry-score-validation-text");
            const videoValidationText = document.getElementById("new-entry-video-validation-text");

            scoreValidationText.innerHTML = "";            // Set display error messages for weight or video submission as empty                                        
            videoValidationText.innerHTML = "";
        
            if (scoreNum === "" || scoreDecimal === "") {                       // If either field for weight is empty
                isValid = false;                                                // Submission is invalid
                scoreValidationText.innerHTML = "Please fill in both fields";   // Display relevant error message
            }

            if (!video) {                                                       // If no video has been selected
                isValid = false;                                                // Submission is invalid
                videoValidationText.innerHTML = "Please select a video";        // Display releant error message
            
            } else if (video.size > maxVideoSize) {                             // If video file size exceeds 50MB
                isValid = false;                                                // Submission is invalid 
                videoValidationText.innerHTML = "Video is too large. Maximum size is 50MB";     // Display relevant error message
            
            } else if (!validFormats.includes(video.type)) {                    // If video is not a valid format
                isValid = false;                                                // Submission is invalid
                videoValidationText.innerHTML = "Video must be of the following formats: mp4, mov, avi, wmv, flv"       // Display relevant error message
            }

            if (!isValid) {         // If submission is invalid, do not submit
                return;
            }

            this.submit();          // If submission is valid, submit 
        });

        // ---


        // --- HANDLE SUCCESSFUL UPLOAD 

        var uploadSuccess = <?php echo json_encode($uploadSuccess); ?>;         // Fetch php variable determining if there's been a successful upload

        const uploadSuccessModal = document.getElementById("upload-success-modal");

        if (uploadSuccess == true) {                // If there has been a successful upload
            uploadSuccessModal.showModal();         // Show modal for confirming a successful upload

            <?php unset($_SESSION["upload_success"]); ?>        // Set session variable upload_success as false so this does not repeat when page is reloaded
        }

        const closeUploadSuccessModal = document.getElementById("close-upload-success-modal-button");  

        closeUploadSuccessModal.addEventListener("click", () => {               // When close upload success modal button is pressed             
            uploadSuccessModal.close();                                         // Close modal
            
            xpToAdd = 250;                              // XP reward value for user for uploading to a leaderboard

            fetch("../progression/add-xp-process.php", {               // Post request to php file to add xp to user's xp total
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ xpToAdd:xpToAdd })       // Pass value of xp to be added
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                             // If xp was successfully added to user's xp total

                    var overlay = document.getElementById("xp-overlay");        // Display overlay with xp reward message
                    overlay.style.display = "flex"; 

                    setTimeout(() => {                      // Show message for 2 seconds
                        overlay.style.display = "none"; 
                        window.location.reload();           // After the 2 seconds, reload the page
                    }, 2000);                               // This is essential as it allows to check if user has leveled up after the xp increase
                } else {
                    console.log("Error updating balance:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));

        }) 

        // ---


        // --- FUNCTIONS FOR OPENING MODALS

        function openNewEntryModal() {          // Function for opening modal to add an entry to the leaderboard

            newEntryModal.showModal();          // Display the modal

            const exercise = leaderboardName.innerHTML;     
            const reps = leaderboardReps.textContent;       
            const repsValue = parseInt(reps);               // Extract number of reps from display text                      

            newEntryExercise.value = exercise;              // Display exercise
            newEntryReps.value = repsValue;                 // Display number of reps
        }

        function openDeleteEntryModal() {       // Function for opening modal to delete entry
            deleteEntryModal.showModal();
        }

        function openReportModal() {            // Function for opening modal to report an entry
            reportModal.showModal();
        }

        // ---


        // --- FUNCTIONS FOR CLOSING MODALS

        function closeShowEntryModal() {        // Function for closing modal to display selected entry information
            showEntryModal.close();
        }

        function closeNewEntryModal() {         // Function for closing modal to add an entry to the leaderboard
            newEntryModal.close();
        }

        function closeDeleteEntryModal() {      // Function for closing modal to delete entry
            deleteEntryModal.close();
        }

        function closeDeleteEntrySuccessModal() {   // Function for closing modal confirming entry deleted successfully
            deleteEntrySuccessModal.close();
            window.location.reload();
        }

        function closeReportModal() {           // Function for closing modal to report an entry
            reportModal.close();
        }

        function closeReportConfirmationModal() {       // Function for closing modal to confirm report was submitted successfully
            reportConfirmationModal.close();
        }

        // ---

        
        closeLeaderboard();         // On initial page load, ensure MyRankings card and SearchLeaderboard card are showing, and leaderboard display cards are hidden

    </script>

</body>
</html>