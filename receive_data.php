<?php
/**
 * Data Receiver Endpoint
 * Receives seismic data from ESP32 and triggers alerts
 */

require_once 'config/database.php';
require_once 'includes/sms_handler.php';
require_once 'includes/intensity_calculator.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intensity = isset($_POST['intensity']) ? floatval($_POST['intensity']) : 0;
    $device_id = isset($_POST['device_id']) ? $_POST['device_id'] : 'UNKNOWN';
    
    if ($intensity > 0) {
        $conn = getDBConnection();
        
        // Calculate MMI scale and magnitude
        $mmi = IntensityCalculator::getMMIScale($intensity);
        $percent_g = IntensityCalculator::galToPercentG($intensity);
        $magnitude = IntensityCalculator::estimateMagnitude($intensity);
        
        // Insert seismic log with MMI data and magnitude estimate
        $stmt = $conn->prepare("INSERT INTO seismic_logs (device_id, intensity, magnitude, mmi_level, mmi_name, percent_g) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sddssd", $device_id, $intensity, $magnitude, $mmi['level'], $mmi['name'], $percent_g);
        
        if ($stmt->execute()) {
            $log_id = $conn->insert_id;
            $alert_sent = false;
            
            // Check if SMS alert should be sent (Alarm Level 3)
            if (IntensityCalculator::shouldSendSMS($intensity)) {
                $alert_sent = sendBulkSMSAlert($conn, $log_id, $intensity, $mmi);
                
                // Update alert status
                $update_stmt = $conn->prepare("UPDATE seismic_logs SET alert_sent = ? WHERE id = ?");
                $update_stmt->bind_param("ii", $alert_sent, $log_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            echo json_encode([
                'status' => 'success',
                'log_id' => $log_id,
                'intensity' => $intensity,
                'magnitude' => $magnitude,
                'percent_g' => round($percent_g, 2),
                'mmi_level' => $mmi['level'],
                'mmi_name' => $mmi['name'],
                'alarm_level' => $mmi['alarm_level'],
                'alert_sent' => $alert_sent
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database insert failed']);
        }
        
        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid intensity value']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
