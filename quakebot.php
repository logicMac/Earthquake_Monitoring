<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$activePage = 'quakebot';

// Fetch live stats for the header badges
$liveStats = ['total_events' => 0, 'latest_intensity' => 0, 'latest_mmi' => '-', 'max_intensity' => 0, 'alert_count' => 0];
try {
    $conn = getDBConnection();
    $r = $conn->query("SELECT COUNT(*) as total, MAX(intensity) as max_int, SUM(alert_sent) as alerts FROM seismic_logs");
    if ($r && ($row = $r->fetch_assoc())) {
        $liveStats['total_events'] = (int)$row['total'];
        $liveStats['max_intensity'] = (float)($row['max_int'] ?? 0);
        $liveStats['alert_count'] = (int)($row['alerts'] ?? 0);
    }
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
            height: calc(100vh - 320px);
            min-height: 380px;
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
            position: relative;
        }

        [data-theme="dark"] .bot-message {
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* ── Message action bar (copy, regenerate) ──────────────────── */
        .msg-actions {
            display: flex;
            gap: 4px;
            margin-top: 6px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .msg-group:hover .msg-actions { opacity: 1; }

        .msg-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-tertiary);
            background: transparent;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .msg-action-btn:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-color: var(--border-primary);
        }
        .msg-action-btn.copied {
            color: #22c55e;
        }

        /* ── Markdown content ───────────────────────────────────────── */
        .bot-message strong { font-weight: 700; }
        .bot-message em { font-style: italic; }
        .bot-message {
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
            min-width: 0;
        }
        .bot-message code {
            background: var(--bg-tertiary);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.875em;
            overflow-wrap: break-word;
        }
        .bot-message pre {
            background: var(--bg-tertiary);
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 8px 0;
        }
        .bot-message pre code { background: none; padding: 0; }
        .bot-message ul { list-style: disc; padding-left: 1.25rem; margin: 0.5rem 0; }
        .bot-message ol { list-style: decimal; padding-left: 1.25rem; margin: 0.5rem 0; }
        .bot-message li { margin: 0.25rem 0; }
        .bot-message p { margin: 0.5rem 0; }
        .bot-message p:first-child { margin-top: 0; }
        .bot-message p:last-child { margin-bottom: 0; }
        .bot-message h3 { font-weight: 700; font-size: 1.05em; margin: 0.75rem 0 0.25rem; }

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
            border-color: #2563eb !important;
        }
        .quick-chip:active { transform: translateY(0); }

        /* ── Follow-up chips (after bot reply) ──────────────────────── */
        .followup-chip {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .followup-chip:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
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
        .send-loading { pointer-events: none; opacity: 0.6; }
        @keyframes spin { to { transform: rotate(360deg); } }
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
            opacity: 1; visibility: visible; transform: translateY(0);
        }
        .scroll-bottom-btn:hover { transform: translateY(-2px) scale(1.05); }

        /* ── Auto-resize textarea ───────────────────────────────────── */
        .auto-resize { resize: none; overflow-y: hidden; transition: height 0.1s ease; }

        /* ── Stat badge ─────────────────────────────────────────────── */
        .stat-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: 9999px;
            font-size: 0.75rem; font-weight: 600;
        }

        /* ── Clear chat button ──────────────────────────────────────── */
        .clear-chat-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 10px;
            font-size: 0.8rem; font-weight: 600;
            background: var(--button-secondary-bg);
            color: var(--button-secondary-text);
            border: 1px solid var(--border-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .clear-chat-btn:hover {
            background: #ef4444; color: white; border-color: #ef4444;
            transform: translateY(-1px);
        }

        /* ── Capability card (welcome screen) ───────────────────────── */
        .capability-card {
            transition: all 0.25s ease;
            cursor: pointer;
        }
        .capability-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-color: #2563eb;
        }

        /* ── Stop button ────────────────────────────────────────────── */
        .stop-btn {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .stop-btn:hover { background: #dc2626; transform: translateY(-1px); }

        /* ── Toast notification ─────────────────────────────────────── */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--text-primary);
            color: var(--bg-primary);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 9999;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        .toast.show {
            opacity: 1; visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="sidebar-content px-4 sm:px-6 py-4 sm:py-8 overflow-x-hidden">
        <!-- ═══ QuakeBot Header ═══════════════════════════════════════ -->
        <div class="theme-card rounded-2xl p-5 sm:p-6 mb-5 animate-scale-in overflow-hidden relative">
            <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full opacity-10" style="background: radial-gradient(circle, #2563eb 0%, transparent 70%);"></div>

            <div class="flex items-center justify-between gap-4 relative">
                <div class="flex items-center space-x-4 min-w-0">
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

                <!-- Clear chat button -->
                <button id="clearChatBtn" class="clear-chat-btn flex-shrink-0" onclick="clearChat()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span class="hidden sm:inline">Clear</span>
                </button>
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
                <span class="stat-badge bg-red-50 text-red-700" style="border: 1px solid #fecaca;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <?php echo $liveStats['alert_count']; ?> alerts sent
                </span>
            </div>
        </div>

        <!-- ═══ Chat Container ════════════════════════════════════════ -->
        <div class="theme-card rounded-2xl p-4 sm:p-6 animate-fade-in delay-100">
            <!-- Messages -->
            <div class="chat-container relative">
                <div id="messageContainer" class="message-container space-y-5 mb-4">
                    <!-- Welcome screen will be rendered by JS -->
                </div>

                <!-- Scroll to bottom button -->
                <div id="scrollBottomBtn" class="scroll-bottom-btn" onclick="scrollToBottom()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </div>

            <!-- Quick Questions -->
            <div id="quickQuestionsSection" class="mb-4">
                <p class="text-xs theme-text-tertiary uppercase font-semibold mb-2.5 tracking-wide">Quick Questions</p>
                <div class="flex flex-wrap gap-2">
                    <button onclick="askQuestion('What was the latest earthquake detected?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📊</span> Latest earthquake
                    </button>
                    <button onclick="askQuestion('How many high intensity events this month?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>⚡</span> High intensity events
                    </button>
                    <button onclick="askQuestion('Explain the MMI scale in simple terms')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📚</span> MMI scale
                    </button>
                    <button onclick="askQuestion('What should I do during a Level-3 alert?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>🚨</span> Level-3 actions
                    </button>
                    <button onclick="askQuestion('What is Gal and how does the ESP32 sensor measure it?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>🔬</span> What is Gal?
                    </button>
                    <button onclick="askQuestion('Give me a summary of all seismic activity today')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📋</span> Today's summary
                    </button>
                    <button onclick="askQuestion('What is the difference between magnitude and intensity?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>🧪</span> Magnitude vs intensity
                    </button>
                    <button onclick="askQuestion('How does the SMS alert system work?')" class="quick-chip theme-card px-3.5 py-2 rounded-full text-sm theme-text-secondary border border-gray-200 flex items-center gap-1.5">
                        <span>📱</span> SMS alert system
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
                <button
                    id="stopButton"
                    class="stop-btn hidden"
                    onclick="stopGeneration()"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                    <span class="hidden sm:inline">Stop</span>
                </button>
            </div>

            <!-- Input hint -->
            <p class="text-xs theme-text-tertiary mt-2 text-center">
                Press <kbd class="px-1.5 py-0.5 rounded border text-xs" style="border-color: var(--border-primary); background: var(--bg-tertiary);">Enter</kbd> to send ·
                <kbd class="px-1.5 py-0.5 rounded border text-xs" style="border-color: var(--border-primary); background: var(--bg-tertiary);">Shift+Enter</kbd> for new line
            </p>
        </div>
    </div>

    <!-- Toast notification -->
    <div id="toast" class="toast"></div>

    <script>
        const messageContainer = document.getElementById('messageContainer');
        const userInput = document.getElementById('userInput');
        const sendButton = document.getElementById('sendButton');
        const sendIcon = document.getElementById('sendIcon');
        const sendSpinner = document.getElementById('sendSpinner');
        const scrollBottomBtn = document.getElementById('scrollBottomBtn');
        const stopButton = document.getElementById('stopButton');
        const quickQuestionsSection = document.getElementById('quickQuestionsSection');
        const toast = document.getElementById('toast');

        // ── Conversation history (sent to API for multi-turn context) ──
        let conversationHistory = [];
        let lastUserMessage = '';
        let abortController = null;
        let messageCounter = 0;

        // ── Toast ─────────────────────────────────────────────────────
        function showToast(text) {
            toast.textContent = text;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

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
            scrollBottomBtn.classList.toggle('visible', !isNearBottom);
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

        // ── Welcome screen ────────────────────────────────────────────
        function renderWelcomeScreen() {
            messageContainer.innerHTML = `
                <div class="msg-enter">
                    <div class="flex items-start gap-3 mb-4">
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
                                Hi! I'm <strong>QuakeBot</strong>, your AI earthquake monitoring assistant.
                            </p>
                            <p class="theme-text-secondary text-sm mt-2">
                                I can answer questions about seismic data, explain earthquake concepts, and provide real-time insights from the monitoring system. How can I help you today?
                            </p>
                        </div>
                    </div>

                    <!-- Capability cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 ml-12">
                        <div class="capability-card theme-card rounded-xl p-4 border" style="border-color: var(--border-primary);" onclick="askQuestion('What was the latest earthquake detected?')">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xl">📊</span>
                                <span class="font-semibold text-sm theme-text-primary">Live Data</span>
                            </div>
                            <p class="text-xs theme-text-secondary">Ask about recent earthquakes, intensity levels, and event history</p>
                        </div>
                        <div class="capability-card theme-card rounded-xl p-4 border" style="border-color: var(--border-primary);" onclick="askQuestion('Explain the MMI scale in simple terms')">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xl">📚</span>
                                <span class="font-semibold text-sm theme-text-primary">Learn</span>
                            </div>
                            <p class="text-xs theme-text-secondary">Understand MMI scale, Gal units, magnitude vs intensity, and more</p>
                        </div>
                        <div class="capability-card theme-card rounded-xl p-4 border" style="border-color: var(--border-primary);" onclick="askQuestion('What should I do during a Level-3 alert?')">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xl">🚨</span>
                                <span class="font-semibold text-sm theme-text-primary">Safety</span>
                            </div>
                            <p class="text-xs theme-text-secondary">Get guidance on what to do during different alert levels</p>
                        </div>
                    </div>
                </div>
            `;
        }

        // ── Clear chat ────────────────────────────────────────────────
        function clearChat() {
            conversationHistory = [];
            lastUserMessage = '';
            messageCounter = 0;
            renderWelcomeScreen();
            quickQuestionsSection.style.display = 'block';
            showToast('Conversation cleared');
            userInput.focus();
        }

        // ── Stop generation ───────────────────────────────────────────
        function stopGeneration() {
            if (abortController) {
                abortController.abort();
                abortController = null;
            }
        }

        // ── Send / receive ────────────────────────────────────────────
        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            // Hide quick questions after first message
            quickQuestionsSection.style.display = 'none';

            lastUserMessage = message;

            // Disable input + show spinner + show stop button
            userInput.disabled = true;
            sendButton.classList.add('send-loading');
            sendIcon.classList.add('hidden');
            sendSpinner.classList.remove('hidden');
            stopButton.classList.remove('hidden');

            // Add user message to DOM
            addMessage(message, 'user');

            // Add to conversation history
            conversationHistory.push({ role: 'user', content: message });

            // Clear input
            userInput.value = '';
            autoResize(userInput);

            // Show typing indicator
            const typingId = showTypingIndicator();

            // Create abort controller
            abortController = new AbortController();

            try {
                const response = await fetch('api/quakebot_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        history: conversationHistory.slice(-20) // send last 20 messages (10 turns)
                    }),
                    signal: abortController.signal
                });

                const data = await response.json();
                removeTypingIndicator(typingId);

                if (data.success) {
                    addMessage(data.message, 'bot');
                    conversationHistory.push({ role: 'assistant', content: data.message });
                } else {
                    addMessage(data.message || 'Sorry, I encountered an error. Please try again.', 'bot', true);
                }
            } catch (error) {
                removeTypingIndicator(typingId);
                if (error.name === 'AbortError') {
                    addMessage('⏹️ Generation stopped by user.', 'bot', true);
                    // Remove the last user message from history since it wasn't answered
                    conversationHistory.pop();
                } else {
                    addMessage('Connection error. Please check your internet and try again.', 'bot', true);
                }
            }

            // Re-enable input
            abortController = null;
            userInput.disabled = false;
            sendButton.classList.remove('send-loading');
            sendIcon.classList.remove('hidden');
            sendSpinner.classList.add('hidden');
            stopButton.classList.add('hidden');
            userInput.focus();
        }

        // ── Regenerate last response ──────────────────────────────────
        async function regenerateLastResponse() {
            if (!lastUserMessage) return;

            // Remove the last bot message from DOM
            const messages = messageContainer.querySelectorAll('[data-msg-id]');
            if (messages.length > 0) {
                messages[messages.length - 1].remove();
            }

            // Remove last assistant message from history
            if (conversationHistory.length > 0 && conversationHistory[conversationHistory.length - 1].role === 'assistant') {
                conversationHistory.pop();
            }

            // Re-send the last user message without adding a new user bubble
            const message = lastUserMessage;

            userInput.disabled = true;
            sendButton.classList.add('send-loading');
            sendIcon.classList.add('hidden');
            sendSpinner.classList.remove('hidden');
            stopButton.classList.remove('hidden');

            const typingId = showTypingIndicator();
            abortController = new AbortController();

            try {
                const response = await fetch('api/quakebot_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        history: conversationHistory.slice(-20)
                    }),
                    signal: abortController.signal
                });

                const data = await response.json();
                removeTypingIndicator(typingId);

                if (data.success) {
                    addMessage(data.message, 'bot');
                    conversationHistory.push({ role: 'assistant', content: data.message });
                } else {
                    addMessage(data.message || 'Sorry, I encountered an error. Please try again.', 'bot', true);
                }
            } catch (error) {
                removeTypingIndicator(typingId);
                if (error.name !== 'AbortError') {
                    addMessage('Connection error. Please try again.', 'bot', true);
                }
            }

            abortController = null;
            userInput.disabled = false;
            sendButton.classList.remove('send-loading');
            sendIcon.classList.remove('hidden');
            sendSpinner.classList.add('hidden');
            stopButton.classList.add('hidden');
            userInput.focus();
        }

        // ── Copy message to clipboard ─────────────────────────────────
        function copyMessage(btn, text) {
            // Strip HTML tags for plain text copy
            const tmp = document.createElement('div');
            tmp.innerHTML = text;
            const plainText = tmp.textContent || tmp.innerText;

            navigator.clipboard.writeText(plainText).then(() => {
                btn.classList.add('copied');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = originalHTML;
                }, 1500);
                showToast('Copied to clipboard');
            }).catch(() => {
                showToast('Copy failed');
            });
        }

        // ── Add message to DOM ────────────────────────────────────────
        function addMessage(text, type, isError = false) {
            const msgId = 'msg-' + (++messageCounter);
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start gap-3 msg-enter msg-group';
            messageDiv.setAttribute('data-msg-id', msgId);

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
                    <div class="flex flex-col items-end gap-1 max-w-[85%] sm:max-w-[75%] min-w-0">
                        <div class="user-message rounded-2xl rounded-tr-sm px-4 py-3">
                            <p class="text-sm sm:text-base">${escapeHtml(text)}</p>
                        </div>
                        <span class="text-xs theme-text-tertiary px-1">${timeStr}</span>
                    </div>
                `;
            } else {
                const errorClass = isError ? 'border-red-300' : '';
                const formattedContent = isError ? escapeHtml(text) : formatBotMessage(text);
                const actionsHTML = isError ? '' : `
                    <div class="msg-actions">
                        <button class="msg-action-btn" onclick="copyMessage(this, '${escapeForAttr(text)}')">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12a2 2 0 002 2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8z"/></svg>
                            Copy
                        </button>
                        <button class="msg-action-btn" onclick="regenerateLastResponse()">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Regenerate
                        </button>
                    </div>
                `;

                messageDiv.innerHTML = `
                    <div class="w-9 h-9 quakebot-avatar rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c0 0 2-4 4-4s2 8 4 8 2-8 4-8 2 4 4 4"></path>
                            <circle cx="7" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <circle cx="13" cy="10" r="1.2" fill="currentColor" stroke="none"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14c0.5 0.5 1.5 1 2 1s1.5-0.5 2-1"></path>
                        </svg>
                    </div>
                    <div class="flex flex-col items-start gap-1 max-w-[85%] sm:max-w-[75%] min-w-0">
                        <div class="bot-message rounded-2xl rounded-tl-sm px-4 py-3 ${errorClass}">
                            <div class="theme-text-primary text-sm sm:text-base">${formattedContent}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs theme-text-tertiary px-1">${timeStr}</span>
                            ${actionsHTML}
                        </div>
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

        // Escape for use inside HTML attribute (single quotes)
        function escapeForAttr(text) {
            return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n');
        }

        // ── Markdown-lite formatter for bot messages ──────────────────
        function formatBotMessage(text) {
            let html = escapeHtml(text);

            // Headings (### ...)
            html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');

            // Code blocks (```...```)
            html = html.replace(/```([\s\S]*?)```/g, (m, code) =>
                `<pre><code>${code.trim()}</code></pre>`
            );

            // Inline code (`...`)
            html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Bold (**...**)
            html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

            // Italic (*...*)
            html = html.replace(/(?<!\*)\*(?!\*)([^*]+)\*(?!\*)/g, '<em>$1</em>');

            // Split into lines for list processing
            const lines = html.split('\n');
            let result = [];
            let inUl = false, inOl = false;

            for (let line of lines) {
                const trimmed = line.trim();
                if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
                    if (!inUl) { result.push('<ul>'); inUl = true; }
                    if (inOl) { result.push('</ol>'); inOl = false; }
                    result.push('<li>' + trimmed.substring(2) + '</li>');
                }
                else if (/^\d+\.\s/.test(trimmed)) {
                    if (!inOl) { result.push('<ol>'); inOl = true; }
                    if (inUl) { result.push('</ul>'); inUl = false; }
                    result.push('<li>' + trimmed.replace(/^\d+\.\s/, '') + '</li>');
                }
                else {
                    if (inUl) { result.push('</ul>'); inUl = false; }
                    if (inOl) { result.push('</ol>'); inOl = false; }
                    // Skip lines that are already HTML (pre, h3)
                    if (trimmed.startsWith('<pre>') || trimmed.startsWith('<h3>')) {
                        result.push(trimmed);
                    } else if (trimmed === '') {
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

        // ── Initialize ────────────────────────────────────────────────
        renderWelcomeScreen();
        userInput.focus();
    </script>
</body>
</html>
