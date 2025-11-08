<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', ''); // Change to your MySQL password
define('DB_NAME', 'nashaty_db');

// Site configuration
define('SITE_URL', 'http://localhost/public_html_Nashaty'); // Change to your site URL

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate unique token
function generateUniqueToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Check if user is logged in
function isLoggedIn($userType = 'parent') {
    return isset($_SESSION[$userType . '_id']);
}

// Redirect if not logged in
function requireLogin($userType = 'parent', $redirectUrl = 'login.php') {
    if (!isLoggedIn($userType)) {
        header("Location: $redirectUrl");
        exit();
    }
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>