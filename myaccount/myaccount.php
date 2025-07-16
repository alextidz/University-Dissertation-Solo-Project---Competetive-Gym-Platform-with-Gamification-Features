<?php

session_start();

// --- CHECK IF THE USER IS LOGGED IN AND FETCH AND STORE THEIR DETAILS AND CODES

$codes = [];        // Define array used to store all current user's codes

if (isset($_SESSION["user_id"])) {      // Check user is set

    $mysqli = require __DIR__ . "/database.php";    // Database connection

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";    // Fetch user details from database

    $result = $mysqli->query($sql);
    $user = $result->fetch_assoc();                 // Store details in user variable

    $sql = "SELECT * FROM codes WHERE user_id = {$_SESSION["user_id"]} ORDER BY date_purchased DESC";   // Fetch all codes belonging to current user from database

    $codes = $mysqli->query($sql);          // Store codes in codes array 
}

if (!$user) {       // If user isn't set, user isn't logged in

    header("Location: ../login-signup/login.php");      // Redirect to login page
    exit;
}

// ---


// --- HANDLE USER DELETING ACCOUNT

if ($_SERVER["REQUEST_METHOD"] === "POST") {        // If delete account clicked

    $userId= $_SESSION["user_id"];      // Store user id
    
    $mysqli = require __DIR__ . "/database.php";    // Database connection

    // Home section

    $sql = "DELETE FROM public_leaderboards WHERE user_id = ?";    // Delete all traces of user from public_leaderboards table
    $stmt = $mysqli->prepare($sql);     
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // My Leaderboards section

    $sql = "DELETE FROM private_leaderboards_entries WHERE user_id = ?";      // Delete all traces of user from private leaderboard entries table
    $stmt = $mysqli->prepare($sql);       
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $sql = "SELECT private_leaderboard_id FROM private_leaderboards WHERE creator_id = ?";       // Select private leaderboards where this user is the admin
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {             // Loop through these private leaderboards

        $leaderboardId = $row['private_leaderboard_id'];

        $sql = "DELETE FROM private_leaderboards_entries WHERE leaderboard_id = ?";      // Delete all entries for this leaderboard from private leaderboard entries table
        $stmt = $mysqli->prepare($sql);       
        $stmt->bind_param("i", $leaderboardId);
        $stmt->execute();
        $stmt->close();

    }

    $sql = "DELETE FROM private_leaderboards WHERE creator_id = ?";      // Delete all traces of user from private leaderboards table
    $stmt = $mysqli->prepare($sql);       
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // My Leagues Section

    $sql = "DELETE FROM league_leaderboards_entries WHERE user_id = ?";      // Delete all traces of user from league leaderboard entries table
    $stmt = $mysqli->prepare($sql);       
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $sql = "SELECT league_id FROM leagues WHERE creator_id = ?";       // Select leagues where this user is the admin
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {             // Loop through these leagues
        
        $leagueId = $row['league_id'];

        $sql1 = "SELECT league_leaderboard_id FROM league_leaderboards WHERE league_id = ?";       // Select leagues where this user is the admin
        $stmt1 = $mysqli->prepare($sql1);
        $stmt1->bind_param("i", $leagueId);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $stmt1->close();

        while ($row1 = $result1->fetch_assoc()) {           // Loop through these leagues
            
            $leaderboardId = $row1['league_leaderboard_id'];

            $sql2 = "DELETE FROM league_leaderboards_entries WHERE leaderboard_id = ?";      // Delete all traces of user from league leaderboard entries table
            $stmt2 = $mysqli->prepare($sql2);       
            $stmt2->bind_param("i", $leaderboardId);
            $stmt2->execute();
            $stmt2->close();

        }

        $sql3 = "DELETE FROM league_leaderboards WHERE league_id = ?";  // Delete all leaderboards in these leagues
        $stmt3 = $mysqli->prepare($sql3);
        $stmt3->bind_param("i", $leagueId);
        $stmt3->execute();
        $stmt3->close();

        $sql3 = "DELETE FROM league_entries WHERE league_id = ?";  // Delete all league entries for this league
        $stmt3 = $mysqli->prepare($sql3);
        $stmt3->bind_param("i", $leagueId);
        $stmt3->execute();
        $stmt3->close();
    }
    
    $sql = "DELETE FROM league_entries WHERE user_id = ?";         // Delete all traces of user from league entries table
    $stmt = $mysqli->prepare($sql);       
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    $sql = "DELETE FROM leagues WHERE creator_id = ?";      // Delete all traces of user from leagues table
    $stmt = $mysqli->prepare($sql);       
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // My Account section

    $sql = "DELETE FROM codes WHERE user_id = ?";      // Delete all traces of user from codes table
    $stmt = $mysqli->prepare($sql);       
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $sql = "DELETE FROM users WHERE user_id = ?";       // Delete user from users table
    $stmt = $mysqli->prepare($sql); 
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        header("Location: account-deleted.php");    // Redirect to account deleted confirmation page
        exit;
    } else {
        die($mysqli->errno);
    }
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
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
                <form action="../rewards/rewards.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-coin" style="padding-right:5px"></i><p style="padding-right: 5px;">Rewards</p></div></button>
                </form>
                <form action="../store/store.php" class="sidebar-form">
                    <button type="submit" class="sidebar-btn"><div class="sidebar-cell"><i class="bi bi-bag-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">Store</p></div></button>
                </form>
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-person-circle" style="padding-right:5px"></i><p style="padding-right: 5px;">My Account</p></div></button>
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
                        <div class="card-header">My Account<i class="bi bi-person-circle" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Card containing user's account details -->
                <div class="card" id="account-details-card">

                    <div class = account-details-header>
                        <p class = "sub-header">Account Details</p>
                        <form action="myaccount-edit-details.php">
                            <button class="friends-button" style="width:90px"><p class = "logout-text">Edit<i class="bi bi-pencil-fill" style="padding-left:5px"></i><p style="padding-right: 5px;"></p></button>
                        </form>
                    </div>

                    <hr class="big-divide">

                    <!-- Display user's username -->
                    <div class = "account-detail-container">
                        <p class = "small-sub-header">Username</p>
                        <p class="account-detail"><?= htmlspecialchars($user["username"]); ?></p>
                    </div>

                    <hr>

                    <!-- Display user's email -->
                    <div class = "account-detail-container">
                        <p class = "small-sub-header">Email</p>
                        <p class="account-detail"><?= htmlspecialchars($user["email"]); ?></p>
                    </div>

                    <hr>

                    <div style = "display:flex;justify-content:space-between">

                        <!-- Display user's first name -->
                        <div class = "account-detail-container" style="width:50%">
                            <p class = "small-sub-header">First Name</p>
                            <p class="account-detail"><?= htmlspecialchars($user["first_name"]); ?></p>
                        </div>

                        <!-- Display user's last name -->
                        <div class = "account-detail-container" style="width:50%">
                            <p class = "small-sub-header">Last Name</p>
                            <p class="account-detail"><?= htmlspecialchars($user["last_name"]); }?></p>
                        </div>
                    </div>
                </div>

                <!-- Card containing user's codes when shown -->
                <div class="card"  id="codes-shown">

                    <div class="my-codes-header">
                        <div class="card-header" style="padding-bottom:8px">My Codes<i class="bi bi-card-checklist" style="padding-left:8px; padding-right: 10px;"></i></div>
                        
                        <!-- Dropdwon button to hide codes -->
                        <div style="padding-bottom: 10px;">
                            <button class="my-codes-button" id="hide-my-codes" onclick="hideCodes()"><i class="bi bi-caret-up-fill"></i></button>
                        </div>
                    </div>

                    <hr class="big-divide">

                    <!-- List of user's codes -->
                    <?php if ($codes->num_rows > 0) {                       // If user has codes
                            while ($row = $codes->fetch_assoc()) {?>

                                <!-- Display code and its relevant information -->
                                <div class="code-container">
                                    <div class="code-info"><?= htmlspecialchars($row["item_name"]); ?></div>
                                    <div class="code-info" style="font-weight:bold;"><?= htmlspecialchars($row["code_string"]); ?></div>
                                    
                                    <!-- Button to copy code to clipboard -->
                                    <div class="copy-button-container">
                                        <button class="copy-button"><i class="bi bi-copy" style="padding-left:5px"></i></button>
                                    </div>

                                    <div class="code-info" style="padding-left: 75px;"><?= htmlspecialchars($row["date_purchased"]); ?></div>
                                </div>
                                <hr>

                    <?php   }                                               // If user has no codes
                        } else { ?>

                            <!-- Display no codes message -->
                            <div class="no-codes-container">
                                <i class="small-sub-header">You don't have any codes at the moment</i>
                            </div>

                    <?php } ?>

                </div>

                <!-- Card containing user's codes when hidden -->
                <div class="card" id="codes-hidden">

                    <div class="my-codes-header">
                        <div class="card-header" style="padding-bottom:8px">My Codes<i class="bi bi-card-checklist" style="padding-left:8px; padding-right: 10px;"></i></div>
                        
                        <!-- Dropdwon button to reveal codes -->
                        <div style="padding-bottom: 10px;">
                            <button class="my-codes-button" id="show-my-codes" onclick="showCodes()"><i class="bi bi-caret-down-fill"></i></button>
                        </div>
                    </div>

                    <hr class="big-divide">

                </div>

                <!-- Card containing button to delete account -->
                <div class="card" id="delete-account-card" style="text-align:center;padding:20px">
                    <button class="friends-button" style="width:200px" id="delete-account-button"><p style="color:white">Delete Account</p></button>
                </div>

            </div>

            <!-- Modal asking user to confirm they want to delete account -->
            <dialog class="modal" id="modal">

                <div class="small-sub-header" style="padding-top: 10px;">DELETE ACCOUNT</div>
                <div class="delete-account-text">Are you sure you want to permanently delete your account? This cannot be undone.</div>
                <div class="delete-account-text"><em>This will also delete any private leaderboards and leagues for which you are the admin.</em></div>
                <div class="delete-buttons">

                    <!-- Button to confirm delete -->
                    <form method="post" id="delete-account" novalidate>
                        <button id="confirm-delete-button" class="friends-button" style="width:125px; color: white;">Delete</button>
                    </form>

                    <!-- DButton to cancel deletion -->
                    <button id="close-modal-button" class="cancel-button" style="width:125px; color: rgb(68, 19, 113)">Cancel</button>

                </div>

            </dialog>
            
        </main>

    </div>

    <script>

        // --- STORE HTML ELEMENTS 

        const modal = document.getElementById("modal");
        const openModal = document.getElementById("delete-account-button");
        const closeModal = document.getElementById("close-modal-button");

        const codesHidden = document.getElementById("codes-hidden");
        const codesShown = document.getElementById("codes-shown");

        // ---


        // --- FUNCTIONS FOR OPENING AND CLOSING DELETE ACCOUNT MODAL

        openModal.addEventListener("click", () => {     // When delete button pressed     
            modal.showModal();                          // Open modal asking user to confirm they want to delete account
        })

        closeModal.addEventListener("click", () => {    // When cancel button pressed
            modal.close();                              // Close modal asking user to confirm they want to delete account
        })

        // ---


        // --- FUNCTIONS TO SHOW AND HIDE CODES

        function showCodes() {
            let hidden = codesShown.getAttribute("hidden");
            if (hidden) {
                codesShown.removeAttribute("hidden");
            }

            codesHidden.setAttribute("hidden", "hidden");
        }

        function hideCodes() {
            let hidden = codesHidden.getAttribute("hidden");
            if (hidden) {
                codesHidden.removeAttribute("hidden");
            }

            codesShown.setAttribute("hidden", "hidden");
        }

        // ---


        // --- COPY CODE TO CLIPBOARD

        document.addEventListener("DOMContentLoaded", () => {
            const copyButtons = document.querySelectorAll(".copy-button");

            copyButtons.forEach(button => {
                button.addEventListener("click", function () {
                    const codeInput = this.parentElement.previousElementSibling.innerText; // Get the input field before the button
                    navigator.clipboard.writeText(codeInput).then(() => {
                        this.innerHTML = '<p style="font-weight:lighter; color:rgb(68, 19, 113)" >Copied!</p>';
                        setTimeout(() => { this.innerHTML = `<i class="bi bi-copy" style="padding-left:5px"></i>`; }, 1250);
                    }).catch(err => {
                        console.error("Failed to copy:", err);
                    })

                    
                })
            })
        })

        // ---
        

        hideCodes();        // Initially, hide codes

    </script>

</body>
</html>