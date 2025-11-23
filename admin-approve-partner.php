<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner_id = intval($_POST['partner_id']);
    
    if ($partner_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid partner ID']);
        exit();
    }
    
    $conn = getDBConnection();
    
    // Get partner details
    $stmt = $conn->prepare("SELECT center_name, email, phone FROM partners WHERE id = ? AND is_approved = 0");
    $stmt->bind_param("i", $partner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Partner not found or already approved']);
        exit();
    }
    
    $partner = $result->fetch_assoc();
    $stmt->close();
    
    // Generate unique token
    $unique_token = generateUniqueToken();
    
    // Update partner status
    $stmt = $conn->prepare("UPDATE partners SET is_approved = 1, unique_token = ?, approved_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $unique_token, $partner_id);
    
    if ($stmt->execute()) {
        $registration_link = SITE_URL . "/partner-setup.php?token=" . $unique_token;
        
        // Here you could send an email to the partner with the link
        // mail($partner['email'], "Partner Account Approval", "Your link: $registration_link");
        
        echo json_encode([
            'success' => true,
            'link' => $registration_link,
            'message' => 'Partner approved successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve partner']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>