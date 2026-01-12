<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // Check if email already has a pending invitation
    $stmt = $conn->prepare("SELECT id FROM advisory_invitations WHERE email = ? AND status = 'pending' AND expires_at > NOW()");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email already has a pending invitation']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    
    // Check if advisor already exists
    $stmt = $conn->prepare("SELECT id FROM advisors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered as an advisor']);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
    
    // Generate unique token
    $token = bin2hex(random_bytes(32));
    $adminId = $_SESSION['admin_id'];
    
    // Set expiration to 7 days from now
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Insert invitation
    $stmt = $conn->prepare("INSERT INTO advisory_invitations (email, unique_token, invited_by, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $email, $token, $adminId, $expiresAt);
    
    if ($stmt->execute()) {
        $invitationLink = SITE_URL . '/advisor-register.php?token=' . $token;
        
        // Send email via SendGrid
        $subject = 'Invitation to Join Nashaty Advisory Board';
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to Nashaty Advisory Board!</h1>
                </div>
                <div class="content">
                    <p>Dear Future Advisor,</p>
                    <p>You have been invited to join the Nashaty Advisory Board. We are excited to have you as part of our mission to transform children\'s free time into purposeful growth.</p>
                    <p>To complete your registration, please click the button below:</p>
                    <p style="text-align: center;">
                        <a href="' . $invitationLink . '" class="button">Complete Registration</a>
                    </p>
                    <p><strong>Important:</strong> This invitation link will expire in 7 days.</p>
                    <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #666;">' . $invitationLink . '</p>
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
        
        $textContent = "You have been invited to join the Nashaty Advisory Board. Please visit: $invitationLink to complete your registration. This link expires in 7 days.";
        
        if (sendEmail($email, $subject, $htmlContent, $textContent)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Invitation sent successfully!',
                'link' => $invitationLink
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send invitation email']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create invitation']);
    }
    
    $stmt->close();
    $conn->close();
}
?>