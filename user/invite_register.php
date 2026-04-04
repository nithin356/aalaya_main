<?php
/**
 * Invite-Based Self-Registration Page
 * Flow:
 *  Step 1 – Click "Verify with DigiLocker" → Meon API → redirect → callback stores data in session
 *  Step 2 – Auto-filled identity (locked) + Phone/Email/Password → create account → payment
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
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE referral_code = ?");
    $stmt->execute([$referral_code]);
    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($referrer) {
        $referrer_name = $referrer['full_name'];
        $token_valid   = true;
    }
}

// ---- DigiLocker verified data (set by callback) ----
$digi_done     = isset($_GET['digi']) && $_GET['digi'] === 'done';
$digi_verified = $_SESSION['digi_verified'] ?? null;
$digi_error    = isset($_GET['digi_error']) ? urldecode($_GET['digi_error']) : '';

// ---- Registration fee ----
$pdo     = getDB();
$feeStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
$reg_fee = $feeStmt->fetchColumn();
$reg_fee = ($reg_fee === false || floatval($reg_fee) <= 0) ? 1111 : floatval($reg_fee);

// JSON-encode DigiLocker data for JS
$digi_js = $digi_verified ? json_encode($digi_verified, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
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
        .step-indicator { display:flex; align-items:center; gap:8px; margin-bottom:24px; }
        .step-dot { width:10px; height:10px; border-radius:50%; background:rgba(255,255,255,0.2); transition:background 0.3s; }
        .step-dot.active { background:#F969AA; }
        .step-dot.done   { background:#22c55e; }

        .referrer-badge {
            display:flex; align-items:center; gap:10px;
            background:rgba(249,105,170,0.1);
            border:1px solid rgba(249,105,170,0.25);
            border-radius:14px; padding:12px 16px; margin-bottom:24px; font-size:0.9rem;
        }
        .referrer-badge .bi { color:#F969AA; font-size:1.2rem; }

        .digi-verified-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:rgba(34,197,94,0.12); color:#22c55e;
            border:1px solid rgba(34,197,94,0.3);
            border-radius:24px; padding:6px 14px;
            font-size:0.85rem; font-weight:600; margin-bottom:18px;
        }

        .locked-field { opacity:0.6; pointer-events:none; }

        .digi-btn {
            width:100%; padding:16px; border:none; cursor:pointer;
            border-radius:14px; font-weight:700; font-size:1rem;
            background:linear-gradient(135deg,#F969AA,#BD2D6B); color:#fff;
            display:flex; align-items:center; justify-content:center; gap:10px;
            box-shadow:0 8px 24px rgba(217,69,137,0.35);
            transition:opacity .2s;
        }
        .digi-btn:disabled { opacity:0.6; cursor:not-allowed; }

        .digi-info-box {
            border-radius:14px; padding:14px 18px; margin-bottom:24px;
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            font-size:0.82rem; color:rgba(255,255,255,0.55); line-height:1.6;
        }
        .digi-info-box i { color:#F969AA; margin-right:6px; }

        .invalid-link-box { text-align:center; padding:40px 20px; }
        .invalid-link-box .bi { font-size:3rem; color:#ef4444; display:block; margin-bottom:16px; }

        .identity-card {
            border-radius:14px; padding:14px 18px; margin-bottom:20px;
            background:rgba(34,197,94,0.05); border:1px solid rgba(34,197,94,0.2);
        }
        .identity-card .id-row {
            display:flex; justify-content:space-between; align-items:center;
            padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.83rem;
        }
        .identity-card .id-row:last-child { border-bottom:none; }
        .identity-card .id-label { color:rgba(255,255,255,0.4); font-weight:500; }
        .identity-card .id-value { color:#fff; font-weight:600; }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-visual">
        <h2>You've been personally invited to Aalaya.</h2>
        <p>Verify your identity securely with DigiLocker, then create your account to access exclusive property listings.</p>
        <ul class="feature-list">
            <li class="feature-item"><i class="bi bi-shield-check-fill"></i><span>Aadhaar + PAN Verified</span></li>
            <li class="feature-item"><i class="bi bi-people-fill"></i><span>Referral-Gated Network</span></li>
            <li class="feature-item"><i class="bi bi-lock-fill"></i><span>Secure &amp; Transparent</span></li>
        </ul>
    </div>

    <div class="auth-form-side">
        <div class="logo-container">
            <img src="../assets/images/logo.png" alt="Aalaya Logo">
        </div>

        <?php if (!$token_valid): ?>
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
            <div class="step-dot <?php echo ($digi_done && $digi_verified) ? 'done' : 'active'; ?>" id="dot1"></div>
            <div class="step-dot <?php echo ($digi_done && $digi_verified) ? 'active' : ''; ?>"   id="dot2"></div>
            <span id="stepLabel" style="font-size:0.8rem; color:rgba(255,255,255,0.5); margin-left:6px;">
                <?php echo ($digi_done && $digi_verified) ? 'Step 2 of 2 — Create Account' : 'Step 1 of 2 — Identity Verification'; ?>
            </span>
        </div>

        <!-- ===== STEP 1: DigiLocker Verification ===== -->
        <div id="step1" <?php echo ($digi_done && $digi_verified) ? 'style="display:none;"' : ''; ?>>
            <div class="auth-header">
                <h1>Verify Your Identity</h1>
                <p>Connect with DigiLocker to verify your Aadhaar &amp; PAN securely.</p>
            </div>

            <?php if ($digi_error && !in_array($digi_error, ['session', 'done'])): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:12px; padding:12px 16px; margin-bottom:16px; color:#ef4444; font-size:0.85rem;">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($digi_error); ?> Please try again.
            </div>
            <?php endif; ?>

            <div class="digi-info-box">
                <div><i class="bi bi-check-circle-fill"></i>Your Aadhaar and PAN will be fetched automatically</div>
                <div><i class="bi bi-check-circle-fill"></i>No manual entry needed — data comes directly from DigiLocker</div>
                <div><i class="bi bi-check-circle-fill"></i>100% secure, government-backed verification</div>
            </div>

            <button type="button" id="digiBtn" class="digi-btn">
                <i class="bi bi-person-badge-fill" style="font-size:1.3rem;"></i>
                Verify with DigiLocker
            </button>

            <p style="text-align:center; font-size:0.75rem; color:rgba(255,255,255,0.3); margin-top:12px;">
                You'll be redirected to DigiLocker and brought back automatically.
            </p>
        </div>

        <!-- ===== STEP 2: Complete Registration ===== -->
        <div id="step2" <?php echo ($digi_done && $digi_verified) ? '' : 'style="display:none;"'; ?>>
            <div class="auth-header">
                <h1>Complete Registration</h1>
                <p>Your identity is verified. Fill in the remaining details.</p>
            </div>

            <!-- Verified badge -->
            <div class="digi-verified-badge">
                <i class="bi bi-patch-check-fill"></i> Identity Verified via DigiLocker
            </div>

            <!-- Identity summary card -->
            <?php if ($digi_verified): ?>
            <div class="identity-card">
                <div class="id-row">
                    <span class="id-label"><i class="bi bi-person me-1"></i>Name</span>
                    <span class="id-value"><?php echo htmlspecialchars($digi_verified['name']); ?></span>
                </div>
                <div class="id-row">
                    <span class="id-label"><i class="bi bi-card-text me-1"></i>PAN</span>
                    <span class="id-value"><?php echo htmlspecialchars($digi_verified['pan_number']); ?></span>
                </div>
                <div class="id-row">
                    <span class="id-label"><i class="bi bi-fingerprint me-1"></i>Aadhaar</span>
                    <span class="id-value"><?php echo htmlspecialchars($digi_verified['aadhar_no']); ?></span>
                </div>
                <div class="id-row">
                    <span class="id-label"><i class="bi bi-calendar3 me-1"></i>DOB</span>
                    <span class="id-value"><?php echo htmlspecialchars($digi_verified['dob']); ?></span>
                </div>
                <?php if (!empty($digi_verified['gender'])): ?>
                <div class="id-row">
                    <span class="id-label"><i class="bi bi-gender-ambiguous me-1"></i>Gender</span>
                    <span class="id-value"><?php echo htmlspecialchars($digi_verified['gender']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form id="registerForm" autocomplete="off">
                <input type="hidden" name="invite_token" value="<?php echo htmlspecialchars($raw_token); ?>">

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
                            <p class="mb-0" style="font-size:1.25rem; font-weight:800; color:#fff; line-height:1.2;">
                                ₹<?php echo number_format($reg_fee, 0); ?>
                                <span style="font-size:0.75rem; font-weight:400; color:rgba(255,255,255,0.5);">AALAYA POINTS</span>
                            </p>
                        </div>
                    </div>
                    <div style="background:rgba(234,179,8,0.07); border-top:1px solid rgba(234,179,8,0.15); padding:10px 18px; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-arrow-right-circle-fill" style="color:#facc15; font-size:0.95rem; flex-shrink:0;"></i>
                        <p class="mb-0" style="font-size:0.78rem; color:rgba(255,255,255,0.6); line-height:1.4;">You will be redirected to the secure payment gateway after registration.</p>
                    </div>
                </div>

                <div id="regError"   style="display:none; color:#ef4444; font-size:0.85rem; margin-bottom:12px;"></div>
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
    </div>
</div>

<script src="../assets/js/toast.js"></script>
<script>
const RAW_TOKEN  = <?php echo json_encode($raw_token); ?>;
const DIGI_DATA  = <?php echo $digi_js; ?>;
const DIGI_DONE  = <?php echo ($digi_done && $digi_verified) ? 'true' : 'false'; ?>;

document.addEventListener('DOMContentLoaded', function () {

    // ---- STEP 1: Trigger DigiLocker ----
    const digiBtn = document.getElementById('digiBtn');
    if (digiBtn) {
        digiBtn.addEventListener('click', async function () {
            digiBtn.disabled = true;
            digiBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Connecting to DigiLocker…';

            try {
                const fd = new FormData();
                fd.append('invite_ref', RAW_TOKEN);

                const res  = await fetch('../api/user/digilocker_invite_init.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success && data.url) {
                    window.location.href = data.url;
                } else {
                    showDigiError(data.message || 'Failed to connect to DigiLocker. Please try again.');
                    digiBtn.disabled = false;
                    digiBtn.innerHTML = '<i class="bi bi-person-badge-fill" style="font-size:1.3rem;"></i> Verify with DigiLocker';
                }
            } catch (err) {
                showDigiError('Connection error. Please try again.');
                digiBtn.disabled = false;
                digiBtn.innerHTML = '<i class="bi bi-person-badge-fill" style="font-size:1.3rem;"></i> Verify with DigiLocker';
            }
        });
    }

    function showDigiError(msg) {
        let box = document.getElementById('digiErrorBox');
        if (!box) {
            box = document.createElement('div');
            box.id = 'digiErrorBox';
            box.style = 'background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:12px 16px;margin-bottom:16px;color:#ef4444;font-size:0.85rem;';
            digiBtn.parentNode.insertBefore(box, digiBtn);
        }
        box.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + msg;
    }

    // ---- STEP 2: Registration submit ----
    const regForm    = document.getElementById('registerForm');
    const regError   = document.getElementById('regError');
    const regSuccess = document.getElementById('regSuccess');
    const regBtn     = document.getElementById('registerBtn');

    if (regForm) {
        regForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            regError.style.display = 'none';
            regSuccess.style.display = 'none';

            const origHTML = regBtn.innerHTML;
            regBtn.disabled = true;
            regBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating Account…';

            try {
                const fd  = new FormData(regForm);
                const res  = await fetch('../api/user/invite_register_submit.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    regSuccess.textContent = data.message;
                    regSuccess.style.display = 'block';
                    setTimeout(() => { window.location.href = data.redirect_url || 'dashboard.php'; }, 1200);
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

    // ---- Password toggles ----
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
