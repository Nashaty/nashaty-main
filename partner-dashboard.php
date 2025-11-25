<?php
require_once 'config.php';
requireLogin('partner', 'partner-login.php');

$conn = getDBConnection();
$partner_id = $_SESSION['partner_id'];

$stmt = $conn->prepare("SELECT form_submitted FROM partners WHERE id = ?");
$stmt->bind_param("i", $partner_id);
$stmt->execute();
$result = $stmt->get_result();
$partner_data = $result->fetch_assoc();
$form_submitted = $partner_data['form_submitted'];
$stmt->close();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$form_submitted) {

    // Basic info
    $center_name = sanitizeInput($_POST['center_name']);
    $contact_person = sanitizeInput($_POST['contact_person']);
    $contact_email = sanitizeInput($_POST['contact_email']);
    $contact_phone = sanitizeInput($_POST['contact_phone']);
    $description = sanitizeInput($_POST['description']);

    // Activities
    $activities_offered = isset($_POST['activities_offered']) ? implode(', ', $_POST['activities_offered']) : '';

    // Age groups
    $age_groups = isset($_POST['age_groups']) ? implode(', ', $_POST['age_groups']) : '';

    // Gender
    $gender = sanitizeInput($_POST['gender'] ?? '');

    // Days
    $class_days = isset($_POST['class_days']) ? implode(', ', $_POST['class_days']) : '';

    // Class Timings - store as JSON
    $class_timings = [];
    foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day) {
        if (isset($_POST['timings_'.$day])) {
            $class_timings[$day] = $_POST['timings_'.$day];
        }
    }
    $class_timings_json = json_encode($class_timings);

    // Locations
    $location1 = sanitizeInput($_POST['location1'] ?? '');
    $location2 = sanitizeInput($_POST['location2'] ?? '');
    $location3 = sanitizeInput($_POST['location3'] ?? '');
    $location4 = sanitizeInput($_POST['location4'] ?? '');

    // Website / social
    $website = sanitizeInput($_POST['website'] ?? '');

    // Pricing
    $price_day = sanitizeInput($_POST['price_day'] ?? '');
    $price_week = sanitizeInput($_POST['price_week'] ?? '');
    $price_month = sanitizeInput($_POST['price_month'] ?? '');
    $free_trial = sanitizeInput($_POST['free_trial'] ?? 'No');

    // Terms
    $terms_ack = isset($_POST['terms_ack']) ? 1 : 0;
    $social_post = sanitizeInput($_POST['social_post'] ?? '');
    $confidentiality_ack = isset($_POST['confidentiality_ack']) ? 1 : 0;

    // Insert into database with individual columns
    $stmt = $conn->prepare("
        INSERT INTO partner_forms (
            partner_id, center_name, contact_person, contact_email, contact_phone, 
            description, activities_offered, age_groups, gender, 
            class_days, class_timings, location1, location2, location3, location4, 
            website, price_day, price_week, price_month, free_trial, 
            terms_ack, social_post, confidentiality_ack
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "issssssssssssssssssissi",
        $partner_id, $center_name, $contact_person, $contact_email, $contact_phone,
        $description, $activities_offered, $age_groups, $gender,
        $class_days, $class_timings_json, $location1, $location2, $location3, $location4,
        $website, $price_day, $price_week, $price_month, $free_trial,
        $terms_ack, $social_post, $confidentiality_ack
    );

    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("UPDATE partners SET form_submitted = 1 WHERE id = ?");
        $stmt2->bind_param("i", $partner_id);
        $stmt2->execute();
        $stmt2->close();
        $success = true;
        $form_submitted = true;
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
<title>Partner Dashboard - Nashaty</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
    body { 
        background: #e2ffbd;
        padding-bottom: 40px;
    }
    
    .dashboard-wrapper { 
        max-width: 1100px; 
        margin: 20px auto; 
        padding: 15px; 
    }
    
    .dashboard-card { 
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
    
    .dashboard-card h2 {
        color: #a74fec;
        font-size: 1.5rem;
        margin-top: 1rem;
    }
    
    .dashboard-card .text-muted {
        font-size: 0.9rem;
    }
    
    /* Progress Steps */
    .progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
        overflow-x: auto;
        padding: 10px 0;
    }
    
    .progress-steps::before {
        content: '';
        position: absolute;
        top: 30px;
        left: 5%;
        right: 5%;
        height: 2px;
        background: #e0e0e0;
        z-index: 0;
    }
    
    .step {
        flex: 1;
        min-width: 70px;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-circle {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #e0e0e0;
        color: #666;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 6px;
        transition: all 0.3s;
    }
    
    .step.active .step-circle {
        background: #a74fec;
        color: white;
        transform: scale(1.1);
    }
    
    .step.completed .step-circle {
        background: #28a745;
        color: white;
    }
    
    .step-label {
        font-size: 0.75rem;
        color: #666;
        font-weight: 500;
        line-height: 1.2;
    }
    
    /* Form Sections */
    .form-section {
        display: none;
        animation: fadeIn 0.3s;
    }
    
    .form-section.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
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
    
    .checkbox-flex {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .checkbox-item {
        background: #f8f9fa;
        padding: 12px;
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
    
    /* Timing Collapsible */
    .timing-day {
        margin-bottom: 12px;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .timing-day-header {
        background: #f8f9fa;
        padding: 12px 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        font-size: 0.95rem;
        color: #333;
    }
    
    .timing-day-header:hover {
        background: #e9ecef;
    }
    
    .timing-day-content {
        padding: 15px;
        display: none;
        background: white;
    }
    
    .timing-day-content.show {
        display: block;
    }
    
    .timing-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    /* Form Labels and Inputs */
    .form-label {
        font-size: 0.95rem;
        font-weight: 500;
        margin-bottom: 8px;
    }
    
    .form-control, textarea.form-control {
        font-size: 0.95rem;
        padding: 0.625rem 0.75rem;
    }
    
    /* Navigation Buttons */
    .nav-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 25px;
    }
    
    .btn-nav {
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
    }
    
    .btn-prev {
        background: #6c757d;
        color: white;
    }
    
    .btn-prev:hover {
        background: #5a6268;
    }
    
    .btn-next {
        background: #a74fec;
        color: white;
    }
    
    .btn-next:hover {
        background: #8b3fd1;
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
    
    /* Input Focus States */
    .form-control:focus, .form-select:focus {
        border-color: #a74fec;
        box-shadow: 0 0 0 0.2rem rgba(167, 79, 236, 0.25);
    }
    
    /* Row Layout for Multi-column Forms */
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    /* Spacing adjustments */
    .mb-4 {
        margin-bottom: 1.25rem !important;
    }
    
    /* Alerts */
    .alert h4 {
        font-size: 1.25rem;
    }
    
    /* Tablet and larger screens */
    @media (min-width: 576px) {
        .dashboard-wrapper {
            margin: 40px auto;
            padding: 20px;
        }
        
        .dashboard-card {
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
        
        .dashboard-card h2 {
            font-size: 1.75rem;
        }
        
        .dashboard-card .text-muted {
            font-size: 1rem;
        }
        
        .progress-steps {
            margin-bottom: 40px;
        }
        
        .progress-steps::before {
            top: 30px;
        }
        
        .step {
            min-width: 90px;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        
        .step-label {
            font-size: 0.85rem;
        }
        
        .section-header {
            padding: 20px;
            margin-bottom: 30px;
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
        
        .checkbox-flex {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        .select-all-btn {
            width: auto;
            padding: 8px 20px;
        }
        
        .timing-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .form-row {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .nav-buttons {
            flex-direction: row;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-nav {
            font-size: 1rem;
            padding: 12px 30px;
        }
        
        .form-label {
            font-size: 1rem;
        }
        
        .form-control, textarea.form-control {
            font-size: 1rem;
        }
        
        .alert h4 {
            font-size: 1.5rem;
        }
    }
    
    /* Desktop screens */
    @media (min-width: 768px) {
        .dashboard-card {
            padding: 40px;
        }
        
        .checkbox-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
        
        .timing-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }
        
        .form-row {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
    }
    
    /* Small mobile optimization */
    @media (max-width: 375px) {
        .dashboard-wrapper {
            margin: 15px auto;
            padding: 10px;
        }
        
        .dashboard-card {
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
        
        .dashboard-card h2 {
            font-size: 1.25rem;
        }
        
        .progress-steps {
            margin-bottom: 25px;
        }
        
        .step {
            min-width: 60px;
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            font-size: 0.85rem;
        }
        
        .step-label {
            font-size: 0.7rem;
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
        
        .btn-nav {
            font-size: 0.9rem;
            padding: 10px 15px;
        }
    }
    
    /* Landscape mobile */
    @media (max-height: 600px) and (orientation: landscape) {
        .dashboard-wrapper {
            margin: 15px auto;
        }
        
        .dashboard-card {
            padding: 20px;
        }
        
        .progress-steps {
            margin-bottom: 20px;
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
<a href="partner-logout.php" class="btn btn-sm btn-danger logout-btn">Logout</a>

<div class="dashboard-wrapper">
<div class="dashboard-card">
<div class="text-center mb-4">
    <img src="./assets/images/logo.png" alt="Nashaty Logo" class="logo-img">
    <h2 class="mt-3">Partner Dashboard</h2>
    <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['contact_person']); ?></p>
</div>

<?php if ($success): ?>
<div class="alert alert-success text-center">
    <h4>Thank you for your submission!</h4>
    <p>We have received your information and will contact you soon.</p>
    <a href="index.html" class="btn growth-btn mt-3">Back to Home</a>
</div>

<?php elseif ($form_submitted): ?>
<div class="alert alert-info text-center">
    <h4>Form Already Submitted</h4>
    <p>You have already submitted your partner information. Thank you!</p>
    <a href="index.html" class="btn btn-outline-secondary">Back to Home</a>
</div>

<?php else: ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Validation Summary -->
<div class="validation-summary" id="validationSummary">
    <strong>⚠️ Please fix the following errors:</strong>
    <ul id="errorList"></ul>
</div>

<!-- Progress Steps -->
<div class="progress-steps">
    <div class="step active" data-step="1">
        <div class="step-circle">1</div>
        <div class="step-label">Basic Info</div>
    </div>
    <div class="step" data-step="2">
        <div class="step-circle">2</div>
        <div class="step-label">Activities</div>
    </div>
    <div class="step" data-step="3">
        <div class="step-circle">3</div>
        <div class="step-label">Schedule</div>
    </div>
    <div class="step" data-step="4">
        <div class="step-circle">4</div>
        <div class="step-label">Pricing</div>
    </div>
    <div class="step" data-step="5">
        <div class="step-circle">5</div>
        <div class="step-label">Review</div>
    </div>
</div>

<form method="POST" action="" id="partnerForm" novalidate>

    <!-- Section 1: Basic Information -->
    <div class="form-section active" data-section="1">
        <div class="section-header">
            <h4>📋 Center Information</h4>
            <p>To help us better connect you please share more information</p>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Centre Name *</label>
            <input type="text" class="form-control" name="center_name" id="center_name" required
                value="<?php echo htmlspecialchars($_SESSION['center_name']); ?>"
            >
            <div class="error-message" id="error_center_name">Please enter the centre name</div>
        </div>
        
        <div class="form-row mb-4">
            <div>
                <label class="form-label">Authorised Person Name *</label>
                <input type="text" class="form-control" name="contact_person" id="contact_person" required
                    value="<?php echo htmlspecialchars($_SESSION['contact_person']); ?>"
                >
                <div class="error-message" id="error_contact_person">Please enter the contact person name</div>
            </div>
            <div>
                <label class="form-label">Contact Email *</label>
                <input type="email" class="form-control" name="contact_email" id="contact_email" readonly
                    value="<?php echo htmlspecialchars($_SESSION['partner_email']); ?>"
                >
                <div class="error-message" id="error_contact_email">Please enter a valid email address</div>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Contact Number *</label>
            <input type="tel" class="form-control" name="contact_phone" id="contact_phone" required pattern="[0-9+\-\s]{7,15}"
                value="<?php echo htmlspecialchars($_SESSION['contact_phone']); ?>"
            >
            <div class="error-message" id="error_contact_phone">Please enter a valid phone number (7-15 digits)</div>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Description of Centre *</label>
            <textarea class="form-control" name="description" id="description" rows="4" required></textarea>
            <div class="error-message" id="error_description">Please provide a description of your centre</div>
        </div>
    </div>

    <!-- Section 2: Activities & Age Groups -->
    <div class="form-section" data-section="2">
        <div class="section-header">
            <h4>🎯 Activities & Demographics</h4>
            <p>Select the activities you offer and target age groups</p>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Activities Offered *</label>
            <div class="select-all-btn" onclick="toggleAll('activities_offered[]')">✓ Select All Activities</div>
            <div class="checkbox-grid" id="activities_grid">
                <?php 
                $activities = ['Swimming','Gymnastics','Soccer','Basketball','Martial Arts','Drama','Music','Robotics','Programming','Multi-activities','Ninja Training','Learning about Qatar Traditions','Etiquette','Pottery','Roller Skating','Horse Riding','Tennis','Other'];
                foreach($activities as $activity): ?>
                <div class="checkbox-item">
                    <input type="checkbox" name="activities_offered[]" class="form-check-input" value="<?php echo $activity; ?>" id="act_<?php echo str_replace(' ','_',$activity); ?>">
                    <label for="act_<?php echo str_replace(' ','_',$activity); ?>"><?php echo $activity; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="error-message" id="error_activities">Please select at least one activity</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Age Groups *</label>
            <div class="select-all-btn" onclick="toggleAll('age_groups[]')">✓ Select All Ages</div>
            <div class="checkbox-grid">
                <?php 
                $ages = ['3-5 years','6-8 years','9-11 years','12-15 years','16-18 years'];
                foreach($ages as $age): ?>
                <div class="checkbox-item">
                    <input type="checkbox" name="age_groups[]" value="<?php echo $age; ?>" class="form-check-input" id="age_<?php echo str_replace(' ','',$age); ?>">
                    <label for="age_<?php echo str_replace(' ','',$age); ?>"><?php echo $age; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="error-message" id="error_age_groups">Please select at least one age group</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Gender *</label>
            <div class="checkbox-grid">
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="gender" value="Mixed" id="gender_mixed" required>
                    <label for="gender_mixed">Mixed</label>
                </div>
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="gender" value="Girls Only" id="gender_girls">
                    <label for="gender_girls">Girls Only</label>
                </div>
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="gender" value="Boys Only" id="gender_boys">
                    <label for="gender_boys">Boys Only</label>
                </div>
            </div>
            <div class="error-message" id="error_gender">Please select a gender option</div>
        </div>
    </div>

    <!-- Section 3: Schedule -->
    <div class="form-section" data-section="3">
        <div class="section-header">
            <h4>📅 Class Schedule</h4>
            <p>Set your class days and timings</p>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Class Days *</label>
            <div class="select-all-btn" onclick="toggleAll('class_days[]')">✓ Select All Days</div>
            <div class="checkbox-grid">
                <?php $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                foreach($days as $day): ?>
                <div class="checkbox-item">
                    <input type="checkbox" class="form-check-input day-checkbox" name="class_days[]" value="<?php echo $day; ?>" id="day_<?php echo $day; ?>" onchange="toggleDayTimings('<?php echo $day; ?>')">
                    <label for="day_<?php echo $day; ?>"><?php echo $day; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="error-message" id="error_class_days">Please select at least one class day</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Class Timings per Day</label>
            <p class="text-muted small">Expand each day to select available time slots</p>
            <?php
            $timings = ['7-8 am','8-9 am','9-10 am','10-11 am','11-12 pm','12-1 pm','1-2 pm','2-3 pm','3-4 pm','4-5 pm','5-6 pm','6-7 pm','7-8 pm','8-9 pm','9-10 pm'];
            foreach($days as $day): ?>
            <div class="timing-day" id="timing_<?php echo $day; ?>" style="display:none;">
                <div class="timing-day-header" onclick="toggleTimingContent('<?php echo $day; ?>')">
                    <span>🕐 <?php echo $day; ?> Timings</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="timing-day-content" id="content_<?php echo $day; ?>">
                    <div class="select-all-btn" onclick="toggleAll('timings_<?php echo $day; ?>[]')">✓ Select All Times</div>
                    <div class="timing-grid">
                        <?php foreach($timings as $time): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" class="form-check-input" name="timings_<?php echo $day; ?>[]" value="<?php echo $time; ?>" id="time_<?php echo $day.'_'.str_replace(' ','',$time); ?>">
                            <label for="time_<?php echo $day.'_'.str_replace(' ','',$time); ?>"><?php echo $time; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-4">
            <label class="form-label">Locations *</label>
            <input type="text" class="form-control mb-3" name="location1" id="location1" placeholder="Primary Location *" required>
            <div class="error-message" id="error_location1">Please enter at least one location</div>
            <input type="text" class="form-control mb-2" name="location2" placeholder="Location 2 (Optional)">
            <input type="text" class="form-control mb-2" name="location3" placeholder="Location 3 (Optional)">
            <input type="text" class="form-control" name="location4" placeholder="Location 4 (Optional)">
        </div>
    </div>

    <!-- Section 4: Pricing -->
    <div class="form-section" data-section="4">
        <div class="section-header">
            <h4>💰 Pricing Information</h4>
            <p>Set your pricing structure</p>
        </div>
        
        <div class="form-row mb-4">
            <div>
                <label class="form-label">Price Per Day *</label>
                <input type="number" class="form-control" name="price_day" id="price_day" placeholder="e.g., 500 QAR" required>
                <div class="error-message" id="error_price_month">Please enter daily price</div>
            </div>
            <div>
                <label class="form-label">Price Per Week *</label>
                <input type="number" class="form-control" name="price_week" id="price_week" placeholder="e.g., 1400 QAR" required>
                <div class="error-message" id="price_week">Please enter weekly price</div>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Price Per Month *</label>
            <input type="number" class="form-control" name="price_month" id="price_month" placeholder="e.g., 5000 QAR" required>
            <div class="error-message" id="price_month">Please enter monthly price</div>
        </div>

        <div class="mb-4">
            <label class="form-label">Free Trial Available? *</label>
            <div class="checkbox-flex">
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="free_trial" value="Yes" id="trial_yes" required>
                    <label for="trial_yes">✓ Yes, we offer free trial</label>
                </div>
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="free_trial" value="No" id="trial_no">
                    <label for="trial_no">✗ No free trial</label>
                </div>
            </div>
            <div class="error-message" id="error_free_trial">Please select a free trial option</div>
        </div>
    </div>

    <!-- Section 5: Terms & Review -->
    <div class="form-section" data-section="5">
        <div class="section-header">
            <h4>✅ Terms & Conditions</h4>
            <p>Review and accept our terms</p>
        </div>
        
        <div class="mb-4">
            <!-- <p style="font-size: 0.9rem; line-height: 1.6;">By ticking this box, you acknowledge and agree to the following terms and conditions concerning your use of the Nahsaty service. This service provides a concierge-style manual matching of your offered activities with customer preferences. You understand and accept that Nahsaty does not guarantee, warrant, or promise any specific number of customer referrals, or any customer referrals at all. The provision of customer leads is entirely dependent on customer interest, preferences, and final decisions, which are not influenced or controlled by Nahsaty. This service is currently being offered on a limited, complimentary basis as part of a soft launch. Nahsaty reserves the right to introduce a formal contract and apply service charges at a later date and will notify all registered service providers accordingly. By proceeding, you agree to waive any and all claims, demands, liabilities, and causes of action and fully release Nahsaty, its parent companies, subsidiaries, affiliates, and its and their respective officers, directors, employees, agents, and contractors.</p> -->
             <p>we’re updating our terms and conditions</p>
            <div class="checkbox-item mb-3">
                <input type="checkbox" class="form-check-input" name="terms_ack" id="terms_ack" required>
                <label for="terms_ack">I acknowledge and agree to the above terms & conditions *</label>
            </div>
            <div class="error-message" id="error_terms">Please accept the terms and conditions</div>
        </div>

        <div class="mb-4">
            <label class="form-label" style="font-size: 0.9rem;">By clicking this box, you agree that Nahsaty may publish your centre as a partner in Services and create social media posts that include your centre.</label>
            <div class="checkbox-flex">
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="social_post" value="Allow" id="social_allow" required>
                    <label for="social_allow">✓ Allow social media posts</label>
                </div>
                <div class="checkbox-item">
                    <input type="radio" class="form-check-input" name="social_post" value="Do Not Allow" id="social_deny">
                    <label for="social_deny">✗ Do NOT allow posts</label>
                </div>
            </div>
            <div class="error-message" id="error_social">Please select a social media option</div>
        </div>

        <div class="mb-4">
            <!-- <p style="font-size: 0.9rem; line-height: 1.6;">By ticking this box, you agree to treat all terms and conditions of this service as strictly confidential and not to disclose them to any third party without the express written consent of Nahsaty.</p> -->
             <p>we’re updating our terms and conditions</p>
            <div class="checkbox-item">
                <input type="checkbox" class="form-check-input" name="confidentiality_ack" id="confidentiality_ack" required>
                <label for="confidentiality_ack">I agree to above confidentiality terms *</label>
            </div>
            <div class="error-message" id="error_confidentiality">Please accept confidentiality terms</div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <button type="button" class="btn-nav btn-prev" id="prevBtn" onclick="changeSection(-1)">← Previous</button>
        <button type="button" class="btn-nav btn-next" id="nextBtn" onclick="changeSection(1)">Next →</button>
        <button type="submit" class="btn-nav btn-next" id="submitBtn" style="display:none;">Submit Form ✓</button>
    </div>
</form>
<?php endif; ?>
</div>
</div>

<script>
let currentSection = 1;
const totalSections = 5;

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    updateNavigationButtons();
});

// Toggle all checkboxes
function toggleAll(name) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;

        // If it's days, also show/hide timing sections
        if (name === 'class_days[]') {
            toggleDayTimings(cb.value);
        }
    });
}

// Show/hide day timings based on day selection
function toggleDayTimings(day) {
    const dayCheckbox = document.getElementById('day_' + day);
    const timingSection = document.getElementById('timing_' + day);

    if (dayCheckbox.checked) {
        timingSection.style.display = 'block';
    } else {
        timingSection.style.display = 'none';
        // Uncheck all timings for this day
        const timingCheckboxes = document.querySelectorAll(`input[name="timings_${day}[]"]`);
        timingCheckboxes.forEach(cb => cb.checked = false);
    }
}

// Toggle timing content visibility (expand/collapse)
function toggleTimingContent(day) {
    const content = document.getElementById('content_' + day);
    content.classList.toggle('show');
}

// Change section
function changeSection(direction) {
    // Validate current section before moving forward
    if (direction > 0 && !validateSection(currentSection)) {
        showValidationSummary();
        return;
    }

    // Hide current section
    document.querySelector(`.form-section[data-section="${currentSection}"]`).classList.remove('active');
    
    // Update section number
    currentSection += direction;
    
    // Show new section
    document.querySelector(`.form-section[data-section="${currentSection}"]`).classList.add('active');
    
    // Update progress steps
    updateProgressSteps();
    
    // Update navigation buttons
    updateNavigationButtons();
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Hide validation summary when changing sections
    hideValidationSummary();
}

// Update progress steps
function updateProgressSteps() {
    document.querySelectorAll('.step').forEach(step => {
        const stepNumber = parseInt(step.getAttribute('data-step'));
        step.classList.remove('active', 'completed');
        
        if (stepNumber === currentSection) {
            step.classList.add('active');
        } else if (stepNumber < currentSection) {
            step.classList.add('completed');
        }
    });
}

function validatePriceField(fieldId, errorMessage, errors) {
    const field = document.getElementById(fieldId);
    const value = field?.value.trim();

    // Allow numbers with optional decimal point
    const pricePattern = /^\d+(\.\d{1,2})?$/;

    if (!value || !pricePattern.test(value)) {
        markFieldError(fieldId);
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Update navigation buttons
function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Show/hide previous button
    prevBtn.style.display = currentSection === 1 ? 'none' : 'block';
    
    // Show/hide next and submit buttons
    if (currentSection === totalSections) {
        nextBtn.style.display = 'none';
        submitBtn.style.display = 'block';
    } else {
        nextBtn.style.display = 'block';
        submitBtn.style.display = 'none';
    }
}

// Validate section
function validateSection(section) {
    let isValid = true;
    const errors = [];
    
    // Clear previous errors
    document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
    document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
    
    switch(section) {
        case 1: // Basic Info
            isValid = validateField('center_name', 'Centre name is required', errors) && isValid;
            isValid = validateField('contact_person', 'Contact person name is required', errors) && isValid;
            isValid = validateEmail('contact_email', 'Valid email is required', errors) && isValid;
            isValid = validatePhone('contact_phone', 'Valid phone number is required', errors) && isValid;
            isValid = validateField('description', 'Centre description is required', errors) && isValid;
            break;
            
        case 2: // Activities
            isValid = validateCheckboxGroup('activities_offered[]', 'At least one activity must be selected', errors) && isValid;
            isValid = validateCheckboxGroup('age_groups[]', 'At least one age group must be selected', errors) && isValid;
            isValid = validateRadioGroup('gender', 'Gender option must be selected', errors) && isValid;
            break;
            
        case 3: // Schedule
            isValid = validateCheckboxGroup('class_days[]', 'At least one class day must be selected', errors) && isValid;
            isValid = validateField('location1', 'At least one location is required', errors) && isValid;
            break;
            
        case 4: // Pricing
    isValid = validatePriceField('price_day', 'Please enter a valid daily price', errors) && isValid;
    isValid = validatePriceField('price_week', 'Please enter a valid weekly price', errors) && isValid;
    isValid = validatePriceField('price_month', 'Please enter a valid monthly price', errors) && isValid;
    isValid = validateRadioGroup('free_trial', 'Free trial option must be selected', errors) && isValid;
    break;
            
        case 5: // Terms
            isValid = validateCheckbox('terms_ack', 'You must accept the terms and conditions', errors) && isValid;
            isValid = validateRadioGroup('social_post', 'Social media permission must be selected', errors) && isValid;
            isValid = validateCheckbox('confidentiality_ack', 'You must accept confidentiality terms', errors) && isValid;
            break;
    }
    
    return isValid;
}

// Validate text field
function validateField(fieldId, errorMessage, errors) {
    const field = document.getElementById(fieldId);
    if (!field || !field.value.trim()) {
        markFieldError(fieldId);
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Validate email
function validateEmail(fieldId, errorMessage, errors) {
    const field = document.getElementById(fieldId);
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!field || !field.value.trim() || !emailPattern.test(field.value)) {
        markFieldError(fieldId);
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Validate phone
function validatePhone(fieldId, errorMessage, errors) {
    const field = document.getElementById(fieldId);
    const phonePattern = /^[0-9+\-\s]{7,15}$/;
    if (!field || !field.value.trim() || !phonePattern.test(field.value)) {
        markFieldError(fieldId);
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Validate checkbox group
function validateCheckboxGroup(name, errorMessage, errors) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
    const isChecked = Array.from(checkboxes).some(cb => cb.checked);
    if (!isChecked) {
        const errorId = 'error_' + name.replace('[]', '').replace('_offered', '');
        document.getElementById(errorId)?.classList.add('show');
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Validate radio group
function validateRadioGroup(name, errorMessage, errors) {
    const radios = document.querySelectorAll(`input[name="${name}"]`);
    const isChecked = Array.from(radios).some(rb => rb.checked);
    if (!isChecked) {
        document.getElementById('error_' + name)?.classList.add('show');
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Validate single checkbox
function validateCheckbox(fieldId, errorMessage, errors) {
    const checkbox = document.getElementById(fieldId);
    if (!checkbox || !checkbox.checked) {
        document.getElementById('error_' + fieldId.replace('_ack', ''))?.classList.add('show');
        errors.push(errorMessage);
        return false;
    }
    return true;
}

// Mark field with error
function markFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('field-error');
        document.getElementById('error_' + fieldId)?.classList.add('show');
    }
}

// Show validation summary
function showValidationSummary() {
    const summary = document.getElementById('validationSummary');
    const errorList = document.getElementById('errorList');
    const errors = [];
    
    // Collect all visible error messages
    document.querySelectorAll('.error-message.show').forEach(el => {
        errors.push(el.textContent);
    });
    
    if (errors.length > 0) {
        errorList.innerHTML = errors.map(err => `<li>${err}</li>`).join('');
        summary.classList.add('show');
        summary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Hide validation summary
function hideValidationSummary() {
    document.getElementById('validationSummary').classList.remove('show');
}

// Form submission validation
document.getElementById('partnerForm').addEventListener('submit', function(e) {
    // Validate all sections
    let allValid = true;
    for (let i = 1; i <= totalSections; i++) {
        if (!validateSection(i)) {
            allValid = false;
        }
    }
    
    if (!allValid) {
        e.preventDefault();
        // Go to first section with errors
        for (let i = 1; i <= totalSections; i++) {
            if (!validateSection(i)) {
                currentSection = i;
                document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
                document.querySelector(`.form-section[data-section="${i}"]`).classList.add('active');
                updateProgressSteps();
                updateNavigationButtons();
                showValidationSummary();
                break;
            }
        }
    }
});

// Real-time validation on blur
document.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('blur', function() {
        if (this.hasAttribute('required') && this.value.trim()) {
            this.classList.remove('field-error');
            const errorEl = document.getElementById('error_' + this.id);
            if (errorEl) errorEl.classList.remove('show');
        }
    });
});

// Remove error styling on input
document.querySelectorAll('input, textarea').forEach(field => {
    field.addEventListener('input', function() {
        if (this.classList.contains('field-error') && this.value.trim()) {
            this.classList.remove('field-error');
            const errorEl = document.getElementById('error_' + this.id);
            if (errorEl) errorEl.classList.remove('show');
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>