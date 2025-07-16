<?php

session_start();

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

$balance = $user["balance"];    // Store current user's balance

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
    <title>Store</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styling/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body> 
    
    <div class="min-h-screen flex">

        <!-- Sidebar containing buttons with links to all main pages -->
        <aside class="w-1/4 pt-6 shadow-lg flex flex-col justify-between transition duration-500 ease-in-out transform" id="sidebar"> 
            <div>
                <form action="../home/home.php" class="sidebar-form">
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
                <form class="sidebar-form">
                    <button type="submit" class="sidebar-btn-clicked"><div class="sidebar-cell"><i class="bi bi-bag-fill" style="padding-right:5px"></i><p style="padding-right: 5px;">Store</p></div></button>
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
                        <div class="card-header">Store<i class="bi bi-bag-fill" style="padding-left:8px"></i></div>
                        <div class="right-side-info">
                            <div class="level-and-username"><p class="logout-text"><?= htmlspecialchars($user["current_level"]); ?></p> <p style="color: #d3d3d9;padding-left:5px; padding-right:5px"> | </p> <?= htmlspecialchars($user["username"]); ?></div>
                            <div class="balance"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Card containing Gymshark products -->
                <div class="card">
                    <div class="card-header">Gymshark Codes</div>
                    <div class="store-container">
                        <div class="store-item-container">
                            <img src="../images/hoodie.png"
                            alt="Gymshark Hoodie" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Hoodie</p>
                            <button class="main-btn" style="width: 100px;" id="hoodie-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1500</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/t-shirt.png"
                            alt="Gymshark T-shirt" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark T-shirt</p>
                            <button class="main-btn" style="width: 100px;" id="t-shirt-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>800</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/joggers.png"
                            alt="Gymshark Men's Joggers" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Men's Joggers</p>
                            <button class="main-btn" style="width: 100px;" id="joggers-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1500</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/mens-shorts.png"
                            alt="Gymshark Men's Shorts" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Men's Shorts</p>
                            <button class="main-btn" style="width: 100px;" id="mens-shorts-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1000</p></button>
                        </div>
                    </div>

                    <div class="store-container">
                        <div class="store-item-container">
                            <img src="../images/sports-bra.png"
                            alt="Gymshark Sports Bra" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Sports Bra</p>
                            <button class="main-btn" style="width: 100px;" id="sports-bra-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1000</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/womens-shorts.png"
                            alt="Gymshark Women's Shorts" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Women's Shorts</p>
                            <button class="main-btn" style="width: 100px;" id="womens-shorts-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1000</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/leggings.png"
                            alt="Gymshark Leggings" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Leggings</p>
                            <button class="main-btn" style="width: 100px;" id="leggings-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1000</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/socks.png"
                            alt="Gymshark Socks" loading="lazy" width="100%" height="100%">
                            <p class="store-text">Gymshark Socks</p>
                            <button class="main-btn" style="width: 100px;" id="socks-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>500</p></button>
                        </div>
                    </div>
                </div>

                <!-- Card containing MyProtein products -->
                <div class="card">
                    <div class="card-header">MyProtein Codes</div>
                    <div class="store-container">
                        <div class="store-item-container">
                            <img src="../images/impact-whey.png"
                            alt="MyProtein Impact Whey Protein" loading="lazy" width="100%" height="100%">
                            <p class="store-text">MyProtein Impact Whey 500g</p>
                            <button class="main-btn" style="width: 100px;" id="impact-whey-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>500</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/clear-whey.png"
                            alt="MyProtein Clear Whey" loading="lazy" width="100%" height="100%">
                            <p class="store-text">MyProtein Clear Whey <br> 20 Servings</p>
                            <button class="main-btn" style="width: 100px;" id="clear-whey-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>1000</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/creatine.png"
                            alt="MyProtein Creatine" loading="lazy" width="100%" height="100%">
                            <p class="store-text">MyProtein Creatine <br> 100g</p>
                            <button class="main-btn" style="width: 100px;" id="creatine-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>200</p></button>
                        </div>
                        <div class="store-item-container">
                            <img src="../images/shaker.png"
                            alt="MyProtein Shaker Cup" loading="lazy" width="100%" height="100%">
                            <p class="store-text">MyProtein Shaker <br> Cup</p>
                            <button class="main-btn" style="width: 100px;" id="shaker-button"><p class="logout-text"><i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>500</p></button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Modal for user to confirm they want to make a purchase -->
            <dialog class="modal" id="question-modal">
                <div class="small-sub-header" style="padding-top: 10px;">CONFIRM PURCHASE</div>
                <div class="delete-account-text">
                    <div id="selected-item-image" style="display:flex; justify-content: center;"></div>
                    <p class="store-text" id="selected-item-name"></p>
                    <p class="small-sub-header" id="selected-item-price"></p>
                </div>
                <div class="delete-buttons">

                    <!-- Button to confirm purchase -->
                    <form method="post" id="purchase-item" novalidate>
                        <button id="purchase-item-button" class="friends-button" style="width:125px;"><p class="logout-text">Buy</p></button>
                    </form>

                    <!-- Button to cancel purchase -->
                    <button id="close-question-modal-button" class="cancel-button" style="width:125px;"><p class="logout-text">Cancel</p></button>
                
                </div>
            </dialog>


            <!-- Modal telling user their balance is too low to purchase selected item -->
            <dialog class="modal" id="error-modal">
                <div class="small-sub-header" style="padding-top: 10px;">BALANCE TOO LOW</div>
                <div class="delete-account-text">
                    <p class="store-text">You don't have enough for this item</p>
                    <div style="display: flex; justify-content: center; align-items: center">
                        <p class="store-text" style="font-weight: bold;" id="selected-item-price">Your balance: </p><p class="small-sub-header"><i class="bi bi-currency-exchange" style="padding-right:5px; padding-left:5px font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); ?></p>
                    </div>
                </div>
                <div style="justify-content:center;">
                    <button id="close-error-modal-button" class="friends-button" style="width:125px;"><p class="logout-text">Dismiss</p></button>
                </div>
            </dialog>


            <!-- Model for confirming purchase was successful -->
            <dialog class="modal" id="confirm-modal" style="background: linear-gradient(to right, rgb(74, 254, 110), rgb(81, 182, 254))">
                <div class="small-sub-header" style="padding-top: 10px;">PURCHASE SUCCESSFUL</div>
                <div class="delete-account-text">
                    <p class="store-text">Item purchased successfully.</p>
                    <p class="store-text">Your code can be found under <b class="small-sub-header">My Codes</b> in the <b class="small-sub-header">My Account</b> section</p>
                    <div style="display: flex; justify-content: center; align-items: center">
                        <p class="store-text" style="font-weight: bold;" id="selected-item-price">Your balance: </p><p class="small-sub-header"><i class="bi bi-currency-exchange" style="padding-right:5px; padding-left:5px font-weight:bold; color:goldenrod;"></i><?= htmlspecialchars($user["balance"]); }?></p>
                    </div>
                </div>
                <div style="justify-content:center;">
                    <button id="close-confirm-modal-button" class="friends-button" style="width:125px; color: white;">Dismiss</button>
                </div>
            </dialog>


            <!-- Contains overlay displaying xp gained message -->
            <div class="overlay" id="xp-overlay">
                <div class="level-up-container">
                    <i class="logout-text" style="font-weight: bold; font-size: 4rem;">+500xp</i>
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

        if (levelChanged == true) {              // If level has changed
            
            fetch("../progression/level-up-process.php", {      // Post new level to php file which updates user's level
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ newLevel:newLevel })     // Pass value for new level
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                                     // If level successfully updated
                    sessionStorage.setItem('levelSuccess', 'true');     // Create session variable levelSuccess and set as true 
                                                                        // (This will be used to determine if level up message should be displayed)
                    window.location.reload();                           // Reload the page
                } else {
                    console.log("Error claiming rewards:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));
        }

        // ---
        
        var balance = <?php echo json_encode($balance); ?>;     // Fetch php variable containing user's balance

        const hoodiePrice = 1500;               // Store prices of all items
        const tshirtPrice = 800;
        const joggersPrice = 1500;
        const mensShortsPrice = 1000;
        const sportsBraPrice = 1000;
        const womensShortsPrice = 1000;
        const leggingsPrice = 1000;
        const socksPrice = 500;

        const impactWheyPrice = 500;
        const clearWheyPrice = 1000;
        const creatinePrice = 200;
        const shakerPrice = 500;

        const questionModal = document.getElementById("question-modal");        // Store modals
        const errorModal = document.getElementById("error-modal");
        const confirmModal = document.getElementById("confirm-modal");


        // --- HANDLE USER CLICKING ON AN ITEM

        const hoodieButton = document.getElementById("hoodie-button");      // If hoodie clicked
        hoodieButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/hoodie.png" alt="Gymshark Hoodie" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Hoodie";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + hoodiePrice;
            if (balance >= hoodiePrice) {       
                questionModal.showModal();      // If user has enough coins, show modal containing hoodie
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const tshirtButton = document.getElementById("t-shirt-button");     // If t-shirt clicked
        tshirtButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/t-shirt.png" alt="Gymshark T-shirt" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark T-shirt";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + tshirtPrice;
            if (balance >= tshirtPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing t-shirt
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const joggersButton = document.getElementById("joggers-button");    // If joggers clicked
        joggersButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/joggers.png" alt="Gymshark Men\'s Joggers" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Men's Joggers";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + joggersPrice;
            if (balance >= joggersPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing joggers
            } else {    
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const mensShortsButton = document.getElementById("mens-shorts-button");     // If men's shorts clicked
        mensShortsButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/mens-shorts.png" alt="Gymshark Men\'s Shorts" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Men's Shorts";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + mensShortsPrice;
            if (balance >= mensShortsPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing men's shorts
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const sportsBraButton = document.getElementById("sports-bra-button");       // If sports bra clicked
        sportsBraButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/sports-bra.png" alt="Gymshark Sports Bra" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Sports Bra";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + sportsBraPrice;
            if (balance >= sportsBraPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing sports bra
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const womensShortsButton = document.getElementById("womens-shorts-button"); // If women's shorts clicked
        womensShortsButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/womens-shorts.png" alt="Gymshark Women\'s Shorts" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Women's Shorts";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + womensShortsPrice;
            if (balance >= womensShortsPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing women's shorts
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const leggingsButton = document.getElementById("leggings-button");      // If leggings clicked
        leggingsButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/leggings.png" alt="Gymshark Leggings" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Leggings";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + leggingsPrice;
            if (balance >= leggingsPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing leggings
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const socksButton = document.getElementById("socks-button");        // If socks clicked
        socksButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/socks.png" alt="Gymshark Socks" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "Gymshark Socks";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + socksPrice;
            if (balance >= socksPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing socks
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })


        const impactWheyButton = document.getElementById("impact-whey-button");     // If impact whey clicked
        impactWheyButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/impact-whey.png" alt="MyProtein Impact Whey Protein" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "MyProtein Impact Whey 500g";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + impactWheyPrice;
            if (balance >= impactWheyPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing impact whey
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const clearWheyButton = document.getElementById("clear-whey-button");       // If clear whey clicked
        clearWheyButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/clear-whey.png" alt="MyProtein Clear Whey" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "MyProtein Clear Whey 20 Servings";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + clearWheyPrice;
            if (balance >= clearWheyPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing clear whey
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const creatineButton = document.getElementById("creatine-button");      // If creatine clicked
        creatineButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/creatine.png" alt="MyProtein Creatine" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "MyProtein Creatine 100g";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + creatinePrice;
            if (balance >= creatinePrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing creatine
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        const shakerButton = document.getElementById("shaker-button");      // If shaker clicked
        shakerButton.addEventListener("click", () => {
            document.getElementById("selected-item-image").innerHTML = '<img src="../images/shaker.png" alt="MyProtein Shaker Cup" width="75%" height="75%">';
            document.getElementById("selected-item-name").innerHTML = "MyProtein Shaker Cup";
            document.getElementById("selected-item-price").innerHTML = '<i class="bi bi-currency-exchange" style="padding-right:5px; font-weight:bold; color:goldenrod;"></i>' + shakerPrice;
            if (balance >= shakerPrice) {
                questionModal.showModal();      // If user has enough coins, show modal containing shaker
            } else {
                errorModal.showModal();         // If user's balance too low, show balance too low modal
            }
        })

        // ---


        // --- HANDLE USER PURCHASING AN ITEM

        const purchaseItemButton = document.getElementById("purchase-item-button");
        purchaseItemButton.addEventListener("click", () => {                            // When purchase button clicked

            let itemPrice = parseInt(document.getElementById("selected-item-price").textContent.replace(/\D/g, ""));    // Store item price
            let itemName = document.getElementById("selected-item-name").textContent;                                   // Store item name
            
            let newBalance = balance - itemPrice;           // Calculate user's new balance

            let itemCode = '';
            const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            for (let i = 0; i < 16; i++) {                                              // Generate 16 character item code
                const randNum = Math.floor(Math.random() * characters.length);
                itemCode += characters[randNum];
            }

            fetch("store-process.php", {            // Post request to php file to update user's balance and add item code to their account
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ newBalance: newBalance , itemName:itemName, itemCode:itemCode })     // Pass values for updated balance, item name and item code
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {                                         // If item purchased successfully 
                    sessionStorage.setItem('purchaseSuccess', 'true');      // Set session variable purchaseSuccess to true
                    window.location.reload();                               // Reload page
                } else {                                                    // When page reloads, this variable is then used to realise a purchase has been made, and purchase success modal is shown
                    console.log("Error updating balance:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));
        })

        // ---


        // --- SHOW APPROPRIATE MODAL AFTER PAGE RELOAD

        window.addEventListener("load", function() {                            // When page reloaded
            if (sessionStorage.getItem('purchaseSuccess') === 'true') {         // If there has been a successful purchase

                confirmModal.showModal();                                       // Show purchase success modal
                sessionStorage.removeItem('purchaseSuccess');                   // Remove purchase success variable so that modal isn't shown again if page reloaded again
            
            } else if (sessionStorage.getItem('levelSuccess') === 'true') {     // If user has leveled up

                var overlay = document.getElementById("level-overlay");
                overlay.style.display = "flex";                                 // Display overlay containing level up message

                setTimeout(() => {
                    overlay.style.display = "none";                             // Show message for 3 seconds
                }, 3000);
                sessionStorage.removeItem('levelSuccess');                      // Remove level success variable so that modal isn't shown again if page reloaded again
            }
        });

        // ---


        // --- EVENT LISTENERS FOR CLOSING MODALS

        const closeQuestionModal = document.getElementById("close-question-modal-button");  
        closeQuestionModal.addEventListener("click", () => {
            questionModal.close();                                  // Close modal asking user if they want to purchase selected item
        })

        const closeErrorModal = document.getElementById("close-error-modal-button");        
        closeErrorModal.addEventListener("click", () => {
            errorModal.close();                                     // Close modal telling user their balance is too low
        })

        const closeConfirmModal = document.getElementById("close-confirm-modal-button");   
        closeConfirmModal.addEventListener("click", () => {
            confirmModal.close();                                   // Close modal confirming successful purchase
            xpToAdd = 500;              // Store value of xp to add to user's xp total (500)

            fetch("../progression/add-xp-process.php", {        // Post request to php file to update user's xp total
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ xpToAdd:xpToAdd })       // Pass value for xp to be added
            })
            .then(response => response.json())
            .then(data => {                                     
                if (data.success) {                                             // If user xp total updated successfully
                    var overlay = document.getElementById("xp-overlay");
                    overlay.style.display = "flex";                             // Display overlay containing xp gained message

                    setTimeout(() => {
                        overlay.style.display = "none";         // Show message for 2 seconds
                        window.location.reload();
                    }, 2000);
                } else {
                    console.log("Error updating balance:", data.error);
                }
            })
            .catch(error => console.log("Fetch error:", error));

        }) 

        // ---

        
    </script>

</body>
</html>