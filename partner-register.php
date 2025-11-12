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
    } elseif (strlen($name) < 3) {
        $error = 'Name must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match("/^[0-9\s()]{8,15}$/", $phone)) {
        $error = 'Invalid phone number format.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id FROM partners WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $stmt = $conn->prepare("INSERT INTO partners (name, email, phone, is_approved) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $name, $email, $phone);
            if ($stmt->execute()) {
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
    <title>Partner Registration - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .registration-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #6abaed 0%, #a74fec 100%);
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
            max-width: 550px;
            width: 100%;
        }
        
        .logo-img {
            max-width: 120px;
            height: auto;
        }
        
        .registration-card h2 {
            color: #6abaed;
            font-size: 1.5rem;
            margin-top: 1rem;
        }
        
        .success-card {
            text-align: center;
        }
        
        .success-icon {
            font-size: 60px;
            color: #6abaed;
            margin-bottom: 15px;
        }
        
        .success-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .success-card p {
            font-size: 0.95rem;
        }
        
        .success-card .alert h5 {
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .success-card ol {
            font-size: 0.9rem;
            padding-left: 1.25rem;
        }
        
        .success-card ol li {
            margin-bottom: 0.5rem;
        }
        
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #6abaed;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .info-box strong {
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
        .registration-card h4 {
                font-size: 1rem;
                color: #a74fec;
                margin-top:1rem;
                font-weight: medium;
            }
        .submit-btn {
            background: #6abaed;
            color: white;
            padding: 12px;
            font-weight: bold;
        }
        
        .submit-btn:hover {
            background: #5aa9dc;
            color: white;
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
                font-size: 2.25rem;
            }

            .registration-card h4 {
                font-size: 1.75rem;
                color: #a74fec;
                margin-top:1rem;
                font-weight: medium;
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
            
            .success-card .alert h5 {
                font-size: 1.1rem;
            }
            
            .success-card ol {
                font-size: 1rem;
            }
            
            .info-box {
                padding: 15px;
                margin-bottom: 25px;
                font-size: 0.95rem;
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

            .registration-card h4 {
                font-size: 1rem;
                color: #a74fec;
                margin-top:1rem;
                font-weight: medium;
            }
            
            .success-card h2 {
                font-size: 1.25rem;
            }
            
            .success-card p {
                font-size: 0.9rem;
            }
            
            .info-box {
                padding: 10px;
                font-size: 0.85rem;
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
            
            .success-card ol {
                font-size: 0.85rem;
                padding-left: 1rem;
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
            
            .info-box {
                padding: 10px;
                margin-bottom: 15px;
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
        .alert, .success-card p, .info-box {
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
                    <h2 class="mb-4">Registration Submitted!</h2>
                    <div class="alert alert-info text-start">
                        <h5>What happens next?</h5>
                        <ol class="mb-0">
                            <li>Our admin will review your registration.</li>
                            <li>Once approved, you'll receive a unique setup link.</li>
                            <li>Use that link to set your password.</li>
                            <li>Login and complete your partner profile.</li>
                        </ol>
                    </div>
                    <p class="text-muted mt-3">You will be notified via email once your account is approved.</p>
                    <a href="index.html" class="btn btn-outline-secondary mt-3">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <img src="./assets/images/logo.png" alt="Nashaty Logo" class="logo-img">
                    <h2 class="mt-3 text-black">Partner Registration</h2>
                    <h4 class="">Join Nashaty as an Activity Center Partner</h4>
                </div>

                <div class="info-box">
                    <strong>📝 Registration Process:</strong>
                    <p class="mb-0 small">Fill in the details and once filtered and approved you will receive a link to complete the registration.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form id="registrationForm" method="POST" action="" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Center Name / Contact Person</label>
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
                        <small>Already have an account? <a href="partner-login.php">Login here</a></small><br>
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