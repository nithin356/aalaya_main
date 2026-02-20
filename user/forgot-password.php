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
    <title>Forgot Password | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/user-style.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form-side" style="width: 100%; max-width: 450px; margin: auto;">
            <div class="logo-container">
                <img src="../assets/images/logo.png" alt="Aalaya Logo">
            </div>

            <div class="auth-header text-center">
                <h1>Forgot Password</h1>
                <p>Enter your registered email to receive an OTP.</p>
            </div>

            <form id="forgotPasswordForm">
                <div class="mb-4">
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>

                <button type="submit" id="sendOtpBtn" class="btn-primary" style="width: 100%; padding: 14px; border: none; cursor: pointer; border-radius: 12px; font-weight: 600;">
                    Send OTP
                </button>
            </form>

            <div id="messageArea" class="mt-3 text-center" style="display:none;"></div>

            <p class="auth-footer mt-4">
                Remember your password? <a href="index.php">Sign In</a>
            </p>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
    document.getElementById('forgotPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('sendOtpBtn');
        const msgArea = document.getElementById('messageArea');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
        msgArea.style.display = 'none';

        try {
            const formData = new FormData(this);
            const response = await fetch('../api/user/send_otp.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast.success(result.message);
                setTimeout(() => {
                    window.location.href = 'reset-password.php?email=' + encodeURIComponent(formData.get('email'));
                }, 1500);
            } else {
                showToast.error(result.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            showToast.error('Connection error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
    </script>
</body>
</html>
