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

require_once __DIR__ . '/intensity_calculator.php';

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
    
    // Calculate intensity metrics
    $magnitude = IntensityCalculator::estimateMagnitude($intensity);
    $percent_g = IntensityCalculator::galToPercentG($intensity);
    
    // Build alert message (simplified to avoid spam filter)
    $intensity_text = number_format($intensity, 1);
    $datetime = date('M j g:iA');
    
    $message = "NDSCPM Alert: Mag {$magnitude}, {$intensity_text} Gal. {$datetime}. Move to open area.";
    
    $success_count = 0;
    
    while ($recipient = $result->fetch_assoc()) {
        $phone = $recipient['phone_number'];
        
        // Send SMS via UniSMS API
        $sms_result = sendSMS($phone, $message);
        
        // Log SMS attempt with error details
        $status = $sms_result['success'] ? 'sent' : 'failed';
        $error_message = $sms_result['success'] ? '' : ($sms_result['message'] ?? 'Unknown error');
        $log_stmt = $conn->prepare("INSERT INTO sms_logs (log_id, recipient_id, phone_number, message, status) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("iisss", $log_id, $recipient['id'], $phone, $message, $status);
        $log_stmt->execute();
        $log_stmt->close();
        
        // Log detailed error to server error log
        if (!$sms_result['success']) {
            error_log("SMS Failed - Phone: $phone, Error: $error_message, HTTP Code: " . ($sms_result['http_code'] ?? 'N/A'));
        }
        
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
    
    // Prepare request data (UniSMS requires recipient, content, and sender_id)
    $senderId = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'Unisoft';
    $data = json_encode([
        'recipient' => $phone,
        'content' => $message,
        'sender_id' => $senderId
    ]);
    
    // Initialize cURL
    $ch = curl_init($apiUrl);
    
    // Set cURL options for UniSMS API
    // Uses Basic Authentication: API key as username, empty password
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
        CURLOPT_SSL_VERIFYPEER => false,  // Disable for testing (enable in production)
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Debug logging
    error_log("UniSMS Request: URL=$apiUrl, Phone=$phone, HTTP=$http_code");
    error_log("UniSMS Response: " . substr($response, 0, 500));
    
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
        error_log("UniSMS: SMS sent successfully to $phone");
        return [
            'success' => true,
            'message' => 'SMS sent successfully',
            'data' => $response_data
        ];
    } else if ($http_code === 401 || $http_code === 403) {
        // Authentication error
        error_log("UniSMS Auth Error (HTTP $http_code): Check your API key");
        return [
            'success' => false,
            'message' => 'Authentication failed - check your API key',
            'http_code' => $http_code
        ];
    } else if ($http_code === 422) {
        // Validation error (spam filter, invalid format, etc.)
        $error_message = 'Validation error';
        if (isset($response_data['errors'])) {
            $errors = [];
            foreach ($response_data['errors'] as $field => $messages) {
                $errors[] = "$field: " . implode(', ', $messages);
            }
            $error_message = implode('; ', $errors);
        }
        error_log("UniSMS Validation Error: " . $error_message);
        return [
            'success' => false,
            'message' => $error_message,
            'http_code' => $http_code
        ];
    } else {
        // Other errors
        $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
        error_log("UniSMS Error (HTTP $http_code): " . $error_message);
        
        return [
            'success' => false,
            'message' => $error_message,
            'http_code' => $http_code,
            'response' => $response
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
    $intensity_gal = 176;
    $magnitude = IntensityCalculator::estimateMagnitude($intensity_gal);
    $intensity_text = number_format($intensity_gal, 1);
    $datetime = date('M j g:iA');
    
    $message = "NDSCPM Alert: Mag {$magnitude}, {$intensity_text} Gal. {$datetime}. Move to open area.";
    
    return sendSMS($phone, $message);
}
?>
