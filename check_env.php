<?php
/**
 * Environment Variables Checker
 * Shows all environment variables currently set
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Environment Variables Check</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;} pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>";

echo "<h2>Current Environment Variables</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

$env_vars = [
    'DB_HOST' => 'Database Host',
    'DB_USER' => 'Database User', 
    'DB_PASS' => 'Database Password',
    'DB_NAME' => 'Database Name',
    'SMS_API_URL' => 'SMS API URL',
    'SMS_API_KEY' => 'SMS API Key',
    'SMS_SENDER_NAME' => 'SMS Sender Name',
    'GROQ_API_URL' => 'Groq API URL',
    'GROQ_API_KEY' => 'Groq API Key',
    'GROQ_MODEL' => 'Groq Model'
];

foreach ($env_vars as $key => $description) {
    $value = getenv($key);
    $display_value = $value;
    $status = '';
    
    if (empty($value)) {
        $status = '<span class="error">❌ NOT SET</span>';
        $display_value = '(empty)';
    } elseif (strpos($key, 'KEY') !== false || strpos($key, 'PASS') !== false) {
        // Mask sensitive values
        $display_value = substr($value, 0, 4) . '...' . substr($value, -4);
        if ($value === 'YOUR_UNISMS_API_KEY' || $value === 'YOUR_SEMAPHORE_API_KEY' || $value === 'YOUR_GROQ_API_KEY') {
            $status = '<span class="error">❌ PLACEHOLDER</span>';
        } else {
            $status = '<span class="success">✅ Set</span>';
        }
    } else {
        $status = '<span class="success">✅ Set</span>';
    }
    
    echo "<tr>";
    echo "<td><strong>$key</strong><br><small>$description</small></td>";
    echo "<td>" . htmlspecialchars($display_value) . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

echo "<h2>Database Connection Test</h2>";
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    echo "<p class='success'>✅ Database connected successfully</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>User:</strong> " . DB_USER . "</p>";
    $conn->close();
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h2>Render-Specific Variables</h2>";
$render_vars = ['RENDER', 'RENDER_SERVICE_ID', 'RENDER_EXTERNAL_URL', 'RENDER_EXTERNAL_HOSTNAME', 'PORT'];
echo "<table>";
echo "<tr><th>Variable</th><th>Value</th></tr>";

foreach ($render_vars as $var) {
    $value = getenv($var);
    echo "<tr>";
    echo "<td>$var</td>";
    echo "<td>" . ($value ? htmlspecialchars($value) : '<span class="warning">Not set</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

echo "<h2>Recommendations</h2>";
echo "<div style='background:#f0f0f0;padding:15px;border-radius:5px;'>";

$sms_key_set = !empty(getenv('SMS_API_KEY')) && getenv('SMS_API_KEY') !== 'YOUR_UNISMS_API_KEY';
$db_host_set = !empty(getenv('DB_HOST'));

if (!$sms_key_set) {
    echo "<p class='error'><strong>❌ SMS_API_KEY is not set on Render!</strong></p>";
    echo "<p>To fix this:</p>";
    echo "<ol>";
    echo "<li>Go to your Render dashboard</li>";
    echo "<li>Open your service</li>";
    echo "<li>Go to 'Environment Variables' section</li>";
    echo "<li>Add: <strong>SMS_API_KEY = your_actual_unisms_api_key</strong></li>";
    echo "<li>Click 'Save Changes' and wait for redeploy</li>";
    echo "</ol>";
} else {
    echo "<p class='success'><strong>✅ SMS_API_KEY is configured</strong></p>";
}

if (!$db_host_set) {
    echo "<p class='warning'><strong>⚠️ DB_HOST is not set - using local fallback</strong></p>";
    echo "<p>Your app is connecting to local database instead of cloud database.</p>";
} else {
    echo "<p class='success'><strong>✅ DB_HOST is configured</strong></p>";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='diagnose_sms.php'>Run SMS Diagnostics</a> | <a href='index.php'>Back to Dashboard</a></p>";
?>
