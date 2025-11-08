<?php
require_once 'config.php';
requireLogin('parent', 'parent-register.php');

$conn = getDBConnection();
$parent_id = $_SESSION['parent_id'];

// Check if form already submitted
$stmt = $conn->prepare("SELECT id FROM parent_activities WHERE parent_id = ?");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$result = $stmt->get_result();
$already_submitted = $result->num_rows > 0;
$stmt->close();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_submitted) {
    $phone = sanitizeInput($_POST['phone_number']);
    $email = sanitizeInput($_POST['email']);
    $preferred_contact = sanitizeInput($_POST['preferred_contact']);
    $number_of_kids = intval($_POST['number_of_kids']);
    $activities = isset($_POST['activities']) ? implode(', ', $_POST['activities']) : '';
    $other_activity = sanitizeInput($_POST['other_activity'] ?? '');
    $preferred_centers = sanitizeInput($_POST['preferred_centers'] ?? '');
    $class_preference = sanitizeInput($_POST['class_preference']);
    $language_preference = sanitizeInput($_POST['language_preference']);
    
    $stmt = $conn->prepare("INSERT INTO parent_activities (parent_id, phone_number, email, preferred_contact, number_of_kids, activities, other_activity, preferred_centers, class_preference, language_preference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisssss", $parent_id, $phone, $email, $preferred_contact, $number_of_kids, $activities, $other_activity, $preferred_centers, $class_preference, $language_preference);
    
    if ($stmt->execute()) {
        $success = true;
        $already_submitted = true;
    } else {
        $error = 'Failed to submit form. Please try again.';
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Interest Form - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background: #e2ffbd;
            padding-bottom: 40px;
        }
        
        .form-wrapper { 
            max-width: 900px; 
            margin: 20px auto; 
            padding: 15px; 
        }
        
        .form-card { 
            background: white; 
            border-radius: 20px; 
            padding: 25px 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
        }
        
        .logout-btn { 
            position: fixed; 
            top: 15px; 
            right: 15px;
            z-index: 1000;
            font-size: 0.85rem;
            padding: 6px 12px;
        }
        
        .logo-img {
            max-width: 120px;
            height: auto;
        }
        
        .form-card h2 {
            color: #a74fec;
            font-size: 1.5rem;
            margin-top: 1rem;
        }
        
        .form-card .text-muted {
            font-size: 0.9rem;
        }
        
        /* Section Header */
        .section-header {
            background: linear-gradient(135deg, #a74fec 0%, #8b3fd1 100%);
            color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .section-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .section-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.85rem;
        }
        
        /* Checkbox Grid Layout */
        .checkbox-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-top: 12px;
        }
        
        .checkbox-item {
            background: #f8f9fa;
            padding: 12px 12px;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .checkbox-item:hover {
            background: #e9ecef;
            border-color: #a74fec;
        }
        
        .checkbox-item input[type="checkbox"]:checked ~ label,
        .checkbox-item input[type="radio"]:checked ~ label {
            color: #a74fec;
            font-weight: 600;
        }
        
        .checkbox-item input {
            margin-right: 10px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            font-size: 0.95rem;
        }
        
        /* Select All Button */
        .select-all-btn {
            background: #f8f9fa;
            border: 2px dashed #a74fec;
            color: #a74fec;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
            margin-bottom: 12px;
            transition: all 0.2s;
            text-align: center;
            width: 100%;
        }
        
        .select-all-btn:hover {
            background: #a74fec;
            color: white;
            border-style: solid;
        }
        
        /* Form Labels */
        .form-label {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        /* Input Focus States */
        .form-control:focus, .form-select:focus {
            border-color: #a74fec;
            box-shadow: 0 0 0 0.2rem rgba(167, 79, 236, 0.25);
        }
        
        .form-control, textarea.form-control {
            font-size: 0.95rem;
            padding: 0.625rem 0.75rem;
        }
        
        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        /* Submit Button */
        .btn-submit {
            background: #a74fec;
            color: white;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            font-size: 1rem;
        }
        
        .btn-submit:hover {
            background: #8b3fd1;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(167, 79, 236, 0.4);
        }
        
        /* Read-only fields */
        .form-control[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
            font-size: 0.9rem;
        }
        
        /* Success/Info alerts */
        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.95rem;
        }
        
        .alert h4 {
            font-size: 1.25rem;
        }
        
        /* Error Messages */
        .field-error {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .validation-summary {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            display: none;
            font-size: 0.9rem;
        }
        
        .validation-summary.show {
            display: block;
        }
        
        .validation-summary ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
            font-size: 0.85rem;
        }
        
        /* Spacing adjustments */
        .mb-4 {
            margin-bottom: 1.25rem !important;
        }
        
        /* Tablet and larger screens */
        @media (min-width: 576px) {
            .form-wrapper {
                margin: 40px auto;
                padding: 20px;
            }
            
            .form-card {
                padding: 35px 30px;
            }
            
            .logout-btn {
                top: 20px;
                right: 20px;
                font-size: 0.9rem;
                padding: 8px 16px;
            }
            
            .logo-img {
                max-width: 150px;
            }
            
            .form-card h2 {
                font-size: 1.75rem;
            }
            
            .form-card .text-muted {
                font-size: 1rem;
            }
            
            .section-header {
                padding: 20px;
            }
            
            .section-header h4 {
                font-size: 1.25rem;
            }
            
            .section-header p {
                font-size: 0.9rem;
            }
            
            .checkbox-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .select-all-btn {
                width: auto;
                padding: 8px 20px;
            }
            
            .form-row {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .form-label {
                font-size: 1rem;
            }
            
            .form-control, textarea.form-control {
                font-size: 1rem;
            }
            
            .btn-submit {
                font-size: 1.1rem;
            }
            
            .alert h4 {
                font-size: 1.5rem;
            }
        }
        
        /* Desktop screens */
        @media (min-width: 768px) {
            .form-card {
                padding: 40px;
            }
            
            .checkbox-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .section-header {
                margin-bottom: 30px;
            }
        }
        
        /* Small mobile optimization */
        @media (max-width: 375px) {
            .form-wrapper {
                margin: 15px auto;
                padding: 10px;
            }
            
            .form-card {
                padding: 20px 15px;
                border-radius: 15px;
            }
            
            .logout-btn {
                top: 10px;
                right: 10px;
                font-size: 0.8rem;
                padding: 5px 10px;
            }
            
            .logo-img {
                max-width: 100px;
            }
            
            .form-card h2 {
                font-size: 1.25rem;
            }
            
            .section-header {
                padding: 12px;
            }
            
            .section-header h4 {
                font-size: 1rem;
            }
            
            .section-header p {
                font-size: 0.8rem;
            }
            
            .checkbox-item {
                padding: 10px;
            }
            
            .checkbox-item label {
                font-size: 0.9rem;
            }
            
            .select-all-btn {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
            
            .btn-submit {
                font-size: 0.95rem;
                padding: 12px;
            }
        }
        
        /* Landscape mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            .form-wrapper {
                margin: 15px auto;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .section-header {
                padding: 12px;
                margin-bottom: 20px;
            }
            
            .mb-4 {
                margin-bottom: 1rem !important;
            }
        }
    </style>
</head>
<body>
    <a href="parent-logout.php" class="btn btn-sm btn-danger logout-btn">Logout</a>
    
    <div class="form-wrapper">
        <div class="form-card">
            <div class="text-center mb-4">
                <img src="./assets/images/logo.png" alt="Nashaty Logo" class="logo-img">
                <h2 class="mt-3">Activity Interest Form</h2>
                <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['parent_name']); ?></p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <h4>✓ Thank you for your submission!</h4>
                    <p>We will contact you soon with suitable activity options.</p>
                    <br>
                    <a href="index.html" class="btn btn-outline-secondary">Back to Home</a>
                </div>
            <?php elseif ($already_submitted): ?>
                <div class="alert alert-info text-center">
                    <h4>Form Already Submitted</h4>
                    <p>You have already submitted this form. Thank you!</p>
                    <br>
                    <a href="index.html" class="btn btn-outline-secondary">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="section-header">
                    <h4>📋 Tell Us About Your Interests</h4>
                    <p>This information will not be shared with anyone. It will be used for market research and early user adoption sign-ups.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Validation Summary -->
                <div class="validation-summary" id="validationSummary">
                    <strong>⚠️ Please fix the following errors:</strong>
                    <ul id="errorList"></ul>
                </div>
                
                <form method="POST" action="" id="activityForm" novalidate>
                    <!-- Parent Information -->
                    <div class="mb-4">
                        <label class="form-label">Parent's Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['parent_name']); ?>" readonly>
                    </div>
                    
                    <div class="form-row mb-4">
                        <div>
                            <label for="phone_number" class="form-label">Phone Number *</label>
                            <input 
                                type="tel" 
                                class="form-control" 
                                id="phone_number" 
                                name="phone_number" 
                                value="<?php echo htmlspecialchars($_SESSION['parent_phone'] ?? ''); ?>" 
                                readonly
                                required
                            >
                        </div>
                        <div>
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" readonly class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['parent_email']); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Contact Preferences -->
                    <div class="mb-4">
                        <label class="form-label">Preferred Method of Contact *</label>
                        <div class="checkbox-grid">
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="preferred_contact" value="Mobile Phone" id="contact_mobile" required>
                                <label for="contact_mobile">📱 Mobile Phone</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="preferred_contact" value="Email" id="contact_email">
                                <label for="contact_email">📧 Email</label>
                            </div>
                        </div>
                        <div class="error-message" id="error_preferred_contact">Please select a preferred contact method</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="number_of_kids" class="form-label">Number of Kids *</label>
                        <input type="number" 
                               class="form-control" 
                               id="number_of_kids" 
                               name="number_of_kids" 
                               min="1" 
                               step="1" 
                               required
                               oninput="this.value = this.value.replace(/[^0-9]/g,'');">
                        <div class="error-message" id="error_number_of_kids">
                            Please enter a valid number of kids (whole number, minimum 1)
                        </div>
                    </div>

                    <!-- Activities -->
                    <div class="mb-4">
                        <label class="form-label">Which activities are you interested in for your kids? *</label>
                        <div class="select-all-btn" onclick="toggleAllActivities()">✓ Select All Activities</div>
                        <div class="checkbox-grid">
                            <?php
                            $activities = ['Swimming', 'Gymnastics', 'Soccer', 'Basketball', 'Martial Arts', 'Drama', 'Music', 'Robotics', 'Programming', 'Multi-activities', 'Ninja Training', 'Learning about Qatar Traditions', 'Etiquette', 'Pottery', 'Roller Skating', 'Horse Riding', 'Tennis', 'Other'];
                            foreach ($activities as $activity):
                            ?>
                            <div class="checkbox-item">
                                <input type="checkbox" class="form-check-input" name="activities[]" value="<?php echo $activity; ?>" id="act_<?php echo str_replace(' ', '_', $activity); ?>">
                                <label for="act_<?php echo str_replace(' ', '_', $activity); ?>"><?php echo $activity; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="error-message" id="error_activities">Please select at least one activity</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="other_activity" class="form-label">If 'Other' activity, please specify:</label>
                        <input type="text" class="form-control" id="other_activity" name="other_activity" placeholder="Specify other activity...">
                    </div>
                    
                    <div class="mb-4">
                        <label for="preferred_centers" class="form-label">Are there any centers you usually prefer to join or any preferred centers?</label>
                        <textarea class="form-control" id="preferred_centers" name="preferred_centers" rows="3" placeholder="Tell us about your preferred centers..."></textarea>
                    </div>
                    
                    <!-- Class Preferences -->
                    <div class="mb-4">
                        <label class="form-label">Do you prefer a girls-only or boys-only class? *</label>
                        <div class="checkbox-grid">
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="class_preference" value="Girls Only" id="girls_only" required>
                                <label for="girls_only">👧 Girls Only</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="class_preference" value="Boys Only" id="boys_only">
                                <label for="boys_only">👦 Boys Only</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="class_preference" value="No Preference" id="no_pref">
                                <label for="no_pref">👫 No Preference</label>
                            </div>
                        </div>
                        <div class="error-message" id="error_class_preference">Please select a class preference</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Do you prefer Arabic classes, English classes, or both? *</label>
                        <div class="checkbox-grid">
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="language_preference" value="Arabic" id="lang_arabic" required>
                                <label for="lang_arabic">Arabic</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="language_preference" value="English" id="lang_english">
                                <label for="lang_english">English</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="radio" class="form-check-input" name="language_preference" value="Both" id="lang_both">
                                <label for="lang_both">Both</label>
                            </div>
                        </div>
                        <div class="error-message" id="error_language_preference">Please select a language preference</div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Submit Form ✓</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle all activities
        function toggleAllActivities() {
            const checkboxes = document.querySelectorAll('input[name="activities[]"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }
        
        // Validate form
        function validateForm() {
            let isValid = true;
            const errors = [];
            
            // Clear previous errors
            document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
            document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
            
            // Validate preferred contact
            const preferredContact = document.querySelector('input[name="preferred_contact"]:checked');
            if (!preferredContact) {
                document.getElementById('error_preferred_contact').classList.add('show');
                errors.push('Preferred contact method is required');
                isValid = false;
            }
            
            // Validate number of kids
            const numberKids = document.getElementById('number_of_kids');
            if (!numberKids.value || numberKids.value < 1) {
                numberKids.classList.add('field-error');
                document.getElementById('error_number_of_kids').classList.add('show');
                errors.push('Number of kids must be at least 1');
                isValid = false;
            }
            
            // Validate activities
            const activities = document.querySelectorAll('input[name="activities[]"]:checked');
            if (activities.length === 0) {
                document.getElementById('error_activities').classList.add('show');
                errors.push('At least one activity must be selected');
                isValid = false;
            }
            
            // Validate class preference
            const classPreference = document.querySelector('input[name="class_preference"]:checked');
            if (!classPreference) {
                document.getElementById('error_class_preference').classList.add('show');
                errors.push('Class preference is required');
                isValid = false;
            }
            
            // Validate language preference
            const languagePreference = document.querySelector('input[name="language_preference"]:checked');
            if (!languagePreference) {
                document.getElementById('error_language_preference').classList.add('show');
                errors.push('Language preference is required');
                isValid = false;
            }
            
            
            // Show validation summary
            if (!isValid) {
                const summary = document.getElementById('validationSummary');
                const errorList = document.getElementById('errorList');
                errorList.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
                summary.classList.add('show');
                summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                document.getElementById('validationSummary').classList.remove('show');
            }
            
            return isValid;
        }
        
        // Form submission validation
        document.getElementById('activityForm')?.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
        
        // Real-time validation on input change
        document.getElementById('number_of_kids')?.addEventListener('input', function() {
            if (this.value && this.value >= 1) {
                this.classList.remove('field-error');
                document.getElementById('error_number_of_kids').classList.remove('show');
            }
        });
        
        // Real-time validation on radio/checkbox change
        document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', function() {
                const name = this.name;
                const errorId = 'error_' + name.replace('[]', '');
                const errorEl = document.getElementById(errorId);
                
                if (name.includes('[]')) {
                    // Checkbox group
                    const checked = document.querySelectorAll(`input[name="${name}"]:checked`);
                    if (checked.length > 0 && errorEl) {
                        errorEl.classList.remove('show');
                    }
                } else {
                    // Radio group
                    if (errorEl) {
                        errorEl.classList.remove('show');
                    }
                }
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>