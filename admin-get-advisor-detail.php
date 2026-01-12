<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT 
        id, name, email, phone, address, city, country, expertise,
        nda_agreed, terms_agreed, nda_agreed_at, terms_agreed_at, created_at
    FROM advisors 
    WHERE id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Advisor not found']);
} else {
    $advisor = $result->fetch_assoc();
    echo json_encode($advisor);
}

$stmt->close();
$conn->close();
?>