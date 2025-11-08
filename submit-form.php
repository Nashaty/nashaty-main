<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $mobile = sanitizeInput($_POST['mobile']);
    $comments = sanitizeInput($_POST['comments']);
    
    if (empty($name) || empty($email) || empty($mobile) || empty($comments)) {
        echo 'All fields are required.';
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