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

$userId = $user["user_id"];     // Store user id

// ---


// --- RETRIEVE LEADERBOARD PASSED FROM PREVIOUS PAGE

$leaderboard = [];

if (isset($_SESSION['leaderboard'])) {
    $leaderboard = $_SESSION['leaderboard'];        // Store leaderboard from session variable in leaderboard variable
}

// ---


// --- RETRIEVE LEAGUE PASSED FROM MY LEAGUES PAGE

$league = [];

if (isset($_SESSION['data'])) {
    $league = $_SESSION['data'];        // Store league from session variable in league variable
}

// ---


// --- HANDLE USER SUBMITTING A LEADERBOARD ENTRY

if ($_SERVER["REQUEST_METHOD"] === "POST") {
                           
    $leaderboardId = $_POST["new-entry-leaderboard-id"];    // Define information relevant for entry submission
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

        $sql = "DELETE FROM league_leaderboards_entries WHERE leaderboard_id = ? AND user_id = ?";       // Delete previous entry for current user
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $leaderboardId, $userId);

        if ($stmt->execute()) {

            // Insert new entry record into private leaderboard entries table
            $sql = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";      
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iids", $leaderboardId, $userId, $score, $uploadPath);
            
            if ($stmt->execute()) {
                $_SESSION["upload_success"] = true;                                // Set session variable upload_success to true, so that upload success modal is shown
                header("Location: myleagues-leaderboard-details.php");     // Relocate to current page, reloading page
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

            <!-- Card which displays selected leaderboard info, as well as current user's rank on leaderboard -->
            <div class="card" style="background-color:rgb(68, 19, 113);" id="leaderboard-card">

                <div class="leaderboard-header">

                    <!-- Display leaderboard exercise and number of reps -->
                    <div class="leaderboard-info-container">
                        <form action="myleagues-leaderboards.php">
                            <button class="my-rankings-category-button" style="padding-right: 5px; padding-bottom: 2px;"><i class="bi bi-chevron-left" style="padding-left:5px"></i></button>
                        </form>
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
                </div>

                <hr class="my-rankings-hr">

                <!-- Contains current user's leaderboard entry if they have one -->
                <ul id="user-leaderboard-entry" style="padding-bottom: 20px;">
                    
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

                        <!-- Error message for when no video is selected on submit, or file is invalid -->
                        <em class="validation-text" id="new-entry-video-validation-text"></em>
                    </div>

                    <!-- Button which submits entry when clicked -->
                    <div class="new-entry-submit-container">
                        <button class="friends-button" style="width:125px;" type="submit"><p class="logout-text">Submit</p></button>
                    </div>

                </form>

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
                        <p class="store-text">Remove user from league</p>
                    </div>

                    <!-- Button for removing user -->
                    <button class="my-codes-button" style="padding-right: 5px; padding-bottom: 2px; color:red" onclick="removeUser()"><i class="bi bi-person-x-fill" style="padding-left:5px;"></i></button>
                </div>

            </dialog>


            <!-- Modal for confirming user's leaderboard entry has been uploaded -->
            <dialog class="modal" id="upload-success-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                
                <div class="small-sub-header" style="padding-top: 10px;">SUCCESS!</div>

                <div class="delete-account-text">
                    <p class="store-text">Your entry has successfully been added to the leaderboard!</p>
                    <p class="store-text">Keep track of your position in the<br> <b class="small-sub-header">Leaderboards</b> section</p>
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
                    <p class="store-text">The leaderboard has been updated and the user's entry has been reset to 0.</p>
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
                    <p class="store-text">Your league has been updated and the user and all their entries have been removed from this league.</p>
                </div>

                <!-- Button which closes modal when clicked -->
                <div style="justify-content:center;">
                    <button class="friends-button" style="width:125px; color: white;" onclick="closeRemoveUserSuccessModal()">Dismiss</button>
                </div>

            </dialog>


        </main>

    </div>

    <script>

        // --- STORE HTML ELEMENTS 

        // Display leaderboard

        const leaderboardName = document.getElementById("leaderboard-name");
        const leaderboardReps = document.getElementById("leaderboard-reps");
        const userLeaderboardEntry = document.getElementById("user-leaderboard-entry");

        const leaderboardEntries = document.getElementById("leaderboard-entries");

        // Show entry

        const showEntryModal = document.getElementById("show-entry-modal");
        const showEntryUser = document.getElementById("show-entry-user");
        const showEntryScore = document.getElementById("show-entry-score");
        const videoContainer = document.getElementById("show-entry-video-container")
        const removeButtonContainer = document.getElementById("remove-button-container");

        // Remove entry

        const removeModal = document.getElementById("remove-modal");
        const deleteEntrySuccessModal = document.getElementById("delete-entry-success-modal");
        const removeUserSuccessModal = document.getElementById("remove-user-success-modal");

        // New entry

        const newEntryModal = document.getElementById("new-entry-modal");
        const newEntryLeaderboardId = document.getElementById("new-entry-leaderboard-id");
        const newEntryScoreNum = document.getElementById("new-entry-score-num");
        const newEntryScoreDecimal = document.getElementById("new-entry-score-decimal");
        const newEntryScoreValidationText = document.getElementById("new-entry-score-validation-text");
        const newEntryVideoValidationText = document.getElementById("new-entry-video-validation-text");

        // ---


        // --- DISPLAY ALL INFORMATION AND ENTRIES FOR A LEADERBOARD

        const league = <?php echo json_encode($league); ?>;                 // Fetch php variable containing selected league
        const leaderboard = <?php echo json_encode($leaderboard); ?>;       // Fetch php variable containing selected leaderboard
        const currentUserId = <?php echo json_encode($userId); ?>;          // Fetch php variable containing current user id

        const leagueId = league.league_id;                  // Store league info 
        const creatorId = league.creator_id;

        const leaderboardId = leaderboard[1];               // Store leaderboard info
        const exercise = leaderboard[2];
        const repsValue = leaderboard[3];
        
        var selectedUserId = 0;                 // Variable to store user id of user associated with selected entry

        leaderboardName.innerHTML = exercise;   // Display leaderboard exercise

        if (repsValue == "1") {                 // Display number of reps, with colour coding consistent to previous
            leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:medium; color:rgb(255, 82, 82); padding-right:8px"></i> ${repsValue} x Rep(s)`;
        } else if (repsValue == "3") {
            leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(199, 199, 43); padding-right:8px"></i> ${repsValue} x Rep(s)`;
        } else if (repsValue == "5") {
            leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(48, 235, 48); padding-right:8px"></i> ${repsValue} x Rep(s)`;
        } else if (repsValue == "10") {
            leaderboardReps.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small; color:rgb(83, 173, 247); padding-right:8px"></i> ${repsValue} x Rep(s)`;
        }

        
        fetch("fetch-league-leaderboard-process.php", {             // Post request to php file to fetch entries for leaderboard
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ leaderboardId:leaderboardId })       // Pass value for leaderboard id so that only relevant entries are fetched
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {                                     // If entries fetched successfully

                const entries = data.data;

                leaderboardEntries.innerHTML = "";                  // Set display list as empty 
        
                for (let i = 0; i < entries.length; i++) {          // Loop through list of entries

                    const entry = entries[i];
                    
                    const listItem = document.createElement("li");          // Create and display list of buttons, each containing a leaderboard entry
                    const button = document.createElement("button");
                    button.addEventListener("click", function() {           // When button containing entry is clicked

                        selectedUserId = entry.user_id;

                        const username = entry.username;        // Store entry information                  
                        const score = entry.score;
                        const entryUserId = entry.user_id;

                        showEntryUser.innerHTML = username;                 // Display username associated with entry in the show entry modal
                        showEntryScore.innerHTML = score + "kg";            // Display the weight achieved in this entry in the show entry modal

                        if(!(currentUserId == creatorId)) {                                 // If current user isn't league admin
                            removeButtonContainer.setAttribute("hidden", "hidden");         // Hide the remove entry button
                        
                        } else {                                                            // If current user is league admin
                            if (entryUserId == currentUserId) {                             // If selected entry belongs to current user
                                removeButtonContainer.setAttribute("hidden", "hidden");     // Hide the remove entry button
                            } else {                                                        // If selected entry does not belong to current user
                                let hidden = removeButtonContainer.getAttribute("hidden");  // Ensure remove entry button is not hidden
                                if (hidden) {
                                    removeButtonContainer.removeAttribute("hidden");
                                }
                            }
                        }

                        fetch("fetch-league-leaderboard-entry-process.php", {           // Post request to php file to fetch details about selected entry
                        method: "POST",     
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ leaderboardId:leaderboardId , userId:entryUserId })        // Pass values for leaderboard id and user id
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {                         // If entry details fetched successfully 

                                videoContainer.innerHTML = "";          // Set video container as empty
                                const entryVideo = data.data.video;

                                if (entryVideo == "null") {             // If entry has no video (is default)
                                    videoContainer.innerHTML = "";      // Set video container as empty 
                                } else {

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

                                }

                            } else {
                                console.log("Error:", data.error);
                            }
                        })
                        .catch(error => console.log("Fetch error:", error));


                        showEntryModal.showModal();         // Display the show entry modal
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

                    leaderboardRank.innerHTML = `<i class="bi bi-diamond-fill" style="font-size:small;"></i> ${i + 1}`;         // Use index of list of entries (ordered by weight achieved) to determine entry rank

                    if ((i + 1) == 1) {         // If entry is 1st, 2nd or 3rd, make rank gold, silver or bronze
                        leaderboardRank.style.backgroundImage = "linear-gradient(45deg, #FFD700, #FFC300, #FFD700, #FFEC8B)";      
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

                    if (entry.user_id == currentUserId) {                   // If this entry belongs to the current user

                        let clonedListItem = listItem.cloneNode(true);      // Clone HTML elements to be displayed in the "Your Rank" section
                        let hr2 = document.createElement("hr");

                        hr2.classList.add("my-rankings-hr");

                        let clonedButton = clonedListItem.querySelector(".search-result-button");
                        let clonedUsername = clonedListItem.querySelector(".leaderboard-username");
                        let clonedScore = clonedListItem.querySelector(".leaderboard-score");

                        if (clonedButton) {                                 // Give button appropriate styling for different coloured card
                            clonedButton.addEventListener("mouseenter", () => {
                                clonedButton.style.backgroundColor = "rgb(126, 108, 143)";
                            });

                            clonedButton.addEventListener("mouseleave", () => {
                                clonedButton.style.backgroundColor = "rgb(68, 19, 113)";
                            });

                            clonedButton.addEventListener("click", function() {         // When user's entry button clicked

                                const username = entry.username;                // Store entry details
                                const score = entry.score;
                                const entryUserId = entry.user_id;

                                showEntryUser.innerHTML = username;             // Display username associated with entry in the show entry modal
                                showEntryScore.innerHTML = score + "kg";        // Display weight achieved in this entry in the show entry modal

                                removeButtonContainer.setAttribute("hidden", "hidden");     // Hide remove button

                                fetch("fetch-league-leaderboard-entry-process.php", {       // Post request to php file to fetch details about selected entry
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({ leaderboardId:leaderboardId , userId:entryUserId })        // Pass values for leaderboard id and user id associated with entry
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


                                showEntryModal.showModal();         // Display the show entry modal
                            });
                        }

                        if (clonedUsername) {                       // Give username appropriate styling for different coloured card
                            clonedUsername.style.color = "white";
                        }

                        if (clonedScore) {                          // Give score appropriate styling for different coloured card
                            clonedScore.style.backgroundImage = "linear-gradient(90deg, rgb(3, 244, 51), rgb(0, 149, 255))";
                            clonedScore.style.webkitBackgroundClip = "text";
                            clonedScore.style.backgroundClip = "text";
                            clonedScore.style.color = "transparent";
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


    // --- DELETE USER ENTRY

    function deleteUserEntry() {        // Function for deleting a user entry

        fetch("delete-league-leaderboard-entry-process.php", {     // Post request to php file to delete user entry
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ leaderboardId:leaderboardId , userId:selectedUserId })     // Pass value for leaderboard id and user id associated with entry
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


    // --- REMOVE USER FROM LEAGUE

    function removeUser() {         // Function for removing selected user from league

        fetch("remove-league-member-process.php", {     // Post request to php file to remove selected user from league
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ leagueId:leagueId , userId:selectedUserId })     // Pass value for league id and user id id associated with user
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {                 // If user removed from league successfully

                showEntryModal.close();         // Close modal displaying user entry
                removeModal.close();            // Close modal for removing user

                removeUserSuccessModal.showModal();     // Show modal confirming user was removed from league successfully
                
            } else {
                console.log("Error joining leaderboard:", data.error);
            }
        })
        .catch(error => console.log("Fetch error:", error));

    }

    // --- 


    // --- FUNCTIONS FOR OPENING MODALS

    function openNewEntryModal() {              // Function for opening modal to add new leaderboard entry 
        newEntryLeaderboardId.value = leaderboardId;
        newEntryModal.showModal();
    }

    function openRemoveModal() {                // Function for opening modal to remove user/entry
        removeModal.showModal();
    }

    // ---


    // --- FUNCTIONS FOR CLOSING MODALS

    function closeShowEntryModal() {            // Function for closing modal to display selected entry information
        showEntryModal.close();
    }

    function closeNewEntryModal() {             // Function for closing modal to add new entry to leaderboard
        newEntryModal.close();
    }

    function closeUploadSuccessModal() {        // Function for closing modal confirming new entry added successfuly
        uploadSuccessModal.close();
    }

    function closeRemoveModal() {               // Function for closing modal to remove user/entry
        removeModal.close();
    }

    function closeDeleteEntrySuccessModal() {   // Function for closing modal confirming user entry deleted successfully
        deleteEntrySuccessModal.close();
        window.location.reload();
    }

    function closeRemoveUserSuccessModal() {    // Function for closing modal confirming user removed from league successfully
        removeUserSuccessModal.close();
        window.location.reload();
    }

    // ---


    </script>

</body>
</html>