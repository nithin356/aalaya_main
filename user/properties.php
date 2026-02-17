<?php
require_once '../includes/user_auth.php';
$pdo = getDB();

$prop_stmt = $pdo->query("SELECT * FROM properties WHERE status = 'available' ORDER BY created_at DESC");
$properties = $prop_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties | Aalaya</title>
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
                    <img src="../assets/images/logo.png" alt="Aalaya" style="height: 80px; width: auto; object-fit: contain;">
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
            <h2 style="font-weight: 800; font-size: 2rem; letter-spacing: -0.02em;">Luxury Real Estate</h2>
            <p class="text-muted">Discover premium available properties verified by Aalaya.</p>
        </div>


        <div class="row g-4">
            <?php if (empty($properties)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No properties available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($properties as $prop): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="property-card">
                            <div class="card-img-wrapper">
                                <img src="../<?php echo !empty($prop['image_path']) ? htmlspecialchars($prop['image_path']) : 'assets/images/logo-placeholder.png'; ?>" alt="Property">
                                <span class="badge-tag"><?php echo htmlspecialchars($prop['property_type']); ?></span>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($prop['title']); ?></h3>
                                <div class="card-meta">
                                    <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($prop['location']); ?></span>
                                    <span><i class="bi bi-tag"></i> Available</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="card-price">₹<?php echo number_format($prop['price']); ?></span>
                                    <button class="btn-primary btn-enquire" 
                                            data-type="property" 
                                            data-id="<?php echo $prop['id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($prop['title']); ?>"
                                            style="font-size: 0.8rem;">Enquire</button>
                                </div>
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

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/landing.js"></script>
</body>
</html>
