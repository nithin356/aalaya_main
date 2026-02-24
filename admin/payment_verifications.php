<?php
$page_title = 'Payment Verifications';
$header_title = 'Payment Verifications & Audit';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2>Payment Verifications</h2>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <select id="verificationFilter" class="form-input py-1 px-2" style="font-size: 0.85rem; width:auto; min-width:180px;" onchange="loadVerifications()">
                <option value="pending_verification">Pending Review</option>
                <option value="cashfree_pending">Cashfree Pending (Reconcile)</option>
                <option value="all">All Records</option>
                <option value="paid">Approved</option>
                <option value="pending">Rejected / Pending</option>
            </select>
            <button class="btn-primary" onclick="loadVerifications()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="openAuditLogModal()" title="View full audit log">
                <i class="bi bi-journal-text"></i> Audit Log
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Invoices</span>
                <span class="value" id="statTotalVerif">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending Review</span>
                <span class="value" id="statPendingVerif">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Approved</span>
                <span class="value" id="statApprovedVerif">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Rejected</span>
                <span class="value" id="statRejectedVerif">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                <i class="bi bi-credit-card-2-back"></i>
            </div>
            <div class="stat-info">
                <span class="label">Cashfree Pending</span>
                <span class="value" id="statCashfreePending">0</span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="verificationsTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment / UTR ID</th>
                    <th>Screenshot</th>
                    <th>Status</th>
                    <th>Admin Comment</th>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="verificationsTableBody">
            </tbody>
        </table>
    </div>
</div>

<!-- Approval/Rejection Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirm Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalBodyText">Are you sure you want to approve this payment?</p>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">User:</span>
                        <strong id="modalUserName">--</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Amount:</span>
                        <strong id="modalAmount">--</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Method:</span>
                        <strong id="modalPaymentMethod">--</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">UTR / Order ID:</span>
                        <strong id="modalUTR" class="text-primary">--</strong>
                    </div>
                </div>

                <div id="modalScreenshotContainer" style="display:none;">
                    <p class="small fw-bold text-uppercase ls-1 text-muted mb-2">Payment Screenshot:</p>
                    <a id="modalScreenshotLink" href="#" target="_blank">
                        <img id="modalScreenshotImg" src="#" class="img-fluid rounded border shadow-sm w-100" style="max-height: 300px; object-fit: contain; background: #f8f9fa;">
                    </a>
                </div>

                <div id="adminCommentContainer" class="mt-3" style="display:none;">
                    <label class="form-label fw-bold text-danger"><i class="bi bi-chat-left-text me-1"></i> Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="adminComment" class="form-control" rows="3" placeholder="Enter reason for rejection (required)..." maxlength="500"></textarea>
                    <small class="text-muted">This will be logged in the audit trail.</small>
                </div>

                <div id="approvalCommentContainer" class="mt-3" style="display:none;">
                    <label class="form-label fw-bold text-muted"><i class="bi bi-chat-left-text me-1"></i> Admin Note (optional)</label>
                    <textarea id="approvalComment" class="form-control" rows="2" placeholder="Optional note..." maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBtn" onclick="processAction()">Confirm Approval</button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Audit Log Modal -->
<div class="modal fade" id="auditLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-journal-text me-2"></i> <span id="auditLogTitle">Invoice Audit Log</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button class="btn btn-sm btn-outline-secondary active" onclick="loadAuditLog('')" data-filter="">All</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="loadAuditLog('rejected')" data-filter="rejected">Rejections</button>
                    <button class="btn btn-sm btn-outline-success" onclick="loadAuditLog('approved')" data-filter="approved">Approvals</button>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadAuditLog('reconciled')" data-filter="reconciled">Reconciled</button>
                    <button class="btn btn-sm btn-outline-info" onclick="loadAuditLog('webhook_confirmed')" data-filter="webhook_confirmed">Webhook</button>
                </div>
                <div id="auditLogContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 mb-0">Loading audit log...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reconcile Confirmation Modal -->
<div class="modal fade" id="reconcileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i> Reconcile Cashfree Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will check Cashfree API for the actual payment status and update the invoice if payment was successful.</p>
                <div class="p-3 bg-light rounded border mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Invoice:</span>
                        <strong id="reconcileInvoiceId">--</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">User:</span>
                        <strong id="reconcileUserName">--</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Cashfree Order:</span>
                        <code id="reconcileOrderId" class="text-primary">--</code>
                    </div>
                </div>
                <div id="reconcileResult" style="display:none;" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="reconcileBtn" onclick="doReconcile()">
                    <i class="bi bi-arrow-repeat me-1"></i> Check Cashfree
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let currentInvoice = null;
let currentAction = null;
let reconcileInvoiceId = null;

async function loadVerifications() {
    const filter = document.getElementById('verificationFilter').value;
    const tbody = document.getElementById('verificationsTableBody');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">Loading...</p></td></tr>';

    if ($.fn.dataTable.isDataTable('#verificationsTable')) {
        $('#verificationsTable').DataTable().destroy();
    }

    try {
        const response = await fetch('../api/admin/get_pending_verifications.php?filter=' + filter);
        const result = await response.json();

        if (result.success) {
            const stats = result.stats || {};
            const el = (id, val) => { const e = document.getElementById(id); if(e) e.textContent = val; };
            el('statTotalVerif', stats.total || 0);
            el('statPendingVerif', stats.pending || 0);
            el('statApprovedVerif', stats.approved || 0);
            el('statRejectedVerif', stats.rejected || 0);
            el('statCashfreePending', stats.cashfree_pending || 0);

            renderVerifications(result.data || [], filter);
        } else {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-danger">${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-danger">Server Error. Please try again later.</td></tr>';
    }
}

function renderVerifications(data, filter) {
    const tbody = document.getElementById('verificationsTableBody');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5"><i class="bi bi-check-circle text-success fs-1"></i><p class="mt-2 mb-0">No records found for this filter.</p></td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => {
        let statusBadge = '';
        let actionBtns = '';
        const status = item.status || 'pending';
        
        switch(status) {
            case 'paid':
                statusBadge = '<span class="status-badge status-resolved">Approved</span>';
                actionBtns = `<div class="d-flex justify-content-end gap-1">
                    <button class="btn-action btn-action-view" onclick="showInvoiceHistory(${item.id})" title="View History">
                        <i class="bi bi-journal-text"></i>
                    </button>
                </div>`;
                break;
            case 'pending':
                if (item.admin_comment && item.admin_comment.trim()) {
                    statusBadge = '<span class="status-badge status-danger">Rejected</span>';
                } else if (item.payment_id && item.payment_method === 'cashfree') {
                    statusBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-credit-card me-1"></i>CF Pending</span>';
                } else {
                    statusBadge = '<span class="status-badge status-pending">Pending</span>';
                }
                // Show reconcile for Cashfree pending, and history for rejected
                actionBtns = `<div class="d-flex justify-content-end gap-1">`;
                if (item.payment_id && item.payment_method === 'cashfree') {
                    actionBtns += `<button class="btn btn-sm btn-outline-primary" onclick="openReconcileModal(${item.id}, '${(item.full_name||'').replace(/'/g,"\\'")}', '${item.payment_id || ''}')" title="Reconcile with Cashfree">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>`;
                }
                actionBtns += `<button class="btn-action btn-action-view" onclick="showInvoiceHistory(${item.id})" title="View History">
                        <i class="bi bi-journal-text"></i>
                    </button>
                </div>`;
                break;
            default: // pending_verification
                statusBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending Review</span>';
                actionBtns = `
                    <div class="d-flex justify-content-end gap-1">
                        <button class="btn-action btn-action-view" onclick="openConfirmModal(${item.id}, 'approve', '${(item.full_name||'').replace(/'/g,"\\'")}', '${item.amount}', '${item.manual_utr_id || ''}', '${item.manual_payment_screenshot || ''}', '${item.payment_method || ''}')" title="Approve Payment">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button class="btn-action btn-action-danger" onclick="openConfirmModal(${item.id}, 'reject', '${(item.full_name||'').replace(/'/g,"\\'")}', '${item.amount}', '${item.manual_utr_id || ''}', '${item.manual_payment_screenshot || ''}', '${item.payment_method || ''}')" title="Reject Payment">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <button class="btn-action btn-action-view" onclick="showInvoiceHistory(${item.id})" title="View History">
                            <i class="bi bi-journal-text"></i>
                        </button>
                    </div>`;
        }

        // Payment method badge
        const methodBadge = item.payment_method === 'cashfree' 
            ? '<span class="badge bg-info text-white"><i class="bi bi-credit-card me-1"></i>Cashfree</span>'
            : item.payment_method === 'manual'
                ? '<span class="badge bg-secondary"><i class="bi bi-bank me-1"></i>Manual</span>'
                : '<span class="badge bg-light text-dark">-</span>';

        // Payment/UTR display
        let paymentIdDisplay = '-';
        if (item.payment_id) {
            paymentIdDisplay = `<code style="background: #f0f7ff; color: #0066cc; padding: 2px 4px; border-radius: 4px; font-size: 0.75rem; word-break: break-all;">${item.payment_id}</code>`;
        }
        if (item.manual_utr_id && item.manual_utr_id !== item.payment_id) {
            paymentIdDisplay += `<br><small class="text-muted">UTR: ${item.manual_utr_id}</small>`;
        }

        // Admin comment display
        let commentDisplay = '-';
        if (item.admin_comment) {
            const truncated = item.admin_comment.length > 30 ? item.admin_comment.substring(0, 30) + '...' : item.admin_comment;
            commentDisplay = `<span class="text-muted small" title="${item.admin_comment.replace(/"/g, '&quot;')}">${truncated}</span>`;
        }

        return `
        <tr>
            <td class="fw-bold">#${item.id}</td>
            <td>
                <div class="fw-bold">${item.full_name || '-'}</div>
                <div class="small text-muted">${item.phone || ''}</div>
                <div class="small text-muted">${item.email || ''}</div>
            </td>
            <td class="fw-bold text-primary">₹${parseFloat(item.amount).toLocaleString()}</td>
            <td>${methodBadge}</td>
            <td>${paymentIdDisplay}</td>
            <td>
                ${item.manual_payment_screenshot ? `
                    <a href="../${item.manual_payment_screenshot}" target="_blank" class="text-decoration-none">
                        <img src="../${item.manual_payment_screenshot}" class="img-thumbnail" style="height: 40px; cursor: pointer;" title="Click to view full image">
                    </a>
                ` : '<span class="text-muted small">-</span>'}
            </td>
            <td>${statusBadge}</td>
            <td>${commentDisplay}</td>
            <td data-order="${item.updated_at || item.created_at}">
                <div class="small">${new Date(item.updated_at || item.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})}</div>
                <div class="small text-muted">${new Date(item.updated_at || item.created_at).toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit'})}</div>
            </td>
            <td>${actionBtns}</td>
        </tr>
    `}).join('');

    if ($.fn.dataTable.isDataTable('#verificationsTable')) {
        $('#verificationsTable').DataTable().destroy();
    }
    $('#verificationsTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 15,
        lengthMenu: [[15, 25, 50, -1], [15, 25, 50, "All"]],
        order: [[8, 'desc']],
        language: {
            emptyTable: '<div class="text-center py-4"><i class="bi bi-shield-check fs-2 d-block mb-2 text-muted"></i>No records found</div>',
            zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
        },
        columnDefs: [
            { orderable: false, targets: [4, 5, 9] }
        ]
    });
}

function openConfirmModal(id, action, name, amount, utr, screenshot, paymentMethod) {
    currentInvoice = id;
    currentAction = action;
    
    document.getElementById('modalUserName').textContent = name;
    document.getElementById('modalAmount').textContent = '₹' + parseFloat(amount).toLocaleString();
    document.getElementById('modalPaymentMethod').textContent = paymentMethod || 'N/A';
    document.getElementById('modalUTR').textContent = utr || 'N/A';

    const ssContainer = document.getElementById('modalScreenshotContainer');
    if (screenshot) {
        document.getElementById('modalScreenshotImg').src = '../' + screenshot;
        document.getElementById('modalScreenshotLink').href = '../' + screenshot;
        ssContainer.style.display = 'block';
    } else {
        ssContainer.style.display = 'none';
    }

    if (action === 'approve') {
        document.getElementById('modalTitle').textContent = 'Confirm Approval';
        document.getElementById('modalBodyText').textContent = 'Are you sure you want to approve this payment and activate the user registration?';
        document.getElementById('confirmBtn').className = 'btn btn-primary';
        document.getElementById('confirmBtn').textContent = 'Confirm Approval';
        document.getElementById('adminCommentContainer').style.display = 'none';
        document.getElementById('approvalCommentContainer').style.display = 'block';
        document.getElementById('adminComment').value = '';
        document.getElementById('approvalComment').value = '';
    } else {
        document.getElementById('modalTitle').textContent = 'Confirm Rejection';
        document.getElementById('modalBodyText').textContent = 'Are you sure you want to reject this payment? The reason will be permanently logged in the audit trail.';
        document.getElementById('confirmBtn').className = 'btn btn-danger';
        document.getElementById('confirmBtn').textContent = 'Confirm Rejection';
        document.getElementById('adminCommentContainer').style.display = 'block';
        document.getElementById('approvalCommentContainer').style.display = 'none';
        document.getElementById('adminComment').value = '';
        document.getElementById('approvalComment').value = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

async function processAction() {
    const btn = document.getElementById('confirmBtn');
    const originalText = btn.textContent;

    if (currentAction === 'reject') {
        const comment = document.getElementById('adminComment').value.trim();
        if (!comment) {
            alert('Please provide a reason for rejection. This is mandatory.');
            document.getElementById('adminComment').focus();
            return;
        }
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';

    try {
        const formData = new FormData();
        formData.append('invoice_id', currentInvoice);
        formData.append('action', currentAction);

        if (currentAction === 'reject') {
            formData.append('admin_comment', document.getElementById('adminComment').value.trim());
        } else {
            formData.append('admin_comment', document.getElementById('approvalComment').value.trim());
        }

        const response = await fetch('../api/admin/verify_manual_payment.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            await loadVerifications();
            showToast.success(result.message);
        } else {
            alert(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Server Error.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ============ Reconcile ============
function openReconcileModal(invoiceId, userName, orderId) {
    reconcileInvoiceId = invoiceId;
    document.getElementById('reconcileInvoiceId').textContent = '#' + invoiceId;
    document.getElementById('reconcileUserName').textContent = userName;
    document.getElementById('reconcileOrderId').textContent = orderId || 'N/A';
    document.getElementById('reconcileResult').style.display = 'none';
    document.getElementById('reconcileBtn').disabled = false;
    document.getElementById('reconcileBtn').innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Check Cashfree';

    const modal = new bootstrap.Modal(document.getElementById('reconcileModal'));
    modal.show();
}

async function doReconcile() {
    const btn = document.getElementById('reconcileBtn');
    const resultDiv = document.getElementById('reconcileResult');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Checking...';
    resultDiv.style.display = 'none';

    try {
        const formData = new FormData();
        formData.append('invoice_id', reconcileInvoiceId);

        const response = await fetch('../api/admin/reconcile_payment.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        resultDiv.style.display = 'block';

        if (result.success) {
            let alertClass = 'alert-info';
            let icon = 'bi-info-circle';
            
            if (result.action_taken === 'updated_to_pending_verification') {
                alertClass = 'alert-success';
                icon = 'bi-check-circle';
            } else if (result.action_taken === 'no_change') {
                alertClass = 'alert-warning';
                icon = 'bi-exclamation-triangle';
            }

            resultDiv.innerHTML = `
                <div class="alert ${alertClass} mb-0">
                    <i class="bi ${icon} me-2"></i>
                    <strong>Cashfree Status:</strong> ${result.cashfree_status || 'Unknown'}<br>
                    <strong>Amount:</strong> ₹${result.cashfree_amount || 0}<br>
                    <strong>Action:</strong> ${result.message || 'No action taken'}
                </div>
            `;

            if (result.action_taken === 'updated_to_pending_verification') {
                setTimeout(() => loadVerifications(), 1500);
            }
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger mb-0"><i class="bi bi-x-circle me-2"></i>${result.message}</div>`;
        }
    } catch (error) {
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div class="alert alert-danger mb-0">Server error. Please try again.</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Check Cashfree';
    }
}

// ============ Audit Log ============
function openAuditLogModal(invoiceId) {
    if (invoiceId) {
        document.getElementById('auditLogTitle').textContent = 'Audit Log — Invoice #' + invoiceId;
    } else {
        document.getElementById('auditLogTitle').textContent = 'Invoice Audit Log';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('auditLogModal'));
    modal.show();
    loadAuditLog('', invoiceId);
}

function showInvoiceHistory(invoiceId) {
    openAuditLogModal(invoiceId);
}

async function loadAuditLog(actionFilter, invoiceId) {
    const container = document.getElementById('auditLogContent');
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

    // Update active button
    document.querySelectorAll('#auditLogModal [data-filter]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === (actionFilter || ''));
    });

    let url = '../api/admin/get_invoice_audit_log.php?limit=200';
    if (actionFilter) url += '&action=' + actionFilter;
    if (invoiceId) url += '&invoice_id=' + invoiceId;

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            container.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
            return;
        }

        const logs = result.data || [];
        if (logs.length === 0) {
            container.innerHTML = '<div class="text-center py-4 text-muted"><i class="bi bi-journal fs-1 d-block mb-2"></i>No audit log entries found.</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr>';
        html += '<th>Date</th><th>Invoice</th><th>User</th><th>Action</th><th>Admin</th><th>Reason / Details</th><th>Payment ID</th><th>Method</th></tr></thead><tbody>';

        logs.forEach(log => {
            let actionBadge = '';
            switch(log.action) {
                case 'approved':
                    actionBadge = '<span class="badge bg-success">Approved</span>';
                    break;
                case 'rejected':
                    actionBadge = '<span class="badge bg-danger">Rejected</span>';
                    break;
                case 'reconciled':
                    actionBadge = '<span class="badge bg-primary">Reconciled</span>';
                    break;
                case 'webhook_confirmed':
                    actionBadge = '<span class="badge bg-info">Webhook</span>';
                    break;
                default:
                    actionBadge = `<span class="badge bg-secondary">${log.action}</span>`;
            }

            const date = new Date(log.created_at);
            const dateStr = date.toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
            const timeStr = date.toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit'});

            const reason = log.reason || '-';
            const truncatedReason = reason.length > 60 ? reason.substring(0, 60) + '...' : reason;

            html += `<tr>
                <td><div class="small">${dateStr}</div><div class="small text-muted">${timeStr}</div></td>
                <td class="fw-bold">#${log.invoice_id}</td>
                <td><div class="small fw-bold">${log.user_name || 'User #' + log.user_id}</div><div class="small text-muted">${log.user_phone || ''}</div></td>
                <td>${actionBadge}<br><small class="text-muted">${log.old_status || ''} → ${log.new_status || ''}</small></td>
                <td class="small">${log.admin_user || '-'}</td>
                <td class="small" title="${reason.replace(/"/g, '&quot;')}">${truncatedReason}</td>
                <td><code class="small" style="word-break:break-all;">${log.payment_id || '-'}</code></td>
                <td class="small">${log.payment_method || '-'}</td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;

    } catch (error) {
        container.innerHTML = '<div class="alert alert-danger">Failed to load audit log.</div>';
    }
}

document.addEventListener('DOMContentLoaded', loadVerifications);
</script>
