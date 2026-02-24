<?php
/**
 * Cashfree Webhook Handler
 * 
 * This endpoint receives server-to-server payment notifications from Cashfree.
 * Unlike the browser redirect callback, this is reliable — it doesn't depend on
 * the user's browser staying open.
 * 
 * Configure this URL in your Cashfree Dashboard:
 *   Settings → Webhooks → Add Endpoint → https://aalaya.info/api/payment/cashfree_webhook.php
 * 
 * No session required — uses signature verification instead.
 */

// No session needed for webhooks
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../services/CashfreeService.php';

// Log all webhook calls for debugging
$logFile = __DIR__ . '/../../logs/cashfree_webhook.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function webhookLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Read raw POST body
$rawBody = file_get_contents('php://input');
webhookLog("Webhook received. Body length: " . strlen($rawBody));

if (empty($rawBody)) {
    webhookLog("ERROR: Empty request body");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty body']);
    exit;
}

$payload = json_decode($rawBody, true);
if (!$payload) {
    webhookLog("ERROR: Invalid JSON: " . substr($rawBody, 0, 500));
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

webhookLog("Payload: " . json_encode($payload));

// Verify webhook signature (Cashfree sends x-webhook-signature header)
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$timestamp_header = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';

// Load config to get secret
$configPath = __DIR__ . '/../../config/config.prod.ini';
if (file_exists(__DIR__ . '/../../config/config.ini')) {
    $configPath = __DIR__ . '/../../config/config.ini';
}
$config = file_exists($configPath) ? parse_ini_file($configPath, true) : [];
$webhookSecret = $config['cashfree']['webhook_secret'] ?? '';

// Note: If webhook_secret is not configured, we still process but log a warning
if (empty($webhookSecret)) {
    webhookLog("WARNING: webhook_secret not configured in config. Skipping signature verification.");
} elseif (!empty($signature)) {
    // Cashfree webhook signature verification
    $computedSignature = base64_encode(hash_hmac('sha256', $timestamp_header . $rawBody, $webhookSecret, true));
    if ($computedSignature !== $signature) {
        webhookLog("ERROR: Signature mismatch. Expected: $computedSignature, Got: $signature");
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
    webhookLog("Signature verified OK");
}

// Extract payment data from webhook payload
// Cashfree webhook format: { "data": { "order": { ... }, "payment": { ... } }, "event_time": "...", "type": "PAYMENT_SUCCESS_WEBHOOK" }
$eventType = $payload['type'] ?? '';
$orderData = $payload['data']['order'] ?? [];
$paymentData = $payload['data']['payment'] ?? [];

$orderId = $orderData['order_id'] ?? '';
$orderStatus = strtoupper($orderData['order_status'] ?? '');
$orderAmount = $orderData['order_amount'] ?? 0;
$paymentMethod = $paymentData['payment_group'] ?? 'cashfree';
$bankReference = $paymentData['bank_reference'] ?? '';
$cfPaymentId = $paymentData['cf_payment_id'] ?? '';

webhookLog("Event: $eventType | Order: $orderId | Status: $orderStatus | Amount: $orderAmount | BankRef: $bankReference");

if (empty($orderId)) {
    webhookLog("ERROR: No order_id in webhook payload");
    http_response_code(200); // Still return 200 to prevent Cashfree retries
    echo json_encode(['status' => 'ok', 'message' => 'No order_id']);
    exit;
}

// Parse invoice_id from order_id format: ORD_{invoice_id}_{timestamp}
$parts = explode('_', $orderId);
$invoiceId = intval($parts[1] ?? 0);

if (!$invoiceId) {
    webhookLog("ERROR: Could not parse invoice_id from order_id: $orderId");
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Unparseable order_id']);
    exit;
}

$pdo = getDB();

try {
    // Get the invoice
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        webhookLog("ERROR: Invoice #$invoiceId not found in database");
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Invoice not found']);
        exit;
    }

    // Only process if payment is confirmed
    if ($orderStatus === 'PAID' || $eventType === 'PAYMENT_SUCCESS_WEBHOOK') {
        $currentStatus = $invoice['status'];

        // Already paid? Skip
        if ($currentStatus === 'paid') {
            webhookLog("Invoice #$invoiceId already paid. Skipping.");
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'message' => 'Already paid']);
            exit;
        }

        // Already pending_verification with the same order? Skip
        if ($currentStatus === 'pending_verification' && $invoice['payment_id'] === $orderId) {
            webhookLog("Invoice #$invoiceId already pending_verification with same order. Skipping.");
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'message' => 'Already pending_verification']);
            exit;
        }

        $pdo->beginTransaction();

        // Update invoice to pending_verification
        $utrValue = $bankReference ?: $orderId;
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'pending_verification', 
                payment_id = ?,
                payment_method = 'cashfree',
                manual_utr_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId, $utrValue, $invoiceId]);

        // Log to audit trail
        $stmt = $pdo->prepare("
            INSERT INTO invoice_audit_log 
            (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, manual_utr_id, amount, extra_data)
            VALUES (?, ?, 'cashfree_webhook', 'webhook_confirmed', ?, 'pending_verification', ?, ?, 'cashfree', ?, ?, ?)
        ");
        $stmt->execute([
            $invoiceId,
            $invoice['user_id'],
            $currentStatus,
            "Payment confirmed via Cashfree webhook. Event: $eventType. Bank Ref: $bankReference",
            $orderId,
            $utrValue,
            $orderAmount,
            json_encode([
                'event_type' => $eventType,
                'cf_payment_id' => $cfPaymentId,
                'bank_reference' => $bankReference,
                'payment_group' => $paymentMethod,
                'order_data' => $orderData,
                'payment_data' => $paymentData
            ])
        ]);

        $pdo->commit();
        webhookLog("SUCCESS: Invoice #$invoiceId updated to pending_verification (was: $currentStatus)");
    } else {
        webhookLog("Non-PAID event for Invoice #$invoiceId. Status: $orderStatus. No action taken.");
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    webhookLog("ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
