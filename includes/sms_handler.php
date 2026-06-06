<?php
/**
 * SMS Handler using UniSMS API
 * Documentation: https://unismsapi.com/
 * 
 * UniSMS API Features:
 * - ₱0.38 per SMS (cheaper than Semaphore!)
 * - 99.9% delivery rate
 * - All Philippine networks (Globe, Smart, DITO, Sun, TNT)
 * - No credit expiration
 * - Simple REST API with Basic Auth
 */

/**
 * Send bulk SMS alert to all active recipients
 * 
 * @param mysqli $conn Database connection
 * @param int $log_id Seismic log ID
 * @param float $intensity Earthquake intensity in Gal
 * @param array|null $mmi MMI scale information
 * @return bool True if at least one SMS was sent successfully
 */
function sendBulkSMSAlert($conn, $log_id, $intensity, $mmi = null) {
    // Get all active recipients
    $result = $conn->query("SELECT id, name, phone_number FROM alert_recipients WHERE is_active = 1");
    
    if ($result->num_rows == 0) {
        error_log("UniSMS: No active recipients found");
        return false;
    }
    
    // Prepare message with MMI info if available
    $mmi_info = $mmi ? " (MMI {$mmi['level']} - {$mmi['name']})" : "";
    $message = "🚨 EARTHQUAKE ALERT!\n\n"
             . "Intensity: " . number_format($intensity, 2) . " Gal" . $mmi_info . "\n"
             . "Location: ND-SCPM\n"
             . "Time: " . date('M d, Y h:i A') . "\n\n"
             . "⚠️ DROP, COVER, and HOLD ON!\n"
             . "Proceed to open field if safe.";
    
    $success_count = 0;
    
    while ($recipient = $result->fetch_assoc()) {
        $phone = $recipient['phone_number'];
        
        // Send SMS via UniSMS API
        $sms_result = sendSMS($phone, $message);
        
        // Log SMS attempt
        $status = $sms_result['success'] ? 'sent' : 'failed';
        $log_stmt = $conn->prepare("INSERT INTO sms_logs (log_id, recipient_id, phone_number, message, status) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("iisss", $log_id, $recipient['id'], $phone, $message, $status);
        $log_stmt->execute();
        $log_stmt->close();
        
        if ($sms_result['success']) {
            $success_count++;
        }
        
        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 second delay
    }
    
    error_log("UniSMS: Sent $success_count SMS alerts");
    return $success_count > 0;
}

/**
 * Send SMS using UniSMS API
 * 
 * @param string $phone Phone number (format: +639123456789 or 09123456789)
 * @param string $message SMS content (max 670 characters)
 * @return array Response with 'success' boolean and 'message' string
 */
function sendSMS($phone, $message) {
    // Get API configuration
    $apiKey = defined('SMS_API_KEY') ? SMS_API_KEY : '';
    $apiUrl = defined('SMS_API_URL') ? SMS_API_URL : 'https://unismsapi.com/api/sms';
    
    // Validate API key
    if (empty($apiKey) || $apiKey === 'YOUR_UNISMS_API_KEY') {
        error_log("UniSMS: API key not configured");
        return [
            'success' => false,
            'message' => 'SMS API key not configured'
        ];
    }
    
    // Format phone number (ensure it starts with +63)
    $phone = formatPhoneNumber($phone);
    
    // Prepare request data
    $data = json_encode([
        'recipient' => $phone,
        'content' => $message
    ]);
    
    // Initialize cURL
    $ch = curl_init($apiUrl);
    
    // Set cURL options for UniSMS API
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ],
        CURLOPT_USERPWD => $apiKey . ':', // Basic Auth: API key as username, empty password
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Handle cURL errors
    if ($curl_error) {
        error_log("UniSMS cURL Error: " . $curl_error);
        return [
            'success' => false,
            'message' => 'Network error: ' . $curl_error
        ];
    }
    
    // Parse response
    $response_data = json_decode($response, true);
    
    // Check HTTP status
    if ($http_code === 200 || $http_code === 201) {
        // Success
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => $response_data
        ];
    } else {
        // Failed
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
        error_log("UniSMS Error (HTTP $http_code): " . $error_message);
        
        return [
            'success' => false,
            'message' => $error_message,
            'http_code' => $http_code
        ];
    }
}

/**
 * Format phone number to UniSMS accepted format
 * Accepts: +639123456789, 639123456789, 09123456789
 * Returns: +639123456789
 * 
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function formatPhoneNumber($phone) {
    // Remove spaces, dashes, and parentheses
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // If starts with 0, replace with +63
    if (substr($phone, 0, 1) === '0') {
        $phone = '+63' . substr($phone, 1);
    }
    
    // If starts with 63 (no +), add +
    if (substr($phone, 0, 2) === '63' && substr($phone, 0, 3) !== '+63') {
        $phone = '+' . $phone;
    }
    
    // If doesn't start with +63, add it (assume Philippine number)
    if (substr($phone, 0, 3) !== '+63') {
        $phone = '+63' . $phone;
    }
    
    return $phone;
}

/**
 * Test SMS function - sends a test message
 * Useful for testing your UniSMS API configuration
 * 
 * @param string $phone Phone number to test
 * @return array Test result
 */
function testSMS($phone) {
    $message = "🧪 TEST MESSAGE\n\n"
             . "This is a test from ND-SCPM Earthquake Monitoring System.\n\n"
             . "If you received this, SMS alerts are working correctly!\n\n"
             . "Time: " . date('M d, Y h:i A');
    
    return sendSMS($phone, $message);
}
?>
