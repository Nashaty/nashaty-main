<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

header('Content-Type: application/json');

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        id, name, email, phone, address, city, country, expertise,
        nda_agreed, terms_agreed, nda_agreed_at, terms_agreed_at, created_at
    FROM advisors 
    ORDER BY created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$advisors = [];
while ($row = $result->fetch_assoc()) {
    $advisors[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($advisors);
?>