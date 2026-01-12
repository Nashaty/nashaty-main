<?php
require_once 'config.php';
requireLogin('admin', 'admin-login.php');

$conn = getDBConnection();

// Get pending partners
$pending_partners = $conn->query("SELECT * FROM partners WHERE is_approved = 0 ORDER BY created_at DESC");

// Get approved partners
$approved_partners = $conn->query("SELECT * FROM partners WHERE is_approved = 1 ORDER BY approved_at DESC");

// Get all parents
$parents = $conn->query("
    SELECT 
        p.*, 
        (SELECT COUNT(*) FROM parent_activities pa WHERE pa.parent_id = p.id) AS has_activity
    FROM parents p 
    ORDER BY p.created_at DESC
");


// Get contact submissions
$contacts = $conn->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 20");

// Get partner forms with partner details
$partner_forms = $conn->query("SELECT pf.*, p.center_name as partner_name, p.email as partner_email
    FROM partner_forms pf 
    JOIN partners p ON pf.partner_id = p.id 
    ORDER BY pf.submitted_at DESC");

// Get parent activity forms with parent details
$parent_forms = $conn->query("SELECT pa.*, p.name as parent_name, p.email as parent_email 
    FROM parent_activities pa 
    JOIN parents p ON pa.parent_id = p.id 
    ORDER BY pa.created_at DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f5f5f5; }
        .dashboard-header { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
        .card { border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .table-responsive { border-radius: 10px; }
        .badge-status { padding: 5px 10px; border-radius: 5px; }
        .btn-view-details { padding: 4px 12px; font-size: 0.875rem; }
        
        /* Modal Styling */
        .modal-content { border: none; border-radius: 20px; overflow: hidden; }
        .modal-header { border: none; padding: 25px 30px; }
        .modal-body { padding: 30px; background: #f8f9fa; }
        
        /* Detail Card Sections */
        .detail-section { 
            background: white; 
            border-radius: 15px; 
            padding: 25px; 
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .detail-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 3px solid #e2ffbd;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-section-title .icon {
            font-size: 1.4rem;
        }
        
        .detail-item {
            margin-bottom: 18px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #a74fec;
        }
        .detail-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .detail-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }
        
        /* Grid Layout for Details */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        /* Tags/Badges for lists */
        .detail-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .detail-tag {
            background: linear-gradient(135deg, #a74fec 0%, #8b3fd1 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .detail-tag.secondary {
            background: linear-gradient(135deg, #6abaed 0%, #4a9ad5 100%);
        }
        .detail-tag.success {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        }
        
        /* Timing Schedule */
        .timing-schedule {
            margin-top: 10px;
        }
        .timing-day-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        .timing-day-name {
            font-weight: 700;
            color: #a74fec;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .timing-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .timing-slot {
            background: #e2ffbd;
            color: #333;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Location List */
        .location-list {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }
        .location-item {
            padding: 10px 15px;
            background: white;
            border-left: 4px solid #6abaed;
            border-radius: 8px;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .location-item:before {
            content: "📍 ";
            margin-right: 8px;
        }
        
        /* Pricing Cards */
        .pricing-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .pricing-card {
            background: linear-gradient(135deg, #a74fec 0%, #8b3fd1 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .pricing-label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .pricing-amount {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        /* Yes/No Indicators */
        .indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .indicator.yes {
            background: #d4edda;
            color: #155724;
        }
        .indicator.no {
            background: #f8d7da;
            color: #721c24;
        }
        .indicator:before {
            font-size: 1.2rem;
        }
        .indicator.yes:before {
            content: "✓";
        }
        .indicator.no:before {
            content: "✗";
        }
        
        /* Highlight Box */
        .highlight-box {
            background: linear-gradient(135deg, #e2ffbd 0%, #d4f5a6 100%);
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
            border: 2px solid #c8e68f;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .pricing-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                </div>
                <a href="admin-logout.php" class="btn btn-light">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center" style="background: #a74fec; color: white;">
                    <div class="card-body">
                        <h3><?php echo $pending_partners->num_rows; ?></h3>
                        <p class="mb-0">Pending Partners</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="background: #6abaed; color: white;">
                    <div class="card-body">
                        <h3><?php echo $approved_partners->num_rows; ?></h3>
                        <p class="mb-0">Approved Partners</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="background: #f85646; color: white;">
                    <div class="card-body">
                        <h3><?php echo $parents->num_rows; ?></h3>
                        <p class="mb-0">Registered Parents</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="background: #e2ffbd;">
                    <div class="card-body">
                        <h3><?php echo $contacts->num_rows; ?></h3>
                        <p class="mb-0">Contact Submissions</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-end mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inviteAdvisoryModal" style="background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); border: none; padding: 12px 30px; font-weight: 600;">
                <i class="bi bi-person-plus"></i> Invite Advisory
            </button>
        </div>
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">Pending Partners</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button">Approved Partners</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="partner-forms-tab" data-bs-toggle="tab" data-bs-target="#partner-forms" type="button">Partner Forms</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="parents-tab" data-bs-toggle="tab" data-bs-target="#parents" type="button">Parents</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="parent-forms-tab" data-bs-toggle="tab" data-bs-target="#parent-forms" type="button">Parent Forms</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button">Contact Forms</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="advisors-tab" data-bs-toggle="tab" data-bs-target="#advisors" type="button">Advisors</button>
            </li>
        </ul>
        
        <div class="tab-content" id="adminTabContent">
            <!-- Pending Partners -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Pending Partner Approvals</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Centre Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($partner = $pending_partners->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($partner['center_name']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['email']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($partner['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success approve-partner" data-id="<?php echo $partner['id']; ?>">Approve</button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($pending_partners->num_rows === 0): ?>
                                    <tr><td colspan="5" class="text-center">No pending partners</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approved Partners -->
            <div class="tab-pane fade" id="approved" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Approved Partners</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Centre Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Approved</th>
                                        <th>Form Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($partner = $approved_partners->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($partner['center_name']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['email']); ?></td>
                                        <td><?php echo htmlspecialchars($partner['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($partner['approved_at'])); ?></td>
                                        <td>
                                            <?php if ($partner['form_submitted']): ?>
                                                <span class="badge bg-success">Submitted</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-link" data-token="<?php echo $partner['unique_token']; ?>">View Link</button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($approved_partners->num_rows === 0): ?>
                                    <tr><td colspan="6" class="text-center">No approved partners</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Partner Forms -->
            <div class="tab-pane fade" id="partner-forms" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Partner Form Submissions</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Partner Name</th>
                                        <th>Centre Name</th>
                                        <th>Contact Person</th>
                                        <th>Activities</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($form = $partner_forms->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($form['partner_name']); ?></td>
                                        <td><?php echo htmlspecialchars($form['center_name']); ?></td>
                                        <td><?php echo htmlspecialchars($form['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($form['activities_offered'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($form['submitted_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary btn-view-details view-partner-form" 
                                                data-id="<?php echo $form['id']; ?>"
                                                data-center="<?php echo htmlspecialchars($form['center_name']); ?>"
                                                data-contact="<?php echo htmlspecialchars($form['contact_person']); ?>"
                                                data-email="<?php echo htmlspecialchars($form['contact_email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($form['contact_phone']); ?>"
                                                data-description="<?php echo htmlspecialchars($form['description']); ?>"
                                                data-activities="<?php echo htmlspecialchars($form['activities_offered']); ?>"
                                                data-ages="<?php echo htmlspecialchars($form['age_groups']); ?>"
                                                data-gender="<?php echo htmlspecialchars($form['gender']); ?>"
                                                data-days="<?php echo htmlspecialchars($form['class_days']); ?>"
                                                data-timings='<?php echo htmlspecialchars($form['class_timings']); ?>'
                                                data-location1="<?php echo htmlspecialchars($form['location1']); ?>"
                                                data-location2="<?php echo htmlspecialchars($form['location2']); ?>"
                                                data-location3="<?php echo htmlspecialchars($form['location3']); ?>"
                                                data-location4="<?php echo htmlspecialchars($form['location4']); ?>"
                                                data-website="<?php echo htmlspecialchars($form['website']); ?>"
                                                data-price-day="<?php echo htmlspecialchars($form['price_day']); ?>"
                                                data-price-week="<?php echo htmlspecialchars($form['price_week']); ?>"
                                                data-price-month="<?php echo htmlspecialchars($form['price_month']); ?>"
                                                data-trial="<?php echo htmlspecialchars($form['free_trial']); ?>"
                                                data-social="<?php echo htmlspecialchars($form['social_post']); ?>">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($partner_forms->num_rows === 0): ?>
                                    <tr><td colspan="6" class="text-center">No partner forms submitted</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Parents -->
            <div class="tab-pane fade" id="parents" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Registered Parents</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Activity Form</th>
                                        <!-- <th>Photo Consent</th> -->
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($parent = $parents->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($parent['name']); ?></td>
                                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                                        <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                                        <td>
                                            <?php if ($parent['has_activity'] > 0): ?>
                                                <span class="badge bg-success">✓</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">✗</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($parent['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Parent Forms -->
            <div class="tab-pane fade" id="parent-forms" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Parent Activity Form Submissions</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Parent Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Kids</th>
                                        <th>Activities</th>
                                        <th>Submitted</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($form = $parent_forms->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($form['parent_name']); ?></td>
                                        <td><?php echo htmlspecialchars($form['email']); ?></td>
                                        <td><?php echo htmlspecialchars($form['phone_number']); ?></td>
                                        <td><?php echo $form['number_of_kids']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($form['activities'], 0, 30)) . '...'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($form['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary btn-view-details view-parent-form" 
                                                data-id="<?php echo $form['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($form['parent_name']); ?>"
                                                data-phone="<?php echo htmlspecialchars($form['phone_number']); ?>"
                                                data-email="<?php echo htmlspecialchars($form['email']); ?>"
                                                data-contact="<?php echo htmlspecialchars($form['preferred_contact']); ?>"
                                                data-kids="<?php echo $form['number_of_kids']; ?>"
                                                data-activities="<?php echo htmlspecialchars($form['activities']); ?>"
                                                data-other="<?php echo htmlspecialchars($form['other_activity']); ?>"
                                                data-centers="<?php echo htmlspecialchars($form['preferred_centers']); ?>"
                                                data-class="<?php echo htmlspecialchars($form['class_preference']); ?>"
                                                data-language="<?php echo htmlspecialchars($form['language_preference']); ?>">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($parent_forms->num_rows === 0): ?>
                                    <tr><td colspan="7" class="text-center">No parent forms submitted</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Submissions -->
            <div class="tab-pane fade" id="contacts" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Recent Contact Form Submissions</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Comments</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($contact = $contacts->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                        <td><?php echo htmlspecialchars($contact['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($contact['comments'], 0, 50)) . '...'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($contact['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="advisors" role="tabpanel">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Advisory Board Members</h4>
            <div class="table-responsive">
                <table class="table table-hover" id="advisorsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>Expertise</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="advisorsTableBody">
                        <tr><td colspan="7" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <h5 class="mt-4">Pending Invitations</h5>
            <div class="table-responsive">
                <table class="table table-hover" id="invitationsTable">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Invited By</th>
                            <th>Invited At</th>
                            <th>Expires At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="invitationsTableBody">
                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
    
    <!-- Link Modal -->
    <div class="modal fade" id="linkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Partner Registration Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Share this link with the partner:</p>
                    <div class="input-group">
                        <input type="text" class="form-control" id="partnerLink" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">Copy</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Partner Form Details Modal -->
    <div class="modal fade" id="partnerFormModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #a74fec; color: white;">
                    <h5 class="modal-title">Partner Form Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div id="partnerFormContent"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Parent Form Details Modal -->
    <div class="modal fade" id="parentFormModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: #f85646; color: white;">
                    <h5 class="modal-title">Parent Form Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div id="parentFormContent"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="inviteAdvisoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white;">
                <h5 class="modal-title">Invite Advisory Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="inviteAdvisoryForm">
                    <div class="mb-3">
                        <label for="advisoryEmail" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="advisoryEmail" name="email" required 
                               placeholder="Enter advisor's email address">
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    <div class="alert alert-info">
                        <small>An invitation link will be sent to this email address. The link will expire in 7 days.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sendInviteBtn" 
                        style="background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); border: none;">
                    Send Invitation
                </button>
            </div>
        </div>
    </div>
</div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Approve partner
        document.querySelectorAll('.approve-partner').forEach(btn => {
            btn.addEventListener('click', function() {
                const partnerId = this.getAttribute('data-id');
                
                if (confirm('Approve this partner?')) {
                    fetch('admin-approve-partner.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'partner_id=' + partnerId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Partner approved! Link: ' + data.link);
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
                }
            });
        });
        
        // View partner link
        document.querySelectorAll('.view-link').forEach(btn => {
            btn.addEventListener('click', function() {
                const token = this.getAttribute('data-token');
                const link = '<?php echo SITE_URL; ?>/partner-setup.php?token=' + token;
                document.getElementById('partnerLink').value = link;
                new bootstrap.Modal(document.getElementById('linkModal')).show();
            });
        });
        
        function copyLink() {
            const input = document.getElementById('partnerLink');
            input.select();
            document.execCommand('copy');
            alert('Link copied to clipboard!');
        }
        
        // View partner form details
        document.querySelectorAll('.view-partner-form').forEach(btn => {
            btn.addEventListener('click', function() {
                const data = this.dataset;
                let timings = {};
                try {
                    timings = JSON.parse(data.timings);
                } catch(e) {}
                
                let timingsHtml = '<div class="timing-schedule">';
                for (const [day, times] of Object.entries(timings)) {
                    if (times && times.length > 0) {
                        timingsHtml += `
                            <div class="timing-day-item">
                                <div class="timing-day-name">${day}</div>
                                <div class="timing-slots">
                                    ${times.map(time => `<span class="timing-slot">${time}</span>`).join('')}
                                </div>
                            </div>
                        `;
                    }
                }
                timingsHtml += '</div>';
                
                const locations = [data.location1, data.location2, data.location3, data.location4].filter(loc => loc && loc.trim());
                const locationsHtml = locations.length > 0 ? 
                    '<ul class="location-list">' + locations.map(loc => `<li class="location-item">${loc}</li>`).join('') + '</ul>' : 
                    '<p class="text-muted">No locations specified</p>';
                
                const activities = data.activities.split(',').map(a => a.trim());
                const activitiesHtml = '<div class="detail-tags">' + 
                    activities.map(act => `<span class="detail-tag">${act}</span>`).join('') + 
                    '</div>';
                
                const ages = data.ages.split(',').map(a => a.trim());
                const agesHtml = '<div class="detail-tags">' + 
                    ages.map(age => `<span class="detail-tag secondary">${age}</span>`).join('') + 
                    '</div>';
                
                const days = data.days.split(',').map(d => d.trim());
                const daysHtml = '<div class="detail-tags">' + 
                    days.map(day => `<span class="detail-tag success">${day}</span>`).join('') + 
                    '</div>';
                
                const html = `
                    <!-- Basic Information -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">🏢</span>
                            <span>Basic Information</span>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Centre Name</div>
                                <div class="detail-value">${data.center}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Contact Person</div>
                                <div class="detail-value">${data.contact}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value">${data.email}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value">${data.phone}</div>
                            </div>
                        </div>
                        ${data.website ? `
                        <div class="detail-item" style="margin-top: 15px;">
                            <div class="detail-label">Website</div>
                            <div class="detail-value">
                                <a href="${data.website}" target="_blank" style="color: #a74fec; text-decoration: none; font-weight: 600;">
                                    🔗 ${data.website}
                                </a>
                            </div>
                        </div>` : ''}
                        <div class="highlight-box" style="margin-top: 20px;">
                            <div class="detail-label">Centre Description</div>
                            <div class="detail-value">${data.description}</div>
                        </div>
                    </div>

                    <!-- Activities & Demographics -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">🎯</span>
                            <span>Activities & Demographics</span>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Activities Offered</div>
                            ${activitiesHtml}
                        </div>
                        ${data.importance ? `
                        <div class="detail-item">
                            <div class="detail-label">Activity Importance / Special Features</div>
                            <div class="detail-value">${data.importance}</div>
                        </div>` : ''}
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Age Groups</div>
                                ${agesHtml}
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Gender</div>
                                <div class="detail-value">
                                    <span class="detail-tag secondary">${data.gender}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">📅</span>
                            <span>Class Schedule</span>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Class Days</div>
                            ${daysHtml}
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Class Timings by Day</div>
                            ${timingsHtml || '<p class="text-muted">No specific timings provided</p>'}
                        </div>
                    </div>

                    <!-- Locations -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">📍</span>
                            <span>Locations</span>
                        </div>
                        ${locationsHtml}
                    </div>

                    <!-- Pricing -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">💰</span>
                            <span>Pricing Structure</span>
                        </div>
                        <div class="pricing-cards">
                            <div class="pricing-card">
                                <div class="pricing-label">Per Month</div>
                                <div class="pricing-amount">${data.priceDay}</div>
                            </div>
                            <div class="pricing-card" style="background: linear-gradient(135deg, #6abaed 0%, #4a9ad5 100%);">
                                <div class="pricing-label">Per Term</div>
                                <div class="pricing-amount">${data.priceWeek}</div>
                            </div>
                            <div class="pricing-card" style="background: linear-gradient(135deg, #f85646 0%, #d9453a 100%);">
                                <div class="pricing-label">Per Year</div>
                                <div class="pricing-amount">${data.priceMonth}</div>
                            </div>
                        </div>
                        <div class="detail-item" style="margin-top: 20px;">
                            <div class="detail-label">Free Trial Available</div>
                            <div class="detail-value">
                                <span class="indicator ${data.trial === 'Yes' ? 'yes' : 'no'}">${data.trial}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">✅</span>
                            <span>Terms & Permissions</span>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Social Media Posting Permission</div>
                            <div class="detail-value">
                                <span class="indicator ${data.social === 'Allow' ? 'yes' : 'no'}">${data.social}</span>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('partnerFormContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('partnerFormModal')).show();
            });
        });
        
        // View parent form details
        document.querySelectorAll('.view-parent-form').forEach(btn => {
            btn.addEventListener('click', function() {
                const data = this.dataset;
                
                const activities = data.activities.split(',').map(a => a.trim());
                const activitiesHtml = '<div class="detail-tags">' + 
                    activities.map(act => `<span class="detail-tag">${act}</span>`).join('') + 
                    '</div>';
                
                const html = `
                    <!-- Parent Contact Information -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">👤</span>
                            <span>Parent Contact Information</span>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Parent Name</div>
                                <div class="detail-value">${data.name}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Phone Number</div>
                                <div class="detail-value">${data.phone}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email Address</div>
                                <div class="detail-value">${data.email}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Preferred Contact Method</div>
                                <div class="detail-value">
                                    <span class="detail-tag secondary">${data.contact}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Information -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">👨‍👩‍👧‍👦</span>
                            <span>Family Information</span>
                        </div>
                        <div class="highlight-box">
                            <div class="detail-label">Number of Kids</div>
                            <div class="detail-value" style="font-size: 2rem; font-weight: 700; color: #a74fec; text-align: center; margin-top: 10px;">
                                ${data.kids}
                            </div>
                        </div>
                    </div>

                    <!-- Activity Interests -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">🎨</span>
                            <span>Activity Interests</span>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Activities of Interest</div>
                            ${activitiesHtml}
                        </div>
                        ${data.other && data.other.trim() ? `
                        <div class="detail-item">
                            <div class="detail-label">Other Activities Specified</div>
                            <div class="detail-value">${data.other}</div>
                        </div>` : ''}
                        ${data.centers && data.centers.trim() ? `
                        <div class="detail-item">
                            <div class="detail-label">Preferred Centers</div>
                            <div class="detail-value">${data.centers}</div>
                        </div>` : ''}
                    </div>

                    <!-- Preferences -->
                    <div class="detail-section">
                        <div class="detail-section-title">
                            <span class="icon">⚙️</span>
                            <span>Class Preferences</span>
                        </div>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Class Type Preference</div>
                                <div class="detail-value">
                                    <span class="detail-tag secondary">${data.class}</span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Language Preference</div>
                                <div class="detail-value">
                                    <span class="detail-tag secondary">${data.language}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                `;
                
                document.getElementById('parentFormContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('parentFormModal')).show();
            });
        });
    </script>
    
<script>
// Send Advisory Invitation
document.getElementById('sendInviteBtn').addEventListener('click', function() {
    const form = document.getElementById('inviteAdvisoryForm');
    const email = document.getElementById('advisoryEmail').value;
    
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
    
    fetch('admin-invite-advisor.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Invitation sent successfully!\n\nInvitation link: ' + data.link);
            bootstrap.Modal.getInstance(document.getElementById('inviteAdvisoryModal')).hide();
            form.reset();
            form.classList.remove('was-validated');
            loadInvitations();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error sending invitation: ' + error);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Send Invitation';
    });
});

// Load Advisors
function loadAdvisors() {
    fetch('admin-get-advisors.php')
    .then(response => response.json())
    .then(advisors => {
        const tbody = document.getElementById('advisorsTableBody');
        if (advisors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No advisors registered yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = advisors.map(advisor => `
            <tr>
                <td>${advisor.name}</td>
                <td>${advisor.email}</td>
                <td>${advisor.phone}</td>
                <td>${advisor.city || 'N/A'}</td>
                <td>${advisor.expertise ? advisor.expertise.substring(0, 50) + '...' : 'N/A'}</td>
                <td>${new Date(advisor.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-sm btn-primary btn-view-details view-advisor-detail" 
                            data-id="${advisor.id}">
                        View Details
                    </button>
                </td>
            </tr>
        `).join('');
        
        // Add event listeners for view details
        document.querySelectorAll('.view-advisor-detail').forEach(btn => {
            btn.addEventListener('click', function() {
                viewAdvisorDetail(this.dataset.id);
            });
        });
    });
}

// Load Invitations
function loadInvitations() {
    fetch('admin-get-invitations.php')
    .then(response => response.json())
    .then(invitations => {
        const tbody = document.getElementById('invitationsTableBody');
        if (invitations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No pending invitations</td></tr>';
            return;
        }
        
        tbody.innerHTML = invitations.map(inv => {
            const status = inv.status === 'pending' ? 
                '<span class="badge bg-warning">Pending</span>' : 
                inv.status === 'completed' ? 
                '<span class="badge bg-success">Completed</span>' : 
                '<span class="badge bg-danger">Expired</span>';
            
            const inviteLink = '<?php echo SITE_URL; ?>/advisor-register.php?token=' + inv.unique_token;
            
            return `
                <tr>
                    <td>${inv.email}</td>
                    <td>${status}</td>
                    <td>${inv.invited_by_name}</td>
                    <td>${new Date(inv.invited_at).toLocaleDateString()}</td>
                    <td>${new Date(inv.expires_at).toLocaleDateString()}</td>
                    <td>
                        ${inv.status === 'pending' ? 
                            `<button class="btn btn-sm btn-info" onclick="copyInviteLink('${inviteLink}')">Copy Link</button>` : 
                            '-'}
                    </td>
                </tr>
            `;
        }).join('');
    });
}

function copyInviteLink(link) {
    navigator.clipboard.writeText(link).then(() => {
        alert('Invitation link copied to clipboard!');
    });
}

function viewAdvisorDetail(id) {
    fetch('admin-get-advisor-detail.php?id=' + id)
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        const html = `
            <div class="detail-section">
                <div class="detail-section-title">
                    <span class="icon">👤</span>
                    <span>Personal Information</span>
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value">${data.name}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${data.email}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value">${data.phone}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">City</div>
                        <div class="detail-value">${data.city || 'N/A'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Country</div>
                        <div class="detail-value">${data.country || 'N/A'}</div>
                    </div>
                </div>
                ${data.address ? `
                <div class="detail-item" style="margin-top: 15px;">
                    <div class="detail-label">Address</div>
                    <div class="detail-value">${data.address}</div>
                </div>` : ''}
            </div>

            <div class="detail-section">
                <div class="detail-section-title">
                    <span class="icon">🎯</span>
                    <span>Expertise</span>
                </div>
                <div class="highlight-box">
                    <div class="detail-value">${data.expertise || 'No expertise information provided'}</div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-title">
                    <span class="icon">✅</span>
                    <span>Agreements</span>
                </div>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">NDA Agreement</div>
                        <div class="detail-value">
                            <span class="indicator ${data.nda_agreed ? 'yes' : 'no'}">
                                ${data.nda_agreed ? 'Agreed' : 'Not Agreed'}
                            </span>
                        </div>
                        ${data.nda_agreed_at ? `<small class="text-muted">Agreed on: ${new Date(data.nda_agreed_at).toLocaleDateString()}</small>` : ''}
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Terms & Conditions</div>
                        <div class="detail-value">
                            <span class="indicator ${data.terms_agreed ? 'yes' : 'no'}">
                                ${data.terms_agreed ? 'Agreed' : 'Not Agreed'}
                            </span>
                        </div>
                        ${data.terms_agreed_at ? `<small class="text-muted">Agreed on: ${new Date(data.terms_agreed_at).toLocaleDateString()}</small>` : ''}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('advisorDetailContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('advisorDetailModal')).show();
    });
}

// Load advisors and invitations when Advisors tab is clicked
document.getElementById('advisors-tab').addEventListener('click', function() {
    loadAdvisors();
    loadInvitations();
});
</script>
</body>
</html>