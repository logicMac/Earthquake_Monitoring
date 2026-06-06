<?php
// Simple test file to check if PHP is working
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Test</title></head><body>";
echo "<h1>PHP is Working!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test database config
echo "<h2>Testing Database Config...</h2>";
if (file_exists('config/database.php')) {
    echo "<p>✅ config/database.php exists</p>";
    try {
        require_once 'config/database.php';
        echo "<p>✅ Config loaded successfully</p>";
        echo "<p>DB_HOST: " . DB_HOST . "</p>";
        echo "<p>DB_USER: " . DB_USER . "</p>";
        echo "<p>DB_NAME: " . DB_NAME . "</p>";
        
        // Try connection
        try {
            $conn = getDBConnection();
            echo "<p>✅ Database connected!</p>";
            $conn->close();
        } catch (Exception $e) {
            echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config/database.php not found</p>";
}

echo "<hr>";
echo "<a href='debug_check.php'>Run Full Debug Check</a> | ";
echo "<a href='create_admin.php'>Create Admin</a> | ";
echo "<a href='login.php'>Login</a>";
echo "</body></html>";
?>
