<?php

$linkInvalid = false;   // Variable indicating if link is invalid
$success = false;       // Variable indicatinf if password has been successfully reset

// --- CHECK URL TOKEN IS VALID

if ($_SERVER["REQUEST_METHOD"] === "GET") {         // Gets token from URL

    if (isset($_GET["token"])) {        // Check URL token is set

        $token = $_GET["token"];                            // Store URL token
        $tokenHash = hash("sha256", $token);    // Hash it

        $mysqli = require __DIR__ . "/database.php";        // Database connection

        // Select user from database who has hashed token that matches this token hash and hasn't expired
        $sql = "SELECT * FROM users WHERE reset_token_hash = ? AND reset_token_expiration > NOW()";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $tokenHash);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {               // If no user is fetched
            $linkInvalid = true;    // Link is invalid  
        } 
    }
}

// ---


// --- HANDLE PASSWORD RESET SUBMIT

$passwordInvalidLength = false;         // Variables to indicate different types of invalid password
$passwordInvalidLetter = false;
$passwordInvalidNum = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {        // If password reset attempted

    if (isset($_POST["token"])) {      // Check URL token is set

        $token = $_POST["token"];                           // Store URL token
        $tokenHash = hash("sha256", $token);    // Hash it

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        // Select user from database who has hashed token that matches this token hash and hasn't expired
        $sql = "SELECT * FROM users WHERE reset_token_hash = ? AND reset_token_expiration > NOW()";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $tokenHash);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {        // If user exists

            if (strlen($_POST["password"]) < 8) {       // Check if password is at least 8 characters long
                $passwordInvalidLength = true;
            }
            
            if ( ! preg_match("/[a-z]/i", $_POST["password"])) {    // Check if password contains at least one letter
                $passwordInvalidLetter = true; 
            }
            
            if ( ! preg_match("/[0-9]/", $_POST["password"])) {     // Check if password contains at least one number
                $passwordInvalidNum = true;
            }

            if( ! ($passwordInvalidLength || $passwordInvalidLetter || $passwordInvalidNum)) {      // If new password is valid

                $passwordHash = password_hash($_POST["password"], PASSWORD_DEFAULT);     // Hash it

                // Update password hash value for user and set password reset information to NULL
                $sql = "UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expiration = NULL WHERE user_id = ?";

                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("si", $passwordHash, $user["user_id"]);
                $stmt->execute();

                $success = true;    // Password reset successfully

            }

        } else {
            $linkInvalid = true;    // Link is invalid
        }

    }
}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../styling/style.css">
</head>
<body>

    <div class="login-box-container">

        <!-- Reset Password card -->
        <div class="card" style="width:350px; height:auto; text-align:center; padding: 20px;">
            <div class="login-header">RESET PASSWORD</div>

            <div id="content">
                <form method="post" novalidate>

                    <!-- Get URL token -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_GET["token"]) ?>">

                    <!-- Input field for new password -->
                    <div class="input-box" style="padding-top: 20px;">
                        <input class="login-field" type="password" name="password" id="password" placeholder="New Password" required>
                    </div>

                    <div class="input-box" style="padding-top: 0px; padding-bottom: 10px;">
                        <em class="validation-text" id="validation-text"></em>
                    </div>

                    <!-- Set Password button -->
                    <button type="submit" class="login-button"><p class="logout-text">Reset Password</p></button>

                </form>
            </div>

            <!-- Link to login page -->
            <div class = "register-link">
                <p class="account-detail">Back to <a href = "login.php" class="login-link"><i>Login</i></a></p>
            </div>
            
        </div>

    </div>

    <script>

        const content = document.getElementById("content");         // Store HTML display container

        // --- DISPLAY ERROR MESSAGE IF LINK IS INVALID/EXPIRED

        var linkInvalid = <?php echo json_encode($linkInvalid); ?>;    // Fetch php variable indicating if link is invalid/expired

        if (linkInvalid == true) {      // If link it invalid/expired, display relevant message
            
            content.innerHTML = `<em class="validation-text">Password Reset link is invalid or has expired</em>`;

        }

        // ---


        // --- DISPLAY RELEVANT ERROR MESSAGE IF PASSWORD IS INVALID

        const validationText = document.getElementById("validation-text");      // Store HTML password validation text element

        var passwordInvalidLength = <?php echo json_encode($passwordInvalidLength); ?>;     // Fetch php variable indicating if password has invalid length
        var passwordInvalidLetter = <?php echo json_encode($passwordInvalidLetter); ?>;     // Fetch php variable indicating if password does not contain a letter
        var passwordInvalidNum = <?php echo json_encode($passwordInvalidNum); ?>;           // Fetch php variable indicating if password does not contain a number

        if (passwordInvalidLength == true) {            // If password not correct length, display relevant message
            validationText.innerHTML = " Password must be at least 8 characters long";
        } else if (passwordInvalidLetter == true) {     // If password does not contain a letter, display relevant message
            validationText.innerHTML = " Password must be contain at least 1 letter";
        } else if (passwordInvalidNum == true) {        // If password does not contain a number, display relevant message
            validationText.innerHTML = " Password must be contain at least 1 number";
        }

        // ---


        // --- DISPLAY CONFIRMATION MESSAGE IF PASSWORD UPDATED SUCCESSFULLY

        var success = <?php echo json_encode($success); ?>;     // Fetch php variable indicating if password has been reset successfully

        if (success == true) {      // If password reset successfully, display relevant message

            content.innerHTML = `<em style=color"green">Password has been updated successfully! You can now return to login page and log in.</em>`;

        }

        // ---

    </script>

</body>
</html>