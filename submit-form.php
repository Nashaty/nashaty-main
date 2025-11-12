<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $mobile = sanitizeInput($_POST['mobile']);
    // Comments are optional; check if set and sanitize or set empty string
    $comments = isset($_POST['comments']) ? sanitizeInput($_POST['comments']) : '';
    
    // Validate required fields only: name, email, mobile
    if (empty($name) || empty($email) || empty($mobile)) {
        echo 'Name, Email, and Mobile are required.';
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Invalid email format.';
        exit();
    }
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, mobile, comments) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $mobile, $comments);
    
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'Failed to submit. Please try again.';
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo 'Invalid request method.';
}
?>