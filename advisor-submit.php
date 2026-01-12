<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html");
    exit();
}

$token = sanitizeInput($_POST['token']);
$name = sanitizeInput($_POST['name']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$phone = sanitizeInput($_POST['phone']);
$address = sanitizeInput($_POST['address']);
$city = sanitizeInput($_POST['city']);
$country = sanitizeInput($_POST['country']);
$expertise = sanitizeInput($_POST['expertise']);
$nda_consent = isset($_POST['nda_consent']) ? 1 : 0;
$terms_consent = isset($_POST['terms_consent']) ? 1 : 0;

// Validation
if (empty($token) || empty($name) || !$email || empty($phone) || empty($city) || empty($country) || empty($expertise)) {
    die('Error: All required fields must be filled.');
}

if (!$nda_consent || !$terms_consent) {
    die('Error: You must agree to both the NDA and Terms & Conditions.');
}

$conn = getDBConnection();

// Verify token and get invitation
$stmt = $conn->prepare("SELECT id, email, status, expires_at FROM advisory_invitations WHERE unique_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Error: Invalid invitation link.');
}

$invitation = $result->fetch_assoc();
$stmt->close();

// Check if already completed or expired
if ($invitation['status'] === 'completed') {
    die('Error: This invitation has already been used.');
}

if ($invitation['status'] === 'expired' || strtotime($invitation['expires_at']) < time()) {
    die('Error: This invitation has expired.');
}

// Verify email matches invitation
if ($email !== $invitation['email']) {
    die('Error: Email does not match invitation.');
}

// Check if advisor already exists
$stmt = $conn->prepare("SELECT id FROM advisors WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die('Error: An advisor with this email already exists.');
}
$stmt->close();

// Insert advisor
$stmt = $conn->prepare("INSERT INTO advisors (invitation_id, name, email, phone, address, city, country, expertise, nda_agreed, terms_agreed, nda_agreed_at, terms_agreed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param("issssssiii", $invitation['id'], $name, $email, $phone, $address, $city, $country, $expertise, $nda_consent, $terms_consent);

if (!$stmt->execute()) {
    die('Error: Failed to register advisor. Please try again.');
}
$stmt->close();

// Update invitation status
$stmt = $conn->prepare("UPDATE advisory_invitations SET status = 'completed', completed_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $invitation['id']);
$stmt->execute();
$stmt->close();

$conn->close();

// Send confirmation email
$subject = 'Welcome to Nashaty Advisory Board!';
$htmlContent = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to the Team!</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            <p>Congratulations! Your registration as a Nashaty Advisory Board member has been successfully completed.</p>
            <p>We are thrilled to have you join us in our mission to transform children\'s free time into purposeful growth opportunities.</p>
            <p>Our team will be in touch with you soon regarding next steps and upcoming advisory activities.</p>
            <p>Thank you for your commitment to making a difference in children\'s lives.</p>
            <p>Best regards,<br>The Nashaty Team</p>
        </div>
        <div class="footer">
            <p>© 2026 Nashaty Trading LLC. All rights reserved.</p>
            <p>Doha, Qatar</p>
        </div>
    </div>
</body>
</html>
';

sendEmail($email, $subject, $htmlContent);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Complete - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .success-card { background: white; border-radius: 20px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 600px; text-align: center; }
        .success-icon { font-size: 5rem; color: #28a745; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h2 style="color: #a74fec;">Registration Complete!</h2>
        <p class="lead">Thank you for joining the Nashaty Advisory Board.</p>
        <p>A confirmation email has been sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
        <p class="text-muted">Our team will be in touch with you soon regarding next steps.</p>
        <a href="index.html" class="btn growth-btn mt-4">Return to Home</a>
    </div>
</body>
</html>