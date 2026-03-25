<?php
/**
 * PAN Validation Proxy
 * Calls the Meon PAN API server-side to keep credentials secure.
 * Expects POST: pan, dob, name
 */
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pan  = strtoupper(trim($_POST['pan'] ?? ''));
$dob  = trim($_POST['dob'] ?? '');   // format: DD-MM-YYYY or YYYY-MM-DD
$name = trim($_POST['name'] ?? '');

if (!$pan || !$dob || !$name) {
    echo json_encode(['success' => false, 'message' => 'PAN, date of birth and full name are required.']);
    exit;
}

// Basic PAN format check: 5 letters, 4 digits, 1 letter
if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
    echo json_encode(['success' => false, 'message' => 'Invalid PAN format.']);
    exit;
}

// Normalise DOB to DD/MM/YYYY which the API requires
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    // HTML date input returns YYYY-MM-DD — convert
    [$y, $m, $d] = explode('-', $dob);
    $dob = "$d/$m/$y";
} elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dob)) {
    // DD-MM-YYYY — just swap separator
    $dob = str_replace('-', '/', $dob);
}

$config = parse_ini_file(__DIR__ . '/../../config/config.ini', true);
$apiUrl      = $config['pan_api']['url']          ?? 'https://panapi.meon.co.in/pan';
$company     = $config['pan_api']['company']      ?? '';
$secretToken = $config['pan_api']['secret_token'] ?? '';

$payload = json_encode([
    'pan'          => $pan,
    'dob'          => $dob,
    'name'         => $name,
    'company'      => $company,
    'secret_token' => $secretToken,
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,   // allow self-signed certs on local/staging
    CURLOPT_SSL_VERIFYHOST => 0,
]);
$raw      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $raw === false) {
    echo json_encode(['success' => false, 'message' => 'Could not reach PAN verification service: ' . $curlErr]);
    exit;
}

$apiResp = json_decode($raw, true);

if (!$apiResp || ($apiResp['code'] ?? 0) !== 200 || ($apiResp['success'] ?? false) !== true) {
    // Surface the API's own message if present, otherwise show raw response for debugging
    $apiMsg = $apiResp['msg'] ?? $apiResp['message'] ?? $apiResp['error'] ?? null;
    $errMsg = $apiMsg
        ? 'PAN API: ' . $apiMsg
        : 'PAN verification failed (HTTP ' . $httpCode . '). Response: ' . substr($raw, 0, 300);
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

$data = $apiResp['data'][0] ?? [];
$panStatus = $data['pan_status'] ?? '';
$nameMatch = $data['name']       ?? '';

if ($panStatus !== 'E') {
    echo json_encode(['success' => false, 'message' => 'PAN is not active (status: ' . htmlspecialchars($panStatus) . '). Please use a valid PAN.']);
    exit;
}

if ($nameMatch !== 'Y') {
    echo json_encode(['success' => false, 'message' => 'Name does not match PAN records. Please enter your name exactly as on your PAN card.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'PAN verified successfully.',
    'pan'     => $data['pan'],
    'name_matched' => true,
    'dob_matched'  => ($data['dob'] ?? '') === 'Y',
]);
