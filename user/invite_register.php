<?php
/**
 * Invite-Based Self-Registration Page
 * Reached via an encrypted referral link:  invite_register.php?ref=TOKEN
 *
 * Flow:
 *  Step 1 – Enter PAN + DOB + Name → hit Verify button → server validates via Meon API
 *  Step 2 – Enter Phone, Email, Password → submit → account created, redirect to payment
 */
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once '../includes/db.php';
require_once '../includes/invite_helper.php';

// ---- Decode invite token ----
$raw_token     = $_GET['ref'] ?? '';
$referral_code = $raw_token ? decodeInviteToken($raw_token) : '';
$referrer_name = '';
$token_valid   = false;

if ($referral_code) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE referral_code = ?");
    $stmt->execute([$referral_code]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($referrer) {
        $referrer_name = $referrer['full_name'];
        $token_valid   = true;
    }
}

// ---- Registration fee for display ----
$pdo     = getDB();
$feeStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
$reg_fee = $feeStmt->fetchColumn();
$reg_fee = ($reg_fee === false || floatval($reg_fee) <= 0) ? 1111 : floatval($reg_fee);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Aalaya | Invited Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/user-style.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <link rel="manifest" href="../manifest.json">
    <style>
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transition: background 0.3s;
        }
        .step-dot.active { background: #F969AA; }
        .step-dot.done   { background: #22c55e; }
        .pan-verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(34,197,94,0.12);
            color: #22c55e;
            border: 1px solid rgba(34,197,94,0.3);
            border-radius: 24px;
            padding: 6px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .referrer-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(249,105,170,0.1);
            border: 1px solid rgba(249,105,170,0.25);
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }
        .referrer-badge .bi { color: #F969AA; font-size: 1.2rem; }
        .invalid-link-box {
            text-align: center;
            padding: 40px 20px;
        }
        .invalid-link-box .bi {
            font-size: 3rem;
            color: #ef4444;
            display: block;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <!-- Visual Side -->
    <div class="auth-visual">
        <h2>You've been personally invited to Aalaya.</h2>
        <p>Complete your PAN verification first, then create your secure account to access exclusive property listings.</p>
        <ul class="feature-list">
            <li class="feature-item">
                <i class="bi bi-shield-check-fill"></i>
                <span>PAN-Verified Identity</span>
            </li>
            <li class="feature-item">
                <i class="bi bi-people-fill"></i>
                <span>Referral-Gated Network</span>
            </li>
            <li class="feature-item">
                <i class="bi bi-lock-fill"></i>
                <span>Secure & Transparent</span>
            </li>
        </ul>
    </div>

    <!-- Form Side -->
    <div class="auth-form-side">
        <div class="logo-container">
            <img src="../assets/images/logo.png" alt="Aalaya Logo">
        </div>

        <?php if (!$token_valid): ?>
        <!-- Invalid / missing token -->
        <div class="invalid-link-box">
            <i class="bi bi-link-45deg"></i>
            <h3 style="color:#ef4444; font-weight:700;">Invalid Invitation Link</h3>
            <p class="text-white-50 mt-2">This link is either invalid or has expired.<br>Please ask your referrer to share a new invitation link.</p>
            <a href="index.php" class="btn-primary mt-4" style="display:inline-block; padding:12px 28px; border-radius:12px;">Go to Login</a>
        </div>

        <?php else: ?>

        <!-- Referrer banner -->
        <div class="referrer-badge">
            <i class="bi bi-person-check-fill"></i>
            <span>You were invited by <strong><?php echo htmlspecialchars($referrer_name); ?></strong></span>
        </div>

        <!-- Step indicator -->
        <div class="step-indicator">
            <div class="step-dot active" id="dot1"></div>
            <div class="step-dot" id="dot2"></div>
            <span id="stepLabel" style="font-size:0.8rem; color:rgba(255,255,255,0.5); margin-left:6px;">Step 1 of 2 — PAN Verification</span>
        </div>

        <!-- ===== STEP 1: PAN Verification ===== -->
        <div id="step1">
            <div class="auth-header">
                <h1>Verify Your PAN</h1>
                <p>Enter your PAN details exactly as on your PAN card.</p>
            </div>

            <form id="panForm" autocomplete="off">
                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="text" id="pan_number" name="pan" class="form-input"
                               placeholder="PAN Number (e.g. ABCDE1234F)"
                               maxlength="10" style="text-transform:uppercase;" required
                               pattern="[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}">
                        <i class="bi bi-card-text"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="date" id="pan_dob" name="dob" class="form-input"
                               placeholder="Date of Birth" required>
                        <i class="bi bi-calendar3"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="text" id="pan_name" name="name" class="form-input"
                               placeholder="Full Name (as on PAN card)" required>
                        <i class="bi bi-person"></i>
                    </div>
                </div>

                <div id="panError" style="display:none; color:#ef4444; font-size:0.85rem; margin-bottom:12px;"></div>

                <button type="submit" id="verifyPanBtn" class="btn-primary mt-2"
                        style="width:100%; padding:14px; border:none; cursor:pointer; border-radius:12px; font-weight:600;">
                    <i class="bi bi-shield-check me-2"></i> Verify PAN
                </button>
            </form>
        </div>

        <!-- ===== STEP 2: Complete Registration ===== -->
        <div id="step2" style="display:none;">
            <div class="auth-header">
                <h1>Complete Registration</h1>
                <p>Your PAN is verified. Fill in the remaining details.</p>
            </div>

            <!-- PAN Verified badge -->
            <div class="pan-verified-badge">
                <i class="bi bi-patch-check-fill"></i>
                PAN Verified — <span id="verifiedPanDisplay"></span>
            </div>

            <form id="registerForm" autocomplete="off">
                <!-- Hidden fields populated from PAN verification -->
                <input type="hidden" name="invite_token" value="<?php echo htmlspecialchars($raw_token); ?>">
                <input type="hidden" name="pan_number"   id="reg_pan">
                <input type="hidden" name="full_name"    id="reg_name">
                <input type="hidden" name="dob"          id="reg_dob">

                <!-- Display-only name (from PAN) -->
                <div class="mb-3">
                    <div class="input-wrapper" style="opacity:0.65; pointer-events:none;">
                        <input type="text" class="form-input" id="displayName" placeholder="Full Name" readonly>
                        <i class="bi bi-person-check"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="tel" name="phone" class="form-input"
                               placeholder="Phone Number (10 digits)" maxlength="10"
                               pattern="[0-9]{10}" required>
                        <i class="bi bi-phone"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input"
                               placeholder="Email Address" required>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper password-field">
                        <input type="password" id="regPassword" name="password" class="form-input"
                               placeholder="Create Password (min 6 chars)" required minlength="6">
                        <i class="bi bi-lock"></i>
                        <button type="button" class="password-toggle" data-toggle-password="regPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="input-wrapper password-field">
                        <input type="password" id="regConfirmPassword" name="confirm_password" class="form-input"
                               placeholder="Confirm Password" required>
                        <i class="bi bi-lock-fill"></i>
                        <button type="button" class="password-toggle" data-toggle-password="regConfirmPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Fee notice -->
                <div class="mb-4" style="border-radius:16px; overflow:hidden; border:1px solid rgba(249,105,170,0.2);">
                    <div style="background:linear-gradient(135deg,rgba(249,105,170,0.12),rgba(189,45,107,0.08)); padding:14px 18px; display:flex; align-items:center; gap:14px;">
                        <div style="background:linear-gradient(135deg,#F969AA,#BD2D6B); border-radius:10px; width:40px; height:40px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="bi bi-wallet2" style="color:#fff; font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <p class="mb-0" style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:rgba(255,255,255,0.45); font-weight:600;">Activation Fee</p>
                            <p class="mb-0" style="font-size:1.25rem; font-weight:800; color:#fff; line-height:1.2;">₹<?php echo number_format($reg_fee, 0); ?> <span style="font-size:0.75rem; font-weight:400; color:rgba(255,255,255,0.5);">AALAYA POINTS</span></p>
                        </div>
                    </div>
                    <div style="background:rgba(234,179,8,0.07); border-top:1px solid rgba(234,179,8,0.15); padding:10px 18px; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-arrow-right-circle-fill" style="color:#facc15; font-size:0.95rem; flex-shrink:0;"></i>
                        <p class="mb-0" style="font-size:0.78rem; color:rgba(255,255,255,0.6); line-height:1.4;">You will be redirected to the secure payment gateway after registration.</p>
                    </div>
                </div>

                <div id="regError" style="display:none; color:#ef4444; font-size:0.85rem; margin-bottom:12px;"></div>
                <div id="regSuccess" style="display:none; color:#22c55e; font-size:0.85rem; margin-bottom:12px;"></div>

                <button type="submit" id="registerBtn" class="btn-primary mt-2"
                        style="width:100%; padding:14px; border:none; cursor:pointer; border-radius:12px; font-weight:600;">
                    <i class="bi bi-person-plus me-2"></i> Create Account
                </button>
            </form>
        </div>

        <p class="auth-footer" style="margin-top:20px;">
            Already have an account? <a href="index.php">Sign In</a>
        </p>
        <p class="auth-footer" style="margin-top:10px; font-size:0.8rem;">
            <a href="../privacy_policy.php" target="_blank">Privacy Policy</a> |
            <a href="../terms_and_conditions.php" target="_blank">Terms &amp; Conditions</a>
        </p>

        <?php endif; ?>
    </div><!-- /auth-form-side -->
</div><!-- /auth-container -->

<script src="../assets/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Uppercase PAN as user types
    const panInput = document.getElementById('pan_number');
    if (panInput) {
        panInput.addEventListener('input', () => { panInput.value = panInput.value.toUpperCase(); });
    }

    // ---- STEP 1: PAN Verification ----
    const panForm    = document.getElementById('panForm');
    const panError   = document.getElementById('panError');
    const verifyBtn  = document.getElementById('verifyPanBtn');

    if (panForm) {
        panForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            panError.style.display = 'none';

            const origHTML = verifyBtn.innerHTML;
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verifying…';

            try {
                const fd = new FormData(panForm);
                const res  = await fetch('../api/user/validate_pan.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    // Populate hidden fields for step 2
                    document.getElementById('reg_pan').value  = panInput.value.toUpperCase();
                    document.getElementById('reg_name').value = document.getElementById('pan_name').value;
                    document.getElementById('reg_dob').value  = document.getElementById('pan_dob').value;
                    document.getElementById('displayName').value = document.getElementById('pan_name').value;
                    document.getElementById('verifiedPanDisplay').textContent = panInput.value.toUpperCase();

                    // Move to step 2
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'block';

                    // Update step indicators
                    document.getElementById('dot1').className = 'step-dot done';
                    document.getElementById('dot2').className = 'step-dot active';
                    document.getElementById('stepLabel').textContent = 'Step 2 of 2 — Create Account';
                } else {
                    panError.textContent = data.message;
                    panError.style.display = 'block';
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = origHTML;
                }
            } catch (err) {
                panError.textContent = 'Connection error. Please try again.';
                panError.style.display = 'block';
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = origHTML;
            }
        });
    }

    // ---- STEP 2: Registration ----
    const regForm   = document.getElementById('registerForm');
    const regError  = document.getElementById('regError');
    const regSuccess = document.getElementById('regSuccess');
    const regBtn    = document.getElementById('registerBtn');

    if (regForm) {
        regForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            regError.style.display = 'none';
            regSuccess.style.display = 'none';

            const origHTML = regBtn.innerHTML;
            regBtn.disabled = true;
            regBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating Account…';

            try {
                const fd = new FormData(regForm);
                const res  = await fetch('../api/user/invite_register_submit.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    regSuccess.textContent = data.message;
                    regSuccess.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = data.redirect_url || 'dashboard.php';
                    }, 1200);
                } else {
                    regError.textContent = data.message;
                    regError.style.display = 'block';
                    regBtn.disabled = false;
                    regBtn.innerHTML = origHTML;
                }
            } catch (err) {
                regError.textContent = 'Connection error. Please try again.';
                regError.style.display = 'block';
                regBtn.disabled = false;
                regBtn.innerHTML = origHTML;
            }
        });
    }

    // ---- Password show/hide toggles ----
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.getAttribute('data-toggle-password'));
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            const icon = this.querySelector('i');
            if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
});
</script>
</body>
</html>
