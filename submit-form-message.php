<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars($_POST["name"] ?? '');
    $email = htmlspecialchars($_POST["email"] ?? '');
    $mobile = htmlspecialchars($_POST["mobile"] ?? '');
    $comments = htmlspecialchars($_POST["comments"] ?? '');

    $to = "ceo@nashaty.net";
    $subject = "New Contact Form Submission from Nashaty";

    $message = "
        <strong>Name:</strong> $name<br>
        <strong>Email:</strong> $email<br>
        <strong>Mobile:</strong> $mobile<br>
        <strong>Comments:</strong><br>$comments
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Nashaty Contact Form <mail@nashaty.net>\r\n";

    if (mail($to, $subject, $message, $headers)) {
        echo "success";
    } else {
        echo "Failed to send email.";
    }
} else {
    echo "Invalid request.";
}
