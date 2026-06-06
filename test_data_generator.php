<?php
/**
 * Test Data Generator
 * Generates sample earthquake data for testing the dashboard
 * 
 * Usage: http://localhost/client_earthquake/test_data_generator.php?action=generate&count=10
 */

require_once 'config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$count = intval($_GET['count'] ?? 10);

if ($action === 'generate') {
    $conn = getDBConnection();
    $generated = 0;
    
    for ($i = 0; $i < $count; $i++) {
        // Generate random intensity between 10 and 120 Gal
        $intensity = rand(10, 120);
        $device_id = 'ESP32_TEST';
        
        // Random timestamp within last 24 hours
        $timestamp = date('Y-m-d H:i:s', time() - rand(0, 86400));
        
        $stmt = $conn->prepare("INSERT INTO seismic_logs (device_id, intensity, timestamp, alert_sent) VALUES (?, ?, ?, ?)");
        $alert_sent = $intensity >= 80 ? 1 : 0;
        $stmt->bind_param("sdsi", $device_id, $intensity, $timestamp, $alert_sent);
        
        if ($stmt->execute()) {
            $generated++;
        }
        
        $stmt->close();
    }
    
    $conn->close();
    
    echo json_encode([
        'status' => 'success',
        'message' => "Generated $generated test records",
        'count' => $generated
    ]);
    
} elseif ($action === 'clear') {
    $conn = getDBConnection();
    $conn->query("DELETE FROM seismic_logs WHERE device_id = 'ESP32_TEST'");
    $deleted = $conn->affected_rows;
    $conn->close();
    
    echo json_encode([
        'status' => 'success',
        'message' => "Deleted $deleted test records",
        'count' => $deleted
    ]);
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid action. Use ?action=generate&count=10 or ?action=clear',
        'usage' => [
            'generate' => 'test_data_generator.php?action=generate&count=10',
            'clear' => 'test_data_generator.php?action=clear'
        ]
    ]);
}
?>
