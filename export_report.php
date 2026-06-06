<?php
/**
 * Export Report to CSV
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$conn = getDBConnection();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$min_intensity = $_GET['min_intensity'] ?? 0;

// Get events data
$events_query = "SELECT * FROM seismic_logs 
WHERE DATE(timestamp) BETWEEN ? AND ? AND intensity >= ?
ORDER BY timestamp DESC";

$stmt = $conn->prepare($events_query);
$stmt->bind_param("ssd", $date_from, $date_to, $min_intensity);
$stmt->execute();
$events = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="earthquake_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['ID', 'Timestamp', 'Device ID', 'Intensity (Gal)', 'Alert Sent']);

// Write data rows
while ($event = $events->fetch_assoc()) {
    fputcsv($output, [
        $event['id'],
        $event['timestamp'],
        $event['device_id'],
        number_format($event['intensity'], 2),
        $event['alert_sent'] ? 'Yes' : 'No'
    ]);
}

fclose($output);
$stmt->close();
$conn->close();
exit;
?>
