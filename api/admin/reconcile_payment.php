<?php
/**
 * Admin API: Reconcile a Cashfree payment
 * Checks Cashfree for the actual payment status of a pending invoice
 * and updates the database accordingly.
 * 
 * POST: { invoice_id: int }
 * - Looks up the invoice, finds its payment_id (ORD_xxx)
 * - Calls Cashfree API to get real status
 * - If PAID: updates to pending_verification + logs audit
 * - Returns the Cashfree status either way
 */
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';
require_once '../services/CashfreeService.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();
$invoice_id = intval($_POST['invoice_id'] ?? 0);

if (!$invoice_id) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required.']);
    exit;
}

try {
    // 1. Get the invoice
    $stmt = $pdo->prepare("SELECT i.*, u.full_name FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        exit;
    }

    // If already paid, no need to reconcile
    if ($invoice['status'] === 'paid') {
        echo json_encode(['success' => false, 'message' => 'Invoice is already paid.']);
        exit;
    }

    $payment_id = $invoice['payment_id'] ?? '';

    // If no payment_id stored, we can't look it up — unless the admin provides it
    // For Cashfree orders, the format is ORD_{invoice_id}_{timestamp}
    // We can try to find it by querying with the pattern
    if (empty($payment_id)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No Cashfree order ID found for this invoice. The user may not have initiated payment.',
            'invoice' => [
                'id' => $invoice['id'],
                'status' => $invoice['status'],
                'payment_method' => $invoice['payment_method'],
                'user' => $invoice['full_name']
            ]
        ]);
        exit;
    }

    // 2. Check Cashfree
    $service = new CashfreeService();
    $orderResult = $service->getOrderStatus($payment_id);

    $cfStatus = strtoupper($orderResult['order_status'] ?? '');
    $cfAmount = $orderResult['order_amount'] ?? 0;

    $response = [
        'success' => true,
        'invoice_id' => $invoice_id,
        'invoice_status' => $invoice['status'],
        'cashfree_order_id' => $payment_id,
        'cashfree_status' => $cfStatus,
        'cashfree_amount' => $cfAmount,
        'user' => $invoice['full_name'],
        'action_taken' => 'none'
    ];

    // 3. If Cashfree says PAID but our DB doesn't reflect it
    if ($cfStatus === 'PAID' && $invoice['status'] !== 'pending_verification') {
        $pdo->beginTransaction();

        $oldStatus = $invoice['status'];
        
        // Update invoice to pending_verification
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET status = 'pending_verification', 
                payment_id = ?, 
                payment_method = 'cashfree', 
                manual_utr_id = ?,
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$payment_id, $payment_id, $invoice_id]);

        // Log to audit
        $adminName = $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'admin';
        $stmt = $pdo->prepare("
            INSERT INTO invoice_audit_log 
            (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, amount, extra_data)
            VALUES (?, ?, ?, 'reconciled', ?, 'pending_verification', 'Cashfree reconciliation - payment confirmed by Cashfree API', ?, 'cashfree', ?, ?)
        ");
        $stmt->execute([
            $invoice_id,
            $invoice['user_id'],
            $adminName,
            $oldStatus,
            $payment_id,
            $cfAmount,
            json_encode([
                'cashfree_response' => $orderResult,
                'reconciled_at' => date('Y-m-d H:i:s')
            ])
        ]);

        $pdo->commit();

        $response['action_taken'] = 'updated_to_pending_verification';
        $response['message'] = 'Cashfree confirmed PAID. Invoice moved to Pending Verification for admin approval.';
    } elseif ($cfStatus === 'PAID' && $invoice['status'] === 'pending_verification') {
        $response['action_taken'] = 'already_pending_verification';
        $response['message'] = 'Invoice is already in Pending Verification. Cashfree confirms PAID.';
    } else {
        $response['action_taken'] = 'no_change';
        $response['message'] = "Cashfree status is '{$cfStatus}'. No update needed.";
    }

    echo json_encode($response);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
