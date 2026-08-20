<?php
/**
 * Manage Alert Recipients
 * Enhanced UI with search, filter, modal forms, and edit functionality.
 */
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
$activePage = 'recipients';

$conn = getDBConnection();
$message = '';
$messageType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $category = $_POST['category'] ?? 'student';

                if (empty($name) || empty($phone)) {
                    $message = "Name and phone number are required";
                    $messageType = 'error';
                } else {
                    $stmt = $conn->prepare("INSERT INTO alert_recipients (name, phone_number, category) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $name, $phone, $category);
                    if ($stmt->execute()) {
                        $message = "Recipient added successfully";
                    } else {
                        $message = "Error: " . $conn->error;
                        $messageType = 'error';
                    }
                    $stmt->close();
                }
                break;

            case 'edit':
                $id = intval($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $category = $_POST['category'] ?? 'student';

                if (empty($name) || empty($phone) || $id <= 0) {
                    $message = "All fields are required";
                    $messageType = 'error';
                } else {
                    $stmt = $conn->prepare("UPDATE alert_recipients SET name = ?, phone_number = ?, category = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $phone, $category, $id);
                    if ($stmt->execute()) {
                        $message = "Recipient updated successfully";
                    } else {
                        $message = "Error: " . $conn->error;
                        $messageType = 'error';
                    }
                    $stmt->close();
                }
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

// Get stats
$total = $conn->query("SELECT COUNT(*) as count FROM alert_recipients")->fetch_assoc()['count'];
$active = $conn->query("SELECT COUNT(*) as count FROM alert_recipients WHERE is_active = 1")->fetch_assoc()['count'];
$students = $conn->query("SELECT COUNT(*) as count FROM alert_recipients WHERE category = 'student'")->fetch_assoc()['count'];
$faculty = $conn->query("SELECT COUNT(*) as count FROM alert_recipients WHERE category = 'faculty'")->fetch_assoc()['count'];
$staff = $conn->query("SELECT COUNT(*) as count FROM alert_recipients WHERE category = 'staff'")->fetch_assoc()['count'];
$admins = $conn->query("SELECT COUNT(*) as count FROM alert_recipients WHERE category = 'admin'")->fetch_assoc()['count'];
$inactive = $total - $active;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipients - ND-SCPM Earthquake Monitoring</title>
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

        /* ── Category badge colors ──────────────────────────────────── */
        .badge-student { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-faculty { background: #f3e8ff; color: #6b21a8; border: 1px solid #c4b5fd; }
        .badge-staff   { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .badge-admin   { background: #e0e7ff; color: #3730a3; border: 1px solid #a5b4fc; }

        [data-theme="dark"] .badge-student { background: rgba(59,130,246,0.15); color: #93c5fd; border-color: rgba(59,130,246,0.3); }
        [data-theme="dark"] .badge-faculty { background: rgba(168,85,247,0.15); color: #c4b5fd; border-color: rgba(168,85,247,0.3); }
        [data-theme="dark"] .badge-staff   { background: rgba(245,158,11,0.15); color: #fcd34d; border-color: rgba(245,158,11,0.3); }
        [data-theme="dark"] .badge-admin   { background: rgba(99,102,241,0.15); color: #a5b4fc; border-color: rgba(99,102,241,0.3); }

        /* ── Status badge ───────────────────────────────────────────── */
        .badge-active   { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-inactive { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }

        [data-theme="dark"] .badge-active   { background: rgba(34,197,94,0.15); color: #86efac; border-color: rgba(34,197,94,0.3); }
        [data-theme="dark"] .badge-inactive { background: rgba(107,114,128,0.15); color: #9ca3af; border-color: rgba(107,114,128,0.3); }

        /* ── Recipient row ──────────────────────────────────────────── */
        .recipient-row {
            transition: all 0.2s ease;
        }
        .recipient-row:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        /* ── Avatar circle ──────────────────────────────────────────── */
        .recipient-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.875rem;
            flex-shrink: 0;
        }
        .avatar-student { background: #dbeafe; color: #1e40af; }
        .avatar-faculty { background: #f3e8ff; color: #6b21a8; }
        .avatar-staff   { background: #fef3c7; color: #92400e; }
        .avatar-admin   { background: #e0e7ff; color: #3730a3; }

        [data-theme="dark"] .avatar-student { background: rgba(59,130,246,0.2); color: #93c5fd; }
        [data-theme="dark"] .avatar-faculty { background: rgba(168,85,247,0.2); color: #c4b5fd; }
        [data-theme="dark"] .avatar-staff   { background: rgba(245,158,11,0.2); color: #fcd34d; }
        [data-theme="dark"] .avatar-admin   { background: rgba(99,102,241,0.2); color: #a5b4fc; }

        /* ── Action button ──────────────────────────────────────────── */
        .action-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 6px 10px; border-radius: 8px;
            font-size: 0.75rem; font-weight: 600;
            cursor: pointer; transition: all 0.15s ease;
            border: 1px solid transparent;
        }
        .action-btn:hover { transform: translateY(-1px); }

        .action-edit   { color: #2563eb; background: transparent; }
        .action-edit:hover   { background: #dbeafe; }
        [data-theme="dark"] .action-edit:hover { background: rgba(59,130,246,0.15); }

        .action-toggle { color: #16a34a; background: transparent; }
        .action-toggle:hover { background: #dcfce7; }
        [data-theme="dark"] .action-toggle:hover { background: rgba(34,197,94,0.15); }

        .action-delete { color: #dc2626; background: transparent; }
        .action-delete:hover { background: #fee2e2; }
        [data-theme="dark"] .action-delete:hover { background: rgba(239,68,68,0.15); }

        /* ── Modal ──────────────────────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 100;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden;
            transition: all 0.25s ease;
            padding: 1rem;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-primary);
            border-radius: 16px;
            padding: 1.5rem;
            max-width: 500px; width: 100%;
            transform: scale(0.95) translateY(10px);
            transition: transform 0.25s ease;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-overlay.show .modal-content { transform: scale(1) translateY(0); }

        /* ── Filter chip ────────────────────────────────────────────── */
        .filter-chip {
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
        }
        .filter-chip:hover { transform: translateY(-1px); }
        .filter-chip.active {
            background: var(--button-primary-bg) !important;
            color: var(--button-primary-text) !important;
            border-color: var(--button-primary-bg) !important;
        }

        /* ── Search input ───────────────────────────────────────────── */
        .search-input:focus { box-shadow: 0 0 0 2px #2563eb; }

        /* ── Toast ──────────────────────────────────────────────────── */
        .toast {
            position: fixed; bottom: 30px; left: 50%;
            transform: translateX(-50%) translateY(20px);
            padding: 12px 24px; border-radius: 12px;
            font-size: 0.875rem; font-weight: 600;
            opacity: 0; visibility: hidden;
            transition: all 0.3s ease;
            z-index: 9999;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        .toast.show { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }
        .toast.success { background: #22c55e; color: white; }
        .toast.error { background: #ef4444; color: white; }

        /* ── Empty state ────────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="sidebar-content px-4 sm:px-6 py-4 sm:py-8 overflow-x-hidden">

        <!-- Toast (for JS notifications) -->
        <div id="toast" class="toast"></div>

        <!-- PHP message banner -->
        <?php if ($message): ?>
        <div class="<?php echo $messageType === 'error' ? 'bg-red-50 border-red-500' : 'bg-green-50 border-green-500'; ?> border-l-4 p-3 sm:p-4 mb-4 sm:mb-6 rounded-r-lg animate-fade-in">
            <div class="flex items-center">
                <svg class="w-5 h-5 <?php echo $messageType === 'error' ? 'text-red-600' : 'text-green-600'; ?> mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <?php if ($messageType === 'error'): ?>
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    <?php else: ?>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    <?php endif; ?>
                </svg>
                <p class="text-sm <?php echo $messageType === 'error' ? 'text-red-800' : 'text-green-800'; ?> font-semibold"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex items-center justify-between gap-4 mb-6 animate-scale-in">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold theme-text-primary mb-1">Alert Recipients</h1>
                <p class="theme-text-secondary text-sm">Manage who receives SMS alerts during earthquake events</p>
            </div>
            <button onclick="openAddModal()" class="theme-btn-primary px-4 sm:px-5 py-2.5 sm:py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95 flex items-center gap-2 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="hidden sm:inline">Add Recipient</span>
                <span class="sm:hidden">Add</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6 animate-fade-in delay-100">
            <!-- Total -->
            <div class="theme-card rounded-xl p-4 sm:p-5 border-l-4 border-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Total</p>
                        <p class="text-2xl sm:text-3xl font-bold theme-text-primary mt-1"><?php echo $total; ?></p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 theme-text-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active -->
            <div class="theme-card rounded-xl p-4 sm:p-5 border-l-4 border-green-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Active</p>
                        <p class="text-2xl sm:text-3xl font-bold text-green-600 mt-1"><?php echo $active; ?></p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <?php if ($inactive > 0): ?>
                    <p class="text-xs theme-text-tertiary mt-2"><?php echo $inactive; ?> inactive</p>
                <?php endif; ?>
            </div>

            <!-- Students -->
            <div class="theme-card rounded-xl p-4 sm:p-5 border-l-4 border-blue-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Students</p>
                        <p class="text-2xl sm:text-3xl font-bold text-blue-600 mt-1"><?php echo $students; ?></p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Faculty + Staff -->
            <div class="theme-card rounded-xl p-4 sm:p-5 border-l-4 border-purple-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="theme-text-tertiary text-xs font-medium uppercase tracking-wide">Faculty & Staff</p>
                        <p class="text-2xl sm:text-3xl font-bold text-purple-600 mt-1"><?php echo $faculty + $staff; ?></p>
                    </div>
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs theme-text-tertiary mt-2"><?php echo $faculty; ?> faculty · <?php echo $staff; ?> staff · <?php echo $admins; ?> admin</p>
            </div>
        </div>

        <!-- Recipients List -->
        <div class="theme-card rounded-xl p-4 sm:p-6 animate-fade-in delay-200">
            <!-- Search + Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <!-- Search -->
                <div class="flex-1 relative">
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 theme-text-tertiary pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search by name or phone number..."
                        class="search-input theme-input w-full pl-10 pr-4 py-2.5 rounded-xl text-sm focus:outline-none transition"
                        oninput="filterRecipients()">
                </div>

                <!-- Filter chips -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button class="filter-chip active px-3 py-2 rounded-lg text-xs font-semibold theme-card border" data-filter="all" onclick="setFilter('all')">
                        All (<?php echo $total; ?>)
                    </button>
                    <button class="filter-chip px-3 py-2 rounded-lg text-xs font-semibold theme-card border" data-filter="student" onclick="setFilter('student')">
                        Students
                    </button>
                    <button class="filter-chip px-3 py-2 rounded-lg text-xs font-semibold theme-card border" data-filter="faculty" onclick="setFilter('faculty')">
                        Faculty
                    </button>
                    <button class="filter-chip px-3 py-2 rounded-lg text-xs font-semibold theme-card border" data-filter="staff" onclick="setFilter('staff')">
                        Staff
                    </button>
                    <button class="filter-chip px-3 py-2 rounded-lg text-xs font-semibold theme-card border" data-filter="admin" onclick="setFilter('admin')">
                        Admin
                    </button>
                    <button class="filter-chip px-3 py-2 rounded-lg text-xs font-semibold theme-card border" data-filter="active" onclick="setFilter('active')">
                        Active
                    </button>
                </div>
            </div>

            <!-- Recipient list -->
            <div id="recipientList" class="space-y-2">
                <?php if ($recipients->num_rows > 0): ?>
                    <?php while ($r = $recipients->fetch_assoc()): ?>
                        <div class="recipient-row theme-card rounded-xl p-3 sm:p-4 border flex items-center gap-3 sm:gap-4"
                             data-name="<?php echo strtolower(htmlspecialchars($r['name'])); ?>"
                             data-phone="<?php echo strtolower(htmlspecialchars($r['phone_number'])); ?>"
                             data-category="<?php echo $r['category']; ?>"
                             data-active="<?php echo $r['is_active'] ? '1' : '0'; ?>">

                            <!-- Avatar -->
                            <div class="recipient-avatar avatar-<?php echo $r['category']; ?>">
                                <?php echo strtoupper(substr($r['name'], 0, 1)); ?>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold theme-text-primary text-sm sm:text-base truncate"><?php echo htmlspecialchars($r['name']); ?></p>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold badge-<?php echo $r['category']; ?>">
                                        <?php echo ucfirst($r['category']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <svg class="w-3.5 h-3.5 theme-text-tertiary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-xs sm:text-sm theme-text-secondary font-mono"><?php echo htmlspecialchars($r['phone_number']); ?></span>
                                </div>
                            </div>

                            <!-- Status badge -->
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold badge-<?php echo $r['is_active'] ? 'active' : 'inactive'; ?> hidden sm:inline-flex">
                                <?php echo $r['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button onclick="openEditModal(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($r['phone_number'], ENT_QUOTES); ?>', '<?php echo $r['category']; ?>')"
                                    class="action-btn action-edit" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    <span class="hidden lg:inline">Edit</span>
                                </button>

                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="action-btn action-toggle" title="<?php echo $r['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <?php if ($r['is_active']): ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            <span class="hidden lg:inline">Disable</span>
                                        <?php else: ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="hidden lg:inline">Enable</span>
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <form method="POST" class="inline" onsubmit="return confirm('Delete <?php echo htmlspecialchars($r['name'], ENT_QUOTES); ?>? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="action-btn action-delete" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        <span class="hidden lg:inline">Delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <svg class="w-16 h-16 mx-auto theme-text-tertiary mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <h3 class="text-lg font-bold theme-text-primary mb-1">No recipients yet</h3>
                        <p class="text-sm theme-text-secondary mb-4">Add your first alert recipient to get started.</p>
                        <button onclick="openAddModal()" class="theme-btn-primary px-5 py-2.5 rounded-xl font-semibold transition hover:scale-105">
                            Add Recipient
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- No results (hidden by default, shown by JS when filter yields nothing) -->
            <div id="noResults" class="empty-state hidden">
                <svg class="w-12 h-12 mx-auto theme-text-tertiary mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-sm theme-text-secondary">No recipients match your search.</p>
            </div>
        </div>
    </div>

    <!-- ═══ Add/Edit Modal ═══════════════════════════════════════════ -->
    <div id="modalOverlay" class="modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div id="modalIcon" class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <h3 id="modalTitle" class="text-lg font-bold theme-text-primary">Add New Recipient</h3>
                </div>
                <button onclick="closeModal()" class="p-2 rounded-lg theme-btn-secondary transition hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="recipientForm" method="POST" class="space-y-4">
                <input type="hidden" id="formAction" name="action" value="add">
                <input type="hidden" id="formId" name="id" value="">

                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Full Name</label>
                    <input type="text" name="name" id="formName" required
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                        placeholder="Juan Dela Cruz">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Phone Number</label>
                    <input type="text" name="phone" id="formPhone" required
                        class="theme-input w-full px-4 py-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition font-mono"
                        placeholder="09171234567">
                    <p class="text-xs theme-text-tertiary mt-1.5">Format: 09XXXXXXXXX (11 digits, no spaces)</p>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-semibold theme-text-secondary mb-2">Category</label>
                    <div class="grid grid-cols-4 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="student" class="peer sr-only" checked>
                            <div class="text-center py-2.5 rounded-lg border-2 text-xs font-semibold transition peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 theme-card">
                                Student
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="faculty" class="peer sr-only">
                            <div class="text-center py-2.5 rounded-lg border-2 text-xs font-semibold transition peer-checked:bg-purple-600 peer-checked:text-white peer-checked:border-purple-600 theme-card">
                                Faculty
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="staff" class="peer sr-only">
                            <div class="text-center py-2.5 rounded-lg border-2 text-xs font-semibold transition peer-checked:bg-yellow-500 peer-checked:text-white peer-checked:border-yellow-500 theme-card">
                                Staff
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="admin" class="peer sr-only">
                            <div class="text-center py-2.5 rounded-lg border-2 text-xs font-semibold transition peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 theme-card">
                                Admin
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModal()" class="flex-1 theme-btn-secondary px-4 py-3 rounded-xl font-semibold transition">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmit" class="flex-1 theme-btn-primary px-4 py-3 rounded-xl font-semibold transition hover:scale-105 active:scale-95">
                        Add Recipient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ── Modal management ──────────────────────────────────────────
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Recipient';
            document.getElementById('modalSubmit').textContent = 'Add Recipient';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('formName').value = '';
            document.getElementById('formPhone').value = '';
            // Reset category to student
            document.querySelectorAll('input[name="category"]').forEach(r => r.checked = (r.value === 'student'));
            document.getElementById('modalOverlay').classList.add('show');
            setTimeout(() => document.getElementById('formName').focus(), 100);
        }

        function openEditModal(id, name, phone, category) {
            document.getElementById('modalTitle').textContent = 'Edit Recipient';
            document.getElementById('modalSubmit').textContent = 'Save Changes';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('formName').value = name;
            document.getElementById('formPhone').value = phone;
            document.querySelectorAll('input[name="category"]').forEach(r => r.checked = (r.value === category));
            document.getElementById('modalOverlay').classList.add('show');
        }

        function closeModal() {
            document.getElementById('modalOverlay').classList.remove('show');
        }

        function closeModalOnOverlay(event) {
            if (event.target === document.getElementById('modalOverlay')) {
                closeModal();
            }
        }

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });

        // ── Search + Filter ───────────────────────────────────────────
        let currentFilter = 'all';

        function setFilter(filter) {
            currentFilter = filter;
            // Update active chip
            document.querySelectorAll('.filter-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.filter === filter);
            });
            filterRecipients();
        }

        function filterRecipients() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.recipient-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.dataset.name || '';
                const phone = row.dataset.phone || '';
                const category = row.dataset.category || '';
                const isActive = row.dataset.active === '1';

                // Search match
                const searchMatch = !search || name.includes(search) || phone.includes(search);

                // Filter match
                let filterMatch = true;
                if (currentFilter === 'active') {
                    filterMatch = isActive;
                } else if (currentFilter !== 'all') {
                    filterMatch = category === currentFilter;
                }

                if (searchMatch && filterMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show/hide no results
            document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
        }

        // ── Toast (for future JS-based actions) ───────────────────────
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
