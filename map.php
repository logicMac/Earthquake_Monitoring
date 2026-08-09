<?php
/**
 * Earthquake Map - Public Page
 * Shows all recorded seismic events on a Leaflet map centered at the campus sensor.
 */
require_once 'config/database.php';
require_once 'includes/intensity_calculator.php';

// Sensor / campus location (single ESP32 device)
$sensorLat = 6.224291605598504;
$sensorLng = 125.05919392253091;

$conn = getDBConnection();

// Fetch all seismic events (newest first)
$events = [];
$result = $conn->query("SELECT * FROM seismic_logs ORDER BY timestamp DESC");
while ($row = $result->fetch_assoc()) {
    // Compute MMI if not stored
    if (!isset($row['mmi_level']) || !$row['mmi_level']) {
        $mmi = IntensityCalculator::getMMIScale($row['intensity']);
        $row['mmi_level'] = $mmi['level'];
        $row['mmi_name'] = $mmi['name'];
        $row['mmi_color'] = $mmi['color'];
    }
    $row['percent_g'] = $row['percent_g'] ?? IntensityCalculator::galToPercentG($row['intensity']);
    $events[] = $row;
}
$conn->close();

// Pass to JS safely
$eventsJson = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$sensorLatJson = json_encode($sensorLat);
$sensorLngJson = json_encode($sensorLng);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earthquake Map - ND-SCPM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/animations.css">
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme-toggle.js"></script>
    <script src="assets/smooth-scroll.js"></script>
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        #map { height: calc(100vh - 80px); width: 100%; border-radius: 1rem; }
        .legend {
            background: rgba(255,255,255,0.95);
            padding: 12px 14px;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            font-size: 13px;
            line-height: 1.6;
        }
        [data-theme="dark"] .legend {
            background: rgba(30,41,59,0.95);
            color: #e2e8f0;
        }
        .legend i {
            width: 14px; height: 14px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
            opacity: 0.85;
            border: 1px solid rgba(0,0,0,0.2);
        }
        .leaflet-popup-content { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen theme-bg-primary">
    <!-- Header -->
    <nav class="shadow-sm no-print animate-fade-in-down">
        <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 logo-icon rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold theme-text-primary">ND-SCPM</h1>
                        <p class="text-xs theme-text-tertiary">Earthquake Map</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="login.php" class="theme-btn-primary px-4 py-2 rounded-lg font-semibold text-sm transition">
                        Login
                    </a>
                    <button onclick="toggleTheme()" class="theme-toggle" title="Toggle Dark/Light Mode">
                        <svg id="sunIcon" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Map container -->
    <main class="container mx-auto px-4 sm:px-6 py-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold theme-text-primary">Seismic Events Map</h2>
                <p class="text-sm theme-text-tertiary">
                    Showing <span id="eventCount"><?php echo count($events); ?></span> recorded event(s) at the campus sensor.
                </p>
            </div>
        </div>
        <div id="map" class="shadow-lg"></div>
    </main>

    <script>
        const SENSOR_LAT = <?php echo $sensorLatJson; ?>;
        const SENSOR_LNG = <?php echo $sensorLngJson; ?>;
        const EVENTS = <?php echo $eventsJson; ?>;

        // Color scale by magnitude
        function magnitudeColor(mag) {
            if (mag === null || mag === undefined) return '#9ca3af'; // gray - no magnitude
            if (mag >= 7.0) return '#7f1d1d'; // dark red
            if (mag >= 6.0) return '#dc2626'; // red
            if (mag >= 5.0) return '#ea580c'; // orange
            if (mag >= 4.0) return '#f59e0b'; // amber
            if (mag >= 3.0) return '#eab308'; // yellow
            return '#22c55e'; // green - minor
        }

        // Marker radius by magnitude (min 8, scale up)
        function magnitudeRadius(mag) {
            if (mag === null || mag === undefined) return 8;
            return Math.max(8, Math.min(40, mag * 4));
        }

        // MMI color fallback (matches intensity_calculator.php)
        const MMI_COLORS = {
            'gray':   '#6b7280',
            'blue':   '#3b82f6',
            'cyan':   '#06b6d4',
            'green':  '#22c55e',
            'yellow': '#eab308',
            'orange': '#f97316',
            'red':    '#dc2626',
            'darkred':'#7f1d1d'
        };

        const map = L.map('map', { zoomControl: true }).setView([SENSOR_LAT, SENSOR_LNG], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Sensor marker (the campus device)
        const sensorIcon = L.divIcon({
            html: '<div style="font-size:28px;">📡</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            className: ''
        });
        L.marker([SENSOR_LAT, SENSOR_LNG], { icon: sensorIcon })
            .addTo(map)
            .bindPopup('<b>Campus Sensor</b><br>ESP32 + MPU6050<br><span class="text-xs">All events recorded here</span>');

        // Plot each event. Since all events come from the same device,
        // apply a small deterministic jitter based on the event id so markers
        // spread out around the sensor instead of perfectly overlapping.
        const markers = [];
        EVENTS.forEach(function(ev) {
            const id = parseInt(ev.id, 10) || 0;
            // Deterministic jitter within ~0.003 degrees (~300m)
            const seed = (id * 9301 + 49297) % 233280;
            const jitterLat = ((seed % 1000) / 1000 - 0.5) * 0.006;
            const jitterLng = (((seed * 7) % 1000) / 1000 - 0.5) * 0.006;
            const lat = SENSOR_LAT + jitterLat;
            const lng = SENSOR_LNG + jitterLng;

            const mag = ev.magnitude !== null && ev.magnitude !== undefined ? parseFloat(ev.magnitude) : null;
            const color = magnitudeColor(mag);
            const radius = magnitudeRadius(mag);

            const mmiColor = MMI_COLORS[ev.mmi_color] || '#9ca3af';

            const marker = L.circleMarker([lat, lng], {
                radius: radius,
                color: color,
                fillColor: color,
                fillOpacity: 0.6,
                weight: 2
            }).addTo(map);

            const magText = mag !== null ? mag.toFixed(1) : 'N/A';
            const ts = ev.timestamp || 'Unknown time';
            const alertText = ev.alert_sent == 1 ? '<span style="color:#16a34a;">Yes</span>' : '<span style="color:#6b7280;">No</span>';

            marker.bindPopup(
                '<div style="min-width:180px;">' +
                '<div style="font-weight:700;font-size:15px;margin-bottom:4px;">Event #' + ev.id + '</div>' +
                '<div style="font-size:12px;color:#6b7280;margin-bottom:8px;">' + ts + '</div>' +
                '<table style="font-size:13px;width:100%;">' +
                '<tr><td style="padding:2px 0;">Magnitude</td><td style="text-align:right;font-weight:700;color:' + color + ';">' + magText + '</td></tr>' +
                '<tr><td style="padding:2px 0;">Intensity</td><td style="text-align:right;">' + parseFloat(ev.intensity).toFixed(2) + ' Gal</td></tr>' +
                '<tr><td style="padding:2px 0;">%g</td><td style="text-align:right;">' + parseFloat(ev.percent_g).toFixed(2) + '%</td></tr>' +
                '<tr><td style="padding:2px 0;">MMI</td><td style="text-align:right;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + mmiColor + ';margin-right:4px;vertical-align:middle;"></span>' + (ev.mmi_level || 'N/A') + ' - ' + (ev.mmi_name || '') + '</td></tr>' +
                '<tr><td style="padding:2px 0;">Device</td><td style="text-align:right;font-family:monospace;font-size:11px;">' + ev.device_id + '</td></tr>' +
                '<tr><td style="padding:2px 0;">Alert Sent</td><td style="text-align:right;">' + alertText + '</td></tr>' +
                '</table>' +
                '<div style="font-size:10px;color:#94a3b8;margin-top:6px;font-style:italic;">Plotted near sensor (single device)</div>' +
                '</div>'
            );
            markers.push(marker);
        });

        // Legend
        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function() {
            const div = L.DomUtil.create('div', 'legend');
            div.innerHTML =
                '<div style="font-weight:700;margin-bottom:4px;">Magnitude</div>' +
                '<div><i style="background:#22c55e;"></i> &lt; 3.0 (Minor)</div>' +
                '<div><i style="background:#eab308;"></i> 3.0 - 3.9</div>' +
                '<div><i style="background:#f59e0b;"></i> 4.0 - 4.9</div>' +
                '<div><i style="background:#ea580c;"></i> 5.0 - 5.9</div>' +
                '<div><i style="background:#dc2626;"></i> 6.0 - 6.9</div>' +
                '<div><i style="background:#7f1d1d;"></i> &ge; 7.0 (Major)</div>' +
                '<div><i style="background:#9ca3af;"></i> No magnitude</div>' +
                '<div style="margin-top:6px;font-size:11px;color:#6b7280;">📡 = Campus sensor</div>';
            return div;
        };
        legend.addTo(map);

        // Fit bounds to all markers if there are events, otherwise stay on sensor
        if (markers.length > 0) {
            const group = L.featureGroup(markers).addLayer(L.marker([SENSOR_LAT, SENSOR_LNG]));
            map.fitBounds(group.getBounds(), { padding: [40, 40] });
        }
    </script>
</body>
</html>
