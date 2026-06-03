<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punith Kumar | Portfolio</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <style>
        :root {
            --brand-primary: #D94589;
            --brand-secondary: #BD2D6B;
            --accent: #F969AA;
            --bg-main: #0a0a0b;
            --bg-soft: #141417;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --radius-xl: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Header */
        .portfolio-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #0a0a0b;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-dark);
        }

        .brand-logo img {
            height: 50px;
            width: auto;
            object-fit: contain;
        }

        .brand-logo h1 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Main Content */
        .portfolio-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }

        /* Profile Hero Card */
        .profile-hero {
            background: var(--bg-soft);
            border-radius: var(--radius-xl);
            padding: 48px 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            text-align: center;
            margin-bottom: 32px;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 3px solid rgba(217, 69, 137, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            overflow: hidden;
            position: relative;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo .placeholder-icon {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .profile-photo .photo-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            font-size: 0.6rem;
            padding: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent);
            font-weight: 700;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 4px;
            background: linear-gradient(135deg, #F969AA 0%, #D94589 50%, #BD2D6B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        /* Section Cards */
        .section-card {
            background: var(--bg-soft);
            border-radius: var(--radius-xl);
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 24px;
            transition: transform 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-3px);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 1.3rem;
        }

        /* Info Rows */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(217, 69, 137, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-row .icon-box i {
            color: var(--accent);
            font-size: 1.1rem;
        }

        .info-row .info-content {
            flex: 1;
        }

        .info-row .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 2px;
        }

        .info-row .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Experience Timeline */
        .timeline-item {
            position: relative;
            padding-left: 28px;
            padding-bottom: 24px;
            border-left: 2px solid rgba(217, 69, 137, 0.3);
        }

        .timeline-item:last-child {
            padding-bottom: 0;
            border-left-color: transparent;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--brand-primary);
            box-shadow: 0 0 10px rgba(217, 69, 137, 0.5);
        }

        .timeline-role {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .timeline-org {
            font-size: 0.85rem;
            color: var(--accent);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .timeline-duration {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Documents Section */
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        .doc-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .doc-card:hover {
            background: rgba(217, 69, 137, 0.05);
            border-color: rgba(217, 69, 137, 0.2);
            transform: translateY(-2px);
        }

        .doc-card .doc-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: rgba(217, 69, 137, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
        }

        .doc-card .doc-icon i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .doc-card .doc-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 16px;
        }

        .doc-card .doc-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .doc-card .doc-status {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Footer */
        .portfolio-footer {
            text-align: center;
            padding: 32px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .portfolio-main {
                padding: 24px 16px 60px;
            }

            .profile-hero {
                padding: 32px 20px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .profile-photo {
                width: 120px;
                height: 120px;
            }

            .section-card {
                padding: 24px 20px;
            }

            .doc-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .doc-card {
                padding: 16px 12px;
            }

            .nav-container {
                padding: 12px 16px;
            }
        }

        @media (max-width: 480px) {
            .profile-name {
                font-size: 1.3rem;
            }

            .doc-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="portfolio-header">
        <div class="nav-container">
            <a href="#" class="brand-logo">
                <img src="assets/images/logo.png" alt="Aalaya">
            </a>
            <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.15); color: var(--accent); font-weight: 700; font-size: 0.75rem;">
                <i class="bi bi-patch-check-fill me-1"></i> PORTFOLIO
            </span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="portfolio-main">

        <!-- Profile Hero -->
        <div class="profile-hero">
            <div class="profile-photo">
                <!-- Replace src with actual photo path e.g. "uploads/portfolio/profile_photo.jpg" -->
                <i class="bi bi-person-fill placeholder-icon"></i>
                <!-- <img src="uploads/portfolio/profile_photo.jpg" alt="Punith Kumar"> -->
                <span class="photo-label">Profile Photo</span>
            </div>
            <h1 class="profile-name">PUNITH KUMAR</h1>
            <p class="profile-subtitle">S/O KRISHNA BHANDARY</p>
            <p class="profile-subtitle" style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                <i class="bi bi-geo-alt-fill me-1"></i> Mangalore, Karnataka
            </p>
        </div>

        <div class="row g-4">

            <!-- Left Column -->
            <div class="col-lg-5">

                <!-- Personal Info -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-person-vcard"></i> Personal Information
                    </div>

                    <div class="info-row">
                        <div class="icon-box"><i class="bi bi-house-door"></i></div>
                        <div class="info-content">
                            <div class="info-label">Residential Address</div>
                            <div class="info-value">Shree Hari Krishna, Kudupu Village, Kelrai Road, Mangalore</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="icon-box"><i class="bi bi-mortarboard"></i></div>
                        <div class="info-content">
                            <div class="info-label">Qualification</div>
                            <div class="info-value">Diploma in Civil Engineering</div>
                            <div class="info-value" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Vivekananda Polytechnic, Puttur</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="icon-box"><i class="bi bi-book"></i></div>
                        <div class="info-content">
                            <div class="info-label">Legal Education</div>
                            <div class="info-value">2 Years Law Practice</div>
                            <div class="info-value" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Vivekananda Law College</div>
                        </div>
                    </div>
                </div>



            </div>

            <!-- Right Column -->
            <div class="col-lg-7">

                <!-- Work Experience -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-briefcase"></i> Work Experience
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-role">Contractor, Builder & Developer</div>
                        <div class="timeline-org">Civil Construction</div>
                        <div class="timeline-duration"><i class="bi bi-calendar3 me-1"></i> 15 Years Experience</div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-role">Team Leader – Personal Loan Section</div>
                        <div class="timeline-org">HDFC Bank</div>
                        <div class="timeline-duration"><i class="bi bi-calendar3 me-1"></i> Banking Sector</div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-role">Relationship Officer – Account Section</div>
                        <div class="timeline-org">ICICI Bank</div>
                        <div class="timeline-duration"><i class="bi bi-calendar3 me-1"></i> Since 2006</div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-role">Law Practitioner</div>
                        <div class="timeline-org">Vivekananda Law College</div>
                        <div class="timeline-duration"><i class="bi bi-calendar3 me-1"></i> 2 Years</div>
                    </div>
                </div>

                <!-- Skills & Expertise -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-lightning"></i> Skills & Expertise
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Civil Construction</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Property Development</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Building Contractor</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Banking & Finance</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Personal Loans</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Legal Knowledge</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Team Leadership</span>
                        <span class="badge rounded-pill px-3 py-2" style="background: rgba(217,69,137,0.1); color: var(--accent); font-weight: 600; font-size: 0.8rem;">Relationship Management</span>
                    </div>
                </div>

                <!-- Highlights -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-star"></i> Key Highlights
                    </div>
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);">15+</div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-top: 4px;">Years Construction</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);">2</div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-top: 4px;">Banks Served</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div style="background: rgba(255,255,255,0.03); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);">3+</div>
                                <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700; margin-top: 4px;">Domains</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="portfolio-footer">
        <p>&copy; <?php echo date('Y'); ?> Punith Kumar. All rights reserved.</p>
        <p style="margin-top: 8px; color: rgba(255,255,255,0.3); font-size: 0.7rem;">Powered by Aalaya</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
