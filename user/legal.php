<?php
session_start();
$type = $_GET['t'] ?? 'privacy';
$title = ($type === 'terms') ? 'Terms & Conditions' : 'Privacy Policy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | Aalaya</title>
    <link rel="stylesheet" href="css/landing.css">
    <style>
        .legal-content {
            max-width: 800px;
            margin: 60px auto;
            padding: 40px;
            background: #141417;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .legal-content h1 { margin-bottom: 24px; font-weight: 800; }
        .legal-content p { margin-bottom: 16px; color: var(--text-muted); }
    </style>
</head>
<body style="background: #0a0a0b; color: #f8fafc;">

    <header class="landing-header">
        <div class="nav-container">
            <a href="dashboard.php" class="brand-logo">
                <img src="../assets/images/logo.png" alt="Aalaya" style="height: 45px;">
                <!--<h1>AALAYA</h1>-->
            </a>
        </div>
    </header>

    <main class="legal-content">
        <h1><?php echo $title; ?></h1>
        <p>Last Updated: January 2026</p>
        <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.1); margin: 24px 0;">
        
        <p>This is a placeholder for the official <?php echo strtolower($title); ?> of Aalaya. As a user-centric platform, we prioritize your security and transparency in all property and advertisement transactions.</p>
        
        <h3>1. Information Collection</h3>
        <p>We collect information strictly through secure DigiLocker integrations and user-provided details to facilitate transparent real estate networking.</p>

        <h3>2. Usage Policy</h3>
        <p>All listings and referral points are monitored for security. Any fraudulent activity may lead to account suspension as managed by the system administrator.</p>
        
        <a href="dashboard.php" class="btn-primary" style="text-decoration:none; margin-top: 32px; display:inline-block; width:auto;">Back to Explore</a>
    </main>

</body>
</html>
