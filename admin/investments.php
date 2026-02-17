<?php
$page_title = 'Investment Management';
$header_title = 'Investment Management';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>User Investments</h2>
        <button class="btn-primary" onclick="openModal()">
            <i class="bi bi-plus-lg"></i> Add Investment
        </button>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Amount (₹)</th>
                    <th>Points Earned</th>
                    <th>Date</th>
                    <th>Admin</th>
                </tr>
            </thead>
            <tbody id="investmentsTableBody">
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading investments...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Investment Modal -->
<div id="investmentModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Add New Investment</h2>
            <button class="btn-icon-close" onclick="closeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form id="investmentForm">
            <div class="modal-body">
                
                <div class="form-group">
                    <label class="form-label">Select User *</label>
                    <select id="userSelect" name="user_id" class="form-input" required>
                        <option value="">Loading users...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Investment Amount (₹) *</label>
                    <input type="number" name="amount" class="form-input" required placeholder="e.g. 124511" min="1" step="0.01">
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle"></i> Every <strong>₹<span id="thresholdDisplay">124,511</span></strong> earns <strong>1 Point</strong>.
                    </small>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Add Investment</button>
            </div>
        </form>
    </div>
</div>

<script src="js/admin-investments.js"></script>
<?php require_once 'includes/footer.php'; ?>
