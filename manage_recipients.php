<?php
/**
 * Manage Alert Recipients
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

$conn = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $phone = $_POST['phone'];
                $category = $_POST['category'];
                
                $stmt = $conn->prepare("INSERT INTO alert_recipients (name, phone_number, category) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $phone, $category);
                
                if ($stmt->execute()) {
                    $message = "Recipient added successfully";
                } else {
                    $message = "Error: " . $conn->error;
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $conn->query("DELETE FROM alert_recipients WHERE id = $id");
                $message = "Recipient deleted";
                break;
                
            case 'toggle':
                $id = intval($_POST['id']);
                $conn->query("UPDATE alert_recipients SET is_active = NOT is_active WHERE id = $id");
                $message = "Status updated";
                break;
        }
    }
}

// Get all recipients
$recipients = $conn->query("SELECT * FROM alert_recipients ORDER BY category, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipients - ND-SCPM</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <!-- Navigation -->
    <nav class="shadow-sm animate-fade-in-down">
        <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 logo-icon rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="nav-title text-base sm:text-xl font-bold">ND-SCPM</h1>
                        <p class="nav-subtitle text-xs hidden sm:block">Manage Recipients</p>
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
                        <a href="reports.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition">
                            Reports
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
                    <a href="reports.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        Reports
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

        <?php if ($message): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-3 sm:p-4 mb-4 sm:mb-6 rounded-r-lg card-shadow">
            <div class="flex items-center">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-xs sm:text-sm text-green-800 font-semibold"><?php echo $message; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-6 sm:mb-8 animate-fade-in delay-100">
            <?php
            $conn_stats = getDBConnection();
            $total = $conn_stats->query("SELECT COUNT(*) as count FROM alert_recipients")->fetch_assoc()['count'];
            $active = $conn_stats->query("SELECT COUNT(*) as count FROM alert_recipients WHERE is_active = 1")->fetch_assoc()['count'];
            $students = $conn_stats->query("SELECT COUNT(*) as count FROM alert_recipients WHERE category = 'student'")->fetch_assoc()['count'];
            $faculty = $conn_stats->query("SELECT COUNT(*) as count FROM alert_recipients WHERE category = 'faculty'")->fetch_assoc()['count'];
            $conn_stats->close();
            ?>
            <div class="theme-card rounded-xl p-6 card-shadow card-hover animate-scale-in delay-200 border-l-4 border-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="theme-text-tertiary text-sm font-medium">Total Recipients</p>
                        <p class="text-3xl font-bold theme-text-primary mt-1"><?php echo $total; ?></p>
                    </div>
                    <svg class="w-12 h-12 theme-text-tertiary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="theme-card rounded-xl p-6 card-shadow card-hover animate-scale-in delay-250 border-l-4 border-green-600">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="theme-text-tertiary text-sm font-medium">Active</p>
                        <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $active; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="theme-card rounded-xl p-6 card-shadow card-hover animate-scale-in delay-300 border-l-4 border-blue-600">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="theme-text-tertiary text-sm font-medium">Students</p>
                        <p class="text-3xl font-bold theme-text-primary mt-1"><?php echo $students; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
            
            <div class="theme-card rounded-xl p-6 card-shadow card-hover animate-scale-in delay-350 border-l-4 border-purple-600">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="theme-text-tertiary text-sm font-medium">Faculty</p>
                        <p class="text-3xl font-bold theme-text-primary mt-1"><?php echo $faculty; ?></p>
                    </div>
                    <svg class="w-12 h-12 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Add New Recipient -->
        <div class="theme-card rounded-xl p-6 card-shadow card-hover mb-8 animate-fade-in delay-400">
            <h2 class="text-2xl font-bold theme-text-primary mb-6">Add New Recipient</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold theme-text-secondary mb-2">Full Name</label>
                        <input type="text" name="name" required 
                            class="theme-input w-full px-4 py-3 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 transition">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold theme-text-secondary mb-2">Phone Number</label>
                        <input type="text" name="phone" required placeholder="09171234567" 
                            class="theme-input w-full px-4 py-3 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-900 transition">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Category</label>
                    <select name="category" 
                        class="theme-input w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 transition">
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" 
                    class="theme-btn-primary px-6 py-3 rounded-lg font-semibold transition">
                    Add Recipient
                </button>
            </form>
        </div>

        <!-- Recipients List -->
        <div class="theme-card rounded-xl p-6 card-shadow card-hover animate-fade-in delay-500">
            <h2 class="text-2xl font-bold theme-text-primary mb-6">Current Recipients</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="theme-table-header" style="border-bottom: 2px solid var(--table-border);">
                            <th class="text-left py-3 px-4 font-semibold text-sm uppercase">Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-sm uppercase">Phone</th>
                            <th class="text-left py-3 px-4 font-semibold text-sm uppercase">Category</th>
                            <th class="text-left py-3 px-4 font-semibold text-sm uppercase">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-sm uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($recipient = $recipients->fetch_assoc()): ?>
                        <tr class="theme-table-row hover:bg-opacity-50 transition">
                            <td class="py-4 px-4 font-semibold theme-text-primary"><?php echo htmlspecialchars($recipient['name']); ?></td>
                            <td class="py-4 px-4 font-mono theme-text-secondary"><?php echo htmlspecialchars($recipient['phone_number']); ?></td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                    <?php 
                                    switch($recipient['category']) {
                                        case 'admin': echo 'bg-gray-200 text-gray-900 border border-gray-400'; break;
                                        case 'faculty': echo 'bg-gray-300 text-gray-900 border border-gray-500'; break;
                                        case 'staff': echo 'bg-gray-200 text-gray-800 border border-gray-400'; break;
                                        default: echo 'bg-gray-100 text-gray-700 border border-gray-300';
                                    }
                                    ?>">
                                    <?php echo ucfirst($recipient['category']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                    <?php echo $recipient['is_active'] ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-gray-100 text-gray-600 border border-gray-300'; ?>">
                                    <?php echo $recipient['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $recipient['id']; ?>">
                                        <button type="submit" 
                                            class="px-3 py-1 text-xs font-semibold theme-btn-secondary rounded transition">
                                            Toggle
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this recipient?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $recipient['id']; ?>">
                                        <button type="submit" 
                                            class="px-3 py-1 text-xs font-semibold text-white bg-red-600 border-2 border-red-600 rounded hover:bg-red-700 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
