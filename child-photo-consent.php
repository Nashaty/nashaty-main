<?php
require_once 'config.php';
requireLogin('parent', 'parent-register.php');

$conn = getDBConnection();
$parent_id = $_SESSION['parent_id'];

// Check if consent already submitted
$stmt = $conn->prepare("SELECT id FROM child_photo_consent WHERE parent_id = ?");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$already_submitted = $result->num_rows > 0;
$stmt->close();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_submitted) {
    $parent_name = sanitizeInput($_POST['parent_name']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $consent_given = isset($_POST['consent_given']) ? 1 : 0;
    $is_authorized = isset($_POST['is_authorized']) ? 1 : 0;
    
    if (!$consent_given) {
        $error = 'Please provide consent for using child photos before submitting.';
    } elseif (!$is_authorized) {
        $error = 'You must confirm that you are the parent/guardian and authorized to give this permission.';
    } else {
        $stmt = $conn->prepare("INSERT INTO child_photo_consent (parent_id, parent_name, contact_number, consent_given, is_authorized) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $parent_id, $parent_name, $contact_number, $consent_given, $is_authorized);
        
        if ($stmt->execute()) {
            $success = true;
            $already_submitted = true;
        } else {
            $error = 'Failed to submit consent form. Please try again.';
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Photo Usage Consent - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #e2ffbd; }
        .form-wrapper { max-width: 800px; margin: 40px auto; padding: 20px; }
        .form-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .logout-btn { position: absolute; top: 20px; right: 20px; }
        
        /* Section Header */
        .section-header {
            background: linear-gradient(135deg, #a74fec 0%, #8b3fd1 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .section-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .section-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* Consent Box */
        .consent-box {
            background: #f8f9fa;
            border-left: 4px solid #a74fec;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .consent-box p {
            margin: 0;
            line-height: 1.6;
        }
        
        /* Checkbox Items */
        .consent-checkbox {
            background: #f8f9fa;
            padding: 18px 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .consent-checkbox:hover {
            background: #e9ecef;
            border-color: #a74fec;
        }
        .consent-checkbox.checked {
            background: #f0e7ff;
            border-color: #a74fec;
            box-shadow: 0 0 0 3px rgba(167, 79, 236, 0.1);
        }
        .consent-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #a74fec;
        }
        .consent-checkbox label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            font-size: 0.95rem;
        }
        .consent-checkbox .required-badge {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        /* Input Focus States */
        .form-control:focus {
            border-color: #a74fec;
            box-shadow: 0 0 0 0.2rem rgba(167, 79, 236, 0.25);
        }
        
        /* Submit Button */
        .btn-submit {
            background: #a74fec;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            font-size: 1.1rem;
        }
        .btn-submit:hover:not(:disabled) {
            background: #8b3fd1;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(167, 79, 236, 0.4);
        }
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 15px;
            border: none;
        }
        
        /* Error message */
        .error-hint {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 10px;
            display: none;
        }
        .error-hint.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <a href="parent-logout.php" class="btn btn-sm btn-danger logout-btn">Logout</a>
    
    <div class="form-wrapper">
        <div class="form-card">
            <div class="text-center mb-4">
                <img src="./assets/images/logo.png" alt="Nashaty Logo" style="max-width: 150px;">
                <h2 class="mt-3" style="color: #a74fec;">Child Photo Usage Consent</h2>
                <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['parent_name']); ?></p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <h4>✓ Thank you for your submission!</h4>
                    <p>Your consent form has been recorded successfully.</p>
                    <a href="index.html" class="btn growth-btn mt-3">Back to Home</a>
                </div>
            <?php elseif ($already_submitted): ?>
                <div class="alert alert-info text-center">
                    <h4>Consent Form Already Submitted</h4>
                    <p>You have already submitted this consent form. Thank you!</p>
                    <a href="index.html" class="btn btn-outline-secondary">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="section-header">
                    <h4>📸 Photo Usage Permission</h4>
                    <p>Please review and provide consent for using your child's photos</p>
                </div>
                
                <div class="consent-box">
                    <p><strong>📋 About This Consent:</strong></p>
                    <p>Consent and permission for <strong>Nashaty Trading CR 190168</strong>, represented by Reem Al-Ansari, to use child photos on websites and social media for advertisement purposes only.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="consentForm">
                    <!-- Parent Information -->
                    <div class="form-row mb-4">
                        <div>
                            <label for="parent_name" class="form-label">Digital Signature of Parent/Guardian Name *</label>
                            <input type="text" class="form-control" id="parent_name" name="parent_name" value="<?php echo htmlspecialchars($_SESSION['parent_name']); ?>" required>
                        </div>
                       <div>
    <label for="contact_number" class="form-label">Contact Number *</label>
    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
           placeholder="+974 XXXX XXXX" required
           pattern="^\d{8,15}$" minlength="8" maxlength="15"
           title="Please enter a valid phone number between 8 and 15 digits">
</div>

                    </div>
                    
                    <!-- Consent Checkboxes -->
                    <div class="mb-4">
                        <label class="form-label mb-3"><strong>Required Agreements:</strong></label>
                        
                        <div class="consent-checkbox" id="consent-box-1">
                            <div class="d-flex align-items-start">
                                <input class="form-check-input" type="checkbox" name="consent_given" id="consent_given" value="1" onchange="updateCheckboxStyle('consent-box-1', this); validateForm()">
                                <label class="form-check-label" for="consent_given">
                                    <strong>Photo Usage Consent</strong>
                                    <span class="required-badge">Required</span>
                                    <br>
                                    <small class="text-muted">I consent and give permission for Nashaty Trading CR 190168 represented by Reem Al-Ansari to use my child's photos on websites and social media for advertisement purposes only.</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="consent-checkbox" id="consent-box-2">
                            <div class="d-flex align-items-start">
                                <input class="form-check-input" type="checkbox" name="is_authorized" id="is_authorized" value="1" onchange="updateCheckboxStyle('consent-box-2', this); validateForm()">
                                <label class="form-check-label" for="is_authorized">
                                    <strong>Authorization Confirmation</strong>
                                    <span class="required-badge">Required</span>
                                    <br>
                                    <small class="text-muted">I am the parent/guardian and authorized to give this permission.</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="error-hint" id="error-hint">
                            ⚠️ Both agreements must be checked before submitting the form.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <span id="btnText">✓ Submit Consent</span>
                    </button>
                    <div class="text-center mt-2">
                        <small class="text-muted">Please agree to both terms above to enable submission</small>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update checkbox styling
        function updateCheckboxStyle(boxId, checkbox) {
            const box = document.getElementById(boxId);
            if (checkbox.checked) {
                box.classList.add('checked');
            } else {
                box.classList.remove('checked');
            }
        }
        
        // Validate form - enable submit only if both checkboxes are checked
        function validateForm() {
            const consentGiven = document.getElementById('consent_given').checked;
            const isAuthorized = document.getElementById('is_authorized').checked;
            const submitBtn = document.getElementById('submitBtn');
            const errorHint = document.getElementById('error-hint');
            
            if (consentGiven && isAuthorized) {
                submitBtn.disabled = false;
                submitBtn.style.cursor = 'pointer';
                errorHint.classList.remove('show');
            } else {
                submitBtn.disabled = true;
                submitBtn.style.cursor = 'not-allowed';
                if (consentGiven || isAuthorized) {
                    errorHint.classList.add('show');
                } else {
                    errorHint.classList.remove('show');
                }
            }
        }
        
        // Form submission validation
        document.getElementById('consentForm')?.addEventListener('submit', function(e) {
            const consentGiven = document.getElementById('consent_given').checked;
            const isAuthorized = document.getElementById('is_authorized').checked;
            const contactNumber = document.getElementById('contact_number').value.trim();
            
            if (!consentGiven) {
                e.preventDefault();
                alert('Please provide consent for using child photos.');
                return;
            }
            
            if (!isAuthorized) {
                e.preventDefault();
                alert('Please confirm that you are authorized to give this permission.');
                return;
            }
            
            if (!contactNumber) {
                e.preventDefault();
                alert('Please enter your contact number.');
                return;
            }
        });
        
        // Initialize validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            validateForm();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>