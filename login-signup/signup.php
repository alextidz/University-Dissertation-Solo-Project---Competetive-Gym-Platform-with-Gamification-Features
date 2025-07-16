<?php

// --- HANDLE USER SIGNING UP

$usernameEmpty = false;             // Variables to indicate different types of invalid entries
$emailInvalid = false;
$passwordInvalidLength = false;
$passwordInvalidLetter = false;
$passwordInvalidNum = false;
$passwordInvalidMatch = false;
$first_nameEmpty = false;
$last_nameEmpty = false;

$usernameAvailable = true;
$emailAvailable = true;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty(trim($_POST["username"]))) {      // Check if username field is empty
        $usernameEmpty = true;
    }

    if (empty(trim($_POST["first-name"]))) {    // Check if first name field is empty
        $first_nameEmpty = true;
    }

    if (empty(trim($_POST["last-name"]))) {     // Check if last name field is empty
        $last_nameEmpty = true;
    }

    if ( ! filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {    // Check if email format is valid
        $emailInvalid = true;
    }

    if (strlen($_POST["password"]) < 8) {       // Check if password is at least 8 characters long
        $passwordInvalidLength = true;
    }
    
    if ( ! preg_match("/[a-z]/i", $_POST["password"])) {        // Check if password contains at least one letter
        $passwordInvalidLetter = true; 
    }
    
    if ( ! preg_match("/[0-9]/", $_POST["password"])) {         // Check if password contains at least one number
        $passwordInvalidNum = true;
    }
    
    if ($_POST["password"] !== $_POST["password-confirmation"]) {           // Check that password and password confirmation match
        $passwordInvalidMatch = true;
    }

    // If all fields have been entered and meet requirements 
    if( ! ($usernameEmpty || $emailInvalid || $passwordInvalidLength || $passwordInvalidLetter || $passwordInvalidNum || $passwordInvalidMatch ||  $first_nameEmpty || $last_nameEmpty)) {
    
        $username = $_POST["username"];                 // User data 
        $email = $_POST["email"];
        $first_name = $_POST["first-name"];
        $last_name = $_POST["last-name"];

        $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);       // Hash password for security

        $current_level = 1;                             // Initial values for fresh account 
        $balance = 100;
        $daily_claimed_time = date("Y-m-d H:i:s", strtotime("2001-01-01 00:00:00"));
        $current_xp = 0;

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Check if there is already a user registered with the entered username
        $sql = sprintf("SELECT * FROM users WHERE username = '%s'", $mysqli->real_escape_string($username));
        $result = $mysqli->query($sql);
        $row = $result->fetch_assoc();
        if ($row) {
            $usernameAvailable = false;
        } 
    
        // Check if there is already a user registered with the entered email
        $sql = sprintf("SELECT * FROM users WHERE email = '%s'", $mysqli->real_escape_string($email));
        $result = $mysqli->query($sql);
        $row = $result->fetch_assoc();
        if ($row) {
            $emailAvailable = false;
        }

        if($usernameAvailable && $emailAvailable) {     // If username and email both available

            // Insert new entry into user table with user's information
            $sql = "INSERT INTO users(username, email, password_hash, first_name, last_name, current_level, balance, daily_claimed_time, current_xp) 
            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssiisi", $username, $email, $password_hash, $first_name, $last_name, $current_level, $balance, $daily_claimed_time, $current_xp);

            if ($stmt->execute()) {                     // If user created successfuly  

                $sql = sprintf("SELECT * FROM users WHERE email = '%s'", $mysqli->real_escape_string($_POST["email"]));

                $result = $mysqli->query($sql);

                $user = $result->fetch_assoc();          
                
                session_start();                    // Start session with this user
                session_regenerate_id();            // Prevent session fixation attack
                    
                $_SESSION["user_id"] = $user["user_id"];

                header("Location: welcome.php");        // Redirect to welcome page
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
    <title>Sign Up</title>
    <link rel="stylesheet" href="../styling/style.css">
</head>
<body>    

    <div class = "login-box-container" style="padding-top: 15px;">

            <!-- Signup card -->
            <div class = "card" style = "width:350px; height:625px; text-align:center">
                <div class = "login-header">SIGN UP</div>

                <form method="post" id="signup" novalidate>

                    <!-- Input field for username -->
                    <div class = "input-box">
                        <input spellcheck="false"  class = "login-field" type="text" id="username" name="username" value="<?= htmlspecialchars($_POST["username"] ?? "") ?>" placeholder="Username" required>
                        <!-- Display message if username field left empty or username is taken -->
                        <?php if($usernameEmpty) { ?>
                            <br>
                            <em class="validation-text"> Username is required</em>
                        <?php } elseif($usernameAvailable == false) {?>
                            <br>
                            <em class="validation-text"> Username is taken</em>
                        <?php }?>
                    </div>

                    <hr class="signup-hr">

                    <!-- Input field for first name -->
                    <div class = "input-box">
                        <input spellcheck="false"  class = "login-field" type="text" id="first-name" name="first-name" value="<?= htmlspecialchars($_POST["first-name"] ?? "") ?>" placeholder="First Name" required>
                        <!-- Display message if first name field left empty -->
                        <?php if($first_nameEmpty) { ?>
                            <br>
                            <em class="validation-text"> First name required</em>
                        <?php }?>
                    </div>

                    <!-- Input field for last name -->
                    <div class = "input-box">
                        <input spellcheck="false"  class = "login-field" type="text" id="last-name" name="last-name" value="<?= htmlspecialchars($_POST["last-name"] ?? "") ?>" placeholder="Last Name" required>
                        <!-- Display message if last name field left empty -->
                        <?php if($last_nameEmpty) { ?>
                            <br>
                            <em class="validation-text"> Last name required</em>
                        <?php }?>
                    </div>

                    <hr class="signup-hr">

                    <!-- Input field for email address -->
                    <div class = "input-box">
                        <input spellcheck="false"  class = "login-field" type="email" id="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" placeholder="Email" required>
                        <!-- Display message if email field left empty or email is taken -->
                        <?php if($emailInvalid) { ?>
                            <br>
                            <em class="validation-text"> Email is invalid</em>
                        <?php } elseif($emailAvailable == false) {?>
                            <br>
                            <em class="validation-text"> Email is taken</em>
                        <?php }?>
                    </div>

                    <!-- Input field for password -->
                    <div class = "input-box">
                        <input spellcheck="false"  class = "login-field" type="password" id="password" name="password" placeholder="Password" required>
                        <!-- Display message if password invalid-->
                        <?php if($passwordInvalidLength) { ?>
                            <br>
                            <em class="validation-text"> Password must be at least 8 characters long</em>
                        <?php } elseif($passwordInvalidLetter) { ?>
                            <br>
                            <em class="validation-text"> Password must be contain at least 1 letter</em>
                        <?php } elseif($passwordInvalidNum) { ?>
                            <br>
                            <em class="validation-text"> Password must be contain at least 1 number</em>
                        <?php }?>
                    </div>

                    <!-- Input field for password confirmation -->
                    <div class = "input-box" style = "padding-bottom: 40px">
                        <input spellcheck="false"  class = "login-field" type="password" id="password-confirmation" name="password-confirmation" name="password-confirmation" placeholder="Confirm Password" required>
                        <!-- Display message if passworrd confirmation does not match password -->
                        <?php if($passwordInvalidMatch) { ?>
                            <br>
                            <em class="validation-text"> Passwords do not match</em>
                        <?php }?>
                    </div>

                    <!-- Signup button -->
                    <button class = "login-button"><p class = "logout-text">Sign Up</p></button>
                
                </form>

                <!-- Link to login page -->
                <div class = "register-link">
                    <p class="account-detail">Already have an account? <a href = "login.php" class="login-link"><i>Login</i></a></p>
                </div>
            </div>
    </div>

</body>
</html>