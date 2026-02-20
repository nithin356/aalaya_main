document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('adminLoginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginError = document.getElementById('loginError');
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('adminPasswordToggle');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function () {
            const icon = this.querySelector('i');
            const show = passwordInput.type === 'password';
            passwordInput.type = show ? 'text' : 'password';
            if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Loading state
        loginError.classList.remove('show');
        loginBtn.disabled = true;
        const originalText = loginBtn.innerText;
        loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing In...';

        try {
            const formData = new FormData(loginForm);
            const response = await fetch('../api/admin/login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = 'dashboard.php';
            } else {
                showError(result.message || 'Verification failed. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Server connection lost. Please try again later.');
        } finally {
            if (loginBtn.disabled && !window.location.href.includes('dashboard')) {
                loginBtn.disabled = false;
                loginBtn.innerHTML = originalText;
            }
        }
    });

    function showError(message) {
        if (loginError) {
            loginError.querySelector('span').textContent = message;
            loginError.classList.add('show');
        } else {
            showToast.error(message);
        }
    }
});
