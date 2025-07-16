<?php

session_start();

// --- CHECK IF THE USER IS LOGGED IN AND FETCH AND STORE THEIR DETAILS

if (isset($_SESSION["user_id"])) {      // Check user is set

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


// --- HANDLE USER SUBMITTING NEW ACCOUNT DETAILS 

$usernameEmpty = false;         // Variable to determine if username field is empty
$emailInvalid = false;          // Variable to determine if email is invalid
$first_nameEmpty = false;       // Variable to determine if first name is empty
$last_nameEmpty = false;        // Variable to determine if last name is empty

$usernameAvailable = true;      // Variable to determine if username entered is available
$emailAvailable = true;         // Variable to determine if email entered is available

if ($_SERVER["REQUEST_METHOD"] === "POST") {            // When user submits new account details

    if (empty(trim($_POST["username"]))) {      // Check that username has been entered
        $usernameEmpty = true;
    }

    if (empty(trim($_POST["first-name"]))) {    // Check that first name has been entered
        $first_nameEmpty = true;
    }

    if (empty(trim($_POST["last-name"]))) {     // Check that last name has been entered
        $last_nameEmpty = true;
    }

    if ( ! filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {        // Check that email is valid
        $emailInvalid = true;
    }

    if( ! ($usernameEmpty || $emailInvalid || $first_nameEmpty || $last_nameEmpty)) {  // If all fields are populated and email os valid
        
        $mysqli = require __DIR__ . "/database.php";        // Database connection

        $sql = "SELECT * FROM users WHERE user_id = {$_SESSION["user_id"]}";    // Fetch user details from database

        $result = $mysqli->query($sql);
        $user = $result->fetch_assoc();             // Store details in user variable 

        $currentUsername = $user["username"];       // Store user's current username
        $currentEmail = $user["email"];             // Store user's current email
        
        $username = $_POST["username"];         // Inputted new username
        $email = $_POST["email"];               // Inputted new email
        $first_name = $_POST["first-name"];     // Inputted new first name
        $last_name = $_POST["last-name"];       // Inputted new last name

        if($currentUsername == $username) {     
        } else {                                // If inputted username is different from current username

            // Fetch user from database with the same username
            $sql = sprintf("SELECT * FROM users WHERE username = '%s'", $mysqli->real_escape_string($username));
            $result = $mysqli->query($sql);
            $row = $result->fetch_assoc();
            if ($row) {                         // If such user exists 
                $usernameAvailable = false;     // Username is unavailable
            } 
        }

        if($currentEmail == $email) {
        } else {                                // If inputted email is different from current email

            // Fetch user from database with the same email
            $sql = sprintf("SELECT * FROM users WHERE email = '%s'", $mysqli->real_escape_string($email));
            $result = $mysqli->query($sql);
            $row = $result->fetch_assoc();
            if ($row) {                         // If such user exists 
                $emailAvailable = false;        // Email is unavailable 
            }
        }

        if($usernameAvailable && $emailAvailable) {     // If inputted username and email are both available 

            // Update current user's details in database
            $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name= ?  WHERE user_id = ?";
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ssssi", $username, $email, $first_name, $last_name, $_SESSION["user_id"]);

            if ($stmt->execute()) {
                header("Location: myaccount.php");      // Relocate to My Account page
                exit;
            } else {
                die($mysqli->errno);
            }
        }
    }
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Details</title>
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

                <!-- Card for editing details -->
                <div class="card" id="edit-details-card">

                        <div class = account-details-header>
                            <p class = "sub-header">Edit Details</p>
                                <form action="myaccount.php">
                                    <button class="cancel-button" style="width:100px"><p class = "logout-text">Cancel<i class="bi bi-x-lg" style="padding-left:5px"></i><p style="padding-right: 5px;"></p></button>
                                </form>
                        </div>
                            

                    <form method="post" id="myaccount" novalidate>
                            
                        <hr class="big-divide">

                        <!-- Username input field -->
                        <div class = "account-detail-container">
                            <p class = "small-sub-header" style="padding-left:10px;padding-bottom:5px">Username</p>
                            <input class="search-bar-dark" type="text" id="username" name="username" value= <?= htmlspecialchars($user["username"]); ?> style ="margin-right:5px;width:300px;font-weight:normal" spellcheck="false" required></input>
                            
                            <!-- Display relevant error messages for username field -->
                            <?php if($usernameEmpty) { ?>
                                <br>
                                <em class="validation-text"> Username is required</em>
                            <?php } elseif($usernameAvailable == false) {?>
                                <br>
                                <em class="validation-text"> Username is taken</em>
                            <?php }?>
                        </div>

                        <hr>

                        <!-- Email input field  -->
                        <div class = "account-detail-container">
                            <p class = "small-sub-header" style="padding-left:10px;padding-bottom:5px">Email</p>
                            <input class="search-bar-dark" type="email" id="email" name="email" value= <?= htmlspecialchars($user["email"]); ?> style ="margin-right:5px;width:300px;font-weight:normal" spellcheck="false" required></input>
                            
                            <!-- Display relevant error messages for email field -->
                            <?php if($emailInvalid) { ?>
                                <br>
                                <em class="validation-text"> Email is invalid</em>
                            <?php } elseif($emailAvailable == false) {?>
                                <br>
                                <em class="validation-text"> Email is taken</em>
                            <?php }?>
                        </div>

                        <hr>

                        <!-- First Name input field -->
                        <div style = "display:flex;justify-content:space-between">

                            <!-- First Name input field -->
                            <div class = "account-detail-container" style = "width:50%">
                                <p class = "small-sub-header" style="padding-left:10px;padding-bottom:5px">First Name</p>
                                <input class="search-bar-dark" type="text" id="first-name" name="first-name" value= <?= htmlspecialchars($user["first_name"]); ?> style ="margin-right:5px;width:300px;font-weight:normal" spellcheck="false" required></input>
                                
                                <!-- Display relevant error message for First Name field -->
                                <?php if($first_nameEmpty) { ?>
                                    <br>
                                    <em class="validation-text"> First name required</em>
                                <?php }?>
                            </div>

                            <!-- Last Name input field -->
                            <div class = "account-detail-container" style = "width:50%">
                                <p class = "small-sub-header" style="padding-left:10px;padding-bottom:5px">Last Name</p>
                                <input class="search-bar-dark" type="text" id="last-name" name="last-name" value= <?= htmlspecialchars($user["last_name"]); }?> style ="margin-right:5px;width:300px;font-weight:normal" spellcheck="false" required></input>
                                
                                <!-- Display relevant error message for Last Name field -->
                                <?php if($last_nameEmpty) { ?>
                                    <br>
                                    <em class="validation-text"> Last name required</em>
                                <?php }?>
                            </div>
                        </div>

                        <!-- Save button -->
                        <div style="text-align:right;padding:5px;padding-right:15px">
                            <button class="friends-button" style="width:100px"><p class = "logout-text">Save<i class="bi bi-check-lg" style="padding-left:5px"></i><p style="padding-right: 5px;"></p></button>
                        </div>
                    
                    </form>

                </div>

            </div>

        </main>

    </div>

</body>
</html>