<?php
require_once 'config.php';

$error = '';
$partner = null;
$token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : '';

if (empty($token)) {
    $error = 'Invalid or missing token.';
} else {
    $conn = getDBConnection();
    
    // Verify token and get partner details - FETCH ALL NEEDED FIELDS
    $stmt = $conn->prepare("SELECT id, center_name, contact_person, email, phone, password FROM partners WHERE unique_token = ? AND is_approved = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $partner = $result->fetch_assoc();
        
        // Check if password already set
        if (!empty($partner['password'])) {
            header("Location: partner-login.php");
            exit();
        }
    } else {
        $error = 'Invalid or expired token.';
    }
    
    $stmt->close();
    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $partner) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 5) {
        $error = 'Password must be at least 5 characters long.';
    } else {
        $conn = getDBConnection();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE partners SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $partner['id']);
        
        if ($stmt->execute()) {
            // SET ALL SESSION VARIABLES
            $_SESSION['partner_id'] = $partner['id'];
            $_SESSION['center_name'] = $partner['center_name'];
            $_SESSION['contact_person'] = $partner['contact_person'];
            $_SESSION['partner_email'] = $partner['email'];
            $_SESSION['partner_phone'] = $partner['phone'];
            header("Location: partner-dashboard.php");
            exit();
        } else {
            $error = 'Failed to set password. Please try again.';
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
    <title>Partner Account Setup - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #6abaed 0%, #a74fec 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .setup-card {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }
        
        .logo-img {
            max-width: 120px;
            height: auto;
        }
        
        .setup-card h2 {
            color: #a74fec;
            font-size: 1.5rem;
            margin-top: 1rem;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            word-break: break-word;
        }
        
        .info-box p {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .info-box p:last-child {
            margin-bottom: 0;
        }
        
        .text-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 4px;
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
        
        @media (min-width: 576px) {
            .setup-wrapper {
                padding: 40px 20px;
            }
            
            .setup-card {
                padding: 40px;
            }
            
            .logo-img {
                max-width: 150px;
            }
            
            .setup-card h2 {
                font-size: 1.75rem;
            }
            
            .info-box {
                padding: 20px;
                margin-bottom: 30px;
            }
            
            .info-box p {
                font-size: 1rem;
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 375px) {
            .setup-card {
                padding: 25px 15px;
                border-radius: 15px;
            }
            
            .setup-card h2 {
                font-size: 1.25rem;
            }
            
            .logo-img {
                max-width: 100px;
            }
            
            .info-box {
                padding: 12px;
                font-size: 0.85rem;
            }
            
            .form-control {
                font-size: 0.95rem;
            }
            
            .btn {
                font-size: 0.95rem;
                padding: 0.65rem;
            }
        }
        
        @media (max-height: 600px) and (orientation: landscape) {
            .setup-wrapper {
                padding: 15px;
            }
            
            .setup-card {
                padding: 20px;
                margin: 10px 0;
            }
            
            .logo-img {
                max-width: 80px;
            }
            
            .setup-card h2 {
                font-size: 1.25rem;
                margin-top: 0.5rem;
            }
            
            .info-box {
                padding: 12px;
                margin-bottom: 15px;
            }
            
            .mb-3 {
                margin-bottom: 0.75rem !important;
            }
            
            .mb-4 {
                margin-bottom: 1rem !important;
            }
        }
    </style>
</head>
<body>
    <div class="setup-wrapper">
        <div class="setup-card">
            <div class="text-center mb-4">
                <img src="./assets/images/logo.png" alt="Nashaty Logo" class="logo-img">
                <h2 class="mt-3">Partner Account Setup</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php if (!$partner): ?>
                    <div class="text-center">
                        <a href="index.html" class="btn btn-outline-secondary">Back to Home</a>
                    </div>
                <?php endif; ?>
            <?php elseif ($partner): ?>
                <div class="info-box">
                    <p><strong>Center Name:</strong> <?php echo htmlspecialchars($partner['center_name']); ?></p>
                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($partner['contact_person']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($partner['email']); ?></p>
                    <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($partner['phone']); ?></p>
                </div>
                
                <p class="text-muted mb-4">Please set a password for your account. You'll use your email and this password to log in.</p>
                
                <form method="POST" id="passwordForm" action="">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="5" required>
                        <small class="text-muted">Minimum 5 characters</small>
                        <div id="passwordError" class="text-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="5" required>
                        <div id="confirmError" class="text-error"></div>
                    </div>
                    <button type="submit" class="btn growth-btn w-100">Complete Setup</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');
        const confirmError = document.getElementById('confirmError');
        const form = document.getElementById('passwordForm');

        function validatePasswords() {
            let valid = true;
            passwordError.textContent = '';
            confirmError.textContent = '';

            if (password.value.length > 0 && password.value.length < 5) {
                passwordError.textContent = 'Password must be at least 5 characters.';
                valid = false;
            }

            if (confirm.value && password.value !== confirm.value) {
                confirmError.textContent = 'Passwords do not match.';
                valid = false;
            }

            return valid;
        }

        password.addEventListener('input', validatePasswords);
        confirm.addEventListener('input', validatePasswords);

        form.addEventListener('submit', function(e) {
            if (!validatePasswords()) {
                e.preventDefault();
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>