<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../includes/db.php';
$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Fetch User Data, Stats & Recent Investments
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Investments
$invStmt = $pdo->prepare("SELECT * FROM investments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$invStmt->execute([$user_id]);
$investments = $invStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Referral Network Count
$netStmt = $pdo->prepare("SELECT COUNT(*) FROM referral_transactions WHERE user_id = ?");
$netStmt->execute([$user_id]);
$network_count = $netStmt->fetchColumn();

// Total Investment & Points (from user table directly for speed)
$total_investment = $user['total_investment_amount'] ?? 0;
$total_points = $user['total_points'] ?? 0;
$total_shares = $user['total_shares'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Aalaya</title>
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS (Reusing Landing styles for consistency) -->
    <link rel="stylesheet" href="css/landing.css">
    <style>
        /* Specific Profile Overrides */
        .profile-header-card {
            background: #141417;
            color: white;
            border-radius: 24px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        .profile-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            border: 2px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }

        .stat-box {
            background: #141417;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,0.05);
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05);
        }

        .points-badge {
            background: linear-gradient(135deg, #F969AA 0%, #D94589 50%, #BD2D6B 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(217, 69, 137, 0.4);
            border: none;
        }

        .info-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #f8fafc;
            word-break: break-all;
        }

        .btn-brand-pink {
            background: linear-gradient(135deg, #F969AA 0%, #D94589 50%, #BD2D6B 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(217, 69, 137, 0.4);
            transition: all 0.3s ease;
        }

        .btn-brand-pink:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 45, 136, 0.5);
            color: white;
            filter: brightness(1.1);
        }
        @media (max-width: 991px) {
            .stat-box h3 { font-size: 1rem; }
            .stat-box .points-badge { font-size: 0.8rem; padding: 6px 12px; }
            .display-6 { font-size: 1.5rem !important; }
        }
</style>
</head>
<body style="background: #0a0a0b; color: #f8fafc;">

    <!-- Reusing Premium Header -->
    <header class="landing-header">
        <div class="nav-container">
            <a href="dashboard.php" class="brand-logo">
                <img src="../assets/images/logo.png" alt="Aalaya" style="height: 80px; width: auto; object-fit: contain;">
                <!--<h1>AALAYA</h1>-->
            </a>
            <div class="user-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-bold" style="border:1px solid rgba(255,255,255,0.1); color: #e2e8f0;">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="main-gallery">
        <div class="row g-4">
            
            <!-- Left Col: Profile & Info -->
            <div class="col-lg-4">
                <div class="profile-header-card mb-4">
                    <div class="profile-avatar-large">
                        <i class="bi bi-person"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1" style="word-break: break-word;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="opacity-75 mb-4">Verified Investor</p>
                    
                    <div class="d-flex flex-column gap-3">
                        <div style="background: rgba(255,255,255,0.03); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <span class="d-block text-white-50 small text-uppercase fw-bold">Email</span>
                            <div id="emailDisplay">
                                <span class="d-block text-white fw-medium text-break"><?php echo htmlspecialchars($user['email']); ?></span>
                                <button class="btn btn-sm btn-outline-light mt-2 rounded-pill px-3" onclick="showEmailEdit()">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                            </div>
                            <div id="emailEditForm" style="display: none;">
                                <input type="email" id="newEmail" class="form-control bg-dark border-secondary text-white mb-2" value="<?php echo htmlspecialchars($user['email']); ?>">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="saveEmail()">Save</button>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="cancelEmailEdit()">Cancel</button>
                                </div>
                            </div>
                        </div>
                        <div style="background: rgba(255,255,255,0.03); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <span class="d-block text-white-50 small text-uppercase fw-bold">Phone</span>
                            <span class="d-block text-white fw-medium"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top border-white border-opacity-10">
                        <a href="../api/user/logout.php" class="btn btn-brand-pink w-100 rounded-pill fw-bold py-3">
                            <i class="bi bi-box-arrow-right"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>

            <?php /* Commission stats and investment history */ ?>
            <div class="col-lg-8">
                <!-- Points & Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4 text-center">
                        <div class="stat-box">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="points-badge">
                                    <i class="bi bi-gem"></i> <?php echo number_format($total_shares); ?> Shares
                                </span>
                            </div>
                            <h3 class="fw-bold text-white h5 mb-1">Earned Shares</h3>
                            <p class="text-white-50 small mb-0">Converted from reward points.</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="stat-box">
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="points-badge">
                                    <i class="bi bi-star-fill"></i> <?php echo number_format($total_points); ?> Points
                                </span>
                            </div>
                            <h3 class="fw-bold text-white h5 mb-1">Referral Rewards</h3>
                            <p class="text-white-50 small mb-0">Earned when your referrals invest.</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="stat-box d-flex flex-column align-items-center justify-content-center">
                            <h2 class="display-6 fw-bold mb-0 lotus-gradient-text" style="font-size: 1.8rem;">₹<?php echo number_format($total_investment); ?></h2>
                            <p class="text-white opacity-50 fw-bold mt-2 mb-0 small">Total Investment Volume</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Investment History -->
                <div class="card mt-5 border-0 shadow-lg rounded-4 overflow-hidden" style="background: #141417;">
                    <div class="card-header p-4 border-bottom border-white border-opacity-10" style="background: rgba(255,255,255,0.02);">
                        <h4 class="mb-0 fw-bold text-white">Recent Investment History</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">Date</th>
                                    <th>Amount</th>
                                    <th>Points Earned</th>
                                    <th class="pe-4 text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($investments) > 0): ?>
                                    <?php foreach ($investments as $inv): ?>
                                    <tr>
                                        <td class="ps-4 text-white-50 fw-bold">
                                            <?php echo date('M d, Y', strtotime($inv['created_at'])); ?>
                                        </td>
                                        <td class="fw-bold">₹<?php echo number_format($inv['amount']); ?></td>
                                        <td>
                                            <?php if ($inv['points_earned'] > 0): ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-3">
                                                    <i class="bi bi-star-fill"></i> +<?php echo $inv['points_earned']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                                Completed
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-white-50">
                                            <i class="bi bi-wallet2 display-4 d-block mb-3 opacity-25"></i>
                                            No investments recorded yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- <div class="mt-4 text-center">
                    <p class="text-muted small">Need help? <a href="#" class="text-primary text-decoration-none fw-bold">Contact Support</a></p>
                </div> -->
            </div>
            </div>
            <?php /* END Commission stats */ ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../assets/css/toast.css">
    <script src="../assets/js/toast.js"></script>
    <script>
        function showEmailEdit() {
            document.getElementById('emailDisplay').style.display = 'none';
            document.getElementById('emailEditForm').style.display = 'block';
        }

        function cancelEmailEdit() {
            document.getElementById('emailDisplay').style.display = 'block';
            document.getElementById('emailEditForm').style.display = 'none';
        }

        async function saveEmail() {
            const newEmail = document.getElementById('newEmail').value.trim();
            if (!newEmail) {
                showToast.error('Please enter a valid email.');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('email', newEmail);
                const response = await fetch('../api/user/update_email.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    showToast.success(result.message);
                    // Update display
                    document.querySelector('#emailDisplay span').textContent = newEmail;
                    cancelEmailEdit();
                } else {
                    showToast.error(result.message);
                }
            } catch (err) {
                showToast.error('Failed to update email.');
            }
        }
    </script>
</body>
</html>
