<?php
/**
 * UniSMS Authentication Method Tester
 * Tries different auth methods to find which one works
 */

require_once 'config/database.php';

$apiKey = SMS_API_KEY;
$apiUrl = SMS_API_URL;
$testPhone = '+639123456789'; // Change to your actual test number

echo "<h1>UniSMS Authentication Tester</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

echo "<p><strong>API Key:</strong> " . substr($apiKey, 0, 10) . "...</p>";
echo "<p><strong>API URL:</strong> $apiUrl</p>";
echo "<p><strong>Test Phone:</strong> $testPhone</p>";
echo "<hr>";

$testMessage = "Test from ND-SCPM";
$data = json_encode([
    'recipient' => $testPhone,
    'content' => $testMessage
]);

// ══════════════════════════════════════════════════════════════════════════════
// Method 1: Bearer Token (Authorization: Bearer sk_xxx)
// ══════════════════════════════════════════════════════════════════════════════
echo "<h2>Method 1: Bearer Token</h2>";
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200 || $httpCode == 201) {
    echo "<p class='success'>✅ SUCCESS! Use Method 1 (Bearer Token)</p>";
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// Method 2: API Key Header (X-API-Key: sk_xxx)
// ══════════════════════════════════════════════════════════════════════════════
echo "<hr><h2>Method 2: X-API-Key Header</h2>";
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200 || $httpCode == 201) {
    echo "<p class='success'>✅ SUCCESS! Use Method 2 (X-API-Key)</p>";
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// Method 3: Basic Auth (username: apikey, password: empty)
// ══════════════════════════════════════════════════════════════════════════════
echo "<hr><h2>Method 3: Basic Auth (API Key as Username)</h2>";
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_USERPWD => $apiKey . ':',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200 || $httpCode == 201) {
    echo "<p class='success'>✅ SUCCESS! Use Method 3 (Basic Auth)</p>";
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// Method 4: API Key in JSON Body
// ══════════════════════════════════════════════════════════════════════════════
echo "<hr><h2>Method 4: API Key in JSON Body</h2>";
$dataWithKey = json_encode([
    'api_key' => $apiKey,
    'recipient' => $testPhone,
    'content' => $testMessage
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $dataWithKey,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200 || $httpCode == 201) {
    echo "<p class='success'>✅ SUCCESS! Use Method 4 (API Key in Body)</p>";
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// Method 5: URL Query Parameter
// ══════════════════════════════════════════════════════════════════════════════
echo "<hr><h2>Method 5: API Key as URL Parameter</h2>";
$urlWithKey = $apiUrl . '?api_key=' . urlencode($apiKey);

$ch = curl_init($urlWithKey);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200 || $httpCode == 201) {
    echo "<p class='success'>✅ SUCCESS! Use Method 5 (URL Parameter)</p>";
    exit;
}

echo "<hr><p class='error'>❌ None of the authentication methods worked!</p>";
echo "<p>Please check:</p>";
echo "<ul>";
echo "<li>Is your API key correct and active?</li>";
echo "<li>Do you have sufficient credits in your UniSMS account?</li>";
echo "<li>Is the API URL correct? ($apiUrl)</li>";
echo "<li>Contact UniSMS support for the correct authentication method</li>";
echo "</ul>";
?>
