<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'crime_management_system');

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/police_portal/');

// User types
define('USER_OFFICER', 1);
define('USER_LAWYER', 2);
define('USER_VICTIM', 3);
?>