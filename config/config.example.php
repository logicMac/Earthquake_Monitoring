<?php
/**
 * Configuration Example
 * Copy this file to config.php and update with your settings
 */

// Deployment Mode
define('DEPLOYMENT_MODE', 'local'); // Options: 'local', 'cloud', 'production'

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'earthquake_monitoring');

// Server URL (for documentation)
// Local: http://192.168.1.100/client_earthquake/
// Cloud: http://yourdomain.com/
// Production: https://earthquake.ndscpm.edu.ph/
define('SERVER_URL', 'http://localhost/client_earthquake/');

// SMS API Configuration
define('SMS_API_URL', 'https://api.semaphore.co/api/v4/messages');
define('SMS_API_KEY', 'YOUR_SEMAPHORE_API_KEY');
define('SMS_SENDER_NAME', 'ND-SCPM');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);

// Alert Thresholds
define('ALERT_THRESHOLD_LOW', 25.0);  // Local alert only
define('ALERT_THRESHOLD_HIGH', 80.0); // SMS alert

// Timezone
date_default_timezone_set('Asia/Manila');
?>
