<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted</title>
    <link rel="stylesheet" href="../styling/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>    

    <!-- Container displaying account deleted success message -->
    <div class = "login-box-container">

            <div class = "card" style = "width:400px; height:150px; text-align:center">
                <div class="small-sub-header" style="padding:10px">ACCOUNT DELETED</div>
                <div class="account-detail" style="padding-bottom: 20px;">Your account has been successfully deleted</div>

                <!-- Button that logs out user when pressed -->
                <form method="post" action="../login-signup/logout.php" novalidate>
                    <button type = "submit" class = "login-button" style="width:200px"><p class = "logout-text">Back to login</p></button>
                </form>

            </div>
    </div>

</body>
</html>