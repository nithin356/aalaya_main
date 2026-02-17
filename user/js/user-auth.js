document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');

    // Handle Phone + Password Login
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const errorDiv = document.getElementById('loginError');
            if (errorDiv) errorDiv.style.display = 'none';
            
            const btn = document.getElementById('loginBtn');
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Logging in...';

            try {
                const formData = new FormData(this);
                const response = await fetch('../api/user/login.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    if (errorDiv) {
                        errorDiv.innerText = result.message;
                        errorDiv.style.display = 'block';
                    }
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                if (errorDiv) {
                    errorDiv.innerText = "Connection error. Please try again.";
                    errorDiv.style.display = 'block';
                }
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        });
    }
});
