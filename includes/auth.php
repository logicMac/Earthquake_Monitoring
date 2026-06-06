<?php
/**
 * Authentication Handler
 * Compatible with InfinityFree hosting
 */

// Fix session issues on InfinityFree
if (session_status() === PHP_SESSION_NONE) {
    // Set custom session save path for InfinityFree
    $session_path = sys_get_temp_dir();
    if (is_writable($session_path)) {
        session_save_path($session_path);
    }
    
    // Start session with error suppression for InfinityFree
    @session_start();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function login($username, $password, $conn) {
    $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            
            // Update last login
            $update = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $update->bind_param("i", $user['id']);
            $update->execute();
            
            return true;
        }
    }
    
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Admin';
}
?>
