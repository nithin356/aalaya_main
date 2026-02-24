<?php
require_once __DIR__ . '/../../includes/session.php';
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: index.html');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> | Aalaya</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- jQuery + DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/admin-style.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
</head>
<body class="admin-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/images/logo.png" alt="Aalaya">
            <h3>AALAYA</h3>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="users_management.php" class="nav-link <?php echo $current_page == 'users_management.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Users Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="payment_verifications.php" class="nav-link <?php echo $current_page == 'payment_verifications.php' ? 'active' : ''; ?>">
                    <i class="bi bi-shield-check"></i>
                    <span>Payment Verifications</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="network.php" class="nav-link <?php echo $current_page == 'network.php' ? 'active' : ''; ?>">
                    <i class="bi bi-diagram-3"></i>
                    <span>Network</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="investments.php" class="nav-link <?php echo $current_page == 'investments.php' ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Investments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="properties.php" class="nav-link <?php echo $current_page == 'properties.php' ? 'active' : ''; ?>">
                    <i class="bi bi-building-fill"></i>
                    <span>Properties</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="advertisements.php" class="nav-link <?php echo $current_page == 'advertisements.php' ? 'active' : ''; ?>">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Advertisements</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="enquiries.php" class="nav-link <?php echo $current_page == 'enquiries.php' ? 'active' : ''; ?>">
                    <i class="bi bi-chat-left-dots-fill"></i>
                    <span>Enquiries</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="bi bi-gear-fill"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="invoices-registration.php" class="nav-link <?php echo $current_page == 'invoices-registration.php' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Registration Invoices</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="invoices-investment.php" class="nav-link <?php echo $current_page == 'invoices-investment.php' ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Investment Invoices</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="bids.php" class="nav-link <?php echo $current_page == 'bids.php' ? 'active' : ''; ?>">
                    <i class="bi bi-hammer"></i>
                    <span>Property Bids</span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: auto;">
                <a href="../api/admin/logout.php" class="nav-link logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="content-wrapper">
        <header class="top-bar">
            <div class="header-left">
                <button id="sidebarToggle" class="mobile-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="welcome-text">
                    <h1><?php echo $header_title ?? 'Dashboard Overview'; ?></h1>
                    <p class="d-none d-md-block"><?php echo date('l, d F Y'); ?></p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="admin-global-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="adminGlobalSearch" placeholder="Search current page..." autocomplete="off">
                </div>
                <div class="admin-profile">
                    <div class="profile-info">
                        <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_full_name'] ?? 'Admin'); ?></span>
                        <span class="admin-role">System Admin</span>
                    </div>
                    <div class="profile-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                </div>
            </div>
        </header>

