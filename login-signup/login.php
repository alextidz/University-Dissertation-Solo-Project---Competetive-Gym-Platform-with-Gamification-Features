<?php

// --- HANDLE USER LOGGING IN

$invalid = false;       // Variable to determine if login is invalid    

if ($_SERVER["REQUEST_METHOD"] === "POST") {        // If login attempted

    $mysqli= require __DIR__ . "/database.php";     // Database connection
    
    // Fetches user with corresponding details
    $sql = sprintf("SELECT * FROM users WHERE email = '%s'", $mysqli->real_escape_string($_POST["email"]));

    $result = $mysqli->query($sql);

    $user = $result->fetch_assoc();

    if ($user) {        // If user with corresponding details exists

        if (password_verify($_POST["password"], $user["password_hash"])) {      // Verify password is correct
            
            session_start();            // Start session with this user

            session_regenerate_id();    // Prevent session fixation attack
        
            $_SESSION["user_id"] = $user["user_id"];    // Set session variable user_id as user's user id

            header("Location: ../home/home.php");       // Relocate to home page
            exit;
        } 
    }

    $invalid = true;        // Login was invalid 
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="../styling/style.css">
</head>
<body>    

    <div class = "login-box-container">

            <!-- Login card -->
            <div class = "card" style = "width:350px; height:375px; text-align:center">
                <div class = "login-header">LOGIN</div>

                <form method="post" novalidate>

                    <!-- Input field for email -->
                    <div class = "input-box">
                        <input spellcheck="false" class="login-field" type="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" placeholder="Email" required>
                    </div>

                    <!-- Input field for password -->
                    <div class = "input-box">
                        <input spellcheck="false" class="login-field" type="password" name="password" placeholder="Password" required>

                        <!-- Display message if login details are incorrect -->
                        <?php if ($invalid) { ?>
                            <em class="validation-text">Incorrect login details</em>
                        <?php } ?>
                    </div>
                   
                    <!-- Link to Forgot Password page -->
                    <div class = "forgot-password-box">
                        <a href="forgotpassword.php" class = "login-link"><i>Forgot Password?</i></a>
                    </div>

                    <!-- Login button -->
                    <button type = "submit" class = "login-button"><p class = "logout-text">Login</p></button>

                </form>

                <!-- Link to Sign Up page -->
                <div class = "register-link">
                    <p class="account-detail">Don't have an account? <a href = "signup.php" class="login-link"><i>Sign Up</i></a></p>
                </div>
            </div>
            
    </div>

</body>
</html>