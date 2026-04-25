<?php
/**
 * Save & Verify PAN from User Profile
 * Calls Meon PAN API to verify, then saves to user record.
 * POST: pan (required), dob (optional - used if user has no DOB on record)
 */
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$pan = strtoupper(trim($_POST['pan'] ?? ''));
$dob_input = trim($_POST['dob'] ?? '');

if (!$pan) {
    echo json_encode(['success' => false, 'message' => 'PAN number is required.']);
    exit;
}

if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
    echo json_encode(['success' => false, 'message' => 'Invalid PAN format. Expected: ABCDE1234F']);
    exit;
}

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT full_name, dob, pan_number, pan_verified FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found.');
    }

    if ($user['pan_verified'] == 1 && $user['pan_number'] === $pan) {
        echo json_encode(['success' => true, 'message' => 'PAN is already verified.']);
        exit;
    }

    // Check PAN uniqueness
    $dupStmt = $pdo->prepare("SELECT id FROM users WHERE pan_number = ? AND id != ?");
    $dupStmt->execute([$pan, $user_id]);
    if ($dupStmt->fetch()) {
        throw new Exception('This PAN is already registered to another account.');
    }

    // Use provided DOB or existing DOB
    $dob = $dob_input ?: ($user['dob'] ?? '');
    $name = $user['full_name'] ?? '';

    if (!$dob || !$name) {
        throw new Exception('Full name and date of birth are required for PAN verification. Please update your profile first.');
    }

    // Normalise DOB to DD/MM/YYYY for Meon API
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        [$y, $m, $d] = explode('-', $dob);
        $dob = "$d/$m/$y";
    } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dob)) {
        $dob = str_replace('-', '/', $dob);
    }

    // Call Meon PAN API
    $config = parse_ini_file(__DIR__ . '/../../config/config.ini', true);
    $apiUrl      = $config['pan_api']['url']          ?? 'https://panapi.meon.co.in/pan';
    $company     = $config['pan_api']['company']      ?? '';
    $secretToken = $config['pan_api']['secret_token'] ?? '';

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'pan'          => $pan,
            'dob'          => $dob,
            'name'         => $name,
            'company'      => $company,
            'secret_token' => $secretToken,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $raw === false) {
        throw new Exception('Could not reach PAN verification service. Please try again later.');
    }

    $apiResp = json_decode($raw, true);

    if (!$apiResp || ($apiResp['code'] ?? 0) !== 200 || ($apiResp['success'] ?? false) !== true) {
        $apiMsg = $apiResp['msg'] ?? $apiResp['message'] ?? 'PAN verification failed.';
        throw new Exception($apiMsg);
    }

    $data = $apiResp['data'][0] ?? [];

    if (($data['pan_status'] ?? '') !== 'E') {
        throw new Exception('PAN is not active. Please use a valid PAN.');
    }

    if (($data['name'] ?? '') !== 'Y') {
        throw new Exception('Name does not match PAN records. Please ensure your profile name matches your PAN card.');
    }

    // Save PAN and mark verified
    $updateSql = "UPDATE users SET pan_number = ?, pan_verified = 1";
    $updateParams = [$pan];

    // Also update DOB if user provided one and doesn't have it yet
    if ($dob_input && empty($user['dob'])) {
        $updateSql .= ", dob = ?";
        $updateParams[] = $dob_input;
    }

    $updateSql .= " WHERE id = ?";
    $updateParams[] = $user_id;

    $pdo->prepare($updateSql)->execute($updateParams);

    echo json_encode([
        'success' => true,
        'message' => 'PAN verified and saved successfully.',
        'pan'     => $pan,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
