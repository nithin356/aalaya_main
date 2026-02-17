<?php
require_once '../includes/user_auth.php';
$pdo = getDB();

$invoice_id = $_GET['invoice_id'] ?? 0;
// Fetch Invoice - Allow both pending and pending_verification
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ? AND status IN ('pending', 'pending_verification')");
$stmt->execute([$invoice_id, $_SESSION['user_id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/user-style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%; border-radius: 12px;">
        <div class="text-center mb-4">
            <h3 class="text-primary fw-bold">Registration Fee</h3>
            <p class="text-muted">Complete your payment to activate your account.</p>
        </div>

        <div class="mb-4 p-3 bg-white border rounded">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-dark fw-bold">Total Payable</span>
                <span class="fs-4 fw-bold text-primary">₹<?php echo number_format($invoice['amount'], 2); ?></span>
            </div>
        </div>

        <div id="paymentArea" <?php echo $invoice['status'] === 'pending_verification' ? 'style="display:none;"' : ''; ?>>
            <div class="text-center mb-4">
                <img src="../assets/images/qr_payment.png" alt="Payment QR Code" class="img-fluid rounded border p-2 mb-3" style="max-width: 250px; background: white;">
                <div class="p-2 bg-light rounded border border-dashed mb-3">
                    <small class="text-muted d-block uppercase ls-1 mb-1">UPI ID</small>
                    <strong class="text-dark fs-5">aalaya@upi</strong>
                </div>
            </div>

            <form id="manualPaymentForm" enctype="multipart/form-data">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold ls-1 small text-uppercase">1. Enter Transaction ID (UTR) *</label>
                    <input type="text" name="utr_id" class="form-control form-control-lg bg-light" required placeholder="12-digit UTR Number" minlength="6">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold ls-1 small text-uppercase">2. Upload Payment Screenshot *</label>
                    <div class="upload-wrapper position-relative">
                        <input type="file" name="payment_screenshot" id="screenshotInput" class="form-control" accept="image/*" required>
                        <div id="previewContainer" class="mt-2 text-center" style="display:none;">
                            <img id="screenshotPreview" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    </div>
                    <div class="form-text mt-1" style="font-size: 0.75rem;">Upload a clear screenshot of the successful transaction.</div>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-3 fw-bold fs-5 shadow-sm">
                    <i class="bi bi-shield-check me-2"></i> Submit for Verification
                </button>
            </form>
        </div>
        
        <div id="statusArea" <?php echo $invoice['status'] === 'pending_verification' ? '' : 'style="display:none;"'; ?> class="text-center py-4">
            <div class="mb-3">
                <i class="bi bi-clock-history text-warning" style="font-size: 3rem;"></i>
            </div>
            <h4 class="fw-bold">Verification Pending</h4>
            <p class="text-muted">Your payment details have been submitted. Admin will verify your transaction shortly (usually within 2-4 hours).</p>
            <a href="dashboard.php" class="btn btn-outline-primary mt-3">Back to Dashboard</a>
        </div>

        <div id="errorMsg" class="text-danger mt-3 text-center" style="display:none;"></div>
    </div>

    <script>
        // Image Preview Logic
        document.getElementById('screenshotInput').addEventListener('change', function(e) {
            const preview = document.getElementById('screenshotPreview');
            const container = document.getElementById('previewContainer');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    container.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                container.style.display = 'none';
            }
        });

        document.getElementById('manualPaymentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const errorMsg = document.getElementById('errorMsg');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting...';
            errorMsg.style.display = 'none';

            try {
                const formData = new FormData(this);
                const response = await fetch('../api/payment/submit_manual_payment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    document.getElementById('paymentArea').style.display = 'none';
                    document.getElementById('statusArea').style.display = 'block';
                } else {
                    errorMsg.innerText = result.message || 'Submission failed.';
                    errorMsg.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = 'Submit for Verification';
                }
            } catch (error) {
                console.error(error);
                errorMsg.innerText = 'Connection Error.';
                errorMsg.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = 'Submit for Verification';
            }
        });
    </script>
</body>
</html>
