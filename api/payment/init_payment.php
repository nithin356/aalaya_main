<?php
header('Content-Type: application/json');
session_start();
require_once '../../includes/db.php';
require_once '../services/CashfreeService.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$invoice_id = $_POST['invoice_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Validate Invoice
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ? AND status='pending'");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    echo json_encode(['success' => false, 'message' => 'Invalid or already paid invoice.']);
    exit;
}

// Get User (Phone check)
$stmt = $pdo->prepare("SELECT phone, full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$phone = $user['phone'] ?? '9999999999'; // Fallback if missing

// Generate Order
$orderId = 'ORD_' . $invoice_id . '_' . time();
$amount = floatval($invoice['amount']);

// Use PROTOCOL detection or hardcode for dev
$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['PHP_SELF'])));
$returnUrl = $baseUrl . '/user/payment_callback.php?order_id={order_id}&invoice_id=' . $invoice_id;

$service = new CashfreeService();
$result = $service->createOrder($orderId, $amount, 'CUST_' . $user_id, $phone, $returnUrl);

if (isset($result['payment_session_id'])) {
    echo json_encode(['success' => true, 'payment_session_id' => $result['payment_session_id']]);
} elseif (isset($result['payment_link'])) {
    // Fallback if older API used
    echo json_encode(['success' => true, 'payment_link' => $result['payment_link']]);
} else {
    // Debugging: Return full result to see validation errors or auth errors
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to create order.', 'debug' => $result]);
}
?>
