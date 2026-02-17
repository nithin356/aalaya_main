<?php
// Suppress all error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// buffer output
ob_start();

header('Content-Type: application/json');
require_once '../../includes/session.php';

try {
    require_once '../../includes/db.php';
    require_once '../services/CashfreeService.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed.');
    }

    // Priority: POST -> Session (Retry)
    $referral_code = $_POST['referral_code'] ?? $_SESSION['temp_referral_code'] ?? '';
    // For login, phone is passed from the form
    $phone = $_POST['phone'] ?? $_SESSION['temp_phone'] ?? '';

    // Refresh Session
    $_SESSION['temp_referral_code'] = $referral_code;
    $_SESSION['temp_phone'] = $phone;

    // Load Config Logic
    $configFile = '../../config/config.prod.ini';
    if (file_exists('../../config/config.ini')) {
        $configFile = '../../config/config.ini';
    }
    
    // Explicit check for config file
    if (!file_exists($configFile)) {
        throw new Exception('Configuration file not found.');
    }
    
    $config = parse_ini_file($configFile, true);
    if (!$config) {
        throw new Exception('Failed to parse configuration file.');
    }

    // Fallback detection
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']); // /aalaya_main/api/user
    $base_url = $protocol . "://" . $host . str_replace('/api/user', '', $path);
    
    // Explicit hardcode for dev if needed, else dynamic
    $redirectUrl = $base_url . '/user/digilocker_callback.php';

    // Generate Verification ID
    $verificationId = 'DL_KYC_' . uniqid() . '_' . rand(1000,9999);
    $_SESSION['dl_verification_id'] = $verificationId;

    $service = new CashfreeService();
    $result = $service->createDigilockerUrl($verificationId, $redirectUrl);

    // clear buffer
    ob_end_clean();

    if (isset($result['url'])) {
        echo json_encode(['success' => true, 'url' => $result['url']]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to generate Digilocker link.']);
    }

} catch (Exception $e) {
    // clear buffer
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
