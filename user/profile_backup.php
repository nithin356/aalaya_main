<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../includes/db.php';
$pdo = getDB();

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/landing.css">
    <style>
        .profile-container {
            max-width: 700px;
            margin: 60px auto;
            background: white;
            border-radius: 32px;
            border: 1px solid #f1f5f9;
            padding: 48px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 40px;
        }
        .profile-pic-large {
            width: 80px;
            height: 80px;
            background: #eff6ff;
            color: var(--brand-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .field-group {
            margin-bottom: 24px;
        }
        .field-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: block;
        }
        .field-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
        }
    </style>
</head>
<body>

    <header class="landing-header">
        <div class="nav-container">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="dashboard.php" class="btn-profile" title="Go Back">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <a href="dashboard.php" class="brand-logo">
                    <img src="../assets/images/logo.png" alt="Aalaya" style="height: 28px;">
                    <!--<h1>AALAYA</h1>-->
                </a>
            </div>
            <div class="user-actions">
                <div class="profile-dropdown-container">
                    <button class="btn-profile" id="profileToggle">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <div class="dropdown-menu-custom" id="profileMenu">
                        <div class="dropdown-header">
                            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                            <span>Verified Investor</span>
                        </div>
                        <hr>
                        <a href="profile.php"><i class="bi bi-person"></i> My Profile</a>
                        <a href="dashboard.php?tab=wallet"><i class="bi bi-wallet2"></i> My Earnings</a>
                        <hr>
                        <a href="../api/user/logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-gallery">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-pic-large">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <h2 style="font-weight:800; margin:0;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-muted" style="margin:0;">Verified via DigiLocker #<?php echo htmlspecialchars($user['digilocker_id']); ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 field-group">
                    <span class="field-label">Email Address</span>
                    <span class="field-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="col-md-6 field-group">
                    <span class="field-label">Phone Number</span>
                    <span class="field-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
                <div class="col-md-6 field-group">
                    <span class="field-label">Referral Code</span>
                    <span class="field-value" style="color: var(--brand-primary); font-family: monospace;"><?php echo htmlspecialchars($user['referral_code']); ?></span>
                </div>
                <div class="col-md-6 field-group">
                    <span class="field-label">Joined On</span>
                    <span class="field-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>

            <div style="margin-top: 40px; display: flex; gap: 16px;">
                <button class="btn-outline-brand">Edit Profile</button>
                <a href="../api/user/logout.php" class="btn-ghost" style="text-decoration:none; display:flex; align-items:center;">Logout</a>
            </div>

        </div>
    </main>

    <footer class="landing-footer">
        <div class="footer-bottom">
            <div>&copy; <?php echo date('Y'); ?> Aalaya. All rights reserved.</div>
            <div class="legal-links">
                <a href="../privacy_policy.php" target="_blank">Privacy Policy</a>
                <span class="dot-separator">•</span>
                <a href="../terms_and_conditions.php" target="_blank">Terms & Conditions</a>
                <span class="dot-separator">•</span>
                <a href="../refund_policy.php" target="_blank">Refund Policy</a>
            </div>
        </div>
    </footer>
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden;">
                <div class="modal-body p-5" id="modalDetailsContent">
                    <!-- Content injected by landing.js -->
                </div>
                <div class="modal-footer border-0 p-4 justify-content-center">
                    <button type="button" class="btn-primary" data-bs-dismiss="modal" style="padding: 10px 40px;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/landing.js"></script>
</body>
</html>
