<?php
/**
 * Reports & Analytics
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$conn = getDBConnection();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$min_intensity = $_GET['min_intensity'] ?? 0;

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

// Get events data
$events_query = "SELECT * FROM seismic_logs 
WHERE DATE(timestamp) BETWEEN ? AND ? AND intensity >= ?
ORDER BY timestamp DESC";

$stmt = $conn->prepare($events_query);
$stmt->bind_param("ssd", $date_from, $date_to, $min_intensity);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

// Get SMS logs count
$sms_count = $conn->query("SELECT COUNT(*) as count FROM sms_logs WHERE DATE(sent_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
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
        @media print {
            .no-print { display: none; }
            body { background: white; color: black; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="shadow-sm no-print animate-fade-in-down">
        <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 logo-icon rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="nav-title text-base sm:text-xl font-bold">ND-SCPM</h1>
                        <p class="nav-subtitle text-xs hidden sm:block">Reports & Analytics</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="theme-toggle hidden sm:flex" title="Toggle Dark/Light Mode">
                        <svg id="sunIcon" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-2 sm:space-x-4">
                        <a href="index.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition">
                            ← Dashboard
                        </a>
                        <a href="quakebot.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition">
                            QuakeBot
                        </a>
                        <a href="manage_recipients.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition">
                            Recipients
                        </a>
                        <a href="logout.php" class="theme-btn-primary px-4 py-2 rounded-lg font-semibold text-sm transition">
                            Logout
                        </a>
                    </div>
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleMobileMenu()" class="md:hidden theme-btn-secondary p-2 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="mobileMenu" class="md:hidden mt-3 pt-3" style="border-top: 2px solid var(--border-primary);">
                <div class="flex flex-col space-y-2">
                    <button onclick="toggleTheme()" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center flex items-center justify-center space-x-2">
                        <svg id="sunIconMobile" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIconMobile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <span>Toggle Theme</span>
                    </button>
                    <a href="index.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        ← Dashboard
                    </a>
                    <a href="quakebot.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        QuakeBot
                    </a>
                    <a href="manage_recipients.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        Recipients
                    </a>
                    <a href="logout.php" class="theme-btn-primary px-4 py-2 rounded-lg font-semibold text-sm transition text-center">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('show');
        }
        
        function updateToggleIcon() {
            const theme = document.documentElement.getAttribute('data-theme');
            const sunIcon = document.getElementById('sunIcon');
            const moonIcon = document.getElementById('moonIcon');
            const sunIconMobile = document.getElementById('sunIconMobile');
            const moonIconMobile = document.getElementById('moonIconMobile');
            
            if (theme === 'dark') {
                if (sunIcon) sunIcon.classList.remove('hidden');
                if (moonIcon) moonIcon.classList.add('hidden');
                if (sunIconMobile) sunIconMobile.classList.remove('hidden');
                if (moonIconMobile) moonIconMobile.classList.add('hidden');
            } else {
                if (sunIcon) sunIcon.classList.add('hidden');
                if (moonIcon) moonIcon.classList.remove('hidden');
                if (sunIconMobile) sunIconMobile.classList.add('hidden');
                if (moonIconMobile) moonIconMobile.classList.remove('hidden');
            }
        }
        
        document.addEventListener('DOMContentLoaded', updateToggleIcon);
    </script>

    <div class="container mx-auto px-4 sm:px-6 py-4 sm:py-8">
        <!-- Filters -->
        <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover mb-6 sm:mb-8 no-print animate-scale-in delay-100">
            <h2 class="text-lg sm:text-xl font-bold theme-text-primary mb-4">Filter Report</h2>
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Date From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                        class="theme-input w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Date To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                        class="theme-input w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Min Intensity (Gal)</label>
                    <input type="number" name="min_intensity" value="<?php echo $min_intensity; ?>" step="0.01"
                        class="theme-input w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 transition">
                </div>
                <div class="flex items-end space-x-2 sm:col-span-2 lg:col-span-1">
                    <button type="submit" class="flex-1 theme-btn-primary px-3 sm:px-4 py-2 rounded-lg font-semibold transition text-sm">
                        Generate
                    </button>
                    <button type="button" onclick="window.print()" class="flex-1 bg-gray-700 text-white px-3 sm:px-4 py-2 rounded-lg font-semibold hover:bg-gray-800 transition text-sm">
                        Print
                    </button>
                    <a href="export_report.php?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&min_intensity=<?php echo $min_intensity; ?>" 
                        class="flex-1 bg-green-600 text-white px-3 sm:px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition text-center text-sm">
                        CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Report Header -->
        <div class="text-center mb-8 animate-fade-in delay-200">
            <h1 class="text-3xl font-bold theme-text-primary mb-2">Earthquake Monitoring Report</h1>
            <p class="theme-text-secondary">Period: <?php echo date('F d, Y', strtotime($date_from)); ?> - <?php echo date('F d, Y', strtotime($date_to)); ?></p>
            <p class="theme-text-tertiary text-sm">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 sm:gap-6 mb-8">
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-300 border-l-4 border-gray-900">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-sm font-medium">Total Events</p>
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 theme-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <p class="text-2xl sm:text-3xl font-bold theme-text-primary"><?php echo $stats['total_events']; ?></p>
            </div>
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-350 border-l-4 border-red-600">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-sm font-medium">Max Magnitude</p>
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-red-600"><?php echo $stats['max_magnitude'] ? number_format($stats['max_magnitude'], 1) : 'N/A'; ?></p>
                <p class="text-xs theme-text-tertiary">Est.</p>
            </div>
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-400 border-l-4 border-orange-600">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-sm font-medium">Max Intensity</p>
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-orange-600"><?php echo number_format($stats['max_intensity'], 2); ?></p>
                <p class="text-xs theme-text-tertiary">Gal</p>
            </div>
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-450 border-l-4 border-blue-600">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-sm font-medium">Avg Magnitude</p>
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <p class="text-2xl sm:text-3xl font-bold theme-text-primary"><?php echo $stats['avg_magnitude'] ? number_format($stats['avg_magnitude'], 1) : 'N/A'; ?></p>
                <p class="text-xs theme-text-tertiary">Est.</p>
            </div>
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-500 border-l-4 border-yellow-600">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-sm font-medium">High Intensity</p>
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-yellow-600"><?php echo $stats['high_intensity_events']; ?></p>
                <p class="text-xs theme-text-tertiary">≥80 Gal</p>
            </div>
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-550 border-l-4 border-green-600">
                <div class="flex items-center justify-between mb-2">
                    <p class="theme-text-tertiary text-sm font-medium">SMS Sent</p>
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-green-600"><?php echo $sms_count; ?></p>
                <p class="text-xs theme-text-tertiary">Messages</p>
            </div>
        </div>

        <!-- Events Table -->
        <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover mb-6 sm:mb-8 animate-fade-in delay-600">
            <h2 class="text-lg sm:text-xl font-bold theme-text-primary mb-4 sm:mb-6">Seismic Events</h2>
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
                        <?php if ($events->num_rows > 0): ?>
                            <?php while ($event = $events->fetch_assoc()): ?>
                            <tr class="theme-table-row hover:bg-opacity-50 transition">
                                <td class="py-2 sm:py-3 px-2 sm:px-4 theme-text-secondary text-xs sm:text-sm font-mono">#<?php echo $event['id']; ?></td>
                                <td class="py-2 sm:py-3 px-2 sm:px-4 theme-text-secondary text-xs sm:text-sm"><?php echo date('M d, h:i A', strtotime($event['timestamp'])); ?></td>
                                <td class="py-2 sm:py-3 px-2 sm:px-4 theme-text-secondary font-mono text-xs hidden lg:table-cell"><?php echo $event['device_id']; ?></td>
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
                                    <span class="text-base sm:text-lg font-semibold <?php echo $event['intensity'] >= 80 ? 'text-red-600' : 'theme-text-primary'; ?>">
                                        <?php echo number_format($event['intensity'], 2); ?>
                                    </span>
                                    <span class="text-xs theme-text-tertiary ml-1">Gal</span>
                                </td>
                                <td class="py-2 sm:py-3 px-2 sm:px-4 hidden md:table-cell">
                                    <?php if ($event['mmi_level']): ?>
                                        <span class="text-sm font-semibold theme-text-primary">
                                            <?php echo $event['mmi_level']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs theme-text-tertiary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 sm:py-3 px-2 sm:px-4">
                                    <?php if ($event['alert_sent']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-300">
                                            <span class="hidden sm:inline">Yes</span><span class="sm:hidden">✓</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300">
                                            <span class="hidden sm:inline">No</span><span class="sm:hidden">-</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-8 theme-text-tertiary text-sm">No events found for the selected period</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center theme-text-tertiary text-sm">
            <p>Notre Dame - Siena College of Polomolok</p>
            <p>Earthquake Monitoring System</p>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
