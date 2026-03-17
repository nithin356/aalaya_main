<?php
/**
 * ONE-TIME ADMIN UTILITY: Force-approve a Cashfree Pending invoice by phone number.
 * DELETE THIS FILE after use.
 */
require_once 'includes/header.php';
require_once '../includes/db.php';

$pdo = getDB();
$message = '';
$messageType = '';
$invoice = null;
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_approve'])) {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $admin_note = trim($_POST['admin_note'] ?? 'Force-approved by admin (Cashfree pending override)');

    try {
        $pdo->beginTransaction();

        // Fetch invoice (allow pending OR pending_verification)
        $stmt = $pdo->prepare("SELECT i.*, u.full_name, u.phone FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ? AND i.status IN ('pending', 'pending_verification')");
        $stmt->execute([$invoice_id]);
        $inv = $stmt->fetch();

        if (!$inv) {
            throw new Exception("Invoice #$invoice_id not found or already paid.");
        }

        $adminName = $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'admin';
        $oldStatus = $inv['status'];
        $paymentMethod = $inv['payment_method'] ?? 'cashfree';

        // 1. Mark paid
        $stmt = $pdo->prepare("UPDATE invoices SET status='paid', payment_method=?, admin_comment=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$paymentMethod, $admin_note, $invoice_id]);

        // 2. Reward logic (points for Registration/Subscription Fee)
        if (in_array($inv['description'], ['Registration Fee', 'Subscription Fee'])) {
            $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
            $stmt->execute([$inv['amount'], $inv['user_id']]);

            $stmt = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 0, ?, 100, 'subscription_reward')");
            $stmt->execute([$inv['user_id'], $inv['user_id'], $inv['amount']]);
        }

        // 3. Audit log
        $stmt = $pdo->prepare("INSERT INTO invoice_audit_log (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, manual_utr_id, amount) VALUES (?, ?, ?, 'approved', ?, 'paid', ?, ?, ?, ?, ?)");
        $stmt->execute([
            $invoice_id,
            $inv['user_id'],
            $adminName,
            $oldStatus,
            $admin_note,
            $inv['payment_id'],
            $paymentMethod,
            $inv['manual_utr_id'],
            $inv['amount']
        ]);

        $pdo->commit();
        $message = "✅ Invoice #$invoice_id for {$inv['full_name']} ({$inv['phone']}) has been approved. User is now active.";
        $messageType = 'success';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Search by phone
$phone = trim($_GET['phone'] ?? $_POST['search_phone'] ?? '');
if ($phone) {
    $stmt = $pdo->prepare("
        SELECT i.*, u.full_name, u.phone, u.email
        FROM invoices i 
        JOIN users u ON i.user_id = u.id 
        WHERE u.phone = ? AND i.status IN ('pending', 'pending_verification')
        AND i.description IN ('Registration Fee', 'Subscription Fee')
        ORDER BY i.id DESC LIMIT 1
    ");
    $stmt->execute([$phone]);
    $invoice = $stmt->fetch();
}
?>

<div class="data-card" style="max-width: 600px; margin: 40px auto;">
    <div class="card-header">
        <h2>⚠️ Force Approve Payment</h2>
        <span class="badge bg-danger">Admin Utility — Delete after use</span>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> m-3"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Search -->
    <form method="GET" class="p-4 border-bottom">
        <label class="form-label fw-bold">Search by Phone Number</label>
        <div class="input-group">
            <input type="text" name="phone" class="form-control" placeholder="e.g. 9449663535" value="<?php echo htmlspecialchars($phone); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if ($phone && !$invoice): ?>
        <div class="p-4 text-danger">No pending invoice found for phone: <?php echo htmlspecialchars($phone); ?></div>
    <?php endif; ?>

    <?php if ($invoice): ?>
    <div class="p-4">
        <div class="p-3 bg-light rounded border mb-4">
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">User:</span><strong><?php echo htmlspecialchars($invoice['full_name']); ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Phone:</span><strong><?php echo htmlspecialchars($invoice['phone']); ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Email:</span><strong><?php echo htmlspecialchars($invoice['email']); ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Invoice #:</span><strong><?php echo $invoice['id']; ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Amount:</span><strong>₹<?php echo number_format($invoice['amount'], 2); ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Current Status:</span><strong class="text-warning"><?php echo $invoice['status']; ?></strong></div>
            <div class="d-flex justify-content-between mb-2"><span class="text-muted">Payment Method:</span><strong><?php echo $invoice['payment_method'] ?? '-'; ?></strong></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Cashfree Order ID:</span><code><?php echo $invoice['payment_id'] ?? '-'; ?></code></div>
        </div>

        <form method="POST">
            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
            <input type="hidden" name="search_phone" value="<?php echo htmlspecialchars($phone); ?>">
            <div class="mb-3">
                <label class="form-label fw-bold">Admin Note</label>
                <input type="text" name="admin_note" class="form-control" value="Force-approved by admin (Cashfree pending override)" maxlength="255">
            </div>
            <div class="alert alert-warning">
                <strong>Warning:</strong> This will mark the invoice as <strong>paid</strong> and activate the user immediately.
            </div>
            <button type="submit" name="confirm_approve" class="btn btn-danger w-100 fw-bold py-3">
                ✅ Confirm Force Approve for <?php echo htmlspecialchars($invoice['full_name']); ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
