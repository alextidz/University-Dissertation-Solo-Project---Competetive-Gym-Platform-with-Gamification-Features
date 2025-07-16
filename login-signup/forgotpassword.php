<?php

use PHPMailer\PHPMailer\PHPMailer;      // Using PHP Mailer for sending email
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// --- HANDLE PASSWORD RESET REQUEST

$linkSent = false;      // Variable to determine if email has been sent
$emailInvalid = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {        // If password reset attempted

    $email = $_POST["email"];                               // Store email
    $token = bin2hex(random_bytes(16));     // Generate random token of 16 characters for unique url 
    $tokenHash = hash("sha256", $token);        // Generate hash value of 64 characters for token for security
    $tokenExpiration = date("Y-m-d H:i:s", time() + 60 * 30);    // Set timestamp for token expiration (5 minutes from now)
    
    $mysqli= require __DIR__ . "/database.php";     // Database connection

    // Update current user's reset token and reset token expiration in database
    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expiration = ?  WHERE email = ?";
            
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sss", $tokenHash, $tokenExpiration, $email);
    $stmt->execute();

    if ($mysqli->affected_rows) {       // If email was valid and user was updated in database

        $mail = new PHPMailer(true);

        try {                   // Configure email settings
                                                        
            $mail->isSMTP();                            // Using SMTP for sending email
            $mail->Host = "smtp.gmail.com";             // Using gmail mail server
            $mail->SMTPAuth = true;
            $mail->Username = "your-email@gmail.com";         // Gmail account used to send emails
            $mail->Password = "your-email-password";    // Password for mail server
            $mail->Port = 587;                      
            $mail->SMTPSecure = "tls";                  // Email encryption for security

            $mail->setFrom("alextidey20@gmail.com", "Raise the Bar");   // Email sender
            $mail->addAddress($email);                                  // Email recipient

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";      // Email subject
            // Email body
            $mail->Body = "To reset your password, click <a href='http://localhost:3000/login-signup/reset-password.php?token=$token'>here</a>.<br>This link will expire in 30 minutes.";

            $mail->send();      // Send email
            $linkSent = true;   // Set linkSent to true to indicate email has been sent
        } catch (Exception $e) {
            echo "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $emailInvalid = true;
    }

}

// ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../styling/style.css">
</head>
<body>    

    <div class = "login-box-container">

            <!-- Reset Password card -->
            <div class = "card" style = "width:350px; text-align:center">
                <div class = "login-header">RESET PASSWORD</div>

                <div id = "content" >
                    <form method="post" novalidate>

                        <!-- Input field for email -->
                        <div class = "input-box">
                            <input spellcheck="false" class = "login-field" type="text" placeholder="Email" id="email" name="email" required>
                        </div>

                        <div id="validation-text"></div>

                        <div class = "password-link-box">
                            <p><i>A link will be sent to this email to reset your password</i></p>
                        </div>

                        <button type = "submit" class = "login-button"><p class = "logout-text">Send Link</p></button>

                    </form>
                </div>
                
                <!-- Link to login page -->
                <div class = "register-link">
                    <p class="account-detail">Back to <a href = "login.php" class="login-link"><i>Login</i></a></p>
                </div>
            </div>
    </div>

    <script>

        // --- DISPLAY ERROR MESSAGE IF EMAIL INVALID

        const validationText = document.getElementById("validation-text");      // Store HTML element to display error message

        var emailInvalid = <?php echo json_encode($emailInvalid); ?>;    // Fetch php variable indicating if invalid/incorrect email has been entered      

        if (emailInvalid == true) {             // If invalid/incorrect email entered, display relevant error message

            validationText.innerHTML = `<div class="input-box" style="padding-bottom: 0px; padding-top: 0px;">
                                            <em class="validation-text">Email invalid/incorrect</em>
                                        </div>`;
        }

        // ---


        // --- DISPLAY CONFIRMATION MESSAGE WHEN EMAIL SENT

        const content = document.getElementById("content");             // Store HTML element to display confirmation message

        var linkSent = <?php echo json_encode($linkSent); ?>;    // Fetch php variable indicating if email has beem sent

        if (linkSent == true) {         // If email has been sent, display relevant confirmation message

            content.innerHTML = `<em>Email containing password reset link has been sent, please check your inbox</em>`;
        }

        // ---

    </script>

</body>
</html>