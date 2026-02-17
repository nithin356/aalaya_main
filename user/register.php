<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/user-style.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>

    <div class="auth-container">
        <!-- Visual Side -->
        <div class="auth-visual">
            <h2>Join the Future of Verified Real Estate.</h2>
            <p>Create your account to access exclusive property listings and investment opportunities.</p>
            
            <ul class="feature-list">
                <li class="feature-item">
                    <i class="bi bi-people-fill"></i>
                    <span>Exclusive Investment Network</span>
                </li>
                <li class="feature-item">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Secure & Transparent Transactions</span>
                </li>
            </ul>
        </div>

        <!-- Form Side -->
        <div class="auth-form-side">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="Aalaya Logo">
            </div>

            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Fill in your details to get started.</p>
            </div>

            <form id="selfRegisterForm">
                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="text" name="full_name" class="form-input" placeholder="Full Name" required>
                        <i class="bi bi-person"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="tel" name="phone" class="form-input" placeholder="Phone Number (10 digits)" maxlength="10" pattern="[0-9]{10}" required>
                        <i class="bi bi-phone"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-input" placeholder="Create Password (min 6 chars)" required minlength="6">
                        <i class="bi bi-lock"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
                        <i class="bi bi-lock-fill"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="text" name="aadhaar_number" class="form-input" placeholder="Aadhaar Number (12 digits)" maxlength="12" pattern="[0-9]{12}" required>
                        <i class="bi bi-credit-card"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="text" name="pan_number" class="form-input" placeholder="PAN Number" maxlength="10" style="text-transform: uppercase;" required pattern="[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}">
                        <i class="bi bi-card-text"></i>
                    </div>
                </div>

                <!-- QR Payment Section -->
                <div class="mb-4 p-4 text-center" style="background: rgba(255,255,255,0.05); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.2);">
                    <p class="small text-white-50 mb-3">To activate your account, please pay the registration fee of <strong>₹1111</strong> via the QR code below.</p>
                    <div class="qr-placeholder mb-3" style="width: 180px; height: 180px; margin: 0 auto; background: white; padding: 10px; border-radius: 12px;">
                        <img src="../assets/images/qr_payment.png" alt="Payment QR Code" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Registration+Fee+1111';">
                    </div>
                    <p class="small text-warning mb-0"><i class="bi bi-info-circle me-1"></i> Your account will be on hold until admin verifies the payment.</p>
                </div>

                <!-- Register Button -->
                <button type="submit" id="registerBtn" class="btn-primary mt-2" style="width: 100%; padding: 14px; border: none; cursor: pointer; border-radius: 12px; font-weight: 600;">
                    <i class="bi bi-person-plus me-2"></i> Create Account
                </button>
            </form>

            <!-- Error Message Display Area -->
            <div id="registerError" class="error-alert" style="display:none; color: #ef4444; font-size: 0.85rem; margin-top: 16px; margin-bottom: 16px;"></div>
            <!-- Success Message Display Area -->
            <div id="registerSuccess" class="error-alert" style="display:none; color: #22c55e; font-size: 0.85rem; margin-top: 16px; margin-bottom: 16px;"></div>

            <p class="auth-footer">
                Already have an account? <a href="index.php">Sign In</a>
            </p>
            <p class="auth-footer" style="margin-top: 10px; font-size: 0.8rem;">
                <a href="../privacy_policy.php" target="_blank">Privacy Policy</a> | 
                <a href="../terms_and_conditions.php" target="_blank">Terms & Conditions</a> | 
                <a href="../refund_policy.php" target="_blank">Refund Policy</a>
            </p>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('selfRegisterForm');
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const errorDiv = document.getElementById('registerError');
            const successDiv = document.getElementById('registerSuccess');
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            const btn = document.getElementById('registerBtn');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating Account...';

            try {
                const formData = new FormData(this);
                const response = await fetch('../api/user/self_register.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    successDiv.innerText = result.message + ' Redirecting to login...';
                    successDiv.style.display = 'block';
                    form.reset();
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    errorDiv.innerText = result.message;
                    errorDiv.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                errorDiv.innerText = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        });
    });
    </script>
</body>
</html>
