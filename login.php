<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $conn = getDBConnection();
    
    if (login($username, $password, $conn)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
    
    $conn->close();
}

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ND-SCPM Earthquake Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/animations.css">
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme-toggle.js"></script>
    <script src="assets/smooth-scroll.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-image {
            background-image: url('assets/480171507_1146452367490940_6195091868423627105_n (1).jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        .bg-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.5) 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        [data-theme="dark"] .glass-effect {
            background: rgba(30, 41, 59, 0.95);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="min-h-screen flex">
    <!-- Left Side - Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 relative">
        <div class="w-full max-w-md animate-fade-in">
            <!-- Header with Logo and Theme Toggle -->
            <div class="flex items-center justify-between mb-8 sm:mb-10">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 logo-icon rounded-xl flex items-center justify-center shadow-xl animate-scale-in">
                        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold theme-text-primary">ND-SCPM</h1>
                        <p class="text-xs theme-text-tertiary">Earthquake Monitor</p>
                    </div>
                </div>
                <!-- Theme Toggle -->
                <button onclick="toggleTheme()" class="theme-toggle animate-scale-in delay-100" title="Toggle Dark/Light Mode">
                    <svg id="sunIcon" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg id="moonIcon" class="" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>
            </div>

            <!-- Welcome Text -->
            <div class="mb-8 animate-fade-in delay-200">
                <h2 class="text-3xl sm:text-4xl font-bold theme-text-primary mb-3">Welcome Back</h2>
                <p class="text-base sm:text-lg theme-text-secondary">Sign in to access the earthquake monitoring dashboard</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg animate-shake">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-semibold">Login Failed</p>
                        <p class="text-sm"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-5 animate-fade-in delay-300">
                <!-- Username Field -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Username</label>
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 theme-text-tertiary pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <input type="text" name="username" required 
                            class="theme-input w-full pl-10 pr-4 py-3.5 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-200 text-sm sm:text-base"
                            style="focus:ring-color: var(--button-primary-bg);"
                            placeholder="Enter your username"
                            autocomplete="username">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Password</label>
                    <div class="relative">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 theme-text-tertiary pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <input type="password" name="password" required 
                            class="theme-input w-full pl-10 pr-4 py-3.5 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-200 text-sm sm:text-base"
                            placeholder="Enter your password"
                            autocomplete="current-password">
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900">
                        <span class="ml-2 theme-text-secondary">Remember me</span>
                    </label>
                    <a href="#" class="theme-text-tertiary hover:underline font-medium transition">Forgot password?</a>
                </div>

                <!-- Login Button -->
                <button type="submit" 
                    class="theme-btn-primary w-full font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] transition duration-200 text-base flex items-center justify-center space-x-2">
                    <span>Sign In</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full" style="border-top: 1px solid var(--border-primary);"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 theme-text-tertiary" style="background-color: var(--bg-secondary);">System Information</span>
                </div>
            </div>

            <!-- System Info Cards -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="theme-card rounded-xl p-4 feature-card border-l-4 border-green-500">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-xs theme-text-tertiary">Status</p>
                            <p class="text-sm font-bold text-green-600">Active</p>
                        </div>
                    </div>
                </div>
                <div class="theme-card rounded-xl p-4 feature-card border-l-4 border-blue-500">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-xs theme-text-tertiary">Monitoring</p>
                            <p class="text-sm font-bold text-blue-600">24/7</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs theme-text-tertiary">
                <p>© 2024 Notre Dame - Siena College of Polomolok</p>
                <p class="mt-1">Earthquake Monitoring System v1.0</p>
            </div>
        </div>
    </div>

    <!-- Right Side - Image with Info Overlay -->
    <div class="hidden lg:block lg:w-1/2 bg-image">
        <div class="relative h-full flex flex-col justify-between p-12 text-white">
            <!-- Top Info -->
            <div class="animate-fade-in-down">
                <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-md rounded-full px-4 py-2 mb-6">
                    <div class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></div>
                    <span class="text-sm font-medium">System Online</span>
                </div>
                <h2 class="text-4xl xl:text-5xl font-bold mb-4 drop-shadow-lg leading-tight">
                    Real-Time<br/>Earthquake<br/>Monitoring
                </h2>
                <p class="text-lg xl:text-xl text-gray-100 drop-shadow-md max-w-md">
                    Advanced seismic detection and alert system for campus safety
                </p>
            </div>

            <!-- Bottom Info -->
            <div class="text-center animate-fade-in delay-600">
                <p class="text-sm text-gray-200">Notre Dame - Siena College of Polomolok</p>
                <p class="text-xs text-gray-300 mt-1">Powered by ESP32 & MPU6050 Technology</p>
            </div>
        </div>
    </div>
</body>
</html>
