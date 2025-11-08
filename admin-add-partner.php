<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    
    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $conn = getDBConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM partners WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'A partner with this email already exists.';
        } else {
            // Insert new partner (pending approval)
            $stmt = $conn->prepare("INSERT INTO partners (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $phone);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = 'Failed to add partner. Please try again.';
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Partner - Nashaty Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f5f5f5; }
        .form-wrapper { max-width: 600px; margin: 40px auto; padding: 20px; }
        .form-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <div class="form-card">
            <div class="mb-4">
                <h2 style="color: #a74fec;">Add New Partner</h2>
                <a href="admin-dashboard.php" class="btn btn-sm btn-outline-secondary">← Back to Dashboard</a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h5>Partner Added Successfully!</h5>
                    <p>The partner has been added to the pending list. You can approve them from the dashboard.</p>
                    <a href="admin-dashboard.php" class="btn growth-btn">Go to Dashboard</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Partner Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <button type="submit" class="btn growth-btn">Add Partner</button>
                    <a href="admin-dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>