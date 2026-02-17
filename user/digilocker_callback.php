<?php
$page_title = 'Verifying...';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifying Digilocker | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/user-style.css">
</head>
<body style="height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">

    <div class="text-center">
        <div id="loadingState">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3 class="mt-4">Verifying Credentials</h3>
            <p class="text-muted">Fetching your details from Digilocker...</p>
        </div>

        <div id="errorState" style="display: none;">
            <div class="text-danger mb-3" style="font-size: 3rem;">
                <i class="bi bi-x-circle"></i>
            </div>
            <h3>Verification Failed</h3>
            <p class="text-muted" id="errorMessage">Unable to verify your documents.</p>
            <a href="index.php" class="btn btn-primary mt-3">Try Again</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Get params
            const urlParams = new URLSearchParams(window.location.search);
            const verificationId = urlParams.get('verification_id');
            const status = urlParams.get('status'); // Cashfree sends 'status'

            if (!verificationId) {
                showError("Invalid Callback: Missing Verification ID.");
                return;
            }

            // Optional: Check status param if provided
            // if (status && status !== 'SUCCESS') ... (Wait, status might be PENDING)

            try {
                const formData = new FormData();
                formData.append('verification_id', verificationId);

                const response = await fetch('../api/user/digilocker_finalize.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    if (result.payment_required && result.invoice_id) {
                        window.location.href = 'payment.php?invoice_id=' + result.invoice_id;
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    // Check for PAN specific error
                    // Check for PAN specific error
                    if (result.message && result.message.includes('PAN')) {
                        const loadingState = document.getElementById('loadingState');
                        if (loadingState) loadingState.style.display = 'none';
                        
                        // Try to find container, fallback to body
                        let container = document.querySelector('.text-center'); // The main wrapper in this page structure
                        if (!container) container = document.body;

                        container.innerHTML = `
                            <div class="card shadow-lg p-5 text-center" style="max-width: 500px; border-radius: 16px;">
                                <div class="mb-4">
                                    <i class="bi bi-exclamation-circle text-warning" style="font-size: 4rem;"></i>
                                </div>
                                <h3 class="mb-3 fw-bold">Action Required</h3>
                                <p class="text-muted mb-4 opacity-75">
                                    You did not select your <strong>PAN Card</strong> in Digilocker.<br>
                                    Government regulations require PAN for verification.
                                </p>
                                <button id="retryBtn" class="btn btn-primary w-100 py-3 fw-bold rounded-pill">
                                    <i class="bi bi-arrow-repeat me-2"></i> Try Again
                                </button>
                            </div>
                        `;
                        
                        document.getElementById('retryBtn').addEventListener('click', async function() {
                            this.disabled = true;
                            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Restarting...';
                            
                            try {
                                // Call Init API (re-using session data)
                                const resp = await fetch('../api/user/digilocker_init.php', { method: 'POST' });
                                const data = await resp.json();
                                if (data.success && data.url) {
                                    window.location.href = data.url;
                                } else {
                                    alert('Retry failed: ' + (data.message || 'Unknown error'));
                                    window.location.href = 'index.php';
                                }
                            } catch (e) {
                                window.location.href = 'index.php';
                            }
                        });
                    } else {
                        showError(result.message || 'Failed to complete registration.');
                    }
                }

            } catch (error) {
                showError("Connection Error: " + error.message);
            }
        });

        function showError(msg) {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('errorState').style.display = 'block';
            document.getElementById('errorMessage').innerText = msg;
        }
    </script>
</body>
</html>
