<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Aalaya</title>
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
                <h1>Reset Password</h1>
                <p>Enter the OTP sent to your email and your new password.</p>
            </div>

            <form id="resetPasswordForm">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="text" name="otp" class="form-input" placeholder="Enter 6-digit OTP" required maxlength="6" pattern="[0-9]{6}">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper password-field">
                        <input type="password" id="newPassword" name="password" class="form-input" placeholder="New Password (min 6 chars)" required minlength="6">
                        <i class="bi bi-lock"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="input-wrapper password-field">
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-input" placeholder="Confirm New Password" required>
                        <i class="bi bi-lock-fill"></i>
                    </div>
                </div>

                <button type="submit" id="resetBtn" class="btn-primary" style="width: 100%; padding: 14px; border: none; cursor: pointer; border-radius: 12px; font-weight: 600;">
                    Reset Password
                </button>
            </form>

            <p class="auth-footer mt-4">
                Remember your password? <a href="index.php">Sign In</a>
            </p>
        </div>
    </div>

    <script src="../assets/js/toast.js"></script>
    <script>
    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const password = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;
        
        if (password !== confirm) {
            showToast.error('Passwords do not match!');
            return;
        }

        const btn = document.getElementById('resetBtn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Resetting...';

        try {
            const formData = new FormData(this);
            const response = await fetch('../api/user/reset_password.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast.success(result.message);
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
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
