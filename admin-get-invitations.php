<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

header('Content-Type: application/json');

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        ai.id, ai.email, ai.unique_token, ai.status, 
        ai.invited_at, ai.expires_at, ai.completed_at,
        au.username as invited_by_name
    FROM advisory_invitations ai
    JOIN admin_users au ON ai.invited_by = au.id
    ORDER BY ai.invited_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$invitations = [];
while ($row = $result->fetch_assoc()) {
    $invitations[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($invitations);
?>