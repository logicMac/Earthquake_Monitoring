<?php
/**
 * Direct QuakeBot API Test
 * Tests the actual API handler endpoint
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

// Bypass auth for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;

echo "<h1>Direct QuakeBot API Test</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow:auto;}</style>";

// Test message
$testMessage = "Say hello in one sentence";

echo "<h2>Sending Test Message</h2>";
echo "<p><strong>Message:</strong> " . htmlspecialchars($testMessage) . "</p>";

// Call the API handler
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/quakebot_handler.php';
echo "<p><strong>API URL:</strong> " . htmlspecialchars($url) . "</p>";

$data = json_encode(['message' => $testMessage]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h2>Response</h2>";
echo "<p><strong>HTTP Code:</strong> " . $httpCode . "</p>";

if ($curlError) {
    echo "<p class='error'><strong>cURL Error:</strong> " . htmlspecialchars($curlError) . "</p>";
} else {
    echo "<p class='success'>✅ Request successful</p>";
}

echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($response) {
    $json = json_decode($response, true);
    if ($json) {
        echo "<h3>Parsed JSON:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($json, true)) . "</pre>";
        
        if (isset($json['success']) && $json['success']) {
            echo "<h2 class='success'>✅ QuakeBot Response:</h2>";
            echo "<div style='background:#e8f5e9;padding:15px;border-radius:5px;border-left:4px solid #4caf50;'>";
            echo nl2br(htmlspecialchars($json['message']));
            echo "</div>";
        } else {
            echo "<h2 class='error'>❌ Error:</h2>";
            echo "<div style='background:#ffebee;padding:15px;border-radius:5px;border-left:4px solid #f44336;'>";
            echo htmlspecialchars($json['message'] ?? 'Unknown error');
            echo "</div>";
        }
    }
}

echo "<hr>";
echo "<p><a href='test_quakebot.php'>← Back to Connection Test</a> | <a href='quakebot.php'>Go to QuakeBot</a></p>";
?>
