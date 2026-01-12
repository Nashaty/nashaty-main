<?php
// Add this at the very top of test-sendgrid.php to see detailed errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// CHANGE THIS TO YOUR ACTUAL EMAIL
$testEmail = 'mystzex@gmail.com'; // ← CHANGE THIS!

echo "<!DOCTYPE html><html><head><style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.success { color: green; padding: 20px; background: #d4edda; border-radius: 10px; margin: 20px 0; }
.error { color: red; padding: 20px; background: #f8d7da; border-radius: 10px; margin: 20px 0; }
.info { padding: 20px; background: #d1ecf1; border-radius: 10px; margin: 20px 0; }
pre { background: #2d2d2d; color: #f8f8f2; padding: 20px; border-radius: 10px; overflow-x: auto; }
code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔍 SendGrid Email Test & Debug</h1>";
echo "<hr>";

// Configuration Check
echo "<div class='info'>";
echo "<h2>📋 Configuration Check:</h2>";
echo "<strong>API Key Set:</strong> " . (defined('SENDGRID_API_KEY') && !empty(SENDGRID_API_KEY) ? '✓ Yes' : '✗ No') . "<br>";
echo "<strong>API Key Length:</strong> " . strlen(SENDGRID_API_KEY) . " characters<br>";
echo "<strong>API Key Starts With:</strong> " . substr(SENDGRID_API_KEY, 0, 8) . "...<br>";
echo "<strong>From Email:</strong> " . SENDGRID_FROM_EMAIL . "<br>";
echo "<strong>From Name:</strong> " . SENDGRID_FROM_NAME . "<br>";
echo "<strong>Test Recipient:</strong> " . $testEmail . "<br>";
echo "</div>";

// Validate recipient email
if ($testEmail === 'your-email@example.com') {
    echo "<div class='error'>";
    echo "<h2>⚠️ IMPORTANT: Update Test Email!</h2>";
    echo "Please edit <code>test-sendgrid.php</code> and change:<br>";
    echo "<code>\$testEmail = 'your-email@example.com';</code><br>";
    echo "to your actual email address, like:<br>";
    echo "<code>\$testEmail = 'youremail@gmail.com';</code>";
    echo "</div>";
    exit;
}

// Check API Key format
if (empty(SENDGRID_API_KEY) || strlen(SENDGRID_API_KEY) < 20) {
    echo "<div class='error'>";
    echo "<h2>✗ Invalid API Key!</h2>";
    echo "<p>Your SendGrid API Key appears to be invalid or not set.</p>";
    echo "<ol>";
    echo "<li>Go to <a href='https://app.sendgrid.com/settings/api_keys' target='_blank'>SendGrid API Keys</a></li>";
    echo "<li>Click <strong>Create API Key</strong></li>";
    echo "<li>Choose <strong>Full Access</strong></li>";
    echo "<li>Copy the API key</li>";
    echo "<li>Update in <code>config.php</code>: <code>define('SENDGRID_API_KEY', 'YOUR_NEW_KEY');</code></li>";
    echo "</ol>";
    echo "</div>";
    exit;
}

// Test email content
$subject = "Nashaty - SendGrid Test Email [" . date('H:i:s') . "]";
$htmlContent = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white; padding: 30px; text-align: center; border-radius: 10px; }
        .content { padding: 30px; background: #f9f9f9; border-radius: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ SendGrid Test Successful!</h1>
        </div>
        <div class="content">
            <p><strong>Congratulations!</strong></p>
            <p>Your SendGrid configuration is working correctly.</p>
            <p><strong>Test Details:</strong></p>
            <ul>
                <li>From: ' . SENDGRID_FROM_EMAIL . '</li>
                <li>Sent at: ' . date('Y-m-d H:i:s') . '</li>
                <li>System: Nashaty Platform</li>
            </ul>
            <p>You can now send Advisory Board invitations!</p>
        </div>
    </div>
</body>
</html>
';

$textContent = "SendGrid Test Email - Configuration working! Sent at: " . date('Y-m-d H:i:s');

echo "<h2>📧 Sending Test Email...</h2>";
echo "<p>Attempting to send to: <strong>$testEmail</strong></p>";

// Send the email
$result = sendEmail($testEmail, $subject, $htmlContent, $textContent);

if ($result) {
    echo "<div class='success'>";
    echo "<h2>✓ SUCCESS!</h2>";
    echo "<p>Email sent successfully to: <strong>$testEmail</strong></p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Your inbox</li>";
    echo "<li>Spam/Junk folder</li>";
    echo "<li><a href='https://app.sendgrid.com/email_activity' target='_blank'>SendGrid Email Activity</a></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h2>✗ EMAIL SEND FAILED</h2>";
    echo "<p>The email could not be sent. Check the error details below.</p>";
    echo "</div>";
    
    // Show debug log
    $debugFile = __DIR__ . '/sendgrid_debug.log';
    if (file_exists($debugFile)) {
        echo "<h3>📝 Error Log Details:</h3>";
        $logContent = file_get_contents($debugFile);
        echo "<pre>" . htmlspecialchars($logContent) . "</pre>";
        
        // Parse common errors
        if (strpos($logContent, '401') !== false) {
            echo "<div class='error'>";
            echo "<h3>🔑 Error 401: Unauthorized - Invalid API Key</h3>";
            echo "<p><strong>Solution:</strong></p>";
            echo "<ol>";
            echo "<li>Your API key is invalid or expired</li>";
            echo "<li>Create a new API key at: <a href='https://app.sendgrid.com/settings/api_keys' target='_blank'>SendGrid API Keys</a></li>";
            echo "<li>Make sure to select <strong>Full Access</strong></li>";
            echo "<li>Update <code>config.php</code> with the new key</li>";
            echo "</ol>";
            echo "</div>";
        }
        
        if (strpos($logContent, '403') !== false) {
            echo "<div class='error'>";
            echo "<h3>🚫 Error 403: Forbidden - Sender Not Verified</h3>";
            echo "<p><strong>Solution:</strong></p>";
            echo "<ol>";
            echo "<li>Go to: <a href='https://app.sendgrid.com/settings/sender_auth' target='_blank'>Sender Authentication</a></li>";
            echo "<li>Click <strong>Verify a Single Sender</strong></li>";
            echo "<li>Add and verify: <code>" . SENDGRID_FROM_EMAIL . "</code></li>";
            echo "<li><strong>OR</strong> Change sender email in config.php to one you own and can verify</li>";
            echo "</ol>";
            echo "</div>";
        }
        
        if (strpos($logContent, 'does not contain a valid address') !== false) {
            echo "<div class='error'>";
            echo "<h3>📧 Invalid Email Address</h3>";
            echo "<p>The sender email address format is invalid.</p>";
            echo "<p><strong>Current:</strong> <code>" . SENDGRID_FROM_EMAIL . "</code></p>";
            echo "<p>Update in config.php to a valid email you can verify.</p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>No debug log file found. Check PHP error logs or enable error logging.</p>";
    }
}

echo "<hr>";
echo "<h2>🔧 Troubleshooting Steps:</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>Verify API Key:</strong> <a href='https://app.sendgrid.com/settings/api_keys' target='_blank'>Check/Create API Key</a></li>";
echo "<li><strong>Verify Sender:</strong> <a href='https://app.sendgrid.com/settings/sender_auth' target='_blank'>Verify Email Address</a></li>";
echo "<li><strong>Check Activity:</strong> <a href='https://app.sendgrid.com/email_activity' target='_blank'>View Email Activity</a></li>";
echo "<li><strong>Account Status:</strong> Make sure your SendGrid account is active (check for emails from SendGrid)</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='admin-dashboard.php' style='display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #a74fec 0%, #6abaed 100%); color: white; text-decoration: none; border-radius: 5px;'>← Back to Admin Dashboard</a></p>";

echo "</body></html>";
?>