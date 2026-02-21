<?php
require_once '../includes/user_auth.php';
$pdo = getDB();

// Fetch counts for the Discover tab
// (Local variable $pending_registration_invoice will be available from user_auth.php if needed, 
// but we just need binary check for the blocker)
$show_payment_block = !empty($pending_registration_invoice);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Explore Aalaya | Premium Property & Rewards</title>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Bootstrap Grid only for gallery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Landing CSS -->
    <link rel="stylesheet" href="css/landing.css">
    <link rel="stylesheet" href="css/landing_home.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body>

    <!-- Premium Sticky Header -->
    <header class="landing-header">
        <div class="nav-container">
            <a href="dashboard.php" class="brand-logo">
                <img src="../assets/images/logo.png" alt="Aalaya" style="height: 80px; width: auto; object-fit: contain;">
                <!--<h1>AALAYA</h1>-->
            </a>
            <div class="user-actions">
                <div class="profile-dropdown-container">
                    <button class="btn-profile" id="profileToggle">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <div class="dropdown-menu-custom" id="profileMenu">
                        <div class="dropdown-header">
                            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                            <span><?php echo htmlspecialchars($_SESSION['user_payment_tag'] ?? 'Verified Investor'); ?></span>
                        </div>
                        <hr>
                        <a href="profile.php"><i class="bi bi-person"></i> My Profile</a>
                        
                        <div class="mobile-legal-links">
                            <a href="../privacy_policy.php"><i class="bi bi-shield-check"></i> Privacy Policy</a>
                            <a href="../terms_and_conditions.php"><i class="bi bi-file-text"></i> Terms & Conditions</a>
                            <a href="../refund_policy.php"><i class="bi bi-receipt"></i> Refund Policy</a>
                        </div>

                        <hr>
                        <a href="../api/user/logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Slidable Top Tabs -->
    </header>

    <!-- Slidable Top Tabs -->
    <div class="tabs-outer">
        <div class="tabs-scrollable" id="categoryTabs">
            <div class="category-tab active" data-target="home">
                <i class="bi bi-house-fill mobile-icon"></i>
                <span>Home</span>
            </div>
            <div class="category-tab" data-target="advertisements">
                <i class="bi bi-megaphone mobile-icon"></i>
                <span>Advertisements</span>
            </div>
            <div class="category-tab" data-target="properties">
                <i class="bi bi-houses mobile-icon"></i>
                <span>Properties</span>
            </div>
            <?php if (empty($_SESSION['hide_network_tab'])): ?>
            <div class="category-tab" data-target="my-network">
                <i class="bi bi-person mobile-icon"></i>
                <span>Network</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <main class="main-gallery">
        
        <?php if ($show_payment_block): ?>
        <!-- Payment Pending Blocker -->
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="data-card p-5 text-center" style="border-radius: 24px; background: #141417; border: 1px solid rgba(255,255,255,0.1);">
                    <div class="mb-4">
                        <?php if ($pending_registration_invoice['status'] === 'pending_verification'): ?>
                            <i class="bi bi-clock-history text-info" style="font-size: 4rem;"></i>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-white fw-bold mb-3">
                        <?php echo $pending_registration_invoice['status'] === 'pending_verification' ? 'Verification Pending' : 'Account on Hold'; ?>
                    </h2>
                    <p class="text-white-50 mb-4">
                        <?php 
                        echo $pending_registration_invoice['status'] === 'pending_verification' 
                            ? 'Your payment is being verified by the admin. Please wait a few hours to access the platform.' 
                            : 'Your account is currently on hold. Please complete the registration payment to access all features.'; 
                        ?>
                    </p>
                    <a href="payment.php?invoice_id=<?php echo $pending_registration_invoice['id']; ?>" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold">
                        <i class="bi <?php echo $pending_registration_invoice['status'] === 'pending_verification' ? 'bi-eye' : 'bi-credit-card'; ?> me-2"></i> 
                        <?php echo $pending_registration_invoice['status'] === 'pending_verification' ? 'View Status' : 'Pay Registration Fee'; ?>
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Dynamic Content Area -->
        <div id="landingContent" class="row g-4" style="margin-top: 20px;">

            <!-- Content will be injected by landing.js -->
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- Simplified Professional Footer -->
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
            <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden; background: #141417; color: #f8fafc;">
                <div class="modal-body p-5" id="modalDetailsContent">
                    <!-- Content injected by landing.js -->
                </div>
                <div class="modal-footer border-0 p-4 justify-content-center">
                    <button type="button" class="btn-primary" data-bs-dismiss="modal" style="padding: 10px 40px;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightboxModal" class="lightbox-modal" onclick="closeLightbox()">
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
            <div id="lightboxMediaContainer"></div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/toast.js"></script>
    <script src="js/landing.js"></script>
</body>
</html>
