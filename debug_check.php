<?php
/**
 * Debug Check for InfinityFree Deployment
 * Upload this file and visit it in your browser to diagnose issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>InfinityFree Deployment Debug</h1>";
echo "<hr>";

// 1. PHP Version Check
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Required: 7.4 or higher<br>";
echo phpversion() >= '7.4' ? "✅ OK" : "❌ FAIL - Update PHP version in control panel";
echo "<hr>";

// 2. Session Check
echo "<h2>2. Session Support</h2>";
$session_path = sys_get_temp_dir();
echo "Session Path: " . $session_path . "<br>";
echo "Writable: " . (is_writable($session_path) ? "✅ Yes" : "❌ No") . "<br>";

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
echo "Session Started: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Yes" : "❌ No") . "<br>";
echo "<hr>";

// 3. Database Connection Check
echo "<h2>3. Database Connection</h2>";
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful!<br>";
    echo "Database: " . DB_NAME . "<br>";
    
    // Check if tables exist
    $tables = ['admin_users', 'seismic_logs', 'alert_recipients', 'sms_logs'];
    echo "<h3>Tables Check:</h3>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo $table . ": " . ($result->num_rows > 0 ? "✅ Exists" : "❌ Missing") . "<br>";
    }
    
    // Check admin user
    echo "<h3>Admin User Check:</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_users");
    $row = $result->fetch_assoc();
    echo "Admin users: " . $row['count'] . " " . ($row['count'] > 0 ? "✅" : "❌ No admin user - run create_admin.php") . "<br>";
    
    $conn->close();
} catch (Exception $e) {
    echo "❌ Database connection failed!<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Fix:</strong> Update config/database.php with your InfinityFree credentials<br>";
}
echo "<hr>";

// 4. File Permissions Check
echo "<h2>4. File Permissions</h2>";
$files = ['config/database.php', 'includes/auth.php', 'login.php', 'index.php'];
foreach ($files as $file) {
    echo $file . ": " . (file_exists($file) ? "✅ Exists" : "❌ Missing") . "<br>";
}
echo "<hr>";

// 5. Required Extensions
echo "<h2>5. PHP Extensions</h2>";
$extensions = ['mysqli', 'json', 'session', 'curl'];
foreach ($extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✅ Loaded" : "❌ Missing") . "<br>";
}
echo "<hr>";

// 6. InfinityFree Specific Checks
echo "<h2>6. InfinityFree Configuration</h2>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "<hr>";

echo "<h2>Summary</h2>";
echo "If all checks pass (✅), your system should work.<br>";
echo "If you see ❌, fix those issues first.<br>";
echo "<br>";
echo "<strong>Common InfinityFree Issues:</strong><br>";
echo "1. Database credentials not updated in config/database.php<br>";
echo "2. No admin user created - run create_admin.php<br>";
echo "3. Tables not imported - import database/schema.sql<br>";
echo "<hr>";
echo "<a href='login.php'>Try Login Page</a> | <a href='create_admin.php'>Create Admin User</a>";
?>
