<?php
/**
 * Data Receiver Endpoint
 * Receives seismic data from ESP32 and triggers alerts
 *
 * IMPORTANT: SMS sending is deferred until AFTER the HTTP response is sent
 * to the ESP32, using fastcgi_finish_request(). This prevents the SMS
 * broadcast (which can take 20-200 seconds for many recipients) from
 * blocking the ESP32's loop() and freezing sensor readings.
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
            $should_send_sms = IntensityCalculator::shouldSendSMS($intensity);

            // Respond to ESP32 IMMEDIATELY — before sending any SMS.
            // The ESP32 is blocking on this response (up to 8s timeout),
            // and sending SMS to many recipients can take 20-200 seconds.
            // If we don't flush now, the ESP32 times out and its loop()
            // freezes, missing sensor readings during the earthquake.
            echo json_encode([
                'status' => 'success',
                'log_id' => $log_id,
                'intensity' => $intensity,
                'magnitude' => $magnitude,
                'percent_g' => round($percent_g, 2),
                'mmi_level' => $mmi['level'],
                'mmi_name' => $mmi['name'],
                'alarm_level' => $mmi['alarm_level'],
                'alert_sent' => $should_send_sms ? 'pending' : false
            ]);

            // Flush the response to the client NOW so the ESP32 can
            // continue its loop. On Apache mod_php this is a no-op
            // (response flushes when the script ends), but on PHP-FPM
            // / Render / Railway (which use fastcgi) this releases the
            // request immediately while the script keeps running.
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            // ── SMS sending happens AFTER the response is flushed ──
            // The ESP32 has already received its JSON response and is
            // back to reading the sensor. SMS broadcast runs here in
            // the background from the server's perspective.
            if ($should_send_sms) {
                // Cooldown: skip SMS if one was sent in the last 60 seconds
                // for this device. Prevents 15 SMS blasts during one
                // 30-second earthquake (ESP32 sends every 2s).
                $cooldown_check = $conn->query("SELECT id FROM sms_logs
                    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
                    LIMIT 1");

                if ($cooldown_check && $cooldown_check->num_rows == 0) {
                    $alert_sent = sendBulkSMSAlert($conn, $log_id, $intensity, $mmi);

                    // Update alert status
                    $update_stmt = $conn->prepare("UPDATE seismic_logs SET alert_sent = ? WHERE id = ?");
                    $update_stmt->bind_param("ii", $alert_sent, $log_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    error_log("SMS cooldown active — skipping SMS for log_id=$log_id (intensity=$intensity Gal)");
                }
            }
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
