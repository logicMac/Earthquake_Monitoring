<?php
/**
 * Admin Map Module
 * Separate entry point for admin users. Redirects unauthorized users to login.
 */
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

// Reuse the public map view, but it will render the admin navigation
// because isLoggedIn() is now true.
require 'map.php';
