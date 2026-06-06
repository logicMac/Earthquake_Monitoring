<?php
/**
 * API Endpoint - Get Seismic Data
 */
require_once '../config/database.php';
require_once '../includes/intensity_calculator.php';

header('Content-Type: application/json');

$conn = getDBConnection();

// Get latest reading
$latest_result = $conn->query("SELECT * FROM seismic_logs ORDER BY timestamp DESC LIMIT 1");
$latest = $latest_result->fetch_assoc();

// Calculate MMI if not already stored
if ($latest && !$latest['mmi_level']) {
    $mmi = IntensityCalculator::getMMIScale($latest['intensity']);
    $latest['mmi_level'] = $mmi['level'];
    $latest['mmi_name'] = $mmi['name'];
    $latest['percent_g'] = IntensityCalculator::galToPercentG($latest['intensity']);
}

// Get recent readings (last 20)
$recent = [];
$result = $conn->query("SELECT * FROM seismic_logs ORDER BY timestamp DESC LIMIT 20");
while ($row = $result->fetch_assoc()) {
    // Calculate MMI if not stored
    if (!$row['mmi_level']) {
        $mmi = IntensityCalculator::getMMIScale($row['intensity']);
        $row['mmi_level'] = $mmi['level'];
        $row['mmi_name'] = $mmi['name'];
        $row['percent_g'] = IntensityCalculator::galToPercentG($row['intensity']);
    }
    $recent[] = $row;
}
$recent = array_reverse($recent);

echo json_encode([
    'latest' => $latest,
    'recent' => $recent
]);

$conn->close();
?>
