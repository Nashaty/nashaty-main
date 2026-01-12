<?php

require_once __DIR__ . '/env.php';

// Load environment variables
loadEnv(__DIR__ . '/.env');

/* =========================
   DATABASE CONFIG
========================= */
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);

/* =========================
   SITE CONFIG
========================= */
define('SITE_URL', $_ENV['SITE_URL']);

/* =========================
   SENDGRID CONFIG
========================= */
define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY']);
define('SENDGRID_FROM_EMAIL', $_ENV['SENDGRID_FROM_EMAIL']);
define('SENDGRID_FROM_NAME', $_ENV['SENDGRID_FROM_NAME']);

/* =========================
   DATABASE CONNECTION
========================= */
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

/* =========================
   SESSION
========================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   HELPERS
========================= */
function generateUniqueToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function isLoggedIn($userType = 'parent') {
    return isset($_SESSION[$userType . '_id']);
}

function requireLogin($userType = 'parent', $redirectUrl = 'login.php') {
    if (!isLoggedIn($userType)) {
        header("Location: $redirectUrl");
        exit();
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

/* =========================
   SEND EMAIL (SENDGRID)
========================= */
function sendEmail($to, $subject, $htmlContent, $textContent = '') {
    $ch = curl_init();

    $content = [];

    if (!empty($textContent)) {
        $content[] = [
            'type' => 'text/plain',
            'value' => $textContent
        ];
    }

    $content[] = [
        'type' => 'text/html',
        'value' => $htmlContent
    ];

    $data = [
        'personalizations' => [[
            'to' => [['email' => $to]],
            'subject' => $subject
        ]],
        'from' => [
            'email' => SENDGRID_FROM_EMAIL,
            'name'  => SENDGRID_FROM_NAME
        ],
        'content' => $content
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SENDGRID_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("SendGrid Error: HTTP $httpCode - $response - $error");
        return false;
    }

    return true;
}
