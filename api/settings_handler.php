<?php
/**
 * Settings Handler API
 * Processes profile updates, username changes, and password changes.
 *
 * Security:
 * - All actions require the user to be logged in.
 * - Username and password changes require current password verification.
 * - Passwords are hashed with password_hash() (bcrypt).
 * - Username is checked for uniqueness before updating.
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
    exit;
}

$adminId = $_SESSION['admin_id'];

try {
    $conn = getDBConnection();

    switch ($action) {

        // ──────────────────────────────────────────────────────────────
        // Update profile (full name + email)
        // ──────────────────────────────────────────────────────────────
        case 'update_profile':
            $fullName = trim($input['full_name'] ?? '');
            $email = trim($input['email'] ?? '');

            if (empty($fullName)) {
                echo json_encode(['success' => false, 'message' => 'Full name cannot be empty.']);
                exit;
            }
            if (mb_strlen($fullName) > 100) {
                echo json_encode(['success' => false, 'message' => 'Full name is too long (max 100 characters).']);
                exit;
            }
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
                exit;
            }
            if (mb_strlen($email) > 100) {
                echo json_encode(['success' => false, 'message' => 'Email is too long (max 100 characters).']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $fullName, $email, $adminId);

            if ($stmt->execute()) {
                // Update session
                $_SESSION['admin_name'] = $fullName;
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully.',
                    'new_full_name' => $fullName
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile. Please try again.']);
            }
            $stmt->close();
            break;

        // ──────────────────────────────────────────────────────────────
        // Change username (requires current password)
        // ──────────────────────────────────────────────────────────────
        case 'change_username':
            $newUsername = trim($input['new_username'] ?? '');
            $password = $input['password'] ?? '';

            // Validate
            if (empty($newUsername)) {
                echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
                exit;
            }
            if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
                echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters.']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $newUsername)) {
                echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, underscores, hyphens, and dots.']);
                exit;
            }
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password confirmation is required.']);
                exit;
            }

            // Verify current password
            $stmt = $conn->prepare("SELECT username, password FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
                exit;
            }

            // Check if username is the same as current
            if (strtolower($newUsername) === strtolower($user['username'])) {
                echo json_encode(['success' => false, 'message' => 'New username is the same as your current username.']);
                exit;
            }

            // Check if username is already taken (case-insensitive)
            $stmt = $conn->prepare("SELECT id FROM admin_users WHERE LOWER(username) = LOWER(?) AND id != ?");
            $stmt->bind_param("si", $newUsername, $adminId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'That username is already taken. Please choose another.']);
                exit;
            }

            // Update username
            $stmt = $conn->prepare("UPDATE admin_users SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $newUsername, $adminId);

            if ($stmt->execute()) {
                // Update session
                $_SESSION['admin_username'] = $newUsername;
                echo json_encode([
                    'success' => true,
                    'message' => 'Username updated successfully. Use your new username next time you log in.',
                    'new_username' => $newUsername
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update username. Please try again.']);
            }
            $stmt->close();
            break;

        // ──────────────────────────────────────────────────────────────
        // Change password (requires current password)
        // ──────────────────────────────────────────────────────────────
        case 'change_password':
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            if (empty($currentPassword)) {
                echo json_encode(['success' => false, 'message' => 'Current password is required.']);
                exit;
            }
            if (empty($newPassword)) {
                echo json_encode(['success' => false, 'message' => 'New password is required.']);
                exit;
            }
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
                exit;
            }
            if (strlen($newPassword) > 255) {
                echo json_encode(['success' => false, 'message' => 'New password is too long (max 255 characters).']);
                exit;
            }

            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
                exit;
            }

            // Check that new password is different
            if (password_verify($newPassword, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
                exit;
            }

            // Hash and update
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $adminId);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully. Please use your new password next time you log in.'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to change password. Please try again.']);
            }
            $stmt->close();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
            exit;
    }

    $conn->close();

} catch (Throwable $e) {
    error_log("Settings Handler Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
