<?php
$page_title = 'User Management';
$header_title = 'User Management';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>User Management</h2>
        <button class="btn-primary" onclick="loadUsers()" style="margin-left: auto;">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Users</span>
                <span class="value" id="statTotalUsers">0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Verified</span>
                <span class="value" id="statVerified">0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending</span>
                <span class="value" id="statPending">0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-ban"></i>
            </div>
            <div class="stat-info">
                <span class="label">Banned</span>
                <span class="value" id="statBanned">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="usersTable" class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>PAN</th>
                    <th>Aadhaar</th>
                    <th>Points</th>
                    <th>Shares</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        Loading users...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Points Adjustment Modal -->
<div id="adjustPointsModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Adjust Points</h2>
            <button class="btn-icon-close" onclick="closeAdjustModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form id="adjustPointsForm">
            <input type="hidden" name="user_id" id="adjustUserId">
            <div class="modal-body">
                <p class="mb-3 text-muted">Adjusting points for <strong id="adjustUserName">User</strong></p>
                
                <div class="form-group">
                    <label class="form-label">Adjustment Type</label>
                    <select name="type" class="form-input" required>
                        <option value="credit">Credit (Add)</option>
                        <option value="debit">Debit (Subtract)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Points Amount</label>
                    <input type="number" name="amount" class="form-input" required placeholder="e.g. 500" min="1">
                </div>

                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-input" placeholder="e.g. Bonus points">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn-primary">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script src="js/admin-users.js"></script>
<?php require_once 'includes/footer.php'; ?>
