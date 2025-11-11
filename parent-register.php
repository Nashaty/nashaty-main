<?php
require_once 'config.php';

$error = '';
$success = false;

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
        $stmt = $conn->prepare("SELECT id FROM parents WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            // Insert new parent
            $stmt = $conn->prepare("INSERT INTO parents (name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $phone);
            
            if ($stmt->execute()) {
                $_SESSION['parent_id'] = $conn->insert_id;
                $_SESSION['parent_name'] = $name;
                $_SESSION['parent_email'] = $email;
                $_SESSION['parent_phone'] = $phone;
                $success = true;
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Parent Registration - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .registration-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #e2ffbd 0%, #a74fec 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .registration-card {
            background: #E2FFBD;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .logo-img {
            max-width: 120px;
            height: auto;
        }
        
        .registration-card h2 {
            color: #a74fec;
            font-size: 1.5rem;
            margin-top: 1rem;
        }
        
        .success-card {
            text-align: center;
        }
        
        .success-icon {
            font-size: 60px;
            color: #a74fec;
            margin-bottom: 15px;
        }
        
        .success-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .success-card p {
            font-size: 0.95rem;
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
        
        .is-invalid {
            border-color: #dc3545 !important;
        }
        
        .text-center a {
            font-size: 0.95rem;
            text-decoration: none;
        }
        
        .text-center a:hover {
            text-decoration: underline;
        }
        
        /* Tablet and larger */
        @media (min-width: 576px) {
            .registration-wrapper {
                padding: 40px 20px;
            }
            
            .registration-card {
                padding: 40px;
            }
            
            .logo-img {
                max-width: 150px;
            }
            
            .registration-card h2 {
                font-size: 1.75rem;
            }
            
            .success-icon {
                font-size: 80px;
                margin-bottom: 20px;
            }
            
            .success-card h2 {
                font-size: 1.75rem;
            }
            
            .success-card p {
                font-size: 1rem;
            }
            
            .form-label {
                font-size: 1rem;
            }
            
            .text-center a {
                font-size: 1rem;
            }
        }
        
        /* Small mobile optimization */
        @media (max-width: 375px) {
            .registration-card {
                padding: 25px 15px;
                border-radius: 15px;
            }
            
            .logo-img {
                max-width: 100px;
            }
            
            .registration-card h2 {
                font-size: 1.25rem;
            }
            
            .success-icon {
                font-size: 50px;
                margin-bottom: 12px;
            }
            
            .success-card h2 {
                font-size: 1.25rem;
            }
            
            .success-card p {
                font-size: 0.9rem;
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
        
        /* Landscape mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .registration-wrapper {
                padding: 15px;
            }
            
            .registration-card {
                padding: 20px;
                margin: 10px 0;
            }
            
            .logo-img {
                max-width: 80px;
            }
            
            .registration-card h2 {
                font-size: 1.25rem;
                margin-top: 0.5rem;
            }
            
            .success-icon {
                font-size: 50px;
                margin-bottom: 10px;
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
        
        /* Ensure proper word wrapping */
        .alert, .success-card p {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="registration-wrapper">
        <div class="registration-card">
            <?php if ($success): ?>
                <div class="success-card">
                    <div class="success-icon">✓</div>
                    <h2 class="mb-4">Thank You!</h2>
                    <p class="mb-4">Your registration was successful. We're excited to help you find the perfect activities for your kids.</p>
                    <a href="parent-activity-form.php" class="btn growth-btn">Help us find suitable activities</a>
                    <br><br>
                    <a href="index.html" class="btn btn-outline-secondary">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <img src="./assets/images/logo.png" alt="Nashaty Logo" class="logo-img">
                    <h1 class="mt-2">Start Your Journey</h1>
                    <h2 class="mt-2">Register your interest </h2>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="registrationForm" method="POST" action="" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please enter at least 3 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                        <div class="invalid-feedback">Please enter a valid phone number (8–15 digits).</div>
                    </div>
                    <button type="submit" class="btn growth-btn w-100">Register</button>
                    <div class="text-center mt-3">
                        <a href="index.html">Back to Home</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Bootstrap form validation + live name/phone behavior
    (function () {
        'use strict';
        const form = document.getElementById('registrationForm');
        const nameInput = document.getElementById('name');
        const phoneInput = document.getElementById('phone');

        // Prevent invalid characters in phone input
        phoneInput.addEventListener('keypress', function (e) {
            const allowed = /[0-9+\-\s()]/;
            if (!allowed.test(e.key)) {
                e.preventDefault();
            }
        });

        // Remove error dynamically when user types enough characters
        nameInput.addEventListener('input', function () {
            if (nameInput.value.trim().length >= 3) {
                nameInput.classList.remove('is-invalid');
            }
        });

        // Form submission validation
        form.addEventListener('submit', function (event) {
            let isValid = true;

            // Name validation
            if (nameInput.value.trim().length < 3) {
                nameInput.classList.add('is-invalid');
                isValid = false;
            } else {
                nameInput.classList.remove('is-invalid');
            }

            // Phone validation pattern
            const phoneRegex = /^[0-9+\-\s()]{8,15}$/;
            if (!phoneRegex.test(phoneInput.value.trim())) {
                phoneInput.classList.add('is-invalid');
                isValid = false;
            } else {
                phoneInput.classList.remove('is-invalid');
            }

            if (!form.checkValidity() || !isValid) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    })();
    </script>
</body>
</html>