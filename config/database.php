<?php
// Database configuration
// CS Server MySQL connection details
define('DB_HOST', 'mysql01.cs.virginia.edu');  // CS server hostname
define('DB_USER', 'trs2wd');        // computing ID
define('DB_PASS', 'Fall2025');   // CS MySQL password
define('DB_NAME', 'trs2wd');        // computing ID


// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

