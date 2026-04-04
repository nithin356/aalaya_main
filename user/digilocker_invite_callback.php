<?php
/**
 * DigiLocker Invite Registration - Callback
 * DigiLocker redirects here after user completes authentication.
 * Retrieves Aadhaar + PAN data from Meon API and stores in session,
 * then redirects back to invite_register.php for Step 2.
 */
session_start();

$client_token = $_SESSION['digi_client_token'] ?? '';
$state        = $_SESSION['digi_state']        ?? '';
$invite_ref   = $_SESSION['digi_invite_ref']   ?? '';

// Build redirect base for errors
$back = 'invite_register.php' . ($invite_ref ? '?ref=' . urlencode($invite_ref) : '');

if (!$client_token || !$state) {
    header("Location: $back" . (strpos($back, '?') !== false ? '&' : '?') . "digi_error=session");
    exit;
}

$config  = parse_ini_file(__DIR__ . '/../config/config.ini', true);
$base    = rtrim($config['digilocker_meon']['base_url'] ?? 'https://digilocker.meon.co.in', '/');

// ---- Retrieve data from Meon ----
$ch = curl_init("$base/v2/send_entire_data");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'client_token' => $client_token,
        'state'        => $state,
        'status'       => true,
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$raw = curl_exec($ch);
curl_close($ch);

$resp = $raw ? json_decode($raw, true) : null;

if (!$resp || ($resp['code'] ?? 0) !== 200 || !($resp['success'] ?? false)) {
    $err = urlencode($resp['msg'] ?? 'Failed to retrieve DigiLocker data.');
    header("Location: $back" . (strpos($back, '?') !== false ? '&' : '?') . "digi_error=" . urlencode($err));
    exit;
}

$d = $resp['data'] ?? [];

// Store verified identity in session — used by invite_register_submit.php
$_SESSION['digi_verified'] = [
    'name'        => trim($d['name']           ?? ''),
    'pan_number'  => strtoupper(trim($d['pan_number']  ?? '')),
    'aadhar_no'   => trim($d['aadhar_no']      ?? ''),
    'dob'         => trim($d['dob']            ?? ''),
    'gender'      => trim($d['gender']         ?? ''),
    'address'     => trim($d['aadhar_address'] ?? ''),
    'photo'       => trim($d['adharimg']       ?? ''),
    'fathername'  => trim($d['fathername']     ?? ''),
];

// Clean up DigiLocker session keys (no longer needed)
unset($_SESSION['digi_client_token'], $_SESSION['digi_state']);

// Redirect back to registration page — Step 2 will detect digi_verified in session
$sep = strpos($back, '?') !== false ? '&' : '?';
header("Location: {$back}{$sep}digi=done");
exit;
