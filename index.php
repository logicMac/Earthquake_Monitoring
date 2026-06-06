<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ND-SCPM Earthquake Monitoring</title>
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
    </style>
</head>
<body>
   
    <nav class="shadow-sm animate-fade-in-down">
        <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 logo-icon rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="nav-title text-base sm:text-xl font-bold">ND-SCPM</h1>
                        <p class="nav-subtitle text-xs hidden sm:block">Earthquake Monitor</p>
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
                        <span class="theme-text-tertiary text-sm">Welcome, <span class="theme-text-primary font-medium"><?php echo getAdminName(); ?></span></span>
                        <a href="quakebot.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition button-hover">
                            QuakeBot
                        </a>
                        <a href="reports.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition button-hover">
                            Reports
                        </a>
                        <a href="manage_recipients.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition button-hover">
                            Recipients
                        </a>
                        <a href="logout.php" class="theme-btn-primary px-4 py-2 rounded-lg font-semibold text-sm transition button-hover">
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
                    <span class="theme-text-tertiary text-sm px-4 py-2">Welcome, <span class="theme-text-primary font-medium"><?php echo getAdminName(); ?></span></span>
                    <a href="quakebot.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        QuakeBot
                    </a>
                    <a href="reports.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        Reports
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
        
        // Update mobile theme icons
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
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', updateToggleIcon);
    </script>

    <!-- Alert Banner -->
    <div id="alertBanner" class="hidden bg-red-600 text-white py-3 shadow-lg">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-center space-x-3">
                <svg class="w-6 h-6 alert-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-bold text-lg">HIGH INTENSITY EARTHQUAKE DETECTED!</span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 py-4 sm:py-8">
        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-100 border-l-4 border-gray-900">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xs sm:text-sm font-semibold theme-text-tertiary uppercase mb-2">Current Intensity</h3>
                        <p id="currentIntensity" class="text-4xl sm:text-5xl font-bold theme-text-primary mb-1">0.00</p>
                        <p class="text-sm theme-text-secondary font-medium">Gal</p>
                    </div>
                    <div class="w-16 h-16 logo-icon rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="pt-3" style="border-top: 1px solid var(--border-primary);">
                    <p class="text-xs theme-text-tertiary">MMI Scale</p>
                    <p id="currentMMI" class="text-lg font-bold theme-text-primary">-</p>
                </div>
            </div>
            
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-200 border-l-4 border-blue-600">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xs sm:text-sm font-semibold theme-text-tertiary uppercase mb-2">Last Event</h3>
                        <p id="lastEvent" class="text-xl sm:text-2xl font-bold theme-text-primary mb-1">NO DATA</p>
                        <p id="lastEventTime" class="text-sm theme-text-secondary font-medium">--</p>
                    </div>
                    <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-scale-in delay-300 border-l-4 border-green-600">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xs sm:text-sm font-semibold theme-text-tertiary uppercase mb-2">System Status</h3>
                        <p id="systemStatus" class="text-xl sm:text-2xl font-bold text-green-600 mb-1">MONITORING</p>
                        <p class="text-sm theme-text-secondary font-medium">Active</p>
                    </div>
                    <div class="w-16 h-16 bg-green-600 rounded-xl flex items-center justify-center pulse-dot flex-shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Graph -->
        <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover mb-6 sm:mb-8 animate-fade-in delay-400">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg sm:text-xl font-bold theme-text-primary">Real-Time Seismic Activity</h2>
                <span class="flex items-center text-sm theme-text-secondary">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 pulse-dot"></span>
                    Live
                </span>
            </div>
            <canvas id="seismicChart" height="80"></canvas>
        </div>

        <!-- Recent Logs -->
        <div class="theme-card rounded-xl p-4 sm:p-6 card-shadow card-hover animate-fade-in delay-500">
            <h2 class="text-lg sm:text-xl font-bold theme-text-primary mb-4 sm:mb-6">Recent Events</h2>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="w-full min-w-full">
                    <thead>
                        <tr class="theme-table-header" style="border-bottom: 2px solid var(--table-border);">
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Timestamp</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Intensity</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase hidden md:table-cell">MMI Scale</th>
                            <th class="text-left py-2 sm:py-3 px-2 sm:px-4 font-semibold text-xs sm:text-sm uppercase">Alert</th>
                        </tr>
                    </thead>    
                    <tbody id="logsTable">
                        <tr>
                            <td colspan="4" class="text-center py-8 theme-text-tertiary">
                                <div class="loading-skeleton"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Chart Configuration with theme support
        const ctx = document.getElementById('seismicChart').getContext('2d');
        
        function getChartColors() {
            const theme = document.documentElement.getAttribute('data-theme') || 'light';
            if (theme === 'dark') {
                return {
                    line: '#60a5fa',
                    gradientStart: 'rgba(96, 165, 250, 0.3)',
                    gradientEnd: 'rgba(96, 165, 250, 0.01)',
                    grid: '#334155',
                    text: '#94a3b8',
                    pointBg: '#60a5fa',
                    pointBorder: '#1e293b'
                };
            } else {
                return {
                    line: '#000000',
                    gradientStart: 'rgba(0, 0, 0, 0.15)',
                    gradientEnd: 'rgba(0, 0, 0, 0.01)',
                    grid: '#e5e7eb',
                    text: '#6b7280',
                    pointBg: '#000000',
                    pointBorder: '#ffffff'
                };
            }
        }
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        let colors = getChartColors();
        gradient.addColorStop(0, colors.gradientStart);
        gradient.addColorStop(1, colors.gradientEnd);
        
        const seismicChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Intensity (Gal)',
                    data: [],
                    borderColor: colors.line,
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: colors.pointBg,
                    pointBorderColor: colors.pointBorder,
                    pointBorderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: colors.grid,
                            lineWidth: 1
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            color: colors.text
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '600'
                            },
                            color: colors.text
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Update chart colors when theme changes
        function updateChartTheme() {
            const colors = getChartColors();
            seismicChart.data.datasets[0].borderColor = colors.line;
            seismicChart.data.datasets[0].pointBackgroundColor = colors.pointBg;
            seismicChart.data.datasets[0].pointBorderColor = colors.pointBorder;
            seismicChart.options.scales.y.grid.color = colors.grid;
            seismicChart.options.scales.y.ticks.color = colors.text;
            seismicChart.options.scales.x.ticks.color = colors.text;
            
            // Update gradient
            const newGradient = ctx.createLinearGradient(0, 0, 0, 400);
            newGradient.addColorStop(0, colors.gradientStart);
            newGradient.addColorStop(1, colors.gradientEnd);
            seismicChart.data.datasets[0].backgroundColor = newGradient;
            
            seismicChart.update();
        }
        
        // Override toggleTheme to update chart
        const originalToggleTheme = window.toggleTheme;
        window.toggleTheme = function() {
            originalToggleTheme();
            setTimeout(updateChartTheme, 100);
        };

        // Fetch and update data
        function updateDashboard() {
            fetch('api/get_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.latest) {
                        // Update current intensity
                        document.getElementById('currentIntensity').textContent = parseFloat(data.latest.intensity).toFixed(2);
                        
                        // Update MMI display
                        const mmiDisplay = data.latest.mmi_level ? 
                            `${data.latest.mmi_level} - ${data.latest.mmi_name}` : 
                            'Calculating...';
                        document.getElementById('currentMMI').textContent = mmiDisplay;
                        
                        // Update last event
                        const eventText = data.latest.mmi_level ? 
                            `${parseFloat(data.latest.intensity).toFixed(2)} Gal (MMI ${data.latest.mmi_level})` :
                            `${parseFloat(data.latest.intensity).toFixed(2)} Gal`;
                        document.getElementById('lastEvent').textContent = eventText;
                        document.getElementById('lastEventTime').textContent = new Date(data.latest.timestamp).toLocaleString();
                        
                        // Show alert banner if high intensity
                        const alertBanner = document.getElementById('alertBanner');
                        if (parseFloat(data.latest.intensity) >= 80) {
                            alertBanner.classList.remove('hidden');
                        } else {
                            alertBanner.classList.add('hidden');
                        }
                    }
                    
                    // Update chart
                    if (data.recent && data.recent.length > 0) {
                        const labels = data.recent.map(item => {
                            const date = new Date(item.timestamp);
                            return date.toLocaleTimeString();
                        });
                        const values = data.recent.map(item => parseFloat(item.intensity));
                        
                        seismicChart.data.labels = labels;
                        seismicChart.data.datasets[0].data = values;
                        seismicChart.update();
                    }
                    
                    // Update logs table
                    updateLogsTable(data.recent);
                })
                .catch(error => console.error('Error:', error));
        }

        function updateLogsTable(logs) {
            const tbody = document.getElementById('logsTable');
            if (!logs || logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 theme-text-tertiary">No events recorded</td></tr>';
                return;
            }
            
            tbody.innerHTML = logs.map((log, index) => {
                const date = new Date(log.timestamp);
                const mmiText = log.mmi_level ? `${log.mmi_level} - ${log.mmi_name}` : 'N/A';
                const mmiColor = getMMIColor(log.mmi_level);
                const delay = Math.min(index * 50, 400);
                const intensityColor = parseFloat(log.intensity) >= 80 ? 'text-red-600' : 'theme-text-primary';
                
                return `
                <tr class="theme-table-row hover:bg-opacity-50 transition stagger-fade-in" style="animation-delay: ${delay}ms">
                    <td class="py-2 sm:py-3 px-2 sm:px-4 font-medium theme-text-secondary text-xs sm:text-sm">${date.toLocaleString('en-PH', {timeZone: 'Asia/Manila'})}</td>
                    <td class="py-2 sm:py-3 px-2 sm:px-4">
                        <span class="text-lg sm:text-2xl font-bold ${intensityColor}">
                            ${parseFloat(log.intensity).toFixed(2)}
                        </span>
                        <span class="text-xs theme-text-tertiary ml-1">Gal</span>
                    </td>
                    <td class="py-2 sm:py-3 px-2 sm:px-4 hidden md:table-cell">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${mmiColor}">
                            ${mmiText}
                        </span>
                    </td>
                    <td class="py-2 sm:py-3 px-2 sm:px-4">
                        ${log.alert_sent ? 
                            '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-300"><span class="hidden sm:inline">Yes</span><span class="sm:hidden">✓</span></span>' : 
                            '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300"><span class="hidden sm:inline">No</span><span class="sm:hidden">-</span></span>'
                        }
                    </td>
                </tr>
            `}).join('');
        }
        
        function getMMIColor(mmi_level) {
            if (!mmi_level) return 'bg-gray-100 text-gray-600 border border-gray-300';
            
            const colors = {
                'I': 'bg-gray-100 text-gray-700 border border-gray-300',
                'II-III': 'bg-gray-200 text-gray-800 border border-gray-400',
                'IV': 'bg-gray-300 text-gray-900 border border-gray-500',
                'V': 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                'VI': 'bg-yellow-200 text-yellow-900 border border-yellow-400',
                'VII': 'bg-orange-100 text-orange-800 border border-orange-300',
                'VIII': 'bg-orange-200 text-orange-900 border border-orange-400',
                'IX': 'bg-red-100 text-red-800 border border-red-300',
                'X+': 'bg-red-200 text-red-900 border border-red-400'
            };
            
            return colors[mmi_level] || 'bg-gray-100 text-gray-600 border border-gray-300';
        }

        // Update every 2 seconds
        updateDashboard();
        setInterval(updateDashboard, 2000);
    </script>
</body>
</html>
