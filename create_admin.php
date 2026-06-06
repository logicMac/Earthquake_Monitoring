<?php
/**
 * Admin User Creation Script
 * Creates an admin user with credentials: adminSienna / admin123
 * 
 * Usage: Run this file once in your browser
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to include database config
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    die("Error loading database config: " . $e->getMessage());
}

// Admin credentials
$username = 'adminSienna';
$password = 'admin123';
$full_name = 'Siena Administrator';
$email = 'admin@ndscpm.edu.ph';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $conn = getDBConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "<br><br>Please check your config/database.php file.");
}

// Check if user already exists
$check = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
if (!$check) {
    die("Prepare failed: " . $conn->error);
}

$check->bind_param("s", $username);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Admin Creation - Already Exists</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 min-h-screen flex items-center justify-center p-4'>
        <div class='bg-white border-2 border-gray-200 rounded-xl p-8 max-w-md shadow-lg'>
            <div class='text-center'>
                <div class='w-16 h-16 bg-yellow-500 rounded-full mx-auto mb-4 flex items-center justify-center'>
                    <svg class='w-8 h-8 text-white' fill='currentColor' viewBox='0 0 20 20'>
                        <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'></path>
                    </svg>
                </div>
                <h1 class='text-2xl font-bold text-gray-900 mb-2'>User Already Exists</h1>
                <p class='text-gray-600 mb-6'>The admin user '<span class='text-gray-900 font-semibold'>$username</span>' already exists in the database.</p>
                <a href='login.php' class='inline-block bg-black text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-800 transition'>
                    Go to Login
                </a>
            </div>
        </div>
    </body>
    </html>";
    $check->close();
    $conn->close();
    exit;
}

$check->close();

// Insert new admin user
$stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name, email) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ssss", $username, $hashed_password, $full_name, $email);

if ($stmt->execute()) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Admin Created Successfully</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 min-h-screen flex items-center justify-center p-4'>
        <div class='bg-white border-2 border-gray-200 rounded-xl p-8 max-w-md shadow-lg'>
            <div class='text-center'>
                <div class='w-16 h-16 bg-green-600 rounded-full mx-auto mb-4 flex items-center justify-center'>
                    <svg class='w-8 h-8 text-white' fill='currentColor' viewBox='0 0 20 20'>
                        <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'></path>
                    </svg>
                </div>
                <h1 class='text-2xl font-bold text-gray-900 mb-2'>Admin User Created!</h1>
                <p class='text-gray-600 mb-6'>Your admin account has been successfully created.</p>
                
                <div class='bg-gray-50 border-2 border-gray-200 rounded-lg p-4 mb-6 text-left'>
                    <div class='mb-3'>
                        <p class='text-xs text-gray-500 uppercase mb-1'>Username</p>
                        <p class='text-gray-900 font-semibold'>$username</p>
                    </div>
                    <div>
                        <p class='text-xs text-gray-500 uppercase mb-1'>Password</p>
                        <p class='text-gray-900 font-semibold'>$password</p>
                    </div>
                </div>
                
                <a href='login.php' class='inline-block bg-black text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-800 transition'>
                    Go to Login
                </a>
                
                <p class='text-xs text-gray-500 mt-4'>For security, delete this file after use.</p>
            </div>
        </div>
    </body>
    </html>";
} else {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Admin Creation - Error</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 min-h-screen flex items-center justify-center p-4'>
        <div class='bg-white border-2 border-gray-200 rounded-xl p-8 max-w-md shadow-lg'>
            <div class='text-center'>
                <div class='w-16 h-16 bg-red-600 rounded-full mx-auto mb-4 flex items-center justify-center'>
                    <svg class='w-8 h-8 text-white' fill='currentColor' viewBox='0 0 20 20'>
                        <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'></path>
                    </svg>
                </div>
                <h1 class='text-2xl font-bold text-gray-900 mb-2'>Creation Failed</h1>
                <p class='text-gray-600 mb-6'>Error: " . htmlspecialchars($conn->error) . "</p>
                <a href='create_admin.php' class='inline-block bg-black text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-800 transition'>
                    Try Again
                </a>
            </div>
        </div>
    </body>
    </html>";
}

$stmt->close();
$conn->close();
?>
