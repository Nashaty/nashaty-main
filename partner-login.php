<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn('partner')) {
    header("Location: partner-dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } else {
        $conn = getDBConnection();
        
        // FETCH ALL NEEDED FIELDS INCLUDING contact_person and phone
        $stmt = $conn->prepare("SELECT id, center_name, contact_person, email, phone, password, form_submitted FROM partners WHERE email = ? AND is_approved = 1 AND password IS NOT NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $partner = $result->fetch_assoc();
            
            if (password_verify($password, $partner['password'])) {
                // SET ALL SESSION VARIABLES
                $_SESSION['partner_id'] = $partner['id'];
                $_SESSION['center_name'] = $partner['center_name'];
                $_SESSION['contact_person'] = $partner['contact_person'];
                $_SESSION['partner_email'] = $partner['email'];
                $_SESSION['contact_phone'] = $partner['phone'];
                $_SESSION['form_submitted'] = $partner['form_submitted'];
                header("Location: partner-dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
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
    <title>Partner Login - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #6abaed 0%, #a74fec 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        
        .logo-img {
            max-width: 120px;
            height: auto;
        }
        
        .login-card h2 {
            color: #a74fec;
            font-size: 1.5rem;
            margin-top: 1rem;
        }
        
        .form-label {
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .form-control {
            font-size: 1rem;
            padding: 0.625rem 0.75rem;
        }
        
        .btn {
            font-size: 1rem;
            padding: 0.75rem;
        }
        
        .text-center a {
            font-size: 0.95rem;
            text-decoration: none;
        }
        
        .text-center a:hover {
            text-decoration: underline;
        }
        
        @media (min-width: 576px) {
            .login-wrapper {
                padding: 40px 20px;
            }
            
            .login-card {
                padding: 40px;
            }
            
            .logo-img {
                max-width: 150px;
            }
            
            .login-card h2 {
                font-size: 1.75rem;
            }
            
            .form-label {
                font-size: 1rem;
            }
            
            .text-center a {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 375px) {
            .login-card {
                padding: 25px 15px;
                border-radius: 15px;
            }
            
            .logo-img {
                max-width: 100px;
            }
            
            .login-card h2 {
                font-size: 1.25rem;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control {
                font-size: 0.95rem;
            }
            
            .btn {
                font-size: 0.95rem;
                padding: 0.65rem;
            }
            
            .text-center a {
                font-size: 0.9rem;
            }
        }
        
        @media (max-height: 600px) and (orientation: landscape) {
            .login-wrapper {
                padding: 15px;
            }
            
            .login-card {
                padding: 20px;
                margin: 10px 0;
            }
            
            .logo-img {
                max-width: 80px;
            }
            
            .login-card h2 {
                font-size: 1.25rem;
                margin-top: 0.5rem;
            }
            
            .mb-3 {
                margin-bottom: 0.75rem !important;
            }
            
            .mb-4 {
                margin-bottom: 1rem !important;
            }
            
            .mt-3 {
                margin-top: 0.75rem !important;
            }
        }
        
        .alert {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="./assets/images/logo.png" alt="Nashaty Logo" class="logo-img">
                <h2 class="mt-3">Partner Login</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
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