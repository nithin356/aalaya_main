<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email.']);
    exit;
}

$pdo = getDB();

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_deleted = 0 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this email.']);
        exit;
    }

    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Store OTP in system_config or a dedicated table? 
    // Let's create a temporary session-based or database-based storage.
    // For simplicity, we'll add a column to users or just use the session if it's the same browser.
    // Better to use database for reliability. 
    // Let's check if we can add columns to users table.
    
    // Check if columns exist
    $checkSql = "SHOW COLUMNS FROM users LIKE 'reset_otp'";
    $checkStmt = $pdo->query($checkSql);
    if (!$checkStmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_otp VARCHAR(6) NULL, ADD COLUMN otp_expiry DATETIME NULL");
    }

    // Store OTP in database
    $updateStmt = $pdo->prepare("UPDATE users SET reset_otp = ?, otp_expiry = ? WHERE id = ?");
    $updateStmt->execute([$otp, $expiry, $user['id']]);

    // Get Email Config & Send using Mailer class
    require_once '../../includes/Mailer.php';
    $config = parse_ini_file(CONFIG_FILE, true);
    $mailer = new Mailer($config);

    $subject = "Your Aalaya Verification Code";
    $message = "Hello " . $user['full_name'] . ",\n\n" .
               "You requested a password reset for your Aalaya account. Please use the following One-Time Password (OTP) to proceed:\n\n" .
               "Verification Code: " . $otp . "\n\n" .
               "This code is valid for 15 minutes. For security, do not share this code with anyone.\n\n" .
               "If you did not request this reset, please ignore this email or contact support at support@aalaya.info.\n\n" .
               "Best Regards,\n" .
               "Team Aalaya";

    if ($mailer->send($email, $subject, $message)) {
        echo json_encode(['success' => true, 'message' => 'OTP has been sent to your email.']);
    } else {
        error_log("Email delivery failed to $email for OTP $otp");
        echo json_encode(['success' => false, 'message' => 'Email delivery failed. Please check with administrator.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
