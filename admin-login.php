<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn('admin')) {
    header("Location: admin-dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
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
    <title>Admin Login - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="./assets/images/logo.png" alt="Nashaty Logo" style="max-width: 150px;">
                <h2 class="mt-3" style="color: #a74fec;">Admin Login</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn growth-btn w-100">Login</button>
                <div class="text-center mt-3">
                    <a href="index.html">Back to Home</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>