# University Dissertation Solo Project - Competitive Gym Platform with Gamification Features 

## Overview
This project was developed for a university final year dissertation solo project. The system is a competitive gym platform, where users of varying levels of skill and experience can rank against each other for a variety of gym exercises. Users can create private leaderboards to compete with friends, as well as join public leaderboards to compete with users worldwide. To add an entry to a leaderboard, users must upload a video with it as proof of completion. Users can then view each others' entries on the leaderboard, and report any that seem illegitimate. They can also create and join leagues, which include customisable leaderboards for 5 different exercises, and an overall league table based on these. Leagues have a customisable duration, allowing for competition over a set time period. 

Gamification elements were also included in the platform to incentivise consistent user interaction, with a progression/levelling system where users can earn XP by doing various things, which causes them to level up. There is also a daily reward system, which incentivises daily use, and this reward is improved as the user's level increases. Users can also earn rewards for finishing leagues, which improve the higher the finish. Part of this reward is a virtual currency, which is used to buy items in the store. This store includes various gym related products (thus relevant to the subject matter), and when purchased, gives the user a code which can be used in the checkout for that real life store to buy the associated product. For example, one item is a MyProtein shaker cup, which would then give the user a code to use in the MyProtein checkout. For obvious reasons, these codes are not real and do not actually work. 

The platform naturally also includes an account system, with a login, signup and password reset page, all containing thorough, in-depth validation and verification. The password reset page comes with fully functioning email functionality that sends a password reset link to the user's email. User's can also edit their account details when logged in, and this comes with the same in-depth validation.

As this application is heavilly focussed on the processing and retrieval of user data, it particularly demonstrates a thorough understanding and application of relational database management, more specifically using PHP and MySQL. 


## Key Features
The platform contains the following functionality:

### Account
- Login (with email and password verification)
- Sign-up (with email verification and thorough validation of all details)
- Password Reset (with email verification, email sending functionality, password reset link verification using a token, and password validation)
- Edit Account details (with email verification and thorough validation of all details)
- Logout
- Delete Account (with all user entries removed from all tables in database, ensuring referntial integrity is upheld)

### Leaderboards
- Add leaderboard entry (with video uploading, containing validation for file type and size)
- Show leaderboard entry (including the retrieval and display of the corresponding video)
- Report leaderboard entry
- Delete leaderboard entry
- Create private leaderboard (including customisable exercise and number of reps)
- Join private leaderboard (using a "join code" system)
- Admin privileges (leaderboard admin can remove user entries, or users entirely from the leaderboard) 

### Leagues
- Create league (including 5 customisable leaderboards and a customisable duration)
- Join league (using a "join code" system)
- Overall league table (calculated using based on each member's performance accross the 5 leaderboards)
- End of league rewards (calculated based on the user's final position in the league table)
- Admin privileges (league admin can remove user entries from leaderboards, or users entriely from the league)

### Progression
- XP-based levelling system (with XP per level algorithm)
- Animations for level-based activities (including gaining XP or levelling up)

### Daily Reward
- Level based reward (with algorithm that calculates reward based on user's level)
- Timestamp of last reward claim, indicating if reward is available to claim (if unavailable, time remaining until next available is displayed)
- Animation for claiming reward

### Store
- Virtual currency used to purchase items
- Gym-related product codes
- Balance checking to ensure item is affordable, and corresponding error if not

### System-Wide Features 
- Clean, modern, visually appealing website design and layout
- Seamless navigation throughout the platform. 
- Thorough validation of all input fields throughout the platform. 
- Secure data handling. 
- Session management to provide personalised user experience and restrict access to main application from non-logged in users. 
- Confirmation modals used throughout (to confirm important user actions, or confirm the outcome of an action)
- Referential integrity is maintained throughout the system in all instances where a user of their corresponding information is deleted.


## Technologies Used
The platform uses the following technologies:

- HTML/CSS - For front-end elements and styling
- JavaScript - For front-end interactive features, and back-end functionality
- PHP – For backend functionality and database interactions.
- MySQL - For database interactions 
- PHPMailer - For sending password reset emails


## Project Structure
The project is clearly structured, divided into folders based on the main components of the system.


# INSTRUCTIONS TO RUN CODE

### REQUIREMENTS 
- PHP
- XAMPP 
- Composer
  
### CONFIGURATION STEPS 
1. Download Project folder. 
2. Move the project folder into the XAMPP directory: 
  - Move it to C:\xampp\htdocs\ 
  - The full path should look like C:\xampp\htdocs\finalyearproject\ 
3. Open the XAMPP control panel and start Apache and MySQL. 
4. Visit http://localhost/phpmyadmin and create a new database. 
5. Import the provided SQL file in order to populate the database as intended. 
6. In the folders containing database.php, update the database credentials, 
changing the database name to the one you just created, leaving the password 
blank. 
7. In the forgotpassword file in the login-signup folder, enter your own Gmail 
SMTP credentials. 
8. If the vendor folder is missing, install Composer by running the “composer 
install”  command in your terminal, which will create the vendor folder in the 
project directory. 
9. Enter http://localhost/finalyearproject/login-signup/login.php in your browser. 
10. Alternatively, if you’re running on port 3000, enter http://localhost:3000/login-signup/login.php into your browser.
    
### ADDITIONAL POINTS 
- If your see errors about missing extensions (E.g., pdo_mysql, openssl, etc.), 
these will need to be enabled in XAMPP.
- To do this, open the XAMPP control panel and stop Apache and MySQL. 
- Locate the php.ini file, found here: C:\xampp\php\php.ini 
- Open this file in a text editor and use the search function to locate the lines 
corresponding to the missing extensions (E.g., ;extension=pdo_mysql). 
- Remove the ; from the beginning of each line to enable them and save changes 
to the file. 
- Go back to XAMPP control panel and start Apache and MySQL. 
- The missing extensions should now be enabled. 


# DISCLAIMERS AND INSTRUCTIONS FOR TESTING SPECIFIC FUNCTIONALITY/SCENARIOS

(Disclaimer) For the existing user entries, the same video of me performing an exercise in the gym has been used for all of them. When adding a leaderboard entry, feel free to upload any video as long as it is a valid video format and does not exceed the max file size (50MB).  

### SIGNUP PAGE
- When creating new account, please use an email address that you have access to in order to desmostrate password reset functionality later.
- Password must be minimum of 8 characters, have at least 1 letter and 1 number.

### LOGIN PAGE
- The email for all test accounts in the system is username@gmail.com (replace username with their username)
- The password for all test accounts in the system is compsci123

### PASSWORD RESET PAGE
- Please use the email you signed up with previously that you have access to when testing this password reset functionality, as real email will be sent.

### HOME/GLOBAL LEADERBOARDS PAGE
- The Barbell Bench Press (Flat) - 1 x Rep(s) leaderboard is populated with user data, please use the find leaderboard section to navigate to this leaderboard when testing leaderboard functionality such as showing selected leaderboard enties, and reporting entries.

### MY LEADERBOARDS PAGE
- When testing the join leaderboard functionality, please use the join code: hqOEKSH0 as a valid join code, as this leaderboard is populated with data.
- The admin for this leaderboard is user1, please log in to this account using the details user1@gmail.com, compsci123 to test the admin privileges for the leaderboard (removing entries and users).

### MY LEAGUES PAGE
- To join a league that is in progress, please use the join code: U5zihcUv as a valid join code, as this league is populated with data.
- The admin for this league is user1, please log in to this account using the details user1@gmail.com, compsci123 to test the admin privileges for the league (removing entries and users).
- To see what happens when a league ends, please log in using the details  endofleague@gmail.com, compsci123 and navigate to the "Example League - Finished" in the My Leagues section.

### REWARDS PAGE
- To demonstrate how daily rewards improve as level increases, please log in using the details experienceduser@gmail.com, compsci123 as this is an account with a fairly high level.

### STORE PAGE
- To test purchasing an item, please log in using the details experienceduser@gmail.com, compsci123 as this account has a large balance and can therefore afford to purchase items. Upon purchasing an item, you can navigate to the My Codes section on the My Account page, as detailed in the on screen confirmation message, to view your item code

Aside from the instructions and scenarios detailed above, testing the rest of the system should be fairly self explanatory. 

