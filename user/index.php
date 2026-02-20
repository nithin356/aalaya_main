<?php
require_once '../includes/session.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
if (isset($_GET['ref'])) {
    $_SESSION['referrer_code'] = $_GET['ref'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to Aalaya | Property Investment Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/user-style.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>

    <div class="auth-container">
        <!-- Visual Side -->
        <div class="auth-visual">
            <h2>Experience the Future of Property Management.</h2>
            <p>Join thousands of users managing properties and earning rewards through our verified network.</p>
            
            <ul class="feature-list">
                <li class="feature-item" style="display:none;">
                    <i class="bi bi-patch-check-fill"></i>
                    <span>Verified Listings via DigiLocker</span>
                </li>
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
                <h1>Welcome Back</h1>
                <p>Enter your phone number and password to continue.</p>
            </div>

            <form id="loginForm">
                <!-- Phone Number Input -->
                <div class="mb-4">
                    <div class="input-wrapper">
                        <input type="tel" id="loginPhone" name="phone" class="form-input" placeholder="Enter your phone number" maxlength="10" pattern="[0-9]{10}" required>
                        <i class="bi bi-phone"></i>
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-2">
                    <div class="input-wrapper password-field">
                        <input type="password" id="loginPassword" name="password" class="form-input" placeholder="Enter your password" required>
                        <i class="bi bi-lock"></i>
                        <button type="button" class="password-toggle" data-toggle-password="loginPassword" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="text-end mb-4">
                    <a href="forgot-password.php" class="small text-white-50 text-decoration-none">Forgot Password?</a>
                </div>

                <!-- Login Button -->
                <button type="submit" id="loginBtn" class="btn-primary mt-2" style="width: 100%; padding: 14px; border: none; cursor: pointer; border-radius: 12px; font-weight: 600;">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Login
                </button>
            </form>

            <!-- Error Message Display Area -->
            <div id="loginError" class="error-alert" style="display:none; color: #ef4444; font-size: 0.85rem; margin-top: 16px; margin-bottom: 16px;"></div>

            <p class="auth-footer">
                Don't have an account? <a href="register.php">Register</a>
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
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-toggle-password]').forEach(button => {
                button.addEventListener('click', function () {
                    const input = document.getElementById(this.getAttribute('data-toggle-password'));
                    if (!input) return;
                    const icon = this.querySelector('i');
                    const show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                });
            });
        });
    </script>
    <script src="js/user-auth.js"></script>
</body>
</html>
