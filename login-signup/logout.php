<?php

// --- LOG OUT USER

session_start();
session_destroy();                          // Destroy session, logging out user
header("Location: login.php");      // Relocate to login page
exit;

// ---