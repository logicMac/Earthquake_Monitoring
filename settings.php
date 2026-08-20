<?php
/**
 * Settings - Manage Account Credentials
 * Allows admin users to change their username, password, full name, and email.
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$activePage = 'settings';

$conn = getDBConnection();
$adminId = $_SESSION['admin_id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT id, username, full_name, email, created_at, last_login FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ND-SCPM Earthquake Monitoring</title>
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="apple-touch-icon" href="assets/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/animations.css">
    <link rel="stylesheet" href="assets/theme.css">
    <script src="assets/theme-toggle.js"></script>
    <script src="assets/smooth-scroll.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }

        .password-strength {
            height: 6px;
            border-radius: 3px;
            transition: all 0.3s ease;
            background: var(--bg-tertiary);
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
            width: 0%;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.875rem;
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
        .toast.success { background: #22c55e; color: white; }
        .toast.error { background: #ef4444; color: white; }

        .input-group {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-tertiary);
            transition: color 0.2s ease;
        }
        .toggle-password:hover { color: var(--text-primary); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="sidebar-content px-4 sm:px-6 py-4 sm:py-8">
        <!-- Page Header -->
        <div class="mb-6 animate-scale-in">
            <h1 class="text-2xl sm:text-3xl font-bold theme-text-primary mb-1">Account Settings</h1>
            <p class="theme-text-secondary text-sm">Manage your account credentials and profile information</p>
        </div>

        <!-- Account Info Card -->
        <div class="theme-card rounded-xl p-5 sm:p-6 mb-6 animate-fade-in delay-100">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 logo-icon rounded-2xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg font-bold theme-text-primary"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-sm theme-text-secondary">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="text-xs theme-text-tertiary mt-1">
                        <?php if ($user['email']): ?>
                            <?php echo htmlspecialchars($user['email']); ?> ·
                        <?php endif; ?>
                        Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Profile Information Section -->
        <div class="theme-card rounded-xl p-5 sm:p-6 mb-6 animate-fade-in delay-200">
            <div class="flex items-center gap-2 mb-5">
                <svg class="w-5 h-5 theme-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <h3 class="text-lg font-bold theme-text-primary">Profile Information</h3>
            </div>

            <form id="profileForm" class="space-y-4">
                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Full Name</label>
                    <input type="text" name="full_name" id="full_name"
                        value="<?php echo htmlspecialchars($user['full_name']); ?>"
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="Enter your full name">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Email Address</label>
                    <input type="email" name="email" id="email"
                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="you@ndscpm.edu.ph">
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-2">
                    <button type="submit" class="theme-btn-primary px-6 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Username Section -->
        <div class="theme-card rounded-xl p-5 sm:p-6 mb-6 animate-fade-in delay-300">
            <div class="flex items-center gap-2 mb-5">
                <svg class="w-5 h-5 theme-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <h3 class="text-lg font-bold theme-text-primary">Change Username</h3>
            </div>

            <form id="usernameForm" class="space-y-4">
                <!-- Current Username (read-only) -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Current Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>"
                        class="theme-input w-full px-4 py-3 rounded-xl opacity-60 cursor-not-allowed" readonly>
                </div>

                <!-- New Username -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">New Username</label>
                    <input type="text" name="new_username" id="new_username"
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="Enter new username" minlength="3" maxlength="50">
                    <p class="text-xs theme-text-tertiary mt-1.5">Must be 3-50 characters. This will be your new login username.</p>
                </div>

                <!-- Verify Password -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Confirm with Password</label>
                    <div class="input-group">
                        <input type="password" name="username_password" id="username_password"
                            class="theme-input w-full px-4 py-3 pr-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                            placeholder="Enter your current password to confirm">
                        <span class="toggle-password" onclick="togglePassword('username_password', this)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-2">
                    <button type="submit" class="theme-btn-primary px-6 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Username
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Section -->
        <div class="theme-card rounded-xl p-5 sm:p-6 mb-6 animate-fade-in delay-400">
            <div class="flex items-center gap-2 mb-5">
                <svg class="w-5 h-5 theme-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <h3 class="text-lg font-bold theme-text-primary">Change Password</h3>
            </div>

            <form id="passwordForm" class="space-y-4">
                <!-- Current Password -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Current Password</label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="current_password"
                            class="theme-input w-full px-4 py-3 pr-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                            placeholder="Enter your current password">
                        <span class="toggle-password" onclick="togglePassword('current_password', this)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">New Password</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password"
                            class="theme-input w-full px-4 py-3 pr-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                            placeholder="Enter new password" minlength="6"
                            oninput="checkPasswordStrength(this.value)">
                        <span class="toggle-password" onclick="togglePassword('new_password', this)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </span>
                    </div>
                    <!-- Password strength indicator -->
                    <div class="password-strength mt-2">
                        <div id="strengthBar" class="password-strength-bar"></div>
                    </div>
                    <p id="strengthText" class="text-xs theme-text-tertiary mt-1.5">Enter a password to see strength</p>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password"
                            class="theme-input w-full px-4 py-3 pr-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                            placeholder="Re-enter new password"
                            oninput="checkPasswordMatch()">
                        <span class="toggle-password" onclick="togglePassword('confirm_password', this)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </span>
                    </div>
                    <p id="matchText" class="text-xs mt-1.5"></p>
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-2">
                    <button type="submit" class="theme-btn-primary px-6 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Tips -->
        <div class="theme-card rounded-xl p-5 sm:p-6 mb-6 animate-fade-in delay-500">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-bold theme-text-primary">Security Tips</h3>
            </div>
            <ul class="space-y-2 text-sm theme-text-secondary">
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    Use a username that's hard to guess but easy for you to remember.
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    Use at least 8 characters with a mix of letters, numbers, and symbols.
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    Don't reuse passwords from other accounts.
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-blue-600 font-bold">•</span>
                    After changing your username, you'll need to log in with the new username next time.
                </li>
            </ul>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <script>
        // ── Toggle password visibility ────────────────────────────────
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const svg = btn.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29.03m3.29.03l3.01 3.01M3 3l3.01 3.01"/>';
            } else {
                input.type = 'password';
                svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }

        // ── Password strength checker ─────────────────────────────────
        function checkPasswordStrength(password) {
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            let score = 0;

            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            const levels = [
                { width: '0%',   color: '#6b7280', label: 'Enter a password to see strength' },
                { width: '20%',  color: '#ef4444', label: 'Very weak' },
                { width: '40%',  color: '#f97316', label: 'Weak' },
                { width: '60%',  color: '#eab308', label: 'Fair' },
                { width: '80%',  color: '#22c55e', label: 'Good' },
                { width: '100%', color: '#16a34a', label: 'Strong' },
            ];

            const level = levels[score];
            bar.style.width = level.width;
            bar.style.background = level.color;
            text.textContent = level.label;
            text.style.color = level.color;

            checkPasswordMatch();
        }

        // ── Password match checker ────────────────────────────────────
        function checkPasswordMatch() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('matchText');

            if (!confirmPass) {
                matchText.textContent = '';
                return;
            }
            if (newPass === confirmPass) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#22c55e';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#ef4444';
            }
        }

        // ── Toast ─────────────────────────────────────────────────────
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ── API call helper ───────────────────────────────────────────
        async function apiCall(formId, formData) {
            const btn = document.querySelector(`#${formId} button[type="submit"]`);
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-5 h-5 spin" style="animation: spin 0.8s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Saving...';

            try {
                const response = await fetch('api/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    // Update session display if username/name changed
                    if (data.new_username) {
                        // Update sidebar display
                        const sidebarUsername = document.querySelector('.sidebar-user-box .sidebar-label p:first-child');
                        if (sidebarUsername) sidebarUsername.textContent = data.new_username;
                    }
                    if (data.new_full_name) {
                        const sidebarName = document.querySelector('.sidebar-user-box .sidebar-label p:last-child');
                        if (sidebarName) sidebarName.textContent = data.new_full_name;
                    }
                    // Clear form on success
                    if (formId === 'usernameForm') {
                        document.getElementById('new_username').value = '';
                        document.getElementById('username_password').value = '';
                        // Update the "current username" read-only field
                        const currentField = document.querySelector('#usernameForm input[readonly]');
                        if (currentField && data.new_username) currentField.value = data.new_username;
                    }
                    if (formId === 'passwordForm') {
                        document.getElementById('current_password').value = '';
                        document.getElementById('new_password').value = '';
                        document.getElementById('confirm_password').value = '';
                        document.getElementById('strengthBar').style.width = '0%';
                        document.getElementById('strengthText').textContent = 'Enter a password to see strength';
                        document.getElementById('strengthText').style.color = '';
                        document.getElementById('matchText').textContent = '';
                    }
                } else {
                    showToast(data.message || 'An error occurred', 'error');
                }
            } catch (error) {
                showToast('Connection error. Please try again.', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }

        // ── Form handlers ─────────────────────────────────────────────
        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await apiCall('profileForm', {
                action: 'update_profile',
                full_name: document.getElementById('full_name').value.trim(),
                email: document.getElementById('email').value.trim()
            });
        });

        document.getElementById('usernameForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const newUsername = document.getElementById('new_username').value.trim();
            const password = document.getElementById('username_password').value;

            if (newUsername.length < 3) {
                showToast('Username must be at least 3 characters', 'error');
                return;
            }
            if (!password) {
                showToast('Please enter your current password to confirm', 'error');
                return;
            }

            await apiCall('usernameForm', {
                action: 'change_username',
                new_username: newUsername,
                password: password
            });
        });

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPass = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (!currentPass) {
                showToast('Please enter your current password', 'error');
                return;
            }
            if (newPass.length < 6) {
                showToast('New password must be at least 6 characters', 'error');
                return;
            }
            if (newPass !== confirmPass) {
                showToast('Passwords do not match', 'error');
                return;
            }

            await apiCall('passwordForm', {
                action: 'change_password',
                current_password: currentPass,
                new_password: newPass
            });
        });
    </script>
</body>
</html>
