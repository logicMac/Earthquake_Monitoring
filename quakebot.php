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
    <title>QuakeBot - ND-SCPM Earthquake Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/animations.css">
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme-toggle.js"></script>
    <script src="assets/smooth-scroll.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        #mobileMenu { display: none; }
        #mobileMenu.show { display: block; }
        
        .message-container {
            max-height: calc(100vh - 350px);
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        
        .user-message {
            background: linear-gradient(135deg, #000000 0%, #1f2937 100%);
            color: white;
            margin-left: auto;
        }
        
        [data-theme="dark"] .user-message {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
        }
        
        .bot-message {
            background-color: var(--card-bg);
            border: 2px solid var(--border-primary);
        }
        
        .typing-indicator span {
            animation: typing 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% { opacity: 0.3; }
            30% { opacity: 1; }
        }
        
        .quick-question {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .quick-question:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* QuakeBot Logo - Earthquake with eyes */
        .quakebot-logo {
            background: #ffffff;
            border: 2px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        [data-theme="dark"] .quakebot-logo {
            background: #1e293b;
            border: 2px solid #334155;
        }
        
        .quakebot-logo svg {
            color: #000000;
        }
        
        [data-theme="dark"] .quakebot-logo svg {
            color: #ffffff;
        }
        
        .quakebot-avatar {
            background: #ffffff;
            border: 2px solid #e5e7eb;
        }
        
        [data-theme="dark"] .quakebot-avatar {
            background: #1e293b;
            border: 2px solid #334155;
        }
        
        .quakebot-avatar svg {
            color: #000000;
        }
        
        [data-theme="dark"] .quakebot-avatar svg {
            color: #ffffff;
        }
        
        @keyframes wave-pulse {
            0%, 100% { transform: scaleX(1); }
            50% { transform: scaleX(1.1); }
        }
        
        .wave-animate {
            animation: wave-pulse 2s ease-in-out infinite;
        }
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
                        <p class="nav-subtitle text-xs hidden sm:block">QuakeBot Assistant</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="toggleTheme()" class="theme-toggle hidden sm:flex" title="Toggle Dark/Light Mode">
                        <svg id="sunIcon" class="hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    <div class="hidden md:flex items-center space-x-2 sm:space-x-4">
                        <span class="theme-text-tertiary text-sm">Welcome, <span class="theme-text-primary font-medium"><?php echo getAdminName(); ?></span></span>
                        <a href="index.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition button-hover">
                            Dashboard
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
                    <button onclick="toggleMobileMenu()" class="md:hidden theme-btn-secondary p-2 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
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
                    <a href="index.php" class="theme-btn-secondary px-4 py-2 rounded-lg font-medium text-sm transition text-center">
                        Dashboard
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
        <!-- QuakeBot Header -->
        <div class="theme-card rounded-xl p-6 mb-6 animate-scale-in">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 quakebot-logo rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg relative">
                    <!-- Seismic Wave Character with Eyes -->
                    <svg class="w-10 h-10 wave-animate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <!-- Wave body -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                        <!-- Left eye -->
                        <circle cx="7" cy="10" r="1.5" fill="currentColor" stroke="none"/>
                        <!-- Right eye -->
                        <circle cx="13" cy="10" r="1.5" fill="currentColor" stroke="none"/>
                        <!-- Smile -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                        <!-- Antenna/sensor on top -->
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6v-2M10 4l-1-1M10 4l1-1"></path>
                    </svg>
                    <!-- Pulse effect -->
                    <div class="absolute inset-0 rounded-2xl pulse-dot opacity-30"></div>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold theme-text-primary mb-1">QuakeBot</h1>
                    <p class="theme-text-secondary">Your AI assistant for earthquake data and insights</p>
                </div>
                <div class="hidden sm:flex items-center space-x-2 px-4 py-2 bg-green-100 text-green-700 rounded-full">
                    <span class="w-2 h-2 bg-green-500 rounded-full pulse-dot"></span>
                    <span class="text-sm font-semibold">Online</span>
                </div>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="theme-card rounded-xl p-4 sm:p-6 animate-fade-in delay-100">
            <!-- Messages -->
            <div id="messageContainer" class="message-container mb-6 space-y-4">
                <!-- Welcome Message -->
                <div class="flex items-start space-x-3 animate-fade-in">
                    <div class="w-10 h-10 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                            <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                        </svg>
                    </div>
                    <div class="bot-message rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%]">
                        <p class="theme-text-primary text-sm sm:text-base">
                            👋 Hi! I'm QuakeBot, your earthquake monitoring assistant. I can help you understand seismic data, answer questions about earthquakes, and provide insights from the system. What would you like to know?
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Questions -->
            <div class="mb-6">
                <p class="text-xs theme-text-tertiary uppercase font-semibold mb-3">Quick Questions</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button onclick="askQuestion('What was the latest earthquake detected?')" class="quick-question theme-card px-4 py-3 rounded-lg text-left text-sm theme-text-secondary hover:theme-text-primary">
                        📊 Latest earthquake detected
                    </button>
                    <button onclick="askQuestion('How many high intensity events this month?')" class="quick-question theme-card px-4 py-3 rounded-lg text-left text-sm theme-text-secondary hover:theme-text-primary">
                        ⚡ High intensity events
                    </button>
                    <button onclick="askQuestion('Explain the MMI scale')" class="quick-question theme-card px-4 py-3 rounded-lg text-left text-sm theme-text-secondary hover:theme-text-primary">
                        📚 Explain MMI scale
                    </button>
                    <button onclick="askQuestion('What should I do during Level-3 alert?')" class="quick-question theme-card px-4 py-3 rounded-lg text-left text-sm theme-text-secondary hover:theme-text-primary">
                        🚨 Level-3 alert actions
                    </button>
                </div>
            </div>

            <!-- Input Area -->
            <div class="flex items-end space-x-3">
                <div class="flex-1">
                    <textarea 
                        id="userInput" 
                        rows="2" 
                        placeholder="Ask me anything about earthquakes or the monitoring system..."
                        class="theme-input w-full px-4 py-3 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-offset-2 transition text-sm sm:text-base"
                        onkeypress="handleKeyPress(event)"
                    ></textarea>
                </div>
                <button 
                    id="sendButton"
                    onclick="sendMessage()" 
                    class="theme-btn-primary px-6 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center space-x-2"
                >
                    <span class="hidden sm:inline">Send</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        const messageContainer = document.getElementById('messageContainer');
        const userInput = document.getElementById('userInput');
        const sendButton = document.getElementById('sendButton');

        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function askQuestion(question) {
            userInput.value = question;
            sendMessage();
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            // Disable input
            userInput.disabled = true;
            sendButton.disabled = true;

            // Add user message
            addMessage(message, 'user');
            userInput.value = '';

            // Show typing indicator
            const typingId = showTypingIndicator();

            try {
                const response = await fetch('api/quakebot_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();

                // Remove typing indicator
                removeTypingIndicator(typingId);

                if (data.success) {
                    addMessage(data.message, 'bot');
                } else {
                    addMessage('❌ ' + (data.message || 'Sorry, I encountered an error. Please try again.'), 'bot');
                }
            } catch (error) {
                removeTypingIndicator(typingId);
                addMessage('❌ Connection error. Please check your internet connection and try again.', 'bot');
            }

            // Re-enable input
            userInput.disabled = false;
            sendButton.disabled = false;
            userInput.focus();
        }

        function addMessage(text, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start space-x-3 animate-fade-in';
            
            if (type === 'user') {
                messageDiv.classList.add('flex-row-reverse', 'space-x-reverse');
                messageDiv.innerHTML = `
                    <div class="w-10 h-10 logo-icon rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="user-message rounded-2xl rounded-tr-none px-4 py-3 max-w-[80%]">
                        <p class="text-sm sm:text-base">${escapeHtml(text)}</p>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="w-10 h-10 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                            <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                        </svg>
                    </div>
                    <div class="bot-message rounded-2xl rounded-tl-none px-4 py-3 max-w-[80%]">
                        <p class="theme-text-primary text-sm sm:text-base">${formatBotMessage(text)}</p>
                    </div>
                `;
            }
            
            messageContainer.appendChild(messageDiv);
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            const id = 'typing-' + Date.now();
            typingDiv.id = id;
            typingDiv.className = 'flex items-start space-x-3 animate-fade-in';
            typingDiv.innerHTML = `
                <div class="w-10 h-10 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                        <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                    </svg>
                </div>
                <div class="bot-message rounded-2xl rounded-tl-none px-4 py-3">
                    <div class="typing-indicator flex space-x-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </div>
                </div>
            `;
            
            messageContainer.appendChild(typingDiv);
            messageContainer.scrollTop = messageContainer.scrollHeight;
            return id;
        }

        function removeTypingIndicator(id) {
            const element = document.getElementById(id);
            if (element) {
                element.remove();
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatBotMessage(text) {
            // Convert line breaks to <br>
            text = escapeHtml(text);
            text = text.replace(/\n/g, '<br>');
            return text;
        }
    </script>
</body>
</html>
