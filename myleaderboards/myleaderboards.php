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

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";        // Fetch user details from database

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();                 // Store details in user variable 
}

if (!$user) {                               // If user isn't set, user isn't logged in  

    header("Location: ../login-signup/login.php");  // Redirect to login page
    exit;
}

// ---


// --- FETCH ALL LEADERBOARDS THE CURRENT USER IS REGISTERED TO 

$userId = $user["user_id"];

$mysqli = require __DIR__ . "/database.php";    // Database connection

// Fetch all private leaderboards that user is registered to from database
$sql = "SELECT private_leaderboards_entries.*, private_leaderboards.*
        FROM private_leaderboards_entries
        JOIN private_leaderboards ON private_leaderboards_entries.leaderboard_id = private_leaderboards.private_leaderboard_id
        WHERE private_leaderboards_entries.user_id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId); 
$stmt->execute();
$result = $stmt->get_result();

$leaderboards = [];                             // Store records in leaderboards array
while ($row = $result->fetch_assoc()) {
    $leaderboards[] = $row;
}

// ---


// --- CALCULATE USER'S RANKING FOR EACH LEADERBOARD THEY'RE REGISTERED TO

$userLeaderboards = [];     // Initialise array to store user's leaderboards with their rank

foreach ($leaderboards as $leaderboard) {       // Loop through leaderboards array

    $leaderboardId = $leaderboard["leaderboard_id"];
    $leaderboardName = $leaderboard["leaderboard_name"];
    $code = $leaderboard["code"];
    $exercise = $leaderboard["exercise"];
    $numReps = $leaderboard["num_reps"];
    $creatorId = $leaderboard["creator_id"];

    $sql = "SELECT * FROM private_leaderboards_entries WHERE leaderboard_id = ? ORDER BY score DESC";    // Fetch all records for leaderboard, from best to worst
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $leaderboardId);
    $stmt->execute();
    $result = $stmt->get_result();

    $entries = [];                              // Store results in entries array
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }

    for ($i = 0; $i < count($entries); $i++) {      // Loop through entries array
        $entry = $entries[$i];
        if ($entry["user_id"] == $userId) {       // If entry is current user's entry, store relevant entry information in user leaderboards array                       

            $userLeaderboardsEntry = [$i + 1, $leaderboardId, $leaderboardName, $code, $exercise, $numReps, $creatorId];
            $userLeaderboards[] = $userLeaderboardsEntry;
        }
    }
}

// ---


// --- HANDLE USER SUBMITTING A LEADERBOARD ENTRY

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $user["user_id"];                            // Define information relevant for entry submission
    $leaderboardId = $_POST["new-entry-leaderboard-id"];
    $scoreNum = $_POST["new-entry-score-num"];
    $scoreDecimal = $_POST["new-entry-score-decimal"];
    $score = (float) $scoreNum . "." . $scoreDecimal;

    $video = $_FILES["video"];                              // Preparing video for upload
    $videoName = basename($video["name"]);
    $videoTmpPath = $video["tmp_name"];
    $videoSize = $video["size"];
    $videoExt = strtolower(pathinfo($videoName, PATHINFO_EXTENSION));
    $newVideoName = uniqid("video_", true) . "." . $videoExt;
    $uploadPath = "../uploads/" . $newVideoName;

    if (move_uploaded_file($videoTmpPath, $uploadPath)) {       // Move video file to upload directory

        $sql = "DELETE FROM private_leaderboards_entries WHERE leaderboard_id = ? AND user_id = ?";       // Delete previous entry for current user
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $leaderboardId, $user_id);

        if ($stmt->execute()) {

            $sql = "INSERT INTO private_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";      // Insert new entry record into private leaderboard entries table
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iids", $leaderboardId, $user_id, $score, $uploadPath);
            
            if ($stmt->execute()) {
                $_SESSION["upload_success"] = true;                 // Set session variable upload_success to true, so that upload success modal is shown
                header("Location: myleaderboards.php");     // Relocate to my leaderboards page, reloading page
                exit;
            } else {
                echo "Error: " . $stmt->error;
            }
        }
    } else {
        echo "Failed to upload video.";
    }
   
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leaderboards</title>
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
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-bar-chart-line-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">My Leaderboards</p></div></button>
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
                        <div class="card-header">My Leaderboards<i class="bi bi-bar-chart-line-fill" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); }?></div>
                        </div>
                    </div>
                </div>
            
                <!-- Container for create and join leaderboard cards -->
                <div class="create-join-container">

                    <!-- Card where user can create leaderboard -->
                    <div class="card" style="background:rgb(68, 19, 113); width: 40%;" id="create-leaderboard-card">

                        <div class="create-leaderboard-container">
                            <div class="card-header" style="color:white;">Create Leaderboard<i class="bi bi-plus-circle" style="padding-left:8px"></i></div>

                            <!-- Button which opens modal for creating leaderboard -->
                            <div class="create-leaderboard-button-container">
                                <button class="my-rankings-category-button" style="padding-right:6.75px" onclick="openCreateLeaderboardModal()"><i class="bi bi-plus-circle" style="padding-left:8px"></i></button>
                            </div>
                        </div>

                    </div>

                    <!-- Card where user can join a leaderboard -->
                    <div class="card" style="background:linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255)); width: 58%;" id="join-leaderboard-card">

                        <div class="join-leaderboard-container">

                            <div class="card-header" style="color:white; padding-bottom: 10px;">Join Leaderboard<i class="bi bi-person-fill-add" style="padding-left:5px"></i></div>

                            <div class="join-leaderboard-right-container">
                                <div class="join-leaderboard-inner-container">

                                    <!-- Input where user enters join code -->
                                    <input class="join-leaderboard-code" type="text" id="join-leaderboard-code" name="join-leaderboard-code" placeholder="Enter join code" spellcheck="false" required></input>

                                    <!-- Button where user submits code to join leaderboard when clicked -->
                                    <button class="main-btn" style="width: 100px; color:white" onclick="joinLeaderboard()">Join</button>
                                    
                                </div>

                                <!-- Error message if user enters invalid join code -->
                                <em class="validation-text" style="padding: 10px; font-size: medium;" id="join-leaderboard-validation-text"></em>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Card containing all leaderboards current user is a part of -->
                <div class="card" id="leaderboards-card">
                    <div class="card-header" style="padding-bottom: 10px;">My Leaderboards<i class="bi bi-bar-chart-line-fill" style="padding-left:8px"></i></div>
                    <hr>

                    <div class="leaderboard-scroll-container">
                        <ul id="leaderboards-list" style="padding-bottom: 10px;">

                        </ul>
                    </div>

                </div>

            </div>


            <!-- Card which displays selected leaderboard info -->
            <div class="card" style="background-color:rgb(68, 19, 113);" id="leaderboard-info-card">

                <div class="leaderboard-header">

                    <!-- Contains leaderboard name-->
                    <div class="leaderboard-info-container">
                        <button class="my-rankings-category-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeLeaderboard()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                        <div class="card-header" id="leaderboard-info-name" style="padding-left: 20px; color: white; font-size: 1.75rem;"></div>
                    </div>

                    <!-- Contains leaderboard join code -->
                    <div class="leaderboard-info-container">
                        <div class="card-header" style="padding-left: 20px; color: white;">Join Code:</div>
                        <div class="card-header" id="leaderboard-info-code" style="padding-left: 20px; padding-right:10px; color: white; font-size: 1.75rem; font-weight:lighter"></div>
                    </div>

                </div>

                <div class="leaderboard-header" style="padding-bottom: 0px;">

                    <!-- Contains leaderboard exercise and number of reps -->
                    <div class="leaderboard-info-container">
                        <div class="card-header" id="leaderboard-info-exercise" style="padding-left: 20px; color: white; font-weight: lighter;"></div>
                        <div ><p style="color:rgb(95, 38, 149); padding-left:20px; padding-bottom: 10px; font-size: 1.75rem;">|</p></div>
                        <div class="card-header" id="leaderboard-info-reps" style="padding-left: 15px; color: white; font-weight:lighter;"></div>
                    </div>

                    <div class="leaderboard-info-container">
                        <div class="card-header" style="padding-right: 15px; padding-top: 10px; color: white;">Members</div>
                        <button class="my-rankings-category-button" id="show-members-button"><i class="bi bi-caret-down-fill"></i></button>
                    </div>

                </div>

                <hr class="my-rankings-hr">

                <!-- Contains list of leaderboard members -->
                <div class="my-rankings-scroll-container">
                    <ul id="leaderboard-members" style="padding-bottom: 20px;">
                    
                    </ul>
                </div>

            </div>


            <!-- Card which displays current user's leaderboard entry -->
            <div class="card" style="background:linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255));" id="leaderboard-your-entry-card">

                <div class="leaderboard-header" style="padding-bottom: 0px; align-items:baseline">

                    <div class="card-header" style="padding: 10px; color: white;">Your Rank<i class="bi bi-trophy-fill" style="padding-left:8px"></i></div>

                    <!-- Button which opens modal for adding an entry to the leaderboard -->
                    <div class="add-entry-container">
                        <button class="my-rankings-category-button blue" style="padding-right: 5px; padding-bottom: 2px; background-color:rgb(10, 131, 217)" onclick="openNewEntryModal()"><p style="color:white"><i class="bi bi-plus-circle" style="padding-left:5px"></i></p></button>
                    </div>

                </div>
                
                <hr>

                <!-- Contains current user's leaderboard entry if they have one -->
                <ul id="user-leaderboard-entry" style="padding-bottom: 20px;">
                    <div style="text-align:center; padding-top: 25px;">
                        <i class="small-sub-header" style=" color: white;"></i>
                    </div>
                </ul>

            </div>

            <!-- Card containing all leaderboard entries -->
            <div class="card" id="leaderboard-display-card">

                <div class="card-header" style="padding-bottom: 10px; padding-left: 10px;">Leaderboard<i class="bi bi-bar-chart-line-fill" style="padding-left:5px"></i></div>
                <hr>

                <!-- List of leaderboard entries -->
                <div class="leaderboard-scroll-container">
                    <ul id="leaderboard-entries" style="padding-bottom: 20px;">
                        
                    </ul>
                </div>

            </div>


            <!-- Card containing button to leave leaderboard -->
            <div class="card" id="leave-leaderboard-card" style="text-align:center;padding:20px">

                <!-- Button which opens modal to leave leaderboard -->
                <button class="friends-button" style="width:150px" onclick="openLeaveLeaderboardModal()"><p style="color:white">Leave</p></button>

            </div>


            <!-- Modal for creating leaderboard -->
            <dialog class="modal" id="create-leaderboard-modal" style="width:26%; overflow-x:hidden;">
                
                <div class="create-leaderboard-modal-header">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeCreateLeaderboardModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px;">CREATE LEADERBOARD</div>
                </div>

                <!-- Input for leaderboard name -->
                <div class="new-entry-exercise-container" style="padding-left: 20px;">
                    <p class="new-entry-label">Leaderboard Name</p>
                    <input class="create-leaderboard-name" type="text" id="create-leaderboard-name" name="create-leaderboard-name" placeholder="Leaderboard Name" spellcheck="false" required></input>
                    <br>
                    <em class="validation-text" style="padding: 10px; font-size: medium;" id="create-leaderboard-name-validation-text"></em>
                </div>

                <!-- Input for exercise -->
                <div class="new-entry-exercise-container" style="padding-left: 20px; padding-top: 0px;">
                    <p class="new-entry-label">Exercise</p>
                    <div class="search-leaderboards-exercise-container" style="padding-left: 0px;">
                        <input class="search-leaderboards-input" type="text" id="create-leaderboard-exercise" name="create-leaderboard-exercise" placeholder="Select Exercise" spellcheck="false" required></input>
                        
                        <!-- Button that displays list of exercises -->
                        <button class="my-codes-button" id="show-exercises-button" style="height:45px; width:45px;" onclick="showExercises()"><i class="bi bi-caret-down-fill"></i></button>
                    </div>

                    <em class="validation-text" style="padding: 10px; font-size: medium;" id="create-leaderboard-exercise-validation-text"></em>

                    <!-- List of exercises -->
                    <div class="create-leaderboard-scroll-container">
                        <ul id="create-leaderboard-exercise-list">

                        </ul>
                    </div>
                </div>

                <!-- Input for number of reps -->
                <div class="new-entry-exercise-container" style="padding-left: 20px;  padding-top: 0px;">
                    <p class="new-entry-label">Number of Reps</p>
                    <div class="search-leaderboards-exercise-container" style="padding-left: 0px;">
                        <input class="search-leaderboards-input" type="text" id="create-leaderboard-reps" name="create-leaderboard-reps" placeholder="Select Number of Reps" spellcheck="false" required></input>

                        <!-- Button that displays list of number of reps -->
                        <button class="my-codes-button" id="show-reps-button" style="height:45px; width:45px;" onclick = "showReps()"><i class="bi bi-caret-down-fill"></i></button>
                    </div>

                    <em class="validation-text" style="padding: 10px; font-size: medium;" id="create-leaderboard-reps-validation-text"></em>

                    <!-- List of number of reps -->
                    <ul id="create-leaderboard-reps-list">

                    </ul>
                </div>    

                <!-- Button which creates leaderboard when clicked -->
                <div class="new-entry-submit-container">
                    <button class="friends-button" style="width:125px;" onclick="createLeaderboard()"><p class="logout-text">Create</p></button>
                </div>

            </dialog>


            <!-- Modal for adding new entry to leaderboard -->
            <dialog class="modal" id="new-entry-modal">
                
                <div class="new-entry-modal-header">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeNewEntryModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px;">ADD ENTRY</div>
                </div>

                <em style="font-size: small; color:rgb(68, 19, 113); font-weight: bold;">NOTE: IF YOU ALREADY HAVE A SUBMISSION ON THIS LEADERBOARD, IT WILL BE REPLACED WITH THIS</em>

                <form method="post" id="add-entry-form" enctype="multipart/form-data" novalidate>

                    <input type="hidden" name="new-entry-leaderboard-id" id="new-entry-leaderboard-id">

                    <!-- Input field for weight achieved by the user -->
                    <div class="new-entry-score-container" style="padding-top: 35px;">
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
                    <div class="new-entry-video-container" style="padding-top: 35px; padding-bottom:30px">
                        <p class="new-entry-label" style="font-weight: bold; padding-left: 0px;">Video</p>
                        <em style="font-size:medium;">Please attatch a video of you performing this exercise for the above weight and reps as proof of completion:</em>
                        <input style="padding-top: 15px;" type="file" id="video" name="video" accept="video/*" required></input>
                        <br>

                        <!-- Error message for when no video is selected on submit -->
                        <em class="validation-text" id="new-entry-video-validation-text"></em>
                    </div>

                    <!-- Button which submits entry when clicked -->
                    <div class="new-entry-submit-container">
                        <button class="friends-button" style="width:125px;" type="submit"><p class="logout-text">Submit</p></button>
                    </div>

                </form>

            </dialog>


            <!-- Modal for displaying a selected entry -->
            <dialog class="modal" id="show-entry-modal">
                
                <div class="new-entry-modal-header" style="padding-right: 0px;">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeShowEntryModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="show-entry-button-container" id="remove-button-container">
                        
                    <!-- Button which displays modal for removing an entry/user -->
                        <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px; color:red" onclick = "openRemoveModal()"><i class="bi bi-trash3" style="padding-left:5px;"></i></button>
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


            <!-- Modal asking admin if they want to remove entry/user -->
            <dialog class="modal" style="width:450px" id="remove-modal">

                <div class="new-entry-modal-header" style="padding-right:28%;">
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px;" onclick="closeRemoveModal()"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                    <div class="small-sub-header" style="padding-top: 10px;">REMOVE ENTRY/USER</div>
                </div>

                <div class="new-entry-modal-header" style="padding: 10px; padding-right: 35px;">
                    <div class="delete-account-text">
                        <p class="store-text">Delete user's entry</p>
                    </div>

                    <!-- Button for deleting entry -->
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px; color:red" onclick="deleteUserEntry()"><i class="bi bi-trash3" style="padding-left:5px;"></i></button>
                </div>

                <hr>

                <div class="new-entry-modal-header" style="padding: 10px; padding-right: 35px;">
                    <div class="delete-account-text">
                        <p class="store-text">Remove user from leaderboard</p>
                    </div>

                    <!-- Button for removing user -->
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px; color:red" onclick="removeUser()"><i class="bi bi-person-x-fill" style="padding-left:5px;"></i></button>
                </div>

            </dialog>


            <!-- Modal asking user to confirm they want to leave leaderboard -->
            <dialog class="modal" id="leave-leaderboard-modal">

                <div class="small-sub-header" style="padding-top: 10px;">LEAVE LEADERBOARD</div>
                <div class="delete-account-text">Are you sure you want to leave this leaderboard?</div>
                <div class="delete-account-text"><i>If your are the leaderboard admin this will also delete the leaderboard itself.</i></div>
                <div class="delete-buttons">

                    <!-- Button to leave leaderboard -->
                    <button class="friends-button" style="width:125px; color: white;" onclick="leaveLeaderboard()">Leave</button>

                    <!-- Button to cancel leaving leaderboard -->
                    <button class="cancel-button" style="width:125px; color: rgb(68, 19, 113)" onclick="closeLeaveLeaderboardModal()">Cancel</button>

                </div>

            </dialog>


            <!-- Modal for confirming leaderboard created successfully -->
            <dialog class="modal" id="create-leaderboard-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">Leaderboard created successfully!</p>
                    <p class="store-text">Keep track of your leaderboard in the <br> <b class="small-sub-header">My Leaderboards</b> section</p>
                </div>

                <!-- Contains join code for leaderboard -->
                <div class="delete-account-text">
                    <p class="small-sub-header">JOIN CODE:</p>
                    <p class="join-code" id="create-leaderboard-join-code"></p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;" style="padding: 10px;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeCreateLeaderboardSuccessModal()">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming leaderboard joined successfully -->
            <dialog class="modal" id="join-leaderboard-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">Leaderboard joined successfully!</p>
                    <p class="store-text">Keep track of this leaderboard in the <br> <b class="small-sub-header">My Leaderboards</b> section</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeJoinLeaderboardSuccessModal()">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming user's leaderboard entry has been uploaded -->
            <dialog class="modal" id="upload-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">Your entry has successfully been added to the leaderboard!</p>
                    <p class="store-text">Keep track of your position in the<br> <b class="small-sub-header">My Leaderboards</b> section</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeUploadSuccessModal()">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming leaderboard entry has been deleted -->
            <dialog class="modal" id="delete-entry-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">User entry successfully deleted.</p>
                    <p class="store-text">Your leaderboard has been updated and the user's entry has been reset to 0.</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeDeleteEntrySuccessModal()">Dismiss</button>
                </div>

            </dialog>

            <!-- Modal for confirming user has been successfully removed -->
            <dialog class="modal" id="remove-user-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">User successfully removed.</p>
                    <p class="store-text">Your leaderboard has been updated and the user and their entry have been removed from the leaderboard.</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeRemoveUserSuccessModal()">Dismiss</button>
                </div>

            </dialog>


            <!-- Modal for confirming user has left leaderboard successfully -->
            <dialog class="modal" id="leave-leaderboard-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS</div>

                <div class="delete-account-text">
                    <p class="store-text">You are no longer part of this leaderboard</p>
                    <p class="store-text">You can still rejoin this leaderboard by entering the join code in the <br> <b class="small-sub-header">Join Leaderboard</b> section</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeLeaveLeaderboardSuccessModal()">Dismiss</button>
                </div>

            </dialog>

        </main>

    </div>

    <script>

        // --- STORE HTML ELEMENTS

        const createLeaderboardCard = document.getElementById("create-leaderboard-card");
        const joinLeaderboardCard = document.getElementById("join-leaderboard-card");
        const leaderboardsCard = document.getElementById("leaderboards-card");

        const createLeaderboardModal = document.getElementById("create-leaderboard-modal");
        const createLeaderboardName = document.getElementById("create-leaderboard-name");
        const createLeaderboardExercise = document.getElementById("create-leaderboard-exercise");
        const createLeaderboardReps = document.getElementById("create-leaderboard-reps");
        const createLeaderboardExerciseList = document.getElementById("create-leaderboard-exercise-list");
        const createLeaderboardRepsList = document.getElementById("create-leaderboard-reps-list");
        const createLeaderboardNameValidationText = document.getElementById("create-leaderboard-name-validation-text");
        const createLeaderboardExerciseValidationText = document.getElementById("create-leaderboard-exercise-validation-text");
        const createLeaderboardRepsValidationText = document.getElementById("create-leaderboard-reps-validation-text");
        
        const createLeaderboardSuccessModal = document.getElementById("create-leaderboard-success-modal");
        const createLeaderboardJoinCode = document.getElementById("create-leaderboard-join-code");

        const joinLeaderboardCode = document.getElementById("join-leaderboard-code");
        const joinLeaderboardValidationText = document.getElementById("join-leaderboard-validation-text");

        const joinLeaderboardSuccessModal = document.getElementById("join-leaderboard-success-modal");

        const leaderboardsList = document.getElementById("leaderboards-list");

        const leaderboardInfoCard = document.getElementById("leaderboard-info-card");
        const leaderboardInfoName = document.getElementById("leaderboard-info-name");
        const leaderboardInfoExercise = document.getElementById("leaderboard-info-exercise");
        const leaderboardInfoReps = document.getElementById("leaderboard-info-reps");
        const leaderboardInfoCode = document.getElementById("leaderboard-info-code");
        const leaderboardMembers = document.getElementById("leaderboard-members");
        const showMembersButton = document.getElementById("show-members-button");

        const leaderboardYourEntryCard = document.getElementById("leaderboard-your-entry-card");
        const userLeaderboardEntry = document.getElementById("user-leaderboard-entry");
        const leaderboardDisplayCard = document.getElementById("leaderboard-display-card");

        const leaderboardEntries = document.getElementById("leaderboard-entries");

        const showEntryModal = document.getElementById("show-entry-modal");
        const removeButtonContainer = document.getElementById("remove-button-container");
        const showEntryUser = document.getElementById("show-entry-user");
        const showEntryScore = document.getElementById("show-entry-score");
        const videoContainer = document.getElementById("show-entry-video-container");

        const newEntryModal = document.getElementById("new-entry-modal");
        const newEntryLeaderboardId = document.getElementById("new-entry-leaderboard-id");
        const newEntryScoreNum = document.getElementById("new-entry-score-num");
        const newEntryScoreDecimal = document.getElementById("new-entry-score-decimal");
        const newEntryScoreValidationText = document.getElementById("new-entry-score-validation-text");
        const newEntryVideoValidationText = document.getElementById("new-entry-video-validation-text");

        const leaveLeaderboardCard = document.getElementById("leave-leaderboard-card");
        const leaveLeaderboardModal = document.getElementById("leave-leaderboard-modal");
        const leaveLeaderboardSuccessModal = document.getElementById("leave-leaderboard-success-modal");

        const removeModal = document.getElementById("remove-modal");
        const deleteEntrySuccessModal = document.getElementById("delete-entry-success-modal");
        const removeUserSuccessModal = document.getElementById("remove-user-success-modal");

        // ---
       
        const currentUserId = <?php echo json_encode($userId); ?>;              // Fetch php variable containing current user's user id


        // --- DISPLAY LIST OF USER'S LEADERBOARDS

        const leaderboards = <?php echo json_encode($userLeaderboards); ?>;     // Fetch php variable containing list of current user's leaderboard rankings

        if (leaderboards.length == 0) {        // If user isn't part of any leaderboards, dispay relevant message

            const firstMessage = document.createElement("li");
            firstMessage.classList.add("my-leaderboards-first-message");
            firstMessage.innerHTML = "You aren't currently a member of any leaderboards";

            const secondMessage = document.createElement("li");
            secondMessage.classList.add("my-leaderboards-second-message");
            secondMessage.innerHTML = "Join or Create your own leaderboards above!";

            leaderboardsList.appendChild(firstMessage);
            leaderboardsList.appendChild(secondMessage);

        } else {
            
            for (let i = 0; i < leaderboards.length; i++) {             // Loop through leaderboards array
                const leaderboard = leaderboards[i];

                const listItem = document.createElement("li");          // Create and display list of buttons containing user's leaderboard rank and leaderboard name
                const button = document.createElement("button");            
                button.addEventListener("click", () => {                // When button clicked
                    console.log(leaderboard);
                    
                    let hidden1 = leaderboardInfoCard.getAttribute("hidden");           // Show card containing leaderboard information
                    if (hidden1) {
                        leaderboardInfoCard.removeAttribute("hidden");
                    }

                    let hidden2 = leaderboardYourEntryCard.getAttribute("hidden");      // Show card containing current user's entry
                    if (hidden2) {
                        leaderboardYourEntryCard.removeAttribute("hidden");
                    }

                    let hidden3 = leaderboardDisplayCard.getAttribute("hidden");        // Show card containing all leaderboard entries
                    if (hidden3) {
                        leaderboardDisplayCard.removeAttribute("hidden");
                    }

                    let hidden4 = leaveLeaderboardCard.getAttribute("hidden");          // Show card containing all leaderboard entries
                    if (hidden4) {
                        leaveLeaderboardCard.removeAttribute("hidden");
                    }

                    createLeaderboardCard.setAttribute("hidden", "hidden");             // Hide card for creating leaderboard
                    joinLeaderboardCard.setAttribute("hidden", "hidden");               // Hide card for joining leaderboard
                    leaderboardsCard.setAttribute("hidden", "hidden");                  // Hide card for displaying user's leaderboards

                    showLeaderboard(leaderboard);                           // Call function to display leaderboard information and entries          
                    
                })

                const leaderboardButtonContainer = document.createElement("div");       // Create HTML elements containing user's entry with their rank and the leaderboard name
                const leaderboardRankContainer = document.createElement("div");
                const leaderboardNameContainer = document.createElement("div");

                const leaderboardRankInnerContainer = document.createElement("div");
                const leaderboardRank = document.createElement("p");
                const leaderboardName = document.createElement("p");
                
                const hr = document.createElement("hr");

                button.classList.add("search-result-button");

                leaderboardButtonContainer.classList.add("leaderboard-button-container");
                leaderboardRankContainer.classList.add("leaderboard-rank-container");
                leaderboardNameContainer.classList.add("leaderboard-username-container");

                leaderboardRankInnerContainer.classList.add("leaderboard-rank-inner-container");
                leaderboardRank.classList.add("leaderboard-rank");
                leaderboardName.classList.add("leaderboard-username"); 

                leaderboardRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${leaderboard[0]}`;            // User's rank on the leaderboard

                if (leaderboard[0] == 1) {
                    leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";      // Make rank colour gold, silver or bronze if 1st, 2nd or 3rd
                    leaderboardRank.style.textShadow = "2px 2px 4px rgba(255, 215, 0, 0.6)";
                } else if (leaderboard[0] == 2) {
                    leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #6D6D6D, #A9A9A9, #D3D3D3, #8F8F8F, #6D6D6D)";
                    leaderboardRank.style.textShadow = "2px 2px 4px rgba(90, 90, 90, 0.6)";
                } else if (leaderboard[0] == 3) {
                    leaderboardRank.style.backgroundImage = " linear-gradient(45deg, #8C6239, #CD7F32, #D49A6A, #B87333, #8C6239)";
                    leaderboardRank.style.textShadow = "2px 2px 4px rgba(139, 69, 19, 0.5)";
                }

                leaderboardName.innerHTML = leaderboard[2];          // Leaderboard name

                leaderboardRankInnerContainer.appendChild(leaderboardRank);
                leaderboardRankContainer.appendChild(leaderboardRankInnerContainer);
                leaderboardNameContainer.appendChild(leaderboardName);

                leaderboardButtonContainer.appendChild(leaderboardRankContainer);
                leaderboardButtonContainer.appendChild(leaderboardNameContainer);

                button.appendChild(leaderboardButtonContainer);
                listItem.appendChild(button);
                leaderboardsList.appendChild(listItem);
                leaderboardsList.appendChild(hr);
            }

        }

        // ---
        

        // --- ADD FUNCTIONALITY TO SHOW LIST OF EXERCISES / LIST OF NUMBER OF REPS WHEN CREATING LEADERBOARD

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

            createLeaderboardExerciseValidationText.innerHTML = "";         // Remove any displayed validation text
            createLeaderboardExerciseList.innerHTML = "<hr>";               // Empty display list

            for (let i = 0; i < exercises.length; i++) {                    // Loop through list of exercises

                const listItem = document.createElement("li");              // For each exercise, add button for that exercise to the display list
                const button = document.createElement("button");
                const hr = document.createElement("hr");

                button.textContent = exercises[i]; 
                listItem.classList.add("create-leaderboard-list-item");
                button.classList.add("create-leaderboard-button"); 

                button.addEventListener("click", function() {               // When button clicked
                    createLeaderboardExercise.value = button.textContent;   // Set value of currently selected exercise to this button's exercise
                    createLeaderboardExerciseList.innerHTML = "";           // Empty display list 
                });
                
                listItem.appendChild(button);
                createLeaderboardExerciseList.appendChild(listItem);
                createLeaderboardExerciseList.appendChild(hr);
            }

        }

        function showReps() {           // Function for displaying the list of number of reps

            const reps = [              // Array containing list of all number of reps 
                1,
                3,
                5,
                10
            ];

            createLeaderboardRepsValidationText.innerHTML = "";         // Remove any displayed validation text
            createLeaderboardRepsList.innerHTML = "<hr>";               // Empty display list

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

                    createLeaderboardReps.value = button.textContent;           // Set value of currently selected number of reps to this button's number of reps

                    const repsValue = button.textContent.split(" ")[0];

                    if (repsValue == "1") {                                             // Colour code currently selected number of reps to match previous 
                        createLeaderboardReps.style.color = "rgb(255, 82, 82)";
                    } else if (repsValue == "3") {
                        createLeaderboardReps.style.color = "rgb(199, 199, 43)";
                    } else if (repsValue == "5") {
                        createLeaderboardReps.style.color = "rgb(48, 235, 48)";
                    } else if (repsValue == "10") {
                        createLeaderboardReps.style.color = "rgb(83, 173, 247)";
                    }

                    createLeaderboardRepsList.innerHTML = "";                   // Empty display list
                });
                            
                listItem.appendChild(button);
                createLeaderboardRepsList.appendChild(listItem);
                createLeaderboardRepsList.appendChild(hr);
            }
        }

        // ---


        // --- CREATE LEADERBOARD 

        function createLeaderboard() {                  // Function for creating leaderboard

            createLeaderboardNameValidationText.innerHTML = "";             // Set all validation text as empty
            createLeaderboardExerciseValidationText.innerHTML = "";
            createLeaderboardRepsValidationText.innerHTML = "";

            const name = createLeaderboardName.value;                   // Store inputted leaderboard info
            const exercise = createLeaderboardExercise.value;
            const reps = createLeaderboardReps.value;

            var nameEmpty = false;              // Variables indicating if input fields are empty
            var exerciseEmpty = false;
            var repsEmpty = false;

            if (name.trim() == "") {            // If leaderboard name empty, display relevant message
                nameEmpty = true;
                createLeaderboardNameValidationText.innerHTML = "Please enter leaderboard name";
            }

            if (exercise == "") {               // If exercise empty, display relevant message
                exerciseEmpty = true;
                createLeaderboardExerciseValidationText.innerHTML = "Please select exercise";
            }

            if (reps == "") {                   // If number of reps empty, display relevant message
                repsEmpty = true;
                createLeaderboardRepsValidationText.innerHTML = "Please select number of reps";
            }

            if (!( nameEmpty || exerciseEmpty || repsEmpty )) {         // If all fields have been filled

                let joinCode = '';
                const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

                for (let i = 0; i < 8; i++) {               // Generate random 8 digit join code for leaderboard           

                    const randNum = Math.floor(Math.random() * characters.length);
                    joinCode += characters[randNum];
                }

                fetch("create-leaderboard-process.php", {   // Post request to php file to create new leaderboard
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ name:name , code:joinCode , exercise:exercise , reps:reps })     // Pass value for leaderboard name, join code, exercise and number of reps
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {                                         // If leaderboard successfully created
                        createLeaderboardModal.close();                         // Close modal for creating leaderboard
                        createLeaderboardJoinCode.innerHTML = joinCode;
                        createLeaderboardSuccessModal.showModal();              // Open modal confirming leaderboard created successfully, containing join code
                    } else {
                        console.log("Error creating leaderboard:", data.error);
                    }
                })
                .catch(error => console.log("Fetch error:", error));

            }

        }

        // ---


        // --- JOIN LEADERBOARD

        function joinLeaderboard() {                    // Function for joining leaderboard 

            const code = joinLeaderboardCode.value;     // Store inputted join code

            fetch("join-leaderboard-process.php", {     // Post request to php file to join leaderboard
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ code:code })     // Pass value for inputted join code
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {                 // If data returned
                        const exists = data.exists;     // Variable indicating if leaderboard with this join code exists

                        if (exists == true) {               // If leaserboard with this join code exists        
                            const member = data.member;     // Variable indicating if user is already a member of this leaderboard

                            if (member == true) {           // If user is already a member, display relevant message    

                                joinLeaderboardValidationText.innerHTML = "Already a member of this leaderboard";

                            } else {                        // If user not already a member

                                joinLeaderboardValidationText.innerHTML = ""    // Set join code input field and validation text as empty
                                joinLeaderboardCode.value = "";
                                joinLeaderboardSuccessModal.showModal();        // Show modal confirming leaderboard joined successfully 
                            }

                        } else {        // If no leaderboard exists with inputted join code, display relevant message

                            joinLeaderboardValidationText.innerHTML = "Invalid join code";  
                        }
                    } else {
                        console.log("Error joining leaderboard:", data.error);
                    }
                })
                .catch(error => console.log("Fetch error:", error));

        }

        // ---


        var leaderboardId = 0;      // Variable to store leaderboard id of selected leaderboard
        var creatorId = 0;          // Variable to store user id of creator of selected leaderboard


        // --- DISPLAY ALL INFORMATION AND ENTRIES FOR A LEADERBOARD

        function showLeaderboard(leaderboard) {

            leaderboardId = leaderboard[1];                 // Store leaderboard info 
            const leaderboardName = leaderboard[2];
            const code = leaderboard[3];
            const exercise = leaderboard[4];
            const reps = leaderboard[5];
            creatorId = leaderboard[6];

            leaderboardInfoName.innerHTML = leaderboardName;        // Display leaderboard name, join code and exercise
            leaderboardInfoCode.innerHTML = code;
            leaderboardInfoExercise.innerHTML = exercise;

            // Display number of reps, with colour coding consistent to prevoious
            if (reps == "1") {                                                                                                                                     
                leaderboardInfoReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:medium; color:rgb(255, 82, 82); padding-right:8px"></i> ${reps} x Reps(s)`;
            } else if (reps == "3") {
                leaderboardInfoReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(199, 199, 43); padding-right:8px"></i> ${reps} x Reps(s)`;
            } else if (reps == "5") {
                leaderboardInfoReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(48, 235, 48); padding-right:8px"></i> ${reps} x Reps(s)`;
            } else if (reps == "10") {
                leaderboardInfoReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(83, 173, 247); padding-right:8px"></i> ${reps} x Reps(s)`;
            }

            fetch("fetch-private-leaderboard-process.php", {            // Post request to php file to fetch leaderboard entries
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leaderboardId:leaderboardId })   // Pass value for leaderboard id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {                                 // If leaderboard entries fetched successfully

                        const entries = data.data;                      // Store entries

                        leaderboardEntries.innerHTML = "";              // Set display list as empty 
                
                        for (let i = 0; i < entries.length; i++) {      // Loop through list of entries

                            const entry = entries[i];                   // Store entry
                            
                            const listItem = document.createElement("li");          // Create and display list of buttons, each containing a leaderboard entry
                            const button = document.createElement("button");
                            button.addEventListener("click", function() {           // When button containing entry is clicked

                                const username = entry.username;        // Store username, score and user id associated with selected entry                  
                                const score = entry.score;
                                const entryUserId = entry.user_id;

                                showEntryUser.innerHTML = username;                 // Display username associated with entry in the show entry modal
                                showEntryScore.innerHTML = score + "kg";            // Display the weight achieved in this entry in the show entry modal

                                if(!(currentUserId == creatorId)) {                                 // If current user isn't leaderboard admin
                                    removeButtonContainer.setAttribute("hidden", "hidden");         // Hide the remove entry button
                                
                                } else {                                                            // If current user is leaderboard admin
                                    if (entryUserId == currentUserId) {                             // If selected entry belongs to current user
                                        removeButtonContainer.setAttribute("hidden", "hidden");     // Hide the remove entry button
                                    } else {                                                        // If selected entry does not belong to current user
                                        let hidden = removeButtonContainer.getAttribute("hidden");  // Ensure remove entry button is not hidden
                                        if (hidden) {
                                            removeButtonContainer.removeAttribute("hidden");
                                        }
                                    }
                                }


                                fetch("fetch-private-leaderboard-entry-process.php", {          // Post request to php file to fetch details about selected entry
                                method: "POST",     
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({ leaderboardId:leaderboardId, userId:entryUserId })        // Pass values for leaderboard id and user id associated with entry
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {                         // If entry is fetched successfully 

                                        videoContainer.innerHTML = "";          // Set video container as empty
                                        const entryVideo = data.data.video;

                                        if (entryVideo == "null") {             // If entry has no video (is default)
                                            videoContainer.innerHTML = "";      // Set video container as empty 
                                        } else {

                                            const video = document.createElement("video");          // Create and display video element containing video associated with selected entry
                                            video.setAttribute("controls", "controls");
                                            video.setAttribute("width", "600");
                                            video.innerHTML = `                                         
                                                <source src="${entryVideo}" type="video/mp4">
                                                <source src="${entryVideo}" type="video/quicktime">
                                                <source src="${entryVideo}" type="video/x-msvideo">
                                                <source src="${entryVideo}" type="video/webm">
                                                Your browser does not support the video tag.
                                            `;                                                      // Ensure all valid video file formats can be displayed
                                            videoContainer.appendChild(video);

                                        }

                                    } else {
                                        console.log("Error:", data.error);
                                    }
                                })
                                .catch(error => console.log("Fetch error:", error));
        

                                showEntryModal.showModal();             // Display the show entry modal
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

                            leaderboardRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${i + 1}`;     // Use index of list of entries (ordered by weight achieved) to determine entry rank

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

                            if (entry.user_id == currentUserId) {                       // If this entry belongs to the current user
                                let clonedListItem = listItem.cloneNode(true);          // Clone HTML elements to be displayed in the "Your Rank" section
                                let hr2 = document.createElement("hr");

                                let clonedButton = clonedListItem.querySelector(".search-result-button");
                                let clonedRank = clonedListItem.querySelector(".leaderboard-rank");
                                let clonedUsername = clonedListItem.querySelector(".leaderboard-username");
                                let clonedScore = clonedListItem.querySelector(".leaderboard-score");

                                if (clonedButton) {                                                 // Give button appropriate styling for different coloured card
                                    clonedButton.addEventListener("mouseenter", () => {
                                        clonedButton.style.background = "linear-gradient(to right, rgb(40, 248, 82), rgb(45, 167, 255))";
                                    });

                                    clonedButton.addEventListener("mouseleave", () => {
                                        clonedButton.style.background = "linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255))";
                                    });

                                    clonedButton.addEventListener("click", function() {             // When user's entry button clicked

                                        const username = entry.username;
                                        const score = entry.score;
                                        const entryUserId = entry.user_id;

                                        showEntryUser.innerHTML = username;                 // Display username associated with entry in the show entry modal
                                        showEntryScore.innerHTML = score + "kg";            // Display weight achieved in this entry in the show entry modal

                                        removeButtonContainer.setAttribute("hidden", "hidden");

                                        fetch("fetch-private-leaderboard-entry-process.php", {      // Post request to php file to fetch details about selected entry
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json"
                                        },
                                        body: JSON.stringify({ leaderboardId:leaderboardId , userId:entryUserId })      // Pass values for leaderboard id and user id associated with selected entry
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {                         // If entry is fetched successfully 

                                                videoContainer.innerHTML = "";          // Set video container as empty
                                                const entryVideo = data.data.video;

                                                if (entryVideo == "null") {             // If entry has no video (is default)       
                                                    videoContainer.innerHTML = "";      // Set video container as empty
                                                } else {

                                                    const video = document.createElement("video");          // Create and display video element containing video associated with selected entry
                                                    video.setAttribute("controls", "controls");
                                                    video.setAttribute("width", "600");
                                                    video.innerHTML = `
                                                        <source src="${entryVideo}" type="video/mp4">
                                                        <source src="${entryVideo}" type="video/quicktime">
                                                        <source src="${entryVideo}" type="video/x-msvideo">
                                                        <source src="${entryVideo}" type="video/webm">
                                                        Your browser does not support the video tag.
                                                    `;                                                      // Ensure all valid video file formats can be displayed
                                                    videoContainer.appendChild(video);

                                                }

                                            } else {
                                                console.log("Error:", data.error);
                                            }
                                        })
                                        .catch(error => console.log("Fetch error:", error));


                                        showEntryModal.showModal();             // Display the show entry modal
                                    });
                                }

                                if (clonedRank) {                               // Give rank appropriate styling for different coloured card
                                    clonedRank.style.color = "white"
                                }

                                if (clonedUsername) {                           // Give username appropriate styling for different coloured card
                                    clonedUsername.style.color = "rgb(68, 19, 113)";
                                }

                                userLeaderboardEntry.innerHTML = "";                    // Set user entry display as empty
                                userLeaderboardEntry.appendChild(clonedListItem);       // Display user's entry
                                userLeaderboardEntry.appendChild(hr2);
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
            let hidden1 = createLeaderboardCard.getAttribute("hidden");         // Show card creating a leaderboard
            if (hidden1) {
                createLeaderboardCard.removeAttribute("hidden");
            }

            let hidden2 = joinLeaderboardCard.getAttribute("hidden");           // Show card for joining a leaderboard
            if (hidden2) {
                joinLeaderboardCard.removeAttribute("hidden");
            }

            let hidden3 = leaderboardsCard.getAttribute("hidden");              // Show card for displaying user's leaderboard rankings
            if (hidden3) {
                leaderboardsCard.removeAttribute("hidden");
            }

            leaderboardInfoCard.setAttribute("hidden", "hidden");               // Hide card containing leaderboard information 
            leaderboardYourEntryCard.setAttribute("hidden", "hidden");          // Hide card containing current user's leaderboard entry 
            leaderboardDisplayCard.setAttribute("hidden", "hidden");            // Hide card containing all leaderboard entries 
            leaveLeaderboardCard.setAttribute("hidden", "hidden");

            hideMembers();          // Hide leaderboard members list

            showMembersButton.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;      // Set show members button as down icon
            membersShowing = false; 

        }

        // ---


        // --- SHOW/HIDE LEADERBOARD MEMBERS

        var membersShowing = false;         // Variable indicating if leaderboard members are currently being displayed

        showMembersButton.addEventListener("click", () => {         // When show members button clicked
            
            if (membersShowing == false) {                                                  // If members are not currently showing
                showMembers();                                                              // Show them 
                showMembersButton.innerHTML = `<i class="bi bi-caret-up-fill"></i>`;        // Change symbol on button to up, as list has now "dropped down"
                membersShowing = true;                                                      // Change varianle to indicate members are showing

            } else {                                                                        // Else leaderboard members are currently showing                                  
                hideMembers();                                                              // Hide them
                showMembersButton.innerHTML = `<i class="bi bi-caret-down-fill"></i>`;      // Change symbol on button to down, as list has now "gone back up"
                membersShowing = false;                                                     // Change variable to indicate members are not showing
            }
        })


        function showMembers() {            // Function for displaying leaderboard members

            leaderboardMembers.innerHTML = "";

            fetch("fetch-leaderboard-members-process.php", {            // Post request to php file to fetch leaderboard members
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leaderboardId:leaderboardId })   // Pass value for leaderboard id
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

                        if (userId == creatorId) {          // If member is leaderboard admin, display their username with admin symbol
                            div.innerHTML = `<i class="bi bi-star-fill" style="font-size:medium; background-image:linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B); background-clip: text; color: transparent; padding-right:8px;"></i> ${username}`;
                        } else {                            // If member is not leaderboard admin, display their name with normal member symbol
                            div.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:medium; background-image: linear-gradient(to right, rgb(3, 244, 51), rgb(0, 149, 255)); background-clip: text; color: transparent; padding-right:8px"></i> ${username}`;
                        }

                        listItem.appendChild(div);
                        leaderboardMembers.appendChild(listItem);
                        leaderboardMembers.appendChild(hr);

                    }
                } else {
                    console.log("Error creating leaderboard:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));            

        }

        function hideMembers() {                    // Function for hiding members
            leaderboardMembers.innerHTML = "";      // Set members list to empty
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

        // ---


        // --- LEAVE LEADERBOARD

        function leaveLeaderboard() {           // Function for allowing current user to leave leaderboard

            fetch("leave-private-leaderboard-process.php", {        // Post request to php file to leave leaderboard
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leaderboardId:leaderboardId , userId:currentUserId, creatorId:creatorId })       // Pass values for leaderboard id, current user id, leaderboard creator id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                                 // If leaderboard left successfully
                    leaveLeaderboardModal.close();                  // Close leave leaderboard modal
                    leaveLeaderboardSuccessModal.showModal();       // Show modal confirmaing leaderboard left successfully
                    
                } else {
                    console.log("Error joining leaderboard:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));

        }

        // ---


        // --- DELETE USER ENTRY

        function deleteUserEntry() {        // Function for deleting a user entry

            const username = showEntryUser.innerHTML;       // Store username associated with selected entry

            fetch("delete-private-leaderboard-entry-process.php", {     // Post request to php file to delete user entry
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leaderboardId:leaderboardId , username:username })     // Pass value for leaderboard id and username associated with entry
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                 // If leaderboard successfully removed

                    showEntryModal.close();         // Close modal displaying user entry
                    removeModal.close();            // Close modal for deleting user entry

                    deleteEntrySuccessModal.showModal();    // Show modal confirming user entry was deleted successfully 
                    
                } else {
                    console.log("Error joining leaderboard:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));

        }

        // ---


        // --- REMOVE USER FROM LEADERBOARD

        function removeUser() {         // Function for removing selected user from leaderboard

            const username = showEntryUser.innerHTML;       // Store username of selected user

            fetch("remove-private-leaderboard-member-process.php", {     // Post request to php file to remove selected user from leaderboard
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ leaderboardId:leaderboardId , username:username })     // Pass value for leaderboard id and username id associated with user
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                 // If user removed from leaderboard successfully

                    showEntryModal.close();         // Close modal displaying user entry
                    removeModal.close();            // Close modal for removing user

                    removeUserSuccessModal.showModal();     // Show modal confirming user was removed from leaderboard successfully
                    
                } else {
                    console.log("Error joining leaderboard:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));

        }

        // --- 


        // --- FUNCTIONS FOR OPENING MODALS
        
        function openCreateLeaderboardModal() {     // Function for opening modal to create leaderboard
            createLeaderboardModal.showModal();
        }

        function openNewEntryModal() {              // Function for opening modal to add new leaderboard entry 
            newEntryLeaderboardId.value = leaderboardId;
            newEntryModal.showModal();
        }

        function openLeaveLeaderboardModal() {      // Function for opening modal to leave leaderboard
            leaveLeaderboardModal.showModal();
        }

        function openRemoveModal() {                // Function for opening modal to remove user/entry
            removeModal.showModal();
        }

        // ---


        // --- FUNCTIONS FOR CLOSING MODALS

        function closeCreateLeaderboardModal() {    // Function for closing modal to create leaderboard
            createLeaderboardModal.close();

            createLeaderboardName.value = "";           // Set all input fields, validation text and display lists as empty
            createLeaderboardExercise.value = "";
            createLeaderboardReps.value = "";

            createLeaderboardNameValidationText.innerHTML = "";
            createLeaderboardExerciseValidationText.innerHTML = "";
            createLeaderboardRepsValidationText.innerHTML = "";

            createLeaderboardExerciseList.innerHTML = "";
            createLeaderboardRepsList.innerHTML = "";

        }

        function closeNewEntryModal() {             // Function for closing modal to add new entry to leaderboard
            newEntryModal.close();
        }

        function closeShowEntryModal() {            // Function for closing modal to display selected entry information
            showEntryModal.close();
        }

        function closeLeaveLeaderboardModal() {     // Function for closing modal to leave leaderboard
            leaveLeaderboardModal.close();
        }

        function closeRemoveModal() {               // Function for closing modal to remove user/entry
            removeModal.close();
        }

        function closeCreateLeaderboardSuccessModal() {     // Function for closing modal confirming leaderboard created successfully
            createLeaderboardSuccessModal.close();
            window.location.reload(); 
        }

        function closeJoinLeaderboardSuccessModal() {       // Function for closing modal confirming leaderboard joined successfully
            joinLeaderboardSuccessModal.close();
            window.location.reload(); 
        }

        function closeUploadSuccessModal() {        // Function for closing modal confirming new entry added successfuly
            uploadSuccessModal.close();
        }

        function closeDeleteEntrySuccessModal() {   // Function for closing modal confirming user entry deleted successfully
            deleteEntrySuccessModal.close();
            window.location.reload();
        }

        function closeRemoveUserSuccessModal() {    // Function for closing modal confirming user removed from leaderboard successfully
            removeUserSuccessModal.close();
            window.location.reload();
        }

        function closeLeaveLeaderboardSuccessModal() {      // Function for closing modal confirming user left leaderboard successfully 
            leaveLeaderboardSuccessModal.close();
            window.location.reload(); 
        }

        // ---
            

        closeLeaderboard();         // On initial page load, ensure createLeaderboard card, joinLeaderboard and leaderboards card are showing, and leaderboard display cards are hidden

    </script>

</body>
</html>