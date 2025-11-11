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


<!-- <div class="reachout-box" style="width: 290px; text-align: center;">
                        <a href="javascript:void(0)" class="btn growth-btn" data-bs-toggle="modal"
                            data-bs-target="#reachOutModal">
                            Reach Out
                        </a>

                        <div class="button-row ms-lg-auto mt-3" style="display: flex; justify-content: space-between;">
                            <a href="parent-register.php" class="btn growth-btn"
                                style="background: #a74fec; color: white; border-style: none;">Parents</a>
                            <a href="partner-register.php" class="btn growth-btn"
                                style="background: #6abaed; color: white; border-style: none;">Partners</a>
                        </div>
                        <a href="https://wa.me/97433793376" target="_blank" class="btn growth-btn mt-3">
                            Get in touch via call
                        </a>
                    </div> -->