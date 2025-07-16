

## INSTRUCTIONS TO RUN CODE

### REQUIREMENTS 
- PHP
- XAMPP 
- Composer
  
### CONFIGURATION STEPS 
1. Download Project folder and extract the .zip file. 
2. Move the unzipped project folder into the XAMPP directory: 
o Move it to C:\xampp\htdocs\ 
o The full path should look like C:\xampp\htdocs\finalyearproject\ 
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


## DISCLAIMERS AND INSTRUCTIONS FOR TESTING SPECIFIC FUNCTIONALITY/SCENARIOS

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

