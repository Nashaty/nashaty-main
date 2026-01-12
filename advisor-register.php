<?php
require_once 'config.php';

$token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : '';
$error = '';
$invitation = null;

if (empty($token)) {
    $error = 'Invalid invitation link.';
} else {
    $conn = getDBConnection();
    
    // Verify token and check expiration
    $stmt = $conn->prepare("SELECT id, email, status, expires_at FROM advisory_invitations WHERE unique_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = 'Invalid invitation link.';
    } else {
        $invitation = $result->fetch_assoc();
        
        if ($invitation['status'] === 'completed') {
            $error = 'This invitation has already been used.';
        } elseif ($invitation['status'] === 'expired' || strtotime($invitation['expires_at']) < time()) {
            $error = 'This invitation has expired.';
            // Update status to expired
            $updateStmt = $conn->prepare("UPDATE advisory_invitations SET status = 'expired' WHERE id = ?");
            $updateStmt->bind_param("i", $invitation['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Registration - Nashaty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); min-height: 100vh; padding: 40px 0; }
        .register-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 800px; margin: 0 auto; }
        .form-section { background: #f8f9fa; border-radius: 15px; padding: 25px; margin-bottom: 25px; border-left: 4px solid #a74fec; }
        .form-section-title { font-size: 1.2rem; font-weight: 700; color: #a74fec; margin-bottom: 20px; }
        .document-card { background: white; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; margin-bottom: 15px; transition: all 0.3s; cursor: pointer; }
        .document-card:hover { border-color: #a74fec; box-shadow: 0 4px 12px rgba(167, 79, 236, 0.2); }
        .document-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; }
        .consent-checkbox { transform: scale(1.3); margin-right: 10px; }
        .btn-submit { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); border: none; padding: 15px 40px; font-size: 1.1rem; font-weight: 600; }
        .btn-submit:hover { opacity: 0.9; }
        .error-card { background: #f8d7da; border: 2px solid #dc3545; border-radius: 15px; padding: 30px; text-align: center; color: #721c24; }
        .success-card { background: #d4edda; border: 2px solid #28a745; border-radius: 15px; padding: 30px; text-align: center; color: #155724; }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="text-center mb-4">
                <img src="./assets/images/logo.png" alt="Nashaty Logo" style="max-width: 150px;">
                <h2 class="mt-3" style="color: #a74fec;">Advisory Board Registration</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="error-card">
                    <h4>❌ Registration Unavailable</h4>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
            <?php else: ?>
                <form id="advisorForm" method="POST" action="advisor-submit.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <!-- Personal Information -->
                    <div class="form-section">
                        <div class="form-section-title">👤 Personal Information</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($invitation['email']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="phone" pattern="[0-9+\s\-()]+" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"></textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Country *</label>
                                <input type="text" class="form-control" name="country" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Professional Background -->
                    <div class="form-section">
                        <div class="form-section-title">💼 Professional Background</div>
                        <div class="mb-3">
                            <label class="form-label">Areas of Expertise *</label>
                            <textarea class="form-control" name="expertise" rows="4" placeholder="Please describe your relevant experience and areas of expertise..." required></textarea>
                        </div>
                    </div>
                    
                    <!-- Legal Documents -->
                    <div class="form-section">
                        <div class="form-section-title">📄 Legal Documents & Agreements</div>
                        <p class="text-muted mb-4">Please review the following documents carefully before proceeding:</p>
                        
                        <!-- NDA Document -->
                        <div class="document-card" onclick="openDocument('nda')">
                            <div class="d-flex align-items-center">
                                <div class="document-icon me-3">📋</div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Non-Disclosure Agreement (NDA)</h5>
                                    <p class="mb-0 text-muted small">Click to view the complete NDA document</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary">View</button>
                            </div>
                        </div>
                        
                        <!-- Terms & Conditions -->
                        <div class="document-card" onclick="openDocument('terms')">
                            <div class="d-flex align-items-center">
                                <div class="document-icon me-3">📜</div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">Terms & Conditions</h5>
                                    <p class="mb-0 text-muted small">Click to view the complete Terms & Conditions</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary">View</button>
                            </div>
                        </div>
                        
                        <!-- Consent Checkboxes -->
                        <div class="mt-4">
                            <div class="form-check mb-3 p-3 bg-white rounded">
                                <input class="form-check-input consent-checkbox" type="checkbox" id="ndaConsent" name="nda_consent" required>
                                <label class="form-check-label" for="ndaConsent">
                                    <strong>I have read and agree to the Non-Disclosure Agreement (NDA)</strong>
                                    <p class="mb-0 text-muted small mt-1">By checking this box, you acknowledge that you have read, understood, and agree to be legally bound by the NDA terms.</p>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3 p-3 bg-white rounded">
                                <input class="form-check-input consent-checkbox" type="checkbox" id="termsConsent" name="terms_consent" required>
                                <label class="form-check-label" for="termsConsent">
                                    <strong>I have read and agree to the Terms & Conditions</strong>
                                    <p class="mb-0 text-muted small mt-1">By checking this box, you acknowledge that you have read, understood, and agree to be legally bound by the Terms & Conditions.</p>
                                </label>
                            </div>
                            
                            <div class="alert alert-info">
                                <small><strong>Legal Notice:</strong> Checking these boxes constitutes a legal electronic signature under the Laws of the State of Qatar and carries the same weight as a handwritten signature.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-submit">Complete Registration</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Document Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white;">
                    <h5 class="modal-title" id="documentTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe id="documentFrame" style="width: 100%; height: 600px; border: none;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openDocument(type) {
            const modal = new bootstrap.Modal(document.getElementById('documentModal'));
            const title = document.getElementById('documentTitle');
            const frame = document.getElementById('documentFrame');
            
            if (type === 'nda') {
                title.textContent = 'Non-Disclosure Agreement (NDA)';
                frame.src = 'view-nda.php';
            } else {
                title.textContent = 'Terms & Conditions';
                frame.src = 'view-terms.php';
            }
            
            modal.show();
        }
        
        document.getElementById('advisorForm').addEventListener('submit', function(e) {
            const ndaConsent = document.getElementById('ndaConsent');
            const termsConsent = document.getElementById('termsConsent');
            
            if (!ndaConsent.checked || !termsConsent.checked) {
                e.preventDefault();
                alert('Please review and agree to both the NDA and Terms & Conditions before submitting.');
                return false;
            }
        });
    </script>
</body>
</html>