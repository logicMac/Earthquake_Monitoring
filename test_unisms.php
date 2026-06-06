<?php
/**
 * UniSMS API Test Script
 * Use this to test your UniSMS configuration
 */

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/sms_handler.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniSMS Test - ND-SCPM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-4 sm:p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white border-2 border-gray-200 rounded-xl p-6 shadow-lg mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">UniSMS API Test</h1>
            <p class="text-gray-600 mb-4">Test your UniSMS configuration before going live</p>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Note:</strong> Make sure you've updated your API key in <code>.env</code> file
                </p>
            </div>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>⚠️ Test Message Preview:</strong><br>
                    This test sends the actual earthquake alert format:<br><br>
                    <em>"ND-SCPM Earthquake Alert: Est. magnitude 7.5 detected. Ground motion: 176.00 Gal. Intensity: MMI VII (Very Strong). Recorded on [current date/time]. Drop, cover, and hold on. Move to open area if safe."</em><br><br>
                    <strong>Note:</strong> Magnitude is estimated from local ground motion. For official magnitude, check PHIVOLCS reports.
                </p>
            </div>

            <?php
            // Check if API key is configured
            $apiKey = defined('SMS_API_KEY') ? SMS_API_KEY : '';
            $isConfigured = !empty($apiKey) && $apiKey !== 'YOUR_UNISMS_API_KEY';
            
            if (!$isConfigured) {
                echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-sm text-red-800">
                        <strong>❌ API Key Not Configured!</strong><br>
                        Please update <code>SMS_API_KEY</code> in <code>config/database.php</code>
                    </p>
                </div>';
            } else {
                echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-sm text-green-800">
                        <strong>✅ API Key Configured</strong><br>
                        Key: ' . substr($apiKey, 0, 10) . '...' . substr($apiKey, -4) . '
                    </p>
                </div>';
            }
            ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input type="text" name="phone" required 
                        placeholder="09123456789 or +639123456789"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900">
                    <p class="text-xs text-gray-500 mt-1">Enter your phone number to receive a test SMS</p>
                </div>

                <button type="submit" name="test_sms" 
                    class="w-full bg-black text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-800 transition">
                    Send Test SMS
                </button>
            </form>

            <?php
            if (isset($_POST['test_sms']) && isset($_POST['phone'])) {
                $phone = $_POST['phone'];
                
                echo '<div class="mt-6 p-4 bg-gray-50 border-2 border-gray-200 rounded-lg">';
                echo '<h3 class="font-bold text-gray-900 mb-2">Test Results:</h3>';
                
                // Test SMS
                $result = testSMS($phone);
                
                if ($result['success']) {
                    echo '<div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <p class="text-sm text-green-800">
                            <strong>✅ Success!</strong><br>
                            SMS sent to ' . htmlspecialchars($phone) . '<br>
                            Check your phone for the test message.
                        </p>
                    </div>';
                } else {
                    echo '<div class="bg-red-50 border-l-4 border-red-500 p-4">
                        <p class="text-sm text-red-800">
                            <strong>❌ Failed!</strong><br>
                            Error: ' . htmlspecialchars($result['message']) . '<br>';
                    
                    if (isset($result['http_code'])) {
                        echo 'HTTP Code: ' . $result['http_code'] . '<br>';
                    }
                    
                    echo '</p>
                        <p class="text-xs text-red-700 mt-2">
                            <strong>Common Issues:</strong><br>
                            • Invalid API key<br>
                            • Insufficient credits<br>
                            • Invalid phone number format<br>
                            • Network/firewall blocking the request
                        </p>
                    </div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>

        <div class="bg-white border-2 border-gray-200 rounded-xl p-6 shadow-lg">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Setup Instructions</h2>
            
            <div class="space-y-4 text-sm text-gray-700">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">1. Get Your API Key</h3>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>Go to <a href="https://unismsapi.com/" target="_blank" class="text-blue-600 hover:underline">https://unismsapi.com/</a></li>
                        <li>Click "Register Now"</li>
                        <li>Verify your email</li>
                        <li>Login to dashboard</li>
                        <li>Go to "API Keys" section</li>
                        <li>Copy your API key</li>
                    </ol>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">2. Update Configuration</h3>
                    <p>Open <code class="bg-gray-100 px-2 py-1 rounded">.env</code> file and update:</p>
                    <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg mt-2 overflow-x-auto text-xs">SMS_API_KEY=your_actual_api_key_here</pre>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">3. Add Credits</h3>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>Login to UniSMS dashboard</li>
                        <li>Click "Top Up"</li>
                        <li>Choose amount (minimum ₱100)</li>
                        <li>Pay via GCash, PayMaya, or Bank</li>
                    </ol>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">4. Test</h3>
                    <p>Use this page to send a test SMS and verify everything works!</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">5. Troubleshooting Spam Filters</h3>
                    <p class="mb-2">If messages still get blocked with HTTP 422 error:</p>
                    <ul class="list-disc list-inside space-y-1 ml-4 text-xs">
                        <li>Your account might need verification (check UniSMS dashboard)</li>
                        <li>Add credits first - some filters check account balance</li>
                        <li>Contact UniSMS support to whitelist your account for institutional alerts</li>
                        <li>Verify sender name is approved in dashboard settings</li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500">
                <p class="text-sm text-blue-800">
                    <strong>💰 Pricing:</strong> ₱0.38 per SMS (much cheaper than Semaphore!)<br>
                    <strong>📱 Networks:</strong> Globe, Smart, DITO, Sun, TNT<br>
                    <strong>⏰ Expiration:</strong> Credits never expire<br>
                    <strong>📊 Delivery Rate:</strong> 99.9%
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="index.php" class="text-gray-600 hover:text-gray-900 text-sm">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
