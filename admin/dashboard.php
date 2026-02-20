<?php
$page_title = 'Admin Dashboard';
$header_title = 'Dashboard Overview';
require_once 'includes/header.php';
?>

<!-- Statistics Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue">
            <i class="bi bi-people"></i>
        </div>
        <div class="stat-info">
            <span class="label">Total Users</span>
            <span class="value" id="statUsers">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-green">
            <i class="bi bi-building"></i>
        </div>
        <div class="stat-info">
            <span class="label">Properties</span>
            <span class="value" id="statProperties">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-purple">
            <i class="bi bi-megaphone"></i>
        </div>
        <div class="stat-info">
            <span class="label">Active Ads</span>
            <span class="value" id="statAds">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon icon-orange">
            <i class="bi bi-chat-left-text"></i>
        </div>
        <div class="stat-info">
            <span class="label">Enquiries</span>
            <span class="value" id="statEnquiries">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="bi bi-currency-rupee"></i>
        </div>
        <div class="stat-info">
            <span class="label">Total Investments</span>
            <span class="value" id="statIncome">₹0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(249, 105, 170, 0.1); color: #F969AA;">
            <i class="bi bi-coin"></i>
        </div>
        <div class="stat-info">
            <span class="label">AALAYA POINTS</span>
            <span class="value" id="statPoints">0</span>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-lg-7">
        <div class="data-card p-4">
            <h2 class="mb-4">Income Trend (Last 30 Days)</h2>
            <canvas id="incomeChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="data-card p-4">
            <h2 class="mb-4">User Growth</h2>
            <canvas id="growthChart" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activity Table -->
<div class="data-card">
    <div class="card-header">
        <h2>Recent Enquiries</h2>
        <a href="enquiries.php" class="btn-link" style="text-decoration: none; color: var(--primary); font-weight: 600; font-size: 0.875rem;">View All</a>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                        No recent enquiries found
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/admin-dashboard.js"></script>
<?php require_once 'includes/footer.php'; ?>
