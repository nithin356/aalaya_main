<?php
$page_title = 'User Audit';
$header_title = 'User Audit & Cleanup';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>User Registration & Payment Audit</h2>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Users</span>
                <span class="value" id="statTotal">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Paid</span>
                <span class="value" id="statPaid">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending Verification</span>
                <span class="value" id="statPendingVerify">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Not Paid</span>
                <span class="value" id="statNotPaid">0</span>
            </div>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="d-flex gap-2 mb-3 flex-wrap" style="padding: 0 20px;">
        <button class="btn btn-sm btn-outline-secondary active" onclick="filterTable('all', this)">All</button>
        <button class="btn btn-sm btn-outline-success" onclick="filterTable('paid', this)">Paid</button>
        <button class="btn btn-sm btn-outline-warning" onclick="filterTable('pending_verification', this)">Pending Verification</button>
        <button class="btn btn-sm btn-outline-danger" onclick="filterTable('pending', this)">Pending Payment</button>
        <button class="btn btn-sm btn-outline-dark" onclick="filterTable('no_invoice', this)">No Invoice</button>
    </div>
    
    <div class="table-responsive">
        <table id="auditTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Referred By</th>
                    <th>Payment Status</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                    <th>Points</th>
                    <th>Shares</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="auditTableBody">
                <tr>
                    <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-card" style="max-width: 450px;">
        <div class="modal-header">
            <h2 style="color: #ef4444;"><i class="bi bi-exclamation-triangle me-2"></i>Permanent Delete</h2>
            <button class="btn-icon-close" onclick="closeDeleteModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>You are about to <strong style="color:#ef4444;">permanently delete</strong> user:</p>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin: 12px 0;">
                <strong id="deleteUserName">—</strong><br>
                <small class="text-muted">ID: <span id="deleteUserId">—</span> | Phone: <span id="deleteUserPhone">—</span></small>
            </div>
            <p class="text-muted" style="font-size: 13px;">
                This will permanently remove the user and <b>all</b> their related data (invoices, transactions, referrals, investments, bids, enquiries).
                <br><br>
                <strong>This action cannot be undone.</strong>
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-ghost" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                <i class="bi bi-trash me-1"></i> Delete Permanently
            </button>
        </div>
    </div>
</div>

<script src="js/admin-user-audit.js"></script>
<?php require_once 'includes/footer.php'; ?>
