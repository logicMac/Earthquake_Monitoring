<?php
/**
 * QuakeBot API Handler
 * Processes user queries and returns AI-powered responses
 * Using Groq API with Llama 3.3 70B model
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get user message
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Log for debugging (remove in production)
error_log("QuakeBot: Received message: " . $userMessage);

// Get earthquake data context
$conn = getDBConnection();
$context = getEarthquakeContext($conn);
$conn->close();

// Build system prompt with context
$systemPrompt = buildSystemPrompt($context);

// Call Groq API
$response = callGroqAPI($systemPrompt, $userMessage);

// Log response for debugging
error_log("QuakeBot: Response - " . json_encode($response));

echo json_encode($response);

/**
 * Get earthquake data context for AI
 */
function getEarthquakeContext($conn) {
    $context = [
        'total_events' => 0,
        'latest_event' => null,
        'high_intensity_count' => 0,
        'recent_events' => [],
        'stats' => []
    ];
    
    // Total events
    $result = $conn->query("SELECT COUNT(*) as total FROM seismic_logs");
    if ($row = $result->fetch_assoc()) {
        $context['total_events'] = $row['total'];
    }
    
    // Latest event
    $result = $conn->query("SELECT * FROM seismic_logs ORDER BY timestamp DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $context['latest_event'] = $row;
    }
    
    // High intensity count (>= 80 Gal)
    $result = $conn->query("SELECT COUNT(*) as count FROM seismic_logs WHERE intensity >= 80");
    if ($row = $result->fetch_assoc()) {
        $context['high_intensity_count'] = $row['count'];
    }
    
    // Recent 10 events
    $result = $conn->query("SELECT * FROM seismic_logs ORDER BY timestamp DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $context['recent_events'][] = $row;
    }
    
    // Statistics
    $result = $conn->query("
        SELECT 
            MAX(intensity) as max_intensity,
            MAX(magnitude) as max_magnitude,
            AVG(intensity) as avg_intensity,
            AVG(magnitude) as avg_magnitude,
            COUNT(CASE WHEN alert_sent = 1 THEN 1 END) as sms_sent,
            COUNT(CASE WHEN intensity >= 176 THEN 1 END) as emergency_events
        FROM seismic_logs
    ");
    if ($row = $result->fetch_assoc()) {
        $context['stats'] = $row;
    }
    
    return $context;
}

/**
 * Build system prompt with earthquake data context
 */
function buildSystemPrompt($context) {
    $latestEvent = $context['latest_event'];
    $stats = $context['stats'];
    
    $latestMagnitude = $latestEvent && isset($latestEvent['magnitude']) ? 
        "M" . number_format($latestEvent['magnitude'], 1) : 
        "N/A";
    
    $latestInfo = $latestEvent ? 
        "Latest: {$latestMagnitude}, {$latestEvent['intensity']} Gal (MMI {$latestEvent['mmi_level']}) at {$latestEvent['timestamp']}" :
        "No events recorded yet";
    
    $maxMagnitude = isset($stats['max_magnitude']) && $stats['max_magnitude'] ? 
        number_format($stats['max_magnitude'], 1) : 
        'N/A';
    
    $avgMagnitude = isset($stats['avg_magnitude']) && $stats['avg_magnitude'] ? 
        number_format($stats['avg_magnitude'], 1) : 
        'N/A';
    
    return "You are QuakeBot, a friendly AI assistant for the ND-SCPM Earthquake Monitoring System. 
You help users understand seismic data, answer questions about earthquakes, and provide insights.

CURRENT SYSTEM DATA:
- Total Events Recorded: {$context['total_events']}
- {$latestInfo}
- High Intensity Events (≥80 Gal): {$context['high_intensity_count']}
- Maximum Magnitude: {$maxMagnitude} (estimated)
- Average Magnitude: {$avgMagnitude} (estimated)
- Maximum Intensity: " . ($stats['max_intensity'] ?? 'N/A') . " Gal
- Average Intensity: " . (isset($stats['avg_intensity']) ? round($stats['avg_intensity'], 2) : 'N/A') . " Gal
- SMS Alerts Sent: " . ($stats['sms_sent'] ?? 0) . "
- Emergency Events (≥176 Gal): " . ($stats['emergency_events'] ?? 0) . "

RECENT EVENTS (Last 10):
" . formatRecentEvents($context['recent_events']) . "

KNOWLEDGE BASE:

**Magnitude vs Intensity:**
- Magnitude: Measures earthquake size at the source (e.g., M7.5). Our system ESTIMATES magnitude from local ground motion using formula M ≈ 2/3 × MMI + 1. This is an approximation - actual magnitude requires data from multiple seismic stations.
- Intensity (Gal): Measures ground shaking strength at our location (Peak Ground Acceleration). 1 Gal = 1 cm/s².

**MMI Scale (Modified Mercalli Intensity):**
- I: Not felt
- II-III: Weak (felt by few)
- IV: Light (dishes disturbed)
- V: Moderate (felt by all)
- VI: Strong (furniture moved)
- VII: Very Strong (building damage)
- VIII: Severe (considerable damage)
- IX: Violent (buildings collapse)
- X+: Extreme (mass destruction)

**Alert Thresholds:**
- Level-1 (Monitor): 25-80 Gal - Local monitoring, no SMS
- Level-2 (Alert): 80-176 Gal - High intensity, buzzer + LCD only
- Level-3 (Emergency): ≥176 Gal - SMS alerts sent to all recipients

**Conversion Reference:**
- 25 Gal ≈ 2.6%g ≈ M4.3 ≈ MMI IV (Light)
- 80 Gal ≈ 8.2%g ≈ M5.7 ≈ MMI V-VI (Moderate-Strong)
- 176 Gal ≈ 18%g ≈ M7.5 ≈ MMI VII (Very Strong)

**System Details:**
- Hardware: ESP32 microcontroller + MPU6050 accelerometer
- Location: Notre Dame - Siena College of Polomolok
- SMS Provider: UniSMS (₱0.38 per message)
- Data Updates: Real-time (every 2 seconds)

**Important Notes:**
- Our magnitude estimates are approximate and best for nearby earthquakes
- For official earthquake magnitude, refer to PHIVOLCS (Philippine Institute of Volcanology and Seismology)
- We measure local ground shaking, not the earthquake's true size

RESPONSE GUIDELINES:
- Be friendly, helpful, and educational
- Use simple language for non-technical users
- Provide specific data when asked (include magnitude when relevant)
- Explain the difference between magnitude and intensity if asked
- Clarify that magnitude is estimated, not measured directly
- Suggest checking PHIVOLCS for official magnitude data
- Keep responses concise (2-4 sentences unless detailed explanation needed)
- Use emojis sparingly for friendliness 🌍⚡📊

Answer the user's question based on this context.";
}

/**
 * Format recent events for context
 */
function formatRecentEvents($events) {
    if (empty($events)) {
        return "No recent events";
    }
    
    $formatted = [];
    foreach ($events as $event) {
        $magnitude = isset($event['magnitude']) && $event['magnitude'] ? 
            "M" . number_format($event['magnitude'], 1) : 
            "M?";
        $formatted[] = "- {$event['timestamp']}: {$magnitude}, {$event['intensity']} Gal (MMI {$event['mmi_level']}) - Alert: " . 
                      ($event['alert_sent'] ? 'Yes' : 'No');
    }
    
    return implode("\n", $formatted);
}

/**
 * Call Groq API
 */
function callGroqAPI($systemPrompt, $userMessage) {
    // Prefer env var (supports .env via environment configuration / php-fpm / WAMP)
    $apiKey = getenv('GROQ_API_KEY');

    // Fallback to constant defined in config/database.php (if env vars are not set)
    if ($apiKey === false || $apiKey === null || trim($apiKey) === '') {
        $apiKey = GROQ_API_KEY;
    }

    if ($apiKey === false || $apiKey === null || trim($apiKey) === '') {
        return [
            'success' => false,
            'message' => "⚙️ QuakeBot is not configured yet. Set GROQ_API_KEY in your server environment (or .env loader) with your key from https://console.groq.com/."
        ];
    }
    
    $data = [
        'model' => GROQ_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500,
        'top_p' => 1,
        'stream' => false
    ];
    
    $jsonData = json_encode($data);
    
    // Check if cURL is available
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'cURL is not enabled on this server. Please contact your hosting provider.'
        ];
    }
    
    $ch = curl_init(GROQ_API_URL);
    
    // Enhanced cURL options for InfinityFree compatibility
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    // Additional options for compatibility
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification for hosting compatibility
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // Disable host verification
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ND-SCPM-QuakeBot/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    
    // Log for debugging
    error_log("QuakeBot API Call - HTTP Code: $httpCode, cURL Error: $curlErrno - $curlError");
    
    curl_close($ch);
    
    // Detailed error handling
    if ($curlErrno) {
        $errorMessages = [
            6 => 'Could not resolve host. DNS issue or no internet connection.',
            7 => 'Failed to connect to server. Server may be down or firewall blocking.',
            28 => 'Connection timeout. Server took too long to respond.',
            35 => 'SSL connection error. Certificate issue.',
            51 => 'SSL certificate verification failed.',
            52 => 'Empty response from server.',
            60 => 'SSL certificate problem. Verify certificate is valid.'
        ];
        
        $errorMsg = isset($errorMessages[$curlErrno]) ? 
            $errorMessages[$curlErrno] : 
            'cURL error #' . $curlErrno . ': ' . $curlError;
        
        return [
            'success' => false,
            'message' => '🔌 Connection Error: ' . $errorMsg . ' (Your hosting may block external API calls)'
        ];
    }
    
    if ($httpCode === 0) {
        return [
            'success' => false,
            'message' => '🚫 Cannot reach Groq API. Your hosting provider (InfinityFree) may block outbound HTTPS connections. This is a common limitation on free hosting.'
        ];
    }
    
    if ($httpCode === 401) {
        return [
            'success' => false,
            'message' => '🔑 Invalid API key. Please check your Groq API key in config/database.php'
        ];
    }
    
    if ($httpCode === 429) {
        return [
            'success' => false,
            'message' => '⏱️ Rate limit exceeded. Please wait a moment and try again.'
        ];
    }
    
    if ($httpCode !== 200) {
        $errorDetail = $response ? ' Response: ' . substr($response, 0, 200) : '';
        return [
            'success' => false,
            'message' => '⚠️ API error (HTTP ' . $httpCode . ').' . $errorDetail
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => '📄 Invalid JSON response from API: ' . json_last_error_msg()
        ];
    }
    
    if (isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'message' => $result['choices'][0]['message']['content']
        ];
    }
    
    if (isset($result['error'])) {
        return [
            'success' => false,
            'message' => '❌ API Error: ' . ($result['error']['message'] ?? 'Unknown error')
        ];
    }
    
    return [
        'success' => false,
        'message' => '🤔 Unexpected API response format. Please try again.'
    ];
}
?>
