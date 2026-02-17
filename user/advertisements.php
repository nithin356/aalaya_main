<?php
require_once '../includes/user_auth.php';
$pdo = getDB();

$ad_stmt = $pdo->query("SELECT * FROM advertisements ORDER BY created_at DESC");
$ads = $ad_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Campaigns | Aalaya</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/landing.css">
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
        <div class="mb-5">
            <h2 style="font-weight: 800; font-size: 2rem; letter-spacing: -0.02em;">Active Campaigns</h2>
            <p class="text-muted">Explore exclusive advertisements and rewards from our trusted network.</p>
        </div>


        <div class="row g-4">
            <?php if (empty($ads)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No active campaigns at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($ads as $ad): ?>
                    <div class="col-12">
                        <div class="property-card" style="display: flex; align-items: center; min-height: 200px;">
                            <div class="card-img-wrapper" style="width: 300px; aspect-ratio: 16/9; flex-shrink: 0;">
                                <img src="../<?php echo !empty($ad['image_path']) ? htmlspecialchars($ad['image_path']) : 'assets/images/logo-placeholder.png'; ?>" alt="Banner">
                            </div>
                            <div class="card-body">
                                <span class="text-primary fw-bold small text-uppercase"><?php echo htmlspecialchars($ad['ad_type']); ?></span>
                                <h3 class="card-title mt-2"><?php echo htmlspecialchars($ad['title']); ?></h3>
                                <p class="text-muted small"><?php echo htmlspecialchars($ad['company_name']); ?></p>
                                <button class="btn-primary btn-enquire mt-3" 
                                        data-type="advertisement" 
                                        data-id="<?php echo $ad['id']; ?>" 
                                        data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                                        style="font-size: 0.8rem;">Learn More</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
