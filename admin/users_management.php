<?php
$page_title = 'Users Management';
$header_title = 'Complete User Management & Audit';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2>User Management & Audit</h2>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <select id="userFilter" class="form-input py-1 px-2" style="font-size: 0.85rem; width:auto; min-width:200px;" onchange="loadUsers()">
                <option value="all">All Users</option>
                <option value="active">Active (Paid)</option>
                <option value="pending_verification">Pending Verification</option>
                <option value="pending">Pending Payment</option>
                <option value="no_invoice">No Invoice</option>
                <option value="banned">Banned</option>
            </select>
            <button class="btn-primary" onclick="loadUsers()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
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
                <span class="label">Active (Paid)</span>
                <span class="value" id="statActive">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending Verification</span>
                <span class="value" id="statPendingVerif">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                <i class="bi bi-hourglass-bottom"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending Payment</span>
                <span class="value" id="statPending">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-slash-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Banned</span>
                <span class="value" id="statBanned">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="usersTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email / Phone</th>
                    <th>PAN / Aadhaar</th>
                    <th>Referred By</th>
                    <th>Payment Status</th>
                    <th>Payment Method</th>
                    <th>Points</th>
                    <th>Shares</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="12" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading users...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Points Adjustment Modal -->
<div id="adjustPointsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Adjust Points</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustPointsForm">
                <input type="hidden" name="user_id" id="adjustUserId">
                <div class="modal-body">
                    <p class="mb-3">Adjusting points for <strong id="adjustUserName">User</strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="credit">Credit (Add Points)</option>
                            <option value="debit">Debit (Subtract Points)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Points Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" required placeholder="e.g. 500" min="1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Bonus for referral">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ban/Unban Modal -->
<div id="banModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="banModalTitle">Ban User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="banActionText" class="fw-bold">ban</span> this user?</p>
                <div class="p-3 bg-light rounded">
                    <div><strong id="banUserName">User Name</strong></div>
                    <div class="small text-muted">ID: <span id="banUserId">—</span> | Phone: <span id="banUserPhone">—</span></div>
                </div>
                <p class="mt-3 text-muted small" id="banWarning">They will not be able to access their account or make investments.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmBanBtn" onclick="confirmBan()">Ban User</button>
            </div>
        </div>
    </div>
</div>

<!-- Send to Payment Modal -->
<div id="sendPaymentModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Send to Payment Gateway</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>You are about to send this user to the <strong>payment gateway</strong> stage:</p>
                <div class="p-3 bg-light rounded">
                    <div><strong id="payUserName">User Name</strong></div>
                    <div class="small text-muted">ID: <span id="payUserId">—</span> | Phone: <span id="payUserPhone">—</span></div>
                </div>
                <div id="payPaidWarning" class="alert alert-warning small mt-3 mb-2" style="display:none;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>This user was previously approved manually without actual payment.</strong>
                    Their paid status will be revoked and registration points deducted. They must pay via the payment gateway to be re-activated.
                </div>
                <div class="alert alert-info small mt-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>What happens:</strong>
                    <ul class="mb-0 mt-1">
                        <li>A pending registration invoice will be created/reset for this user</li>
                        <li>On their next login, they will see the <strong>Cashfree payment gateway</strong></li>
                        <li>Once they pay, the invoice will be auto-confirmed</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSendPaymentBtn" onclick="confirmSendToPayment()">
                    <i class="bi bi-send me-1"></i> Send to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 2px solid #ef4444;">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Permanent Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>You are about to <strong style="color:#ef4444;">delete</strong> this user:</p>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin: 12px 0;">
                    <strong id="deleteUserName">—</strong><br>
                    <small class="text-muted">ID: <span id="deleteUserId">—</span> | Phone: <span id="deleteUserPhone">—</span></small>
                </div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    The user will be <strong>deactivated and blocked</strong> from logging in. All data (invoices, investments, referrals, shares) is <strong>preserved</strong> in the database.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                    <i class="bi bi-trash me-1"></i> Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-key-fill me-2"></i>Change User Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Changing password for: <strong id="cpUserNameUM"></strong></p>
                <input type="hidden" id="cpUserIdUM">
                <div class="mb-3">
                    <label class="form-label">New Password <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <input type="password" id="cpNewPasswordUM" class="form-input" placeholder="Min 6 characters" style="padding-right:42px;">
                        <button type="button" onclick="togglePw('cpNewPasswordUM', this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <input type="password" id="cpConfirmPasswordUM" class="form-input" placeholder="Repeat password" style="padding-right:42px;">
                        <button type="button" onclick="togglePw('cpConfirmPasswordUM', this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-primary px-4" onclick="submitChangePasswordUM()">Update Password</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let currentUserId = null;
let currentUserAction = null;
let banModal, deleteModal, adjustModal, sendPaymentModal, changePasswordModal;

document.addEventListener('DOMContentLoaded', function() {
    banModal            = new bootstrap.Modal(document.getElementById('banModal'));
    deleteModal         = new bootstrap.Modal(document.getElementById('deleteModal'));
    adjustModal         = new bootstrap.Modal(document.getElementById('adjustPointsModal'));
    changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    sendPaymentModal = new bootstrap.Modal(document.getElementById('sendPaymentModal'));
    
    document.getElementById('adjustPointsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await submitAdjustPoints();
    });

    loadUsers();
});

async function loadUsers() {
    const filter = document.getElementById('userFilter').value;
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="12" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

    if ($.fn.dataTable.isDataTable('#usersTable')) {
        $('#usersTable').DataTable().destroy();
    }

    try {
        const response = await fetch('../api/admin/users_management.php?filter=' + filter);
        const result = await response.json();

        if (result.success) {
            const stats = result.stats || {};
            document.getElementById('statTotal').textContent = stats.total || 0;
            document.getElementById('statActive').textContent = stats.active || 0;
            document.getElementById('statPendingVerif').textContent = stats.pending_verification || 0;
            document.getElementById('statPending').textContent = stats.pending || 0;
            document.getElementById('statBanned').textContent = stats.banned || 0;

            renderUsers(result.data || []);
        } else {
            tbody.innerHTML = `<tr><td colspan="12" class="text-center py-5 text-danger">${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="12" class="text-center py-5 text-danger">Server Error</td></tr>';
    }
}

function renderUsers(data) {
    const tbody = document.getElementById('usersTableBody');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="text-center py-5"><i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>No users found</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(user => {
        let paymentBadge = '';
        let statusBadge = '';

        // Payment status badge
        if (user.payment_status === 'paid') {
            paymentBadge = '<span class="badge bg-success">Paid</span>';
        } else if (user.payment_status === 'pending_verification') {
            paymentBadge = '<span class="badge bg-warning text-dark">Pending Review</span>';
        } else if (user.payment_status === 'pending') {
            paymentBadge = '<span class="badge bg-info text-white">Pending Payment</span>';
        } else {
            paymentBadge = '<span class="badge bg-secondary">No Invoice</span>';
        }

        // User status badge
        if (user.is_banned) {
            statusBadge = '<span class="badge bg-danger">Banned</span>';
        } else if (user.payment_status === 'paid') {
            statusBadge = '<span class="badge bg-success">Active</span>';
        } else {
            statusBadge = '<span class="badge bg-secondary">Inactive</span>';
        }

        // Referred by name
        let referredByText = user.referred_by_name ? `${user.referred_by_name} (#${user.referred_by})` : 'Direct';

        // Date
        const date = new Date(user.created_at);
        const dateStr = date.toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});

        // Actions
        const isPaid = user.payment_status === 'paid';
        const safeName = (user.full_name || '').replace(/'/g, "\\'");
        let actionBtns = `
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-outline-${isPaid ? 'warning' : 'primary'}" onclick="openSendPaymentModal(${user.id}, '${safeName}', '${user.phone}', ${isPaid})" title="${isPaid ? 'Revoke & Send to Payment' : 'Send to Payment Gateway'}">
                    <i class="bi bi-credit-card"></i>
                </button>
                <button class="btn btn-outline-secondary" onclick="openAdjustModal(${user.id}, '${user.full_name}', ${user.total_points})" title="Adjust Points">
                    <i class="bi bi-plus-circle"></i>
                </button>
                <button class="btn btn-outline-info" onclick="openChangePasswordModal(${user.id}, '${safeName}')" title="Change Password">
                    <i class="bi bi-key"></i>
                </button>
                <button class="btn btn-outline-${user.is_banned ? 'warning' : 'danger'}" onclick="openBanModal(${user.id}, '${safeName}', '${user.phone}', ${user.is_banned})" title="${user.is_banned ? 'Unban' : 'Ban'}">
                    <i class="bi bi-${user.is_banned ? 'arrow-counterclockwise' : 'lock'}"></i>
                </button>
                <button class="btn btn-outline-danger" onclick="openDeleteModal(${user.id}, '${safeName}', '${user.phone}')" title="Delete User">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

        return `
        <tr>
            <td class="fw-bold">#${user.id}</td>
            <td>
                <div class="fw-bold">${user.full_name}</div>
                <div class="small text-muted">${user.referral_code}</div>
            </td>
            <td>
                <div class="small">${user.email || '—'}</div>
                <div class="small fw-bold">${user.phone || '—'}</div>
            </td>
            <td>
                <div class="small"><code>${user.pan_number || '—'}</code></div>
                <div class="small text-muted">${user.aadhaar_number || '—'}</div>
            </td>
            <td class="small">${referredByText}</td>
            <td>${paymentBadge}</td>
            <td class="small">${user.payment_method || '—'}</td>
            <td class="fw-bold text-primary">${parseInt(user.total_points).toLocaleString()}</td>
            <td class="fw-bold text-success">${user.total_shares || 0}</td>
            <td>${statusBadge}</td>
            <td class="small">${dateStr}</td>
            <td>${actionBtns}</td>
        </tr>
        `;
    }).join('');

    $('#usersTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 20,
        lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]],
        order: [[10, 'desc']],
        columnDefs: [
            { orderable: false, targets: [11] }
        ]
    });
}

function openSendPaymentModal(userId, userName, userPhone, isPaid) {
    currentUserId = userId;
    document.getElementById('payUserName').textContent = userName;
    document.getElementById('payUserId').textContent = userId;
    document.getElementById('payUserPhone').textContent = userPhone;
    
    // Show/hide warning for already-paid users
    const warning = document.getElementById('payPaidWarning');
    const btn = document.getElementById('confirmSendPaymentBtn');
    if (isPaid) {
        warning.style.display = 'block';
        btn.className = 'btn btn-warning';
        btn.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Revoke & Send to Payment';
    } else {
        warning.style.display = 'none';
        btn.className = 'btn btn-primary';
        btn.innerHTML = '<i class="bi bi-send me-1"></i> Send to Payment';
    }
    sendPaymentModal.show();
}

async function confirmSendToPayment() {
    const btn = document.getElementById('confirmSendPaymentBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

    try {
        const response = await fetch('../api/admin/users_management.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'send_to_payment',
                user_id: currentUserId
            })
        });
        const result = await response.json();

        if (result.success) {
            sendPaymentModal.hide();
            await loadUsers();
            showToast.success(result.message);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Server error');
        console.error(error);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-1"></i> Send to Payment';
    }
}

function openAdjustModal(userId, userName, points) {
    currentUserId = userId;
    document.getElementById('adjustUserId').value = userId;
    document.getElementById('adjustUserName').textContent = userName + ` (${parseInt(points).toLocaleString()} pts)`;
    document.getElementById('adjustPointsForm').reset();
    adjustModal.show();
}

async function submitAdjustPoints() {
    const formData = new FormData(document.getElementById('adjustPointsForm'));
    const type = formData.get('type');
    const amount = parseInt(formData.get('amount'));
    const reason = formData.get('reason');
    const user_id = formData.get('user_id');

    if (!type || !amount || !reason) {
        alert('Please fill all fields');
        return;
    }

    try {
        const response = await fetch('../api/admin/users_management.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'adjust_points',
                user_id: user_id,
                type: type,
                amount: amount,
                reason: reason
            })
        });
        const result = await response.json();

        if (result.success) {
            adjustModal.hide();
            await loadUsers();
            showToast.success('Points adjusted successfully');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Server error');
        console.error(error);
    }
}

function openBanModal(userId, userName, userPhone, isBanned) {
    currentUserId = userId;
    const title = isBanned ? 'Unban User' : 'Ban User';
    const action = isBanned ? 'unban' : 'ban';
    const actionText = isBanned ? 'unban' : 'ban';

    document.getElementById('banModalTitle').textContent = title;
    document.getElementById('banActionText').textContent = actionText;
    document.getElementById('banUserName').textContent = userName;
    document.getElementById('banUserId').textContent = userId;
    document.getElementById('banUserPhone').textContent = userPhone;
    document.getElementById('confirmBanBtn').textContent = isBanned ? 'Unban User' : 'Ban User';
    document.getElementById('confirmBanBtn').className = isBanned ? 'btn btn-warning' : 'btn btn-danger';
    document.getElementById('banWarning').textContent = isBanned 
        ? 'They will regain access to their account.' 
        : 'They will not be able to access their account or make any transactions.';

    currentUserAction = action;
    banModal.show();
}

async function confirmBan() {
    try {
        const response = await fetch('../api/admin/users_management.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'toggle_ban',
                user_id: currentUserId
            })
        });
        const result = await response.json();

        if (result.success) {
            banModal.hide();
            await loadUsers();
            showToast.success(result.message);
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Server error');
    }
}

function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function openChangePasswordModal(userId, userName) {
    document.getElementById('cpUserIdUM').value = userId;
    document.getElementById('cpUserNameUM').textContent = userName;
    document.getElementById('cpNewPasswordUM').value = '';
    document.getElementById('cpConfirmPasswordUM').value = '';
    changePasswordModal.show();
}

async function submitChangePasswordUM() {
    const userId   = document.getElementById('cpUserIdUM').value;
    const password = document.getElementById('cpNewPasswordUM').value.trim();
    const confirm  = document.getElementById('cpConfirmPasswordUM').value.trim();
    if (password.length < 6) { showToast.error('Password must be at least 6 characters.'); return; }
    if (password !== confirm) { showToast.error('Passwords do not match.'); return; }
    try {
        const res  = await fetch('../api/admin/users_management.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_password', user_id: parseInt(userId), new_password: password })
        });
        const data = await res.json();
        if (data.success) {
            showToast.success(data.message);
            changePasswordModal.hide();
        } else {
            showToast.error(data.message);
        }
    } catch (e) {
        showToast.error('Server error. Please try again.');
    }
}

function openDeleteModal(userId, userName, userPhone) {
    currentUserId = userId;
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserId').textContent = userId;
    document.getElementById('deleteUserPhone').textContent = userPhone;
    deleteModal.show();
}

async function confirmDelete() {
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';

    try {
        const response = await fetch('../api/admin/users_management.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: currentUserId })
        });
        const result = await response.json();

        if (result.success) {
            deleteModal.hide();
            await loadUsers();
            showToast.success(result.message);
        } else {
            alert('Error: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Permanently';
        }
    } catch (error) {
        alert('Server error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Permanently';
    }
}
</script>
