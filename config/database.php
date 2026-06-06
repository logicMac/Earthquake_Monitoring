<?php
/**
 * Database Configuration
 * Notre Dame - Siena College of Polomolok
 * Earthquake Monitoring System
 */

// Load .env file for local development
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Set timezone to Asia/Manila (Philippine Time)
@date_default_timezone_set('Asia/Manila');

// Database Configuration - Render & Railway Cloud Environment
// The system will read these values from Render's Environment Variables.
// Fallback defaults are set for your local environment.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'railway'); // Default Railway database name


/**
 * Establishes a secure database connection.
 * Handles custom ports automatically for cloud platform networking (Railway Proxy).
 * * @return mysqli
 */
function getDBConnection() {
    // Break apart the host and custom proxy port from your Render environment variable (e.g., host:port)
    $hostParts = explode(':', DB_HOST);
    $realHost = $hostParts[0];
    $port = isset($hostParts[1]) ? (int)$hostParts[1] : 3306; // Defaults to standard 3306 if no custom port is passed

    // Initialize connection using the custom port cleanly as the 5th parameter
    $conn = @new mysqli($realHost, DB_USER, DB_PASS, DB_NAME, $port);
    
    if ($conn->connect_error) {
        // Log error securely to the server file instead of showing raw error paths to users
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please check your configuration.");
    }
    
    // Set charset to prevent encoding issues with special characters
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// SMS API Configuration - UniSMS
// Get your API key from: https://unismsapi.com/
// Pricing: ₱0.38 per SMS (cheaper than Semaphore!)
// Features: 99.9% delivery, all PH networks, no expiration
define('SMS_API_URL', 'https://unismsapi.com/api/sms');
define('SMS_API_KEY', getenv('SMS_API_KEY') ?: '');  // Set via environment variable

define('SMS_SENDER_NAME', 'ND-SCPM');  // Optional: Your sender name (requires approval)

// Groq API Configuration - QuakeBot
// Get your free API key from: https://console.groq.com/
// Model: llama-3.3-70b-versatile (Fast & Free)
// Features: Natural language queries, data analysis, educational assistant
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');  // Set via environment variable

define('GROQ_MODEL', 'llama-3.3-70b-versatile');  // Fast and accurate model
?>