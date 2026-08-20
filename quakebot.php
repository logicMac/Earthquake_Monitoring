<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$activePage = 'quakebot';

// Fetch live stats for the header badges
$liveStats = ['total_events' => 0, 'latest_intensity' => 0, 'latest_mmi' => '-'];
try {
    $conn = getDBConnection();
    $r = $conn->query("SELECT COUNT(*) as total FROM seismic_logs");
    if ($r && ($row = $r->fetch_assoc())) $liveStats['total_events'] = (int)$row['total'];
    $r = $conn->query("SELECT intensity, mmi_level FROM seismic_logs ORDER BY timestamp DESC LIMIT 1");
    if ($r && ($row = $r->fetch_assoc())) {
        $liveStats['latest_intensity'] = (float)$row['intensity'];
        $liveStats['latest_mmi'] = $row['mmi_level'] ?: '-';
    }
    $conn->close();
} catch (Throwable $e) {}
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

        /* ── Chat container ─────────────────────────────────────────── */
        .chat-container {
            height: calc(100vh - 280px);
            min-height: 400px;
        }

        .message-container {
            height: 100%;
            overflow-y: auto;
            scroll-behavior: smooth;
            padding-right: 8px;
        }

        /* ── Message bubbles ────────────────────────────────────────── */
        .user-message {
            background: linear-gradient(135deg, #000000 0%, #1f2937 100%);
            color: white;
            margin-left: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }

        [data-theme="dark"] .user-message {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(59,130,246,0.25);
        }

        .bot-message {
            background-color: var(--card-bg);
            border: 1px solid var(--border-primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        [data-theme="dark"] .bot-message {
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* ── Message content (markdown) ─────────────────────────────── */
        .bot-message strong { font-weight: 700; }
        .bot-message em { font-style: italic; }
        .bot-message code {
            background: var(--bg-tertiary);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.875em;
        }
        .bot-message ul { list-style: disc; padding-left: 1.25rem; margin: 0.5rem 0; }
        .bot-message ol { list-style: decimal; padding-left: 1.25rem; margin: 0.5rem 0; }
        .bot-message li { margin: 0.25rem 0; }
        .bot-message p { margin: 0.5rem 0; }
        .bot-message p:first-child { margin-top: 0; }
        .bot-message p:last-child { margin-bottom: 0; }

        /* ── Typing indicator ───────────────────────────────────────── */
        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-tertiary);
            animation: typing-bounce 1.4s infinite ease-in-out;
        }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typing-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }

        /* ── Quick question chips ───────────────────────────────────── */
        .quick-chip {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            user-select: none;
        }
        .quick-chip:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }
        .quick-chip:active {
            transform: translateY(0);
        }

        /* ── QuakeBot avatar ────────────────────────────────────────── */
        .quakebot-avatar {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 2px solid var(--border-primary);
        }
        [data-theme="dark"] .quakebot-avatar {
            background: linear-gradient(135deg, #334155 0%, #475569 100%);
        }
        .quakebot-avatar svg { color: #60a5fa; }

        .quakebot-header-avatar {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
        }
        .quakebot-header-avatar svg { color: #ffffff; }

        /* ── Seismic wave animation ─────────────────────────────────── */
        @keyframes wave-pulse {
            0%, 100% { transform: scaleX(1); opacity: 1; }
            50% { transform: scaleX(1.15); opacity: 0.8; }
        }
        .wave-animate { animation: wave-pulse 2s ease-in-out infinite; }

        /* ── Online status pulse ────────────────────────────────────── */
        @keyframes status-pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
            70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .status-dot { animation: status-pulse 2s infinite; }

        /* ── Message slide-in animation ─────────────────────────────── */
        @keyframes msg-slide-in {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .msg-enter { animation: msg-slide-in 0.35s ease-out; }

        /* ── Send button loading state ──────────────────────────────── */
        .send-loading {
            pointer-events: none;
            opacity: 0.6;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spin { animation: spin 0.8s linear infinite; }

        /* ── Scroll-to-bottom button ────────────────────────────────── */
        .scroll-bottom-btn {
            position: absolute;
            bottom: 12px;
            right: 12px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--button-primary-bg);
            color: var(--button-primary-text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.25s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10;
        }
        .scroll-bottom-btn.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .scroll-bottom-btn:hover {
            transform: translateY(-2px) scale(1.05);
        }

        /* ── Auto-resize textarea ───────────────────────────────────── */
        .auto-resize {
            resize: none;
            overflow-y: hidden;
            transition: height 0.1s ease;
        }

        /* ── Stat badge ─────────────────────────────────────────────── */
        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="sidebar-content px-4 sm:px-6 py-4 sm:py-8 max-w-5xl mx-auto">
        <!-- ═══ QuakeBot Header ═══════════════════════════════════════ -->
        <div class="theme-card rounded-2xl p-5 sm:p-6 mb-5 animate-scale-in overflow-hidden relative">
            <!-- Decorative gradient blob -->
            <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full opacity-10" style="background: radial-gradient(circle, #2563eb 0%, transparent 70%);"></div>

            <div class="flex items-center space-x-4 relative">
                <!-- Avatar -->
                <div class="w-16 h-16 quakebot-header-avatar rounded-2xl flex items-center justify-center flex-shrink-0 relative">
                    <svg class="w-9 h-9 wave-animate" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                        <circle cx="7" cy="10" r="1.5" fill="currentColor" stroke="none"/>
                        <circle cx="13" cy="10" r="1.5" fill="currentColor" stroke="none"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6v-2M10 4l-1-1M10 4l1-1"></path>
                    </svg>
                </div>

                <!-- Title + status -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold theme-text-primary">QuakeBot</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                            <span class="w-2 h-2 bg-green-500 rounded-full status-dot"></span>
                            Online
                        </span>
                    </div>
                    <p class="text-sm theme-text-secondary mt-0.5">AI assistant for earthquake data & insights</p>
                </div>
            </div>

            <!-- Live stat badges -->
            <div class="flex items-center gap-2 mt-4 flex-wrap relative">
                <span class="stat-badge bg-blue-50 text-blue-700" style="border: 1px solid #bfdbfe;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <?php echo $liveStats['total_events']; ?> events
                </span>
                <span class="stat-badge bg-orange-50 text-orange-700" style="border: 1px solid #fed7aa;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Latest: <?php echo number_format($liveStats['latest_intensity'], 1); ?> Gal
                </span>
                <span class="stat-badge bg-purple-50 text-purple-700" style="border: 1px solid #e9d5ff;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    MMI <?php echo htmlspecialchars($liveStats['latest_mmi']); ?>
                </span>
            </div>
        </div>

        <!-- ═══ Chat Container ════════════════════════════════════════ -->
        <div class="theme-card rounded-2xl p-4 sm:p-6 animate-fade-in delay-100">
            <!-- Messages -->
            <div class="chat-container relative">
                <div id="messageContainer" class="message-container space-y-5 mb-4">
                    <!-- Welcome Message -->
                    <div class="flex items-start gap-3 msg-enter">
                        <div class="w-9 h-9 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                                <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                                <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                            </svg>
                        </div>
                        <div class="bot-message rounded-2xl rounded-tl-sm px-4 py-3 max-w-[85%] sm:max-w-[75%]">
                            <p class="theme-text-primary text-sm sm:text-base">
                                Hi! I'm <strong>QuakeBot</strong>, your earthquake monitoring assistant. I can help you understand seismic data, answer questions about earthquakes, and provide insights from the system.
                            </p>
                            <p class="theme-text-secondary text-sm mt-2">
                                Ask me anything, or tap a quick question below to get started.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Scroll to bottom button -->
                <div id="scrollBottomBtn" class="scroll-bottom-btn" onclick="scrollToBottom()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </div>

            <!-- Quick Questions -->
            <div class="mb-4">
                <p class="text-xs theme-text-tertiary uppercase font-semibold mb-2.5 tracking-wide">Quick Questions</p>
                <div class="flex flex-wrap gap-2">
                    <button onclick="askQuestion('What was the latest earthquake detected?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📊</span> Latest earthquake
                    </button>
                    <button onclick="askQuestion('How many high intensity events this month?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>⚡</span> High intensity events
                    </button>
                    <button onclick="askQuestion('Explain the MMI scale')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📚</span> MMI scale
                    </button>
                    <button onclick="askQuestion('What should I do during Level-3 alert?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>🚨</span> Level-3 actions
                    </button>
                    <button onclick="askQuestion('What is Gal and how does the sensor measure it?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>🔬</span> What is Gal?
                    </button>
                    <button onclick="askQuestion('Give me a summary of all seismic activity today')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📋</span> Today's summary
                    </button>
                </div>
            </div>

            <!-- Input Area -->
            <div class="flex items-end gap-2 sm:gap-3 border-t pt-4" style="border-color: var(--border-primary);">
                <div class="flex-1 relative">
                    <textarea
                        id="userInput"
                        rows="1"
                        placeholder="Ask about earthquakes, seismic data, safety protocols..."
                        class="auto-resize theme-input w-full px-4 py-3 pr-10 rounded-xl text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        onkeypress="handleKeyPress(event)"
                        oninput="autoResize(this)"
                    ></textarea>
                </div>
                <button
                    id="sendButton"
                    onclick="sendMessage()"
                    class="theme-btn-primary px-4 sm:px-5 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2 flex-shrink-0"
                >
                    <svg id="sendIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <svg id="sendSpinner" class="w-5 h-5 spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="hidden sm:inline">Send</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        const messageContainer = document.getElementById('messageContainer');
        const userInput = document.getElementById('userInput');
        const sendButton = document.getElementById('sendButton');
        const sendIcon = document.getElementById('sendIcon');
        const sendSpinner = document.getElementById('sendSpinner');
        const scrollBottomBtn = document.getElementById('scrollBottomBtn');

        // ── Auto-resize textarea ──────────────────────────────────────
        function autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        }

        // ── Scroll management ─────────────────────────────────────────
        function scrollToBottom() {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }

        messageContainer.addEventListener('scroll', () => {
            const isNearBottom = messageContainer.scrollHeight - messageContainer.scrollTop - messageContainer.clientHeight < 80;
            if (isNearBottom) {
                scrollBottomBtn.classList.remove('visible');
            } else {
                scrollBottomBtn.classList.add('visible');
            }
        });

        // ── Key handling ──────────────────────────────────────────────
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function askQuestion(question) {
            userInput.value = question;
            autoResize(userInput);
            sendMessage();
        }

        // ── Send / receive ────────────────────────────────────────────
        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            // Disable input + show spinner
            userInput.disabled = true;
            sendButton.classList.add('send-loading');
            sendIcon.classList.add('hidden');
            sendSpinner.classList.remove('hidden');

            // Add user message
            addMessage(message, 'user');
            userInput.value = '';
            autoResize(userInput);

            // Show typing indicator
            const typingId = showTypingIndicator();

            try {
                const response = await fetch('api/quakebot_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message })
                });

                const data = await response.json();
                removeTypingIndicator(typingId);

                if (data.success) {
                    addMessage(data.message, 'bot');
                } else {
                    addMessage(data.message || 'Sorry, I encountered an error. Please try again.', 'bot', true);
                }
            } catch (error) {
                removeTypingIndicator(typingId);
                addMessage('Connection error. Please check your internet and try again.', 'bot', true);
            }

            // Re-enable input
            userInput.disabled = false;
            sendButton.classList.remove('send-loading');
            sendIcon.classList.remove('hidden');
            sendSpinner.classList.add('hidden');
            userInput.focus();
        }

        // ── Add message to DOM ────────────────────────────────────────
        function addMessage(text, type, isError = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start gap-3 msg-enter';

            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Manila' });

            if (type === 'user') {
                messageDiv.classList.add('flex-row-reverse');
                messageDiv.innerHTML = `
                    <div class="w-9 h-9 logo-icon rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col items-end gap-1 max-w-[85%] sm:max-w-[75%]">
                        <div class="user-message rounded-2xl rounded-tr-sm px-4 py-3">
                            <p class="text-sm sm:text-base">${escapeHtml(text)}</p>
                        </div>
                        <span class="text-xs theme-text-tertiary px-1">${timeStr}</span>
                    </div>
                `;
            } else {
                const errorClass = isError ? 'border-red-300' : '';
                messageDiv.innerHTML = `
                    <div class="w-9 h-9 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                            <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col items-start gap-1 max-w-[85%] sm:max-w-[75%]">
                        <div class="bot-message rounded-2xl rounded-tl-sm px-4 py-3 ${errorClass}">
                            <div class="theme-text-primary text-sm sm:text-base">${formatBotMessage(text)}</div>
                        </div>
                        <span class="text-xs theme-text-tertiary px-1">${timeStr}</span>
                    </div>
                `;
            }

            messageContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        // ── Typing indicator ──────────────────────────────────────────
        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            const id = 'typing-' + Date.now();
            typingDiv.id = id;
            typingDiv.className = 'flex items-start gap-3 msg-enter';
            typingDiv.innerHTML = `
                <div class="w-9 h-9 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                        <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                    </svg>
                </div>
                <div class="bot-message rounded-2xl rounded-tl-sm px-4 py-4">
                    <div class="flex items-center gap-1.5">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            `;
            messageContainer.appendChild(typingDiv);
            scrollToBottom();
            return id;
        }

        function removeTypingIndicator(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        // ── HTML escape ───────────────────────────────────────────────
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ── Markdown-lite formatter for bot messages ──────────────────
        // Supports: **bold**, *italic*, `code`, - bullet lists, 1. numbered lists, line breaks
        function formatBotMessage(text) {
            // Escape HTML first
            let html = escapeHtml(text);

            // Code blocks (```...```)
            html = html.replace(/```([\s\S]*?)```/g, (m, code) =>
                `<pre style="background:var(--bg-tertiary);padding:12px;border-radius:8px;overflow-x:auto;margin:8px 0;"><code>${code.trim()}</code></pre>`
            );

            // Inline code (`...`)
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Bold (**...**)
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

            // Italic (*...*)  — avoid matching ** which is already handled
            html = html.replace(/(?<!\*)\*(?!\*)([^*]+)\*(?!\*)/g, '<em>$1</em>');

            // Split into lines for list processing
            const lines = html.split('\n');
            let result = [];
            let inUl = false, inOl = false;

            for (let line of lines) {
                const trimmed = line.trim();
                // Bullet list item
                if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
                    if (!inUl) { result.push('<ul>'); inUl = true; }
                    if (inOl) { result.push('</ol>'); inOl = false; }
                    result.push('<li>' + trimmed.substring(2) + '</li>');
                }
                // Numbered list item
                else if (/^\d+\.\s/.test(trimmed)) {
                    if (!inOl) { result.push('<ol>'); inOl = true; }
                    if (inUl) { result.push('</ul>'); inUl = false; }
                    result.push('<li>' + trimmed.replace(/^\d+\.\s/, '') + '</li>');
                }
                // Regular line
                else {
                    if (inUl) { result.push('</ul>'); inUl = false; }
                    if (inOl) { result.push('</ol>'); inOl = false; }
                    if (trimmed === '') {
                        result.push('');
                    } else {
                        result.push('<p>' + trimmed + '</p>');
                    }
                }
            }
            if (inUl) result.push('</ul>');
            if (inOl) result.push('</ol>');

            return result.join('\n');
        }

        // Focus input on load
        userInput.focus();
    </script>
</body>
</html>
