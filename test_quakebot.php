<?php
/**
 * QuakeBot Connection Test
 * Tests if your server can connect to Groq API
 */

require_once 'config/database.php';

echo "<h1>QuakeBot Connection Test</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

// Test 1: Check if cURL is enabled
echo "<h2>Test 1: cURL Extension</h2>";
if (function_exists('curl_init')) {
    echo "<p class='success'>✅ cURL is enabled</p>";
    $curlVersion = curl_version();
    echo "<p class='info'>Version: " . $curlVersion['version'] . "</p>";
    echo "<p class='info'>SSL Version: " . $curlVersion['ssl_version'] . "</p>";
} else {
    echo "<p class='error'>❌ cURL is NOT enabled. Contact your hosting provider.</p>";
    exit;
}

// Test 2: Check API key configuration
echo "<h2>Test 2: API Key Configuration</h2>";
if (defined('GROQ_API_KEY') && GROQ_API_KEY !== 'YOUR_GROQ_API_KEY' && GROQ_API_KEY !== 'gsk_PASTE_YOUR_ACTUAL_API_KEY_HERE' && !empty(GROQ_API_KEY)) {
    echo "<p class='success'>✅ API key is configured</p>";
    echo "<p class='info'>Key starts with: " . substr(GROQ_API_KEY, 0, 7) . "...</p>";
} else {
    echo "<p class='error'>❌ API key not configured. Edit config/database.php</p>";
    echo "<p class='info'>📝 Steps:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='https://console.groq.com/' target='_blank'>https://console.groq.com/</a></li>";
    echo "<li>Sign up for free (no credit card needed)</li>";
    echo "<li>Create an API key (starts with 'gsk_')</li>";
    echo "<li>Open config/database.php</li>";
    echo "<li>Replace <code>gsk_PASTE_YOUR_ACTUAL_API_KEY_HERE</code> with your real key</li>";
    echo "<li>Save and refresh this page</li>";
    echo "</ol>";
    exit;
}

// Test 3: Test basic HTTPS connection
echo "<h2>Test 3: HTTPS Connection Test</h2>";
$testUrl = "https://api.groq.com/openai/v1/models";
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification for compatibility
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // Disable host verification
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . GROQ_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($curlErrno) {
    echo "<p class='error'>❌ Connection failed</p>";
    echo "<p class='error'>Error #" . $curlErrno . ": " . $curlError . "</p>";
    
    if ($curlErrno == 6) {
        echo "<p class='info'>💡 DNS resolution failed. Check your internet connection.</p>";
    } elseif ($curlErrno == 7) {
        echo "<p class='info'>💡 Cannot connect to server. Your hosting may block outbound connections.</p>";
        echo "<p class='info'>🔧 <strong>InfinityFree blocks most external API calls on free hosting.</strong></p>";
        echo "<p class='info'>📌 Solutions:</p>";
        echo "<ul>";
        echo "<li>Upgrade to premium hosting (₱100-300/month)</li>";
        echo "<li>Use a different hosting provider that allows API calls</li>";
        echo "<li>Test on localhost first (XAMPP/WAMP)</li>";
        echo "</ul>";
    } elseif ($curlErrno == 28) {
        echo "<p class='info'>💡 Connection timeout. Server is slow or unreachable.</p>";
    }
    exit;
}

if ($httpCode === 0) {
    echo "<p class='error'>❌ No response from server (HTTP code 0)</p>";
    echo "<p class='error'>🚫 <strong>Your hosting provider blocks outbound HTTPS connections.</strong></p>";
    echo "<p class='info'>This is common on free hosting like InfinityFree.</p>";
    echo "<p class='info'>📌 QuakeBot requires a hosting plan that allows external API calls.</p>";
    exit;
}

echo "<p class='success'>✅ Connection successful (HTTP " . $httpCode . ")</p>";

if ($httpCode === 401) {
    echo "<p class='error'>❌ Invalid API key</p>";
    echo "<p class='info'>Get a new key from: https://console.groq.com/</p>";
    exit;
}

if ($httpCode === 200) {
    echo "<p class='success'>✅ API key is valid!</p>";
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "<p class='info'>Available models: " . count($data['data']) . "</p>";
    }
}

// Test 4: Test actual API call
echo "<h2>Test 4: Test API Call</h2>";
$testData = [
    'model' => GROQ_MODEL,
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Say "Hello from QuakeBot!" in one sentence.'
        ]
    ],
    'temperature' => 0.7,
    'max_tokens' => 50
];

$ch = curl_init(GROQ_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // Disable host verification
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . GROQ_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "<p class='error'>❌ API call failed: " . $curlError . "</p>";
    exit;
}

if ($httpCode !== 200) {
    echo "<p class='error'>❌ API returned HTTP " . $httpCode . "</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    exit;
}

$result = json_decode($response, true);
if (isset($result['choices'][0]['message']['content'])) {
    echo "<p class='success'>✅ API call successful!</p>";
    echo "<p class='info'><strong>Response:</strong> " . htmlspecialchars($result['choices'][0]['message']['content']) . "</p>";
    echo "<h2 style='color:green;'>🎉 QuakeBot is ready to use!</h2>";
} else {
    echo "<p class='error'>❌ Unexpected response format</p>";
    echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests passed, QuakeBot should work. If you see connection errors, your hosting provider likely blocks external API calls.</p>";
echo "<p><a href='quakebot.php'>← Back to QuakeBot</a></p>";
?>
