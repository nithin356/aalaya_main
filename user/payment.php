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

$is_gateway_registration_flow = !empty($_SESSION['hide_network_tab'])
    || strtolower($_SESSION['user_payment_tag'] ?? '') === 'gateway user';
$show_gateway_payment = $is_gateway_registration_flow;
$show_manual_payment = !$is_gateway_registration_flow;

// Load Cashfree Mode
$config = parse_ini_file('../config/config.ini', true);
$cashfree_mode = strtolower($config['cashfree']['payment_mode'] ?? ($config['cashfree']['mode'] ?? 'test'));
// Cashfree SDK expects 'sandbox' or 'production'
$sdk_mode = ($cashfree_mode === 'prod' || $cashfree_mode === 'production') ? 'production' : 'sandbox';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .payment-card {
            max-width: 390px;
            width: 100%;
            border-radius: 12px;
        }

        @media (max-width: 576px) {
            .payment-card {
                max-width: 360px;
                padding: 14px !important;
            }

            .payment-card h3 {
                font-size: 1.55rem;
                margin-bottom: 0.25rem;
            }

            .payment-card .fs-4 {
                font-size: 1.8rem !important;
            }

            .payment-card .form-control-lg,
            .payment-card .form-control,
            .payment-card .btn {
                padding-top: 0.6rem !important;
                padding-bottom: 0.6rem !important;
                font-size: 1rem !important;
            }

            .payment-card .mb-4 {
                margin-bottom: 0.9rem !important;
            }

            .payment-card .mb-3 {
                margin-bottom: 0.7rem !important;
            }

            .payment-card .p-3 {
                padding: 0.8rem !important;
            }
        }
    </style>
</head>
<body class="bg-light d-flex align-items-start justify-content-center" style="min-height: 100dvh; height: auto; overflow-x: hidden; overflow-y: auto; padding: 14px 0;">

    <div class="card shadow-lg p-4 payment-card">
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
            <?php if ($show_gateway_payment): ?>
                <div class="mb-4">
                    <button type="button" id="payGatewayBtn" class="btn btn-success w-100 py-3 fw-bold fs-6 shadow-sm">
                        <i class="bi bi-credit-card-2-front me-2"></i> Pay via Secure Gateway
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($show_manual_payment): ?>
                <?php if ($show_gateway_payment): ?>
                    <div class="text-center text-muted small mb-3">or submit manual details</div>
                <?php endif; ?>

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
                        <label class="form-label fw-bold ls-1 small text-uppercase">1. Enter Transaction ID (UTR) (Optional)</label>
                        <input type="text" name="utr_id" class="form-control form-control-lg bg-light" placeholder="12-digit UTR Number" minlength="6">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold ls-1 small text-uppercase">2. Upload Payment Screenshot (Optional)</label>
                        <div class="upload-wrapper position-relative">
                            <input type="file" name="payment_screenshot" id="screenshotInput" class="form-control" accept="image/*">
                            <div id="previewContainer" class="mt-2 text-center" style="display:none;">
                                <img id="screenshotPreview" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px;">
                            </div>
                        </div>
                        <div class="form-text mt-1" style="font-size: 0.75rem;">Upload screenshot if available. You can submit without it.</div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-3 fw-bold fs-5 shadow-sm">
                        <i class="bi bi-shield-check me-2"></i> Submit for Verification
                    </button>
                </form>
            <?php endif; ?>
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

    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        const cashfree = window.Cashfree ? window.Cashfree({ mode: "<?php echo $sdk_mode; ?>" }) : null;
        const errorMsg = document.getElementById('errorMsg');

        // Safety: if cached/duplicated DOM appears, keep only first payment card
        const directCards = document.querySelectorAll('body > .card');
        if (directCards.length > 1) {
            directCards.forEach((card, index) => {
                if (index > 0) card.remove();
            });
        }

        // Image Preview Logic
        const screenshotInput = document.getElementById('screenshotInput');
        if (screenshotInput) {
            screenshotInput.addEventListener('change', function(e) {
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
        }

        const manualPaymentForm = document.getElementById('manualPaymentForm');
        if (manualPaymentForm) {
            manualPaymentForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = document.getElementById('submitBtn');
                
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
        }

        const payGatewayBtn = document.getElementById('payGatewayBtn');
        if (payGatewayBtn) {
            payGatewayBtn.addEventListener('click', async function () {
            const btn = this;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Connecting...';

            try {
                const formData = new FormData();
                formData.append('invoice_id', '<?php echo (int)$invoice_id; ?>');

                const response = await fetch('../api/payment/init_payment.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success && result.payment_link) {
                    window.location.href = result.payment_link;
                    return;
                }

                if (result.success && result.payment_session_id) {
                    if (!cashfree) {
                        errorMsg.innerText = 'Cashfree SDK failed to load. Please try again.';
                        errorMsg.style.display = 'block';
                        return;
                    }

                    const checkoutOptions = {
                        paymentSessionId: result.payment_session_id,
                        redirectTarget: '_self'
                    };

                    const checkoutResult = await cashfree.checkout(checkoutOptions);
                    if (checkoutResult && checkoutResult.error) {
                        errorMsg.innerText = checkoutResult.error.message || 'Unable to open gateway checkout.';
                        errorMsg.style.display = 'block';
                    }
                } else {
                    errorMsg.innerText = result.message || 'Unable to initiate gateway payment.';
                    errorMsg.style.display = 'block';
                }
            } catch (error) {
                errorMsg.innerText = 'Connection Error while initiating gateway payment.';
                errorMsg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
            });
        }
    </script>
</body>
</html>
