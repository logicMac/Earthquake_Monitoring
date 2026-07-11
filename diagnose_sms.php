<?php
/**
 * SMS Diagnostic Script
 * Diagnoses why SMS is not sending
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>SMS Diagnostic Tool</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;} pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;}</style>";

// ── 1. Check API Key Configuration ─────────────────────────────────────────
echo "<h2>1. SMS API Configuration</h2>";
$apiKey = defined('SMS_API_KEY') ? SMS_API_KEY : '';
$apiUrl = defined('SMS_API_URL') ? SMS_API_URL : 'not set';

echo "<p><strong>API URL:</strong> $apiUrl</p>";

if (empty($apiKey)) {
    echo "<p class='error'>❌ SMS_API_KEY is NOT set or empty!</p>";
    echo "<p><strong>Fix:</strong> Set SMS_API_KEY environment variable on Render, or add to .env file locally</p>";
} elseif ($apiKey === 'YOUR_UNISMS_API_KEY' || $apiKey === 'YOUR_SEMAPHORE_API_KEY') {
    echo "<p class='error'>❌ SMS_API_KEY is still set to placeholder value!</p>";
    echo "<p><strong>Fix:</strong> Replace with your actual UniSMS API key</p>";
} else {
    echo "<p class='success'>✅ SMS_API_KEY is configured: " . substr($apiKey, 0, 10) . "..." . substr($apiKey, -4) . "</p>";
}
echo "<hr>";

// ── 2. Check Database Connection ─────────────────────────────────────────────
echo "<h2>2. Database Connection</h2>";
try {
    $conn = getDBConnection();
    echo "<p class='success'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}
echo "<hr>";

// ── 3. Check Active Recipients ────────────────────────────────────────────────
echo "<h2>3. Alert Recipients</h2>";
$result = $conn->query("SELECT COUNT(*) as total FROM alert_recipients");
$total = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as active FROM alert_recipients WHERE is_active = 1");
$active = $result->fetch_assoc()['active'];

echo "<p><strong>Total Recipients:</strong> $total</p>";
echo "<p><strong>Active Recipients:</strong> $active</p>";

if ($active == 0) {
    echo "<p class='error'>❌ No active recipients! SMS cannot be sent.</p>";
    echo "<p><strong>Fix:</strong> Go to Manage Recipients and activate at least one recipient</p>";
} else {
    echo "<p class='success'>✅ $active recipient(s) will receive SMS</p>";
    
    // Show active recipients
    $result = $conn->query("SELECT name, phone_number FROM alert_recipients WHERE is_active = 1");
    echo "<p><strong>Active Recipients:</strong></p><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['name']) . " - " . htmlspecialchars($row['phone_number']) . "</li>";
    }
    echo "</ul>";
}
echo "<hr>";

// ── 4. Check Recent SMS Logs ─────────────────────────────────────────────────
echo "<h2>4. Recent SMS Logs (Last 10)</h2>";
$result = $conn->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 10");
$logs = $result->num_rows;

if ($logs == 0) {
    echo "<p class='warning'>⚠️ No SMS logs found in database</p>";
    echo "<p>This means either:</p>";
    echo "<ul>";
    echo "<li>No earthquake events reached SMS threshold (alarm level 3)</li>";
    echo "<li>SMS function was never called</li>";
    echo "</ul>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Phone</th><th>Status</th><th>Sent At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $statusClass = $row['status'] == 'sent' ? 'success' : 'error';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
        echo "<td class='$statusClass'>{$row['status']}</td>";
        echo "<td>{$row['sent_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count success vs failure
    $result = $conn->query("SELECT status, COUNT(*) as count FROM sms_logs GROUP BY status");
    echo "<p><strong>Summary:</strong></p><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['status']}: {$row['count']}</li>";
    }
    echo "</ul>";
}
echo "<hr>";

// ── 5. Check Recent Seismic Events ────────────────────────────────────────────
echo "<h2>5. Recent Seismic Events (Last 10)</h2>";
$result = $conn->query("SELECT id, intensity, mmi_level, mmi_name, alert_sent, timestamp FROM seismic_logs ORDER BY id DESC LIMIT 10");
$events = $result->num_rows;

if ($events == 0) {
    echo "<p class='warning'>⚠️ No seismic events recorded</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Intensity (Gal)</th><th>MMI</th><th>Alert Sent</th><th>Time</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $alertClass = $row['alert_sent'] == 1 ? 'success' : 'error';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['intensity']}</td>";
        echo "<td>{$row['mmi_level']} ({$row['mmi_name']})</td>";
        echo "<td class='$alertClass'>" . ($row['alert_sent'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$row['timestamp']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "<hr>";

// ── 6. Test SMS API Connection ───────────────────────────────────────────────
echo "<h2>6. SMS API Connection Test</h2>";
if (empty($apiKey) || $apiKey === 'YOUR_UNISMS_API_KEY') {
    echo "<p class='error'>❌ Cannot test API - API key not configured</p>";
} else {
    $testPhone = '+639123456789'; // Test number (won't actually send)
    $testMessage = "Diagnostic test from ND-SCPM";
    
    $senderId = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : '';
    $data = [
        'recipient' => $testPhone,
        'content' => $testMessage
    ];
    if (!empty($senderId)) {
        $data['sender_id'] = $senderId;
    }
    $data = json_encode($data);
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ],
        CURLOPT_USERPWD => $apiKey . ':',
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($curlError) {
        echo "<p class='error'>❌ cURL Error: $curlError</p>";
    } else {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        
        if ($httpCode == 200 || $httpCode == 201) {
            echo "<p class='success'>✅ API connection successful!</p>";
        } elseif ($httpCode == 401 || $httpCode == 403) {
            echo "<p class='error'>❌ Authentication failed - API key is invalid or expired</p>";
        } elseif ($httpCode == 422) {
            echo "<p class='error'>❌ Validation error - Check response above for details</p>";
        } else {
            echo "<p class='error'>❌ API returned error code $httpCode</p>";
        }
    }
}
echo "<hr>";

// ── 7. SMS Threshold Check ─────────────────────────────────────────────────
echo "<h2>7. SMS Threshold Configuration</h2>";
require_once 'includes/intensity_calculator.php';

echo "<p><strong>Current SMS Threshold:</strong> MMI Level VII (Very Strong) or higher</p>";
echo "<p><strong>Equivalent Intensity:</strong> ~176 Gal (18%g)</p>";
echo "<p><strong>Alarm Level:</strong> 3 (Emergency)</p>";

echo "<p><strong>Test Values:</strong></p>";
echo "<ul>";
echo "<li>25 Gal = MMI IV (Light) - Local alert only (no SMS)</li>";
echo "<li>176 Gal = MMI VII (Very Strong) - SMS alert sent</li>";
echo "<li>342 Gal = MMI VIII (Severe) - SMS alert sent</li>";
echo "</ul>";
echo "<hr>";

// ── Summary ─────────────────────────────────────────────────────────────────
echo "<h2>Summary & Recommendations</h2>";
echo "<div style='background:#f0f0f0;padding:15px;border-radius:5px;'>";
echo "<p><strong>Most Common Issues:</strong></p>";
echo "<ol>";
echo "<li><strong>API Key Not Set on Render:</strong> If deployed on Render, check Environment Variables section and add SMS_API_KEY</li>";
echo "<li><strong>API Key Expired:</strong> UniSMS keys may expire. Check your UniSMS dashboard</li>";
echo "<li><strong>No Credits:</strong> Check your UniSMS account balance</li>";
echo "<li><strong>No Active Recipients:</strong> Activate recipients in Manage Recipients page</li>";
echo "<li><strong>Intensity Too Low:</strong> SMS only sends for MMI VII+ (~176 Gal). Lower intensity events won't trigger SMS</li>";
echo "<li><strong>API Changed:</strong> UniSMS might have changed their API. Run test_unisms_auth.php to test different auth methods</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='test_unisms.php'>Test SMS with actual number</a> | <a href='test_unisms_auth.php'>Test different auth methods</a> | <a href='manage_recipients.php'>Manage Recipients</a> | <a href='index.php'>Back to Dashboard</a></p>";

$conn->close();
?>
