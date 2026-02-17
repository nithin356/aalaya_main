<?php
/**
 * DigiLocker OAuth 2.0 Integration Handler
 */
session_start();
require_once '../includes/db.php';
$config = parse_ini_file(CONFIG_FILE, true);

// Get Base URL from config
$base_url = rtrim($config['paths']['base_url'] ?? 'http://localhost/aalaya_main/', '/');

// Config Simulation
$client_id     = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$redirect_uri  = $base_url . '/api/digilocker.php';
$auth_url      = 'https://digitallocker.gov.in/public/oauth2/1/authorize';
$token_url     = 'https://digitallocker.gov.in/public/oauth2/1/token';

$action = $_GET['action'] ?? '';

// --- STAGE 1: Initiation ---
if ($action === 'auth') {
    $state = bin2hex(random_bytes(16));
    $_SESSION['digilocker_state'] = $state;

    $params = [
        'response_type' => 'code',
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'state'         => $state
    ];

    // IN DEVELOPMENT: We will simulate a successful redirect if keys aren't provided
    if ($client_id === 'YOUR_CLIENT_ID') {
        // Use a relative redirect for internal mock handler to stay on the same domain
        header("Location: ?code=MOCK_AUTH_CODE&state=$state");
        exit;
    }

    header('Location: ' . $auth_url . '?' . http_build_query($params));
    exit;
}

// --- STAGE 2: Callback Handler ---
if (isset($_GET['code'])) {
    $code  = $_GET['code'];
    $state = $_GET['state'] ?? '';

    // Verify state to prevent CSRF
    if ($state !== ($_SESSION['digilocker_state'] ?? '')) {
        die("Invalid State. CSRF detected.");
    }

    // Handle Mock Authentication for Development
    if ($code === 'MOCK_AUTH_CODE') {
        $_SESSION['user_data'] = [
            'digilocker_id' => 'DL' . rand(10000, 99999),
            'email' => 'john.doe@example.com',
            'full_name' => 'John Doe',
            'phone' => '9876543210'
        ];
        header("Location: ../user/auth_callback.php");
        exit;
    }

    // Real API implementation would follow here...
    // 1. Exchange code for Token
    // 2. Fetch User Profiles
    // 3. Redirect to registration logic
}
?>
