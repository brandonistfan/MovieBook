<?php
// Database configuration
// CS Server MySQL connection details
define('DB_HOST', 'mysql01.cs.virginia.edu');  // CS server hostname
define('DB_USER', 'trs2wd');        // computing ID
define('DB_PASS', 'Fall2025');   // CS MySQL password
define('DB_NAME', 'trs2wd');        // computing ID


// Singleton connection storage - reuse same connection per request
static $dbConnection = null;
static $shutdownRegistered = false;

// Create database connection (singleton pattern - reuse same connection per request)
function getDBConnection() {
    global $dbConnection, $shutdownRegistered;
    
    // Return existing connection if available and still valid
    if ($dbConnection !== null) {
        // Check if connection is still alive
        if (@$dbConnection->ping()) {
            return $dbConnection;
        } else {
            // Connection is dead, reset it
            $dbConnection = null;
        }
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        // Store connection for reuse (singleton pattern)
        $dbConnection = $conn;
        
        // Register shutdown function once to close connection on script end (even on errors)
        if (!$shutdownRegistered) {
            register_shutdown_function(function() {
                global $dbConnection;
                if ($dbConnection !== null && is_object($dbConnection)) {
                    // Check if connection is still open before closing
                    // Check if the connection has been closed by checking thread_id property
                    // If thread_id is null, connection is already closed
                    try {
                        $threadId = @$dbConnection->thread_id;
                        if ($threadId !== null) {
                            @$dbConnection->close();
                        }
                    } catch (Exception $e) {
                        // Connection is already closed or invalid, ignore
                    } catch (Error $e) {
                        // Connection is already closed, ignore
                    }
                    $dbConnection = null;
                }
            });
            $shutdownRegistered = true;
        }
        
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

