<?php
$page_title   = 'Register User';
$header_title = 'Manual User Registration';
require_once 'includes/header.php';
require_once '../includes/db.php';

$pdo = getDB();
$users = $pdo->query("SELECT full_name, referral_code FROM users WHERE digilocker_verified = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-8">

        <!-- Info Banner -->
        <div style="background:rgba(249,105,170,0.08); border:1px solid rgba(249,105,170,0.25); border-radius:14px; padding:14px 20px; margin-bottom:24px; display:flex; align-items:flex-start; gap:12px;">
            <i class="bi bi-info-circle-fill" style="color:#F969AA; font-size:1.2rem; margin-top:2px; flex-shrink:0;"></i>
            <div style="font-size:0.9rem; color:var(--text-muted); line-height:1.6;">
                Use this form to manually register a customer who was unable to complete self-registration
                (e.g. DigiLocker session loss, browser issues). The account will be created with
                <strong>DigiLocker verified</strong> status and a pending registration invoice.
            </div>
        </div>

        <div class="data-card">
            <div class="card-header">
                <h2><i class="bi bi-person-plus-fill me-2"></i>New Customer Registration</h2>
            </div>
            <div style="padding: 28px;">
                <form id="manualRegisterForm" novalidate>

                    <!-- Identity -->
                    <h6 style="color:var(--text-muted); font-size:0.78rem; letter-spacing:.06em; text-transform:uppercase; margin-bottom:16px; margin-top:4px;">
                        <i class="bi bi-person-badge me-1"></i> Identity Details
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-muted small">(Optional)</span></label>
                            <input type="text" name="full_name" class="form-input" placeholder="As per Aadhaar">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aadhaar Number <span class="text-danger">*</span></label>
                            <input type="text" name="aadhaar_number" class="form-input" required
                                   pattern="[0-9]{12}" maxlength="12"
                                   title="12-digit Aadhaar number" placeholder="12-digit Aadhaar">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">PAN Number <span class="text-danger">*</span></label>
                            <input type="text" name="pan_number" id="pan_number" class="form-input" required
                                   pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" maxlength="10"
                                   title="Valid PAN format e.g. ABCDE1234F" placeholder="e.g. ABCDE1234F"
                                   style="text-transform:uppercase;">
                        </div>
                    </div>

                    <hr style="border-color:rgba(255,255,255,0.07); margin-bottom:20px;">

                    <!-- Contact -->
                    <h6 style="color:var(--text-muted); font-size:0.78rem; letter-spacing:.06em; text-transform:uppercase; margin-bottom:16px;">
                        <i class="bi bi-telephone me-1"></i> Contact Details
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-input" required
                                   pattern="[0-9]{10}" maxlength="10"
                                   title="10-digit mobile number" placeholder="10-digit mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-muted small">(Optional)</span></label>
                            <input type="email" name="email" class="form-input" placeholder="customer@email.com">
                        </div>
                    </div>

                    <hr style="border-color:rgba(255,255,255,0.07); margin-bottom:20px;">

                    <!-- Referrer -->
                    <h6 style="color:var(--text-muted); font-size:0.78rem; letter-spacing:.06em; text-transform:uppercase; margin-bottom:16px;">
                        <i class="bi bi-diagram-2 me-1"></i> Referral
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Referred By <span class="text-muted small">(Optional)</span></label>
                            <select name="referrer_code" class="form-select user-select-dropdown">
                                <option value="">-- Select Referrer (Direct Join if blank) --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u['referral_code']); ?>">
                                        <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo htmlspecialchars($u['referral_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr style="border-color:rgba(255,255,255,0.07); margin-bottom:20px;">

                    <!-- Password -->
                    <h6 style="color:var(--text-muted); font-size:0.78rem; letter-spacing:.06em; text-transform:uppercase; margin-bottom:16px;">
                        <i class="bi bi-lock me-1"></i> Account Password
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div style="position:relative;">
                                <input type="password" name="password" id="reg_password" class="form-input" required minlength="6" placeholder="Min 6 characters" style="padding-right:42px;">
                                <button type="button" onclick="togglePw('reg_password', 'eye1')" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;">
                                    <i class="bi bi-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div style="position:relative;">
                                <input type="password" name="confirm_password" id="reg_confirm_password" class="form-input" required minlength="6" placeholder="Repeat password" style="padding-right:42px;">
                                <button type="button" onclick="togglePw('reg_confirm_password', 'eye2')" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;">
                                    <i class="bi bi-eye" id="eye2"></i>
                                </button>
                            </div>
                            <div id="pw_mismatch" style="color:#e74c3c; font-size:0.82rem; display:none; margin-top:5px;">
                                <i class="bi bi-exclamation-circle me-1"></i>Passwords do not match.
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-3 align-items-center mt-2">
                        <button type="submit" id="submitBtn" class="btn-primary px-5">
                            <i class="bi bi-person-check-fill me-2"></i>Register Customer
                        </button>
                        <a href="users_management.php" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>
        </div>

        <!-- Success panel (hidden until registered) -->
        <div id="successPanel" style="display:none; margin-top:24px;">
            <div style="background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.3); border-radius:14px; padding:24px; text-align:center;">
                <i class="bi bi-check-circle-fill" style="color:#22c55e; font-size:2.5rem; display:block; margin-bottom:12px;"></i>
                <h5 style="color:#22c55e; margin-bottom:6px;">Customer Registered Successfully</h5>
                <p id="successMsg" style="color:var(--text-muted); margin-bottom:20px;"></p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <button onclick="resetForm()" class="btn-primary px-4">
                        <i class="bi bi-plus-circle me-1"></i>Register Another
                    </button>
                    <a href="users_management.php" class="btn btn-secondary px-4">
                        <i class="bi bi-people me-1"></i>View All Users
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// PAN to uppercase
document.getElementById('pan_number').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Password visibility toggle
function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        ico.className = 'bi bi-eye';
    }
}

// Password match check
document.getElementById('reg_confirm_password').addEventListener('input', checkPwMatch);
document.getElementById('reg_password').addEventListener('input', checkPwMatch);
function checkPwMatch() {
    const pw  = document.getElementById('reg_password').value;
    const cpw = document.getElementById('reg_confirm_password').value;
    const msg = document.getElementById('pw_mismatch');
    msg.style.display = (cpw && pw !== cpw) ? 'block' : 'none';
}

// Form submit
document.getElementById('manualRegisterForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const pw  = document.getElementById('reg_password').value;
    const cpw = document.getElementById('reg_confirm_password').value;
    if (pw !== cpw) {
        document.getElementById('pw_mismatch').style.display = 'block';
        document.getElementById('reg_confirm_password').focus();
        return;
    }

    if (!this.checkValidity()) {
        this.classList.add('was-validated');
        return;
    }

    const btn = document.getElementById('submitBtn');
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registering...';

    try {
        const formData = new FormData(this);
        const pan = formData.get('pan_number');
        if (pan) formData.set('pan_number', pan.toUpperCase());

        const resp   = await fetch('../api/user/admin_register_user.php', { method: 'POST', body: formData });
        const result = await resp.json();

        if (result.success) {
            document.getElementById('successMsg').textContent = result.message;
            document.getElementById('manualRegisterForm').style.display = 'none';
            document.getElementById('successPanel').style.display = 'block';
            showToast.success(result.message);
        } else {
            showToast.error(result.message);
            btn.disabled = false;
            btn.innerHTML = origHTML;
        }
    } catch (err) {
        showToast.error('Network error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = origHTML;
    }
});

function resetForm() {
    document.getElementById('manualRegisterForm').reset();
    document.getElementById('manualRegisterForm').classList.remove('was-validated');
    document.getElementById('manualRegisterForm').style.display = 'block';
    document.getElementById('successPanel').style.display = 'none';
    document.getElementById('pw_mismatch').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
