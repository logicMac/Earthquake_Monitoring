<?php
/**
 * Shared Admin Sidebar
 * Include after auth check. Set $activePage before including to highlight
 * the current nav item. Valid values: dashboard, quakebot, reports, recipients, settings
 *
 * Usage:
 *   $activePage = 'dashboard';
 *   requireLogin();
 *   include 'includes/sidebar.php';
 */
$activePage = $activePage ?? '';
$adminName = getAdminName();

$navItems = [
    'dashboard'  => ['href' => 'index.php',             'label' => 'Dashboard',  'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    'quakebot'   => ['href' => 'quakebot.php',          'label' => 'QuakeBot',   'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
    'reports'    => ['href' => 'reports.php',           'label' => 'Reports',    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    'recipients' => ['href' => 'manage_recipients.php', 'label' => 'Recipients', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
    'settings'   => ['href' => 'settings.php',          'label' => 'Settings',   'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.428 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.428 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.428-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
];
?>
<!-- Mobile Top Bar -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-4 py-3 shadow-sm" style="background-color: var(--nav-bg); border-bottom: 1px solid var(--nav-border);">
    <button onclick="toggleSidebar()" class="theme-btn-secondary p-2 rounded-lg" aria-label="Open menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
    <div class="flex items-center space-x-2">
        <div class="w-8 h-8 logo-icon rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
        </div>
        <span class="font-bold text-sm theme-text-primary">EDSSMS</span>
    </div>
    <button onclick="toggleTheme()" class="theme-toggle" aria-label="Toggle theme">
        <svg id="sunIconMobile" class="hidden w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <svg id="moonIconMobile" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>
</div>

<!-- Sidebar Overlay (mobile only) -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="no-print fixed top-0 left-0 h-full z-50 flex flex-col transition-all duration-300 sidebar-expanded md:translate-x-0 -translate-x-full"
       style="background-color: var(--nav-bg); border-right: 1px solid var(--nav-border);">
    <!-- Logo / Brand + Collapse Toggle -->
    <div class="flex items-center px-4 py-5 flex-shrink-0 sidebar-header" style="border-bottom: 1px solid var(--nav-border);">
        <div class="flex items-center space-x-3 flex-1 min-w-0 sidebar-brand">
            <div class="w-11 h-11 logo-icon rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div class="sidebar-label min-w-0">
                <h1 class="text-base font-bold theme-text-primary whitespace-nowrap">EDSSMS</h1>
                <p class="text-xs theme-text-tertiary whitespace-nowrap">ND-SCPM</p>
            </div>
        </div>
        <!-- Desktop collapse hamburger -->
        <button onclick="toggleSidebarCollapse()" class="hidden md:flex sidebar-collapse-btn flex-shrink-0" aria-label="Collapse sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <!-- Nav Links -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-1">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?php echo $item['href']; ?>"
               class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?php echo $activePage === $key ? 'sidebar-active' : 'sidebar-link'; ?>"
               title="<?php echo $item['label']; ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $item['icon']; ?>"></path>
                </svg>
                <span class="sidebar-label whitespace-nowrap"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Section -->
    <div class="flex-shrink-0 px-3 py-4 space-y-3" style="border-top: 1px solid var(--nav-border);">
        <!-- Admin User -->
        <div class="flex items-center space-x-3 px-3 py-2 rounded-lg sidebar-user-box" style="background-color: var(--bg-tertiary);">
            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background-color: var(--button-primary-bg);">
                <span class="text-sm font-bold" style="color: var(--button-primary-text);">
                    <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                </span>
            </div>
            <div class="min-w-0 sidebar-label">
                <p class="text-sm font-semibold theme-text-primary truncate"><?php echo htmlspecialchars($adminName); ?></p>
                <p class="text-xs theme-text-tertiary">Administrator</p>
            </div>
        </div>

        <!-- Theme Toggle + Logout -->
        <div class="sidebar-bottom-buttons flex items-center space-x-2">
            <button onclick="toggleTheme()" class="theme-toggle flex-1 flex items-center justify-center space-x-2 py-2 rounded-lg text-sm font-medium transition" title="Toggle Theme">
                <svg id="sunIcon" class="hidden w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg id="moonIcon" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
                <span class="sidebar-label hidden lg:inline">Theme</span>
            </button>
            <a href="logout.php" class="theme-btn-primary flex-1 flex items-center justify-center space-x-2 py-2 rounded-lg text-sm font-semibold transition" title="Logout">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="sidebar-label">Logout</span>
            </a>
        </div>
    </div>
</aside>

<script>
    // Mobile: open/close sidebar (slide in/out)
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('-translate-x-full');
        if (overlay) overlay.classList.toggle('hidden');
    }

    // Desktop: collapse/expand sidebar (icon-only vs full)
    function toggleSidebarCollapse() {
        const sidebar = document.getElementById('sidebar');
        const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        sidebar.classList.toggle('sidebar-expanded', !isCollapsed);
        // Persist preference
        try { localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0'); } catch(e) {}
        // Dispatch event so pages can adjust content margin
        window.dispatchEvent(new Event('sidebarToggle'));
    }

    // Restore collapsed state from localStorage on desktop
    (function() {
        try {
            if (localStorage.getItem('sidebarCollapsed') === '1') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.add('sidebar-collapsed');
                    sidebar.classList.remove('sidebar-expanded');
                }
            }
        } catch(e) {}
    })();

    // Adjust content margin when sidebar toggles between expanded/collapsed
    function updateContentMargin() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;
        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        const contents = document.querySelectorAll('.sidebar-content');
        contents.forEach(function(el) {
            el.classList.toggle('sidebar-collapsed-content', isCollapsed);
        });
    }
    // Run on load, on toggle, and on resize
    document.addEventListener('DOMContentLoaded', updateContentMargin);
    window.addEventListener('sidebarToggle', updateContentMargin);
    window.addEventListener('resize', updateContentMargin);

    // Close mobile sidebar when resizing to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.add('hidden');
        }
    });

    // Update theme icons
    function updateToggleIcon() {
        const theme = document.documentElement.getAttribute('data-theme');
        ['sunIcon', 'sunIconMobile'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('hidden', theme !== 'dark');
        });
        ['moonIcon', 'moonIconMobile'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('hidden', theme === 'dark');
        });
    }
    document.addEventListener('DOMContentLoaded', updateToggleIcon);
</script>

<style>
    /* Expanded (default) - 256px wide */
    .sidebar-expanded {
        width: 16rem; /* 256px */
    }
    /* Collapsed - icon only, 64px wide */
    .sidebar-collapsed {
        width: 4rem; /* 64px */
    }
    .sidebar-collapsed .sidebar-label {
        display: none !important;
    }
    .sidebar-collapsed .sidebar-brand {
        justify-content: center;
    }
    .sidebar-collapsed .sidebar-header {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        justify-content: center;
    }
    .sidebar-collapsed .sidebar-collapse-btn svg {
        transform: rotate(180deg);
    }
    .sidebar-collapsed nav a {
        justify-content: center;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .sidebar-collapsed .sidebar-user-box {
        justify-content: center;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    /* Collapsed: stack bottom buttons vertically, icon-only */
    .sidebar-collapsed .sidebar-bottom-buttons {
        flex-direction: column;
        space-y: 0.5rem;
        gap: 0.5rem;
    }
    .sidebar-collapsed .sidebar-bottom-buttons > * {
        width: 100%;
        margin-left: 0 !important;
        justify-content: center;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    /* Nav link colors */
    .sidebar-link {
        color: var(--text-secondary);
    }
    .sidebar-link:hover {
        background-color: var(--button-secondary-bg);
        color: var(--text-primary);
    }
    .sidebar-active {
        background-color: var(--button-primary-bg);
        color: var(--button-primary-text);
    }
    .sidebar-active svg {
        color: var(--button-primary-text);
    }

    /* Collapse button styling */
    .sidebar-collapse-btn {
        width: 32px;
        height: 32px;
        border-radius: 0.5rem;
        align-items: center;
        justify-content: center;
        background-color: var(--button-secondary-bg);
        color: var(--text-secondary);
        border: 1px solid var(--border-secondary);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sidebar-collapse-btn:hover {
        background-color: var(--button-secondary-hover);
        color: var(--text-primary);
    }
    .sidebar-collapse-btn svg {
        transition: transform 0.3s ease;
    }

    /* Mobile top bar spacer */
    .mobile-top-spacer { height: 56px; }
    @media (min-width: 768px) {
        .mobile-top-spacer { display: none; }
        /* Default expanded margin on desktop (JS overrides if collapsed) */
        .sidebar-content { margin-left: 16rem !important; transition: margin-left 0.3s ease; }
        .sidebar-content.sidebar-collapsed-content { margin-left: 4rem !important; }
    }
    @media (max-width: 767px) {
        .sidebar-content { margin-left: 0 !important; }
    }
    @media print {
        aside, .mobile-top-spacer, #sidebarOverlay { display: none !important; }
    }
</style>
