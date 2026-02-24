<?php
$page_title = 'Enquiry Management';
$header_title = 'User Enquiries';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>All Enquiries</h2>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-chat-left-dots"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Enquiries</span>
                <span class="value" id="statTotalEnquiries">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending</span>
                <span class="value" id="statPendingEnquiries">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <span class="label">In Progress</span>
                <span class="value" id="statProgressEnquiries">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Resolved</span>
                <span class="value" id="statResolvedEnquiries">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="enquiriesTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Reference</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="enquiriesTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading enquiries...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="js/admin-enquiries.js"></script>
<?php require_once 'includes/footer.php'; ?>
