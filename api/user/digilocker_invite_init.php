<?php
/**
 * DigiLocker Invite Registration - Initialization
 * Uses Meon DigiLocker API (not Cashfree).
 *
 * POST: invite_ref (encrypted invite token, saved to session for callback)
 * Returns JSON: { success, url }
 */
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$config    = parse_ini_file(__DIR__ . '/../../config/config.ini', true);
$company   = $config['digilocker_meon']['company_name'] ?? '';
$secret    = $config['digilocker_meon']['secret_token']  ?? '';
$base      = rtrim($config['digilocker_meon']['base_url'] ?? 'https://digilocker.meon.co.in', '/');
$site_base = rtrim($config['paths']['base_url'] ?? '', '/');

// Preserve invite ref across the DigiLocker redirect
$invite_ref = trim($_POST['invite_ref'] ?? '');
if ($invite_ref) {
    $_SESSION['digi_invite_ref'] = $invite_ref;
}

// ---- Step 1: Get Meon access token ----
$tokenResp = meonPost("$base/get_access_token", [
    'company_name' => $company,
    'secret_token' => $secret,
]);

if (!$tokenResp || !($tokenResp['status'] ?? false)) {
    echo json_encode(['success' => false, 'message' => $tokenResp['msg'] ?? 'Could not reach DigiLocker service. Please try again.']);
    exit;
}

$client_token = $tokenResp['client_token'];
$state        = $tokenResp['state'];

// ---- Step 2: Get DigiLocker URL ----
$callback_url = $site_base . '/user/digilocker_invite_callback.php';

$digiResp = meonPost("$base/digi_url", [
    'client_token' => $client_token,
    'redirect_url' => $callback_url,
    'company_name' => $company,
    'documents'    => 'aadhaar,pan',
]);

if (!$digiResp || ($digiResp['code'] ?? 0) !== 200 || !($digiResp['success'] ?? false)) {
    echo json_encode(['success' => false, 'message' => $digiResp['msg'] ?? 'Failed to generate DigiLocker link. Please try again.']);
    exit;
}

// Store in session — callback will use these to fetch data
$_SESSION['digi_client_token'] = $client_token;
$_SESSION['digi_state']        = $state;
unset($_SESSION['digi_verified']); // clear stale data

echo json_encode(['success' => true, 'url' => $digiResp['url']]);

function meonPost(string $url, array $data): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    return $raw ? json_decode($raw, true) : null;
}
