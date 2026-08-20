<?php
/**
 * Reports & Analytics
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$activePage = 'reports';

$conn = getDBConnection();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$min_intensity = $_GET['min_intensity'] ?? 0;

// ── Pagination ──────────────────────────────────────────────────────
$per_page = 15; // events per page
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_events,
    MAX(intensity) as max_intensity,
    MAX(magnitude) as max_magnitude,
    AVG(intensity) as avg_intensity,
    AVG(magnitude) as avg_magnitude,
    SUM(CASE WHEN alert_sent = 1 THEN 1 ELSE 0 END) as alerts_sent,
    SUM(CASE WHEN intensity >= 80 THEN 1 ELSE 0 END) as high_intensity_events
FROM seismic_logs 
WHERE DATE(timestamp) BETWEEN ? AND ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Count total matching events (for pagination)
$count_query = "SELECT COUNT(*) as total FROM seismic_logs 
WHERE DATE(timestamp) BETWEEN ? AND ? AND intensity >= ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("ssd", $date_from, $date_to, $min_intensity);
$stmt->execute();
$total_events = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_pages = max(1, ceil($total_events / $per_page));
// Clamp page if out of range
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

// Get events data (paginated)
$events_query = "SELECT * FROM seismic_logs 
WHERE DATE(timestamp) BETWEEN ? AND ? AND intensity >= ?
ORDER BY timestamp DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($events_query);
$stmt->bind_param("ssdii", $date_from, $date_to, $min_intensity, $per_page, $offset);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

// Get SMS logs count
$sms_count = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE DATE(sent_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];

// ── Intensity classification helper ─────────────────────────────────
function intensityClass($gal) {
    if ($gal < 14)  return ['text' => 'intensity-safe',     'bar' => 'bar-safe',     'label' => 'Safe'];
    if ($gal < 38)  return ['text' => 'intensity-light',    'bar' => 'bar-light',    'label' => 'Light'];
    if ($gal < 90)  return ['text' => 'intensity-moderate', 'bar' => 'bar-moderate', 'label' => 'Moderate'];
    if ($gal < 180) return ['text' => 'intensity-strong',   'bar' => 'bar-strong',   'label' => 'Strong'];
    if ($gal < 330) return ['text' => 'intensity-very',     'bar' => 'bar-very',     'label' => 'Very Strong'];
    return ['text' => 'intensity-severe', 'bar' => 'bar-severe', 'label' => 'Severe'];
}

// Determine active preset
$presetRange = (strtotime($date_to) - strtotime($date_from)) / 86400;
$activePreset = '';
if ($date_to === date('Y-m-d')) {
    if ($presetRange == 0) $activePreset = 'today';
    elseif ($presetRange == 6) $activePreset = '7d';
    elseif ($presetRange == 29) $activePreset = '30d';
    elseif ($presetRange == 89) $activePreset = '90d';
    elseif ($presetRange == 364) $activePreset = '1y';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - ND-SCPM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/animations.css">
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme-toggle.js"></script>
    <script src="assets/smooth-scroll.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card-shadow { box-shadow: 0 4px 6px -1px var(--card-shadow), 0 2px 4px -1px var(--card-shadow); }
        #mobileMenu { display: none; }
        #mobileMenu.show { display: block; }

        /* ── Date preset chips ──────────────────────────────────────── */
        .preset-chip {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid var(--border-primary);
            background: var(--card-bg);
            color: var(--text-secondary);
        }
        .preset-chip:hover { transform: translateY(-1px); }
        .preset-chip.active {
            background: var(--button-primary-bg);
            color: var(--button-primary-text);
            border-color: var(--button-primary-bg);
        }

        /* ── Intensity color coding ─────────────────────────────────── */
        .intensity-safe    { color: #16a34a; }
        .intensity-light   { color: #65a30d; }
        .intensity-moderate { color: #d97706; }
        .intensity-strong  { color: #dc2626; }
        .intensity-very    { color: #b91c1c; }
        .intensity-severe  { color: #7f1d1d; }

        .intensity-bar {
            display: inline-block;
            width: 4px;
            height: 20px;
            border-radius: 2px;
            margin-right: 8px;
            vertical-align: middle;
        }
        .bar-safe    { background: #22c55e; }
        .bar-light   { background: #84cc16; }
        .bar-moderate { background: #f59e0b; }
        .bar-strong  { background: #ef4444; }
        .bar-very    { background: #dc2626; }
        .bar-severe  { background: #991b1b; }

        /* ── MMI badge ──────────────────────────────────────────────── */
        .mmi-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .mmi-I   { background: #e0f2fe; color: #0369a1; }
        .mmi-II  { background: #dbeafe; color: #1e40af; }
        .mmi-III { background: #d1fae5; color: #065f46; }
        .mmi-IV  { background: #fef9c3; color: #854d0e; }
        .mmi-V   { background: #fed7aa; color: #9a3412; }
        .mmi-VI  { background: #fecaca; color: #991b1b; }
        .mmi-VII { background: #fca5a5; color: #7f1d1d; }
        .mmi-VIII{ background: #f87171; color: #fff; }
        .mmi-IX  { background: #ef4444; color: #fff; }
        .mmi-X   { background: #b91c1c; color: #fff; }

        /* ── Alert badge ────────────────────────────────────────────── */
        .alert-yes { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-no  { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
        [data-theme="dark"] .alert-yes { background: rgba(34,197,94,0.15); color: #86efac; border-color: rgba(34,197,94,0.3); }
        [data-theme="dark"] .alert-no  { background: rgba(107,114,128,0.15); color: #9ca3af; border-color: rgba(107,114,128,0.3); }

        /* ── Event row hover ────────────────────────────────────────── */
        .event-row { transition: all 0.15s ease; }
        .event-row:hover { transform: translateX(2px); }

        /* ── Empty state ────────────────────────────────────────────── */
        .empty-state { text-align: center; padding: 3rem 1rem; }

        @media print {
            @page {
                size: Letter portrait;
                margin: 0.5in 0.5in 0.75in 0.5in;
            }
            /* Reset everything for print */
            * {
                box-shadow: none !important;
                text-shadow: none !important;
                animation: none !important;
                transition: none !important;
            }
            body {
                background: white !important;
                color: #000 !important;
                font-size: 9pt;
                line-height: 1.3;
            }
            /* Hide navigation, filters, and interactive elements */
            .no-print { display: none !important; }
            nav { display: none !important; }

            /* Remove the outer padding from the full-width container */
            .w-full.mx-auto {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            /* Report Header - compact for print */
            .print-header { text-align: center; margin-bottom: 12px; }
            .print-header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 2px 0; }
            .print-header p { font-size: 9pt; margin: 0; color: #333; }

            /* Hide the on-screen stat cards (they don't print well) */
            .print-hide { display: none !important; }

            /* Print-only summary table */
            .print-summary { display: block !important; width: 100%; margin-bottom: 12px; }
            .print-summary table { width: 100%; border-collapse: collapse; }
            .print-summary td { border: 1px solid #000; padding: 3px 6px; font-size: 9pt; }
            .print-summary td.label { font-weight: bold; background: #eee; width: 25%; }

            /* Events table - proper print formatting */
            .print-table { display: block !important; }
            .print-table h2 { font-size: 11pt; font-weight: bold; margin: 0 0 6px 0; }
            .print-table .overflow-x-auto { overflow: visible !important; margin: 0 !important; }
            .print-table table {
                width: 100% !important;
                min-width: 0 !important;
                border-collapse: collapse !important;
                font-size: 8pt !important;
            }
            .print-table thead tr {
                border: none !important;
                background: #333 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-table th {
                color: #fff !important;
                background: #333 !important;
                border: 1px solid #000 !important;
                padding: 3px 4px !important;
                font-size: 8pt !important;
                text-transform: uppercase !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-table td {
                border: 1px solid #000 !important;
                padding: 2px 4px !important;
                color: #000 !important;
                font-size: 8pt !important;
            }
            /* Force hidden columns to show in print */
            .print-table .hidden,
            .print-table .hidden.md\:table-cell,
            .print-table .hidden.lg\:table-cell {
                display: table-cell !important;
            }
            /* Remove colored badge styling in print */
            .print-table span.inline-flex {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                color: #000 !important;
            }

            /* Footer */
            .print-footer { text-align: center; margin-top: 16px; font-size: 8pt; color: #555; }
        }
        /* Print-only elements hidden on screen */
        .print-summary, .print-table { display: none; }
        @media print {
            .print-summary, .print-table { display: block; }
            .screen-only { display: none !important; }
        }
    </style>
    <script>
        function downloadReport() {
            const form = document.querySelector('form');
            const dateFrom = form.querySelector('[name="date_from"]').value;
            const dateTo = form.querySelector('[name="date_to"]').value;
            const minIntensity = form.querySelector('[name="min_intensity"]').value;
            const params = new URLSearchParams({
                date_from: dateFrom,
                date_to: dateTo,
                min_intensity: minIntensity
            });
            // export_report.php sends Content-Disposition: attachment, so the
            // browser downloads the file without leaving the current page.
            window.location.href = 'export_report.php?' + params.toString();
        }

        // ── Date presets ─────────────────────────────────────────────
        function setDatePreset(preset, btn) {
            const today = new Date();
            const fromInput = document.querySelector('[name="date_from"]');
            const toInput = document.querySelector('[name="date_to"]');
            const fmt = d => d.toISOString().split('T')[0];
            toInput.value = fmt(today);

            switch(preset) {
                case 'today':
                    fromInput.value = fmt(today);
                    break;
                case '7d':
                    fromInput.value = fmt(new Date(today.getTime() - 6*86400000));
                    break;
                case '30d':
                    fromInput.value = fmt(new Date(today.getTime() - 29*86400000));
                    break;
                case '90d':
                    fromInput.value = fmt(new Date(today.getTime() - 89*86400000));
                    break;
                case '1y':
                    fromInput.value = fmt(new Date(today.getTime() - 364*86400000));
                    break;
            }

            // Update active chip
            document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');

            // Auto-submit the form
            document.querySelector('form').submit();
        }

        // ── Intensity classification ─────────────────────────────────
        function getIntensityClass(gal) {
            if (gal < 14) return { text: 'intensity-safe', bar: 'bar-safe' };
            if (gal < 38) return { text: 'intensity-light', bar: 'bar-light' };
            if (gal < 90) return { text: 'intensity-moderate', bar: 'bar-moderate' };
            if (gal < 180) return { text: 'intensity-strong', bar: 'bar-strong' };
            if (gal < 330) return { text: 'intensity-very', bar: 'bar-very' };
            return { text: 'intensity-severe', bar: 'bar-severe' };
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="sidebar-content px-4 sm:px-6 py-4 sm:py-8 overflow-x-hidden">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 animate-scale-in no-print">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold theme-text-primary mb-1">Reports & Analytics</h1>
                <p class="theme-text-secondary text-sm">View, filter, and export seismic event data</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="theme-btn-secondary px-4 py-2.5 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print
                </button>
                <button type="button" onclick="downloadReport()" class="theme-btn-primary px-4 py-2.5 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover mb-6 sm:mb-8 no-print animate-fade-in delay-100">
            <!-- Date presets -->
            <div class="flex items-center gap-2 mb-4 flex-wrap">
                <span class="text-xs font-semibold theme-text-tertiary uppercase tracking-wide mr-1">Quick Select:</span>
                <button class="preset-chip <?php echo $activePreset === 'today' ? 'active' : ''; ?>" onclick="setDatePreset('today', this)">Today</button>
                <button class="preset-chip <?php echo $activePreset === '7d' ? 'active' : ''; ?>" onclick="setDatePreset('7d', this)">7 Days</button>
                <button class="preset-chip <?php echo $activePreset === '30d' ? 'active' : ''; ?>" onclick="setDatePreset('30d', this)">30 Days</button>
                <button class="preset-chip <?php echo $activePreset === '90d' ? 'active' : ''; ?>" onclick="setDatePreset('90d', this)">90 Days</button>
                <button class="preset-chip <?php echo $activePreset === '1y' ? 'active' : ''; ?>" onclick="setDatePreset('1y', this)">1 Year</button>
            </div>

            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Date From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Date To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>"
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Min Intensity (Gal)</label>
                    <input type="number" name="min_intensity" value="<?php echo $min_intensity; ?>" step="0.01"
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full theme-btn-primary px-4 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Header (screen) -->
        <div class="text-center mb-8 animate-fade-in delay-200 screen-only">
            <h1 class="text-3xl font-bold theme-text-primary mb-2">Earthquake Monitoring Report</h1>
            <p class="theme-text-secondary">Period: <?php echo date('F d, Y', strtotime($date_from)); ?> - <?php echo date('F d, Y', strtotime($date_to)); ?></p>
            <p class="theme-text-tertiary text-sm">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        </div>

        <!-- Print-only Header -->
        <div class="print-header" style="display:none;">
            <h1>Notre Dame - Siena College of Polomolok</h1>
            <p>Earthquake Monitoring System - Seismic Events Report</p>
            <p>Period: <?php echo date('F d, Y', strtotime($date_from)); ?> - <?php echo date('F d, Y', strtotime($date_to)); ?> | Generated: <?php echo date('F d, Y h:i A'); ?></p>
        </div>

        <!-- Print-only Summary Table -->
        <div class="print-summary" style="display:none;">
            <table>
                <tr>
                    <td class="label">Total Events</td><td><?php echo $stats['total_events']; ?></td>
                    <td class="label">Max Magnitude</td><td><?php echo $stats['max_magnitude'] ? number_format($stats['max_magnitude'], 1) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td class="label">Max Intensity</td><td><?php echo number_format($stats['max_intensity'], 2); ?> Gal</td>
                    <td class="label">Avg Magnitude</td><td><?php echo $stats['avg_magnitude'] ? number_format($stats['avg_magnitude'], 1) : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td class="label">High Intensity (&ge;80 Gal)</td><td><?php echo $stats['high_intensity_events']; ?></td>
                    <td class="label">SMS Sent</td><td><?php echo $sms_count; ?></td>
                </tr>
            </table>
        </div>

        <!-- Statistics Cards (screen only) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 mb-6 screen-only">
            <!-- Total Events -->
            <div class="theme-card rounded-xl p-4 border-l-4 border-gray-900 animate-scale-in delay-300">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Total Events</p>
                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 theme-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-bold theme-text-primary"><?php echo $stats['total_events']; ?></p>
                <p class="text-xs theme-text-tertiary mt-1">in selected period</p>
            </div>

            <!-- Max Magnitude -->
            <div class="theme-card rounded-xl p-4 border-l-4 border-red-600 animate-scale-in delay-350">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Max Magnitude</p>
                    <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-red-600"><?php echo $stats['max_magnitude'] ? number_format($stats['max_magnitude'], 1) : 'N/A'; ?></p>
                <p class="text-xs theme-text-tertiary mt-1">estimated</p>
            </div>

            <!-- Max Intensity -->
            <div class="theme-card rounded-xl p-4 border-l-4 border-orange-600 animate-scale-in delay-400">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Max Intensity</p>
                    <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-orange-600"><?php echo number_format($stats['max_intensity'], 2); ?></p>
                <p class="text-xs theme-text-tertiary mt-1">Gal (PGA)</p>
            </div>

            <!-- Avg Magnitude -->
            <div class="theme-card rounded-xl p-4 border-l-4 border-blue-600 animate-scale-in delay-450">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Avg Magnitude</p>
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-bold theme-text-primary"><?php echo $stats['avg_magnitude'] ? number_format($stats['avg_magnitude'], 1) : 'N/A'; ?></p>
                <p class="text-xs theme-text-tertiary mt-1">estimated</p>
            </div>

            <!-- High Intensity -->
            <div class="theme-card rounded-xl p-4 border-l-4 border-yellow-600 animate-scale-in delay-500">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">High Intensity</p>
                    <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-yellow-600"><?php echo $stats['high_intensity_events']; ?></p>
                <p class="text-xs theme-text-tertiary mt-1">&ge;80 Gal events</p>
            </div>

            <!-- SMS Sent -->
            <div class="theme-card rounded-xl p-4 border-l-4 border-green-600 animate-scale-in delay-550">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">SMS Sent</p>
                    <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-green-600"><?php echo $sms_count; ?></p>
                <p class="text-xs theme-text-tertiary mt-1">alert messages</p>
            </div>
        </div>

        <!-- Events Table (screen) -->
        <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover mb-6 sm:mb-8 animate-fade-in delay-600 screen-only">
            <!-- Table header with count -->
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg sm:text-xl font-bold theme-text-primary">Seismic Events</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold theme-card border"><?php echo $total_events; ?> total</span>
                </div>
            </div>

            <?php if ($events->num_rows > 0): ?>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="w-full min-w-full">
                    <thead>
                        <tr class="theme-table-header" style="border-bottom: 2px solid var(--table-border);">
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">ID</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Timestamp</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase hidden lg:table-cell">Device</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Magnitude</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Intensity</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase hidden md:table-cell">MMI</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Alert</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $events->fetch_assoc()): ?>
                            <?php $ic = intensityClass((float)$event['intensity']); ?>
                        <tr class="theme-table-row event-row transition">
                            <td class="py-2 sm:py-3 px-2 sm:px-4 theme-text-secondary text-xs sm:text-sm font-mono">#<?php echo $event['id']; ?></td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4 theme-text-secondary text-xs sm:text-sm"><?php echo date('M d, h:i A', strtotime($event['timestamp'])); ?></td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4 theme-text-secondary font-mono text-xs hidden lg:table-cell"><?php echo htmlspecialchars($event['device_id']); ?></td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4">
                                <?php if ($event['magnitude']): ?>
                                    <span class="text-base sm:text-lg font-bold <?php echo $event['magnitude'] >= 7.0 ? 'text-red-600' : ($event['magnitude'] >= 5.0 ? 'text-orange-600' : 'theme-text-primary'); ?>">
                                        <?php echo number_format($event['magnitude'], 1); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs theme-text-tertiary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4">
                                <span class="intensity-bar <?php echo $ic['bar']; ?>"></span>
                                <span class="text-base sm:text-lg font-bold <?php echo $ic['text']; ?>">
                                    <?php echo number_format($event['intensity'], 2); ?>
                                </span>
                                <span class="text-xs theme-text-tertiary ml-1">Gal</span>
                            </td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4 hidden md:table-cell">
                                <?php if ($event['mmi_level']): ?>
                                    <?php
                                        $mmi = $event['mmi_level'];
                                        $mmiRoman = preg_replace('/[^IVX]/', '', $mmi);
                                    ?>
                                    <span class="mmi-badge mmi-<?php echo $mmiRoman ?: 'I'; ?>"><?php echo htmlspecialchars($mmi); ?></span>
                                <?php else: ?>
                                    <span class="text-xs theme-text-tertiary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2 sm:py-3 px-2 sm:px-4">
                                <?php if ($event['alert_sent']): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold alert-yes">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="hidden sm:inline">Sent</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold alert-no">
                                        <span class="hidden sm:inline">No</span><span class="sm:hidden">-</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <!-- Empty state -->
                <div class="empty-state">
                    <svg class="w-16 h-16 mx-auto theme-text-tertiary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-bold theme-text-primary mb-1">No events found</h3>
                    <p class="text-sm theme-text-secondary mb-4">No seismic events match the current filter criteria.</p>
                    <p class="text-xs theme-text-tertiary">Try changing the date range or lowering the minimum intensity.</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_events > 0): ?>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 pt-4 border-t no-print" style="border-color: var(--border-primary);">
                <!-- Info -->
                <div class="flex items-center gap-3">
                    <p class="text-sm theme-text-tertiary">
                        Showing <span class="font-semibold theme-text-primary"><?php echo $offset + 1; ?></span>–<span class="font-semibold theme-text-primary"><?php echo min($offset + $per_page, $total_events); ?></span>
                        of <span class="font-semibold theme-text-primary"><?php echo $total_events; ?></span> events
                    </p>
                    <span class="hidden sm:inline px-2.5 py-0.5 rounded-full text-xs font-semibold theme-card border">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                </div>

                <!-- Page buttons -->
                <div class="flex items-center gap-1.5">
                    <?php
                    // Build base query string (preserve filters, exclude page)
                    $baseParams = http_build_query([
                        'date_from' => $date_from,
                        'date_to' => $date_to,
                        'min_intensity' => $min_intensity
                    ]);

                    // Determine page range to show (max 7 buttons)
                    $startPage = max(1, $page - 3);
                    $endPage = min($total_pages, $page + 3);
                    if ($endPage - $startPage < 6) {
                        if ($startPage == 1) $endPage = min($total_pages, $startPage + 6);
                        else $startPage = max(1, $endPage - 6);
                    }
                    ?>

                    <!-- Previous button -->
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo $baseParams; ?>&page=<?php echo $page - 1; ?>"
                           class="px-3 py-2 rounded-xl text-sm font-semibold theme-btn-secondary border transition hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 rounded-xl text-sm font-semibold opacity-40 cursor-not-allowed theme-btn-secondary border">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </span>
                    <?php endif; ?>

                    <!-- First page + ellipsis -->
                    <?php if ($startPage > 1): ?>
                        <a href="?<?php echo $baseParams; ?>&page=1"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold theme-btn-secondary border transition hover:scale-105 active:scale-95">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="px-2 theme-text-tertiary">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Page number buttons -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3.5 py-2 rounded-xl text-sm font-bold theme-btn-primary border">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?<?php echo $baseParams; ?>&page=<?php echo $i; ?>"
                               class="px-3.5 py-2 rounded-xl text-sm font-semibold theme-btn-secondary border transition hover:scale-105 active:scale-95">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Last page + ellipsis -->
                    <?php if ($endPage < $total_pages): ?>
                        <?php if ($endPage < $total_pages - 1): ?>
                            <span class="px-2 theme-text-tertiary">…</span>
                        <?php endif; ?>
                        <a href="?<?php echo $baseParams; ?>&page=<?php echo $total_pages; ?>"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold theme-btn-secondary border transition hover:scale-105 active:scale-95">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo $baseParams; ?>&page=<?php echo $page + 1; ?>"
                           class="px-3 py-2 rounded-xl text-sm font-semibold theme-btn-secondary border transition hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 rounded-xl text-sm font-semibold opacity-40 cursor-not-allowed theme-btn-secondary border">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Print-only Events Table -->
        <div class="print-table" style="display:none;">
            <h2>Seismic Events</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>Device</th>
                        <th>Magnitude</th>
                        <th>Intensity (Gal)</th>
                        <th>MMI</th>
                        <th>Alert</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Re-query for print table — ALL events, no pagination
                    $print_query = "SELECT * FROM seismic_logs 
WHERE DATE(timestamp) BETWEEN ? AND ? AND intensity >= ?
ORDER BY timestamp DESC";
                    $print_stmt = $conn->prepare($print_query);
                    $print_stmt->bind_param("ssd", $date_from, $date_to, $min_intensity);
                    $print_stmt->execute();
                    $print_events = $print_stmt->get_result();
                    ?>
                    <?php if ($print_events->num_rows > 0): ?>
                        <?php while ($event = $print_events->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $event['id']; ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($event['timestamp'])); ?></td>
                            <td><?php echo $event['device_id']; ?></td>
                            <td><?php echo $event['magnitude'] ? number_format($event['magnitude'], 1) : 'N/A'; ?></td>
                            <td><?php echo number_format($event['intensity'], 2); ?></td>
                            <td><?php echo $event['mmi_level'] ?? 'N/A'; ?></td>
                            <td><?php echo $event['alert_sent'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">No events found for the selected period</td>
                        </tr>
                    <?php endif; ?>
                    <?php $print_stmt->close(); ?>
                </tbody>
            </table>
        </div>

        <!-- Footer (screen) -->
        <div class="text-center theme-text-tertiary text-sm screen-only">
            <p>Notre Dame - Siena College of Polomolok</p>
            <p>Earthquake Monitoring System</p>
        </div>

        <!-- Print-only Footer -->
        <div class="print-footer" style="display:none;">
            <p>Notre Dame - Siena College of Polomolok | Earthquake Monitoring System</p>
            <p>This report was generated automatically by the ND-SCPM Earthquake Monitoring System.</p>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
