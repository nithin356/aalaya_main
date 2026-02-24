<?php
$page_title = 'Payment Verifications';
$header_title = 'Manual Payment Verifications';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Payment Verifications</h2>
        <div class="d-flex gap-2 align-items-center">
            <select id="verificationFilter" class="form-input py-1 px-2" style="font-size: 0.85rem; width:auto; min-width:150px;" onchange="loadVerifications()">
                <option value="pending_verification">Pending Review</option>
                <option value="all">All Records</option>
                <option value="paid">Approved</option>
                <option value="pending">Rejected</option>
            </select>
            <button class="btn-primary" onclick="loadVerifications()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
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
                <span class="label">Total Submissions</span>
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
    </div>

    <div class="table-responsive">
        <table id="verificationsTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>UTR ID</th>
                    <th>Screenshot</th>
                    <th>Status</th>
                    <th>Submitted At</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="verificationsTableBody">
                <!-- Data will be loaded via JS -->
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
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">UTR ID:</span>
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
                    <small class="text-muted">This will be visible to the user.</small>
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

<?php require_once 'includes/footer.php'; ?>

<script>
let currentInvoice = null;
let currentAction = null;

async function loadVerifications() {
    const tbody = document.getElementById('verificationsTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">Loading verifications...</p></td></tr>';

    // Destroy DataTable if it exists
    if ($.fn.dataTable.isDataTable('#verificationsTable')) {
        $('#verificationsTable').DataTable().destroy();
    }

    try {
        const response = await fetch('../api/admin/get_pending_verifications.php');
        const result = await response.json();

        if (result.success) {
            // Update stats
            const data = result.data || [];
            let pending = 0, approved = 0, rejected = 0;
            if (result.stats) {
                pending = result.stats.pending || 0;
                approved = result.stats.approved || 0;
                rejected = result.stats.rejected || 0;
            } else {
                pending = data.length; // fallback
            }
            const el = (id, val) => { const e = document.getElementById(id); if(e) e.textContent = val; };
            el('statTotalVerif', pending + approved + rejected);
            el('statPendingVerif', pending);
            el('statApprovedVerif', approved);
            el('statRejectedVerif', rejected);

            renderVerifications(data);
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-danger">Server Error. Please try again later.</td></tr>';
    }
}

function renderVerifications(data) {
    const tbody = document.getElementById('verificationsTableBody');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><i class="bi bi-check-circle text-success fs-1"></i><p class="mt-2 mb-0">No pending verifications found.</p></td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => {
        let statusBadge = '';
        let actionBtns = '';
        const status = item.status || 'pending_verification';
        
        switch(status) {
            case 'paid':
                statusBadge = '<span class="status-badge status-resolved">Approved</span>';
                actionBtns = '<span class="text-muted small">Completed</span>';
                break;
            case 'pending':
                statusBadge = '<span class="status-badge status-danger">Rejected</span>';
                actionBtns = '<span class="text-muted small">Rejected</span>';
                break;
            default:
                statusBadge = '<span class="status-badge status-pending">Pending</span>';
                actionBtns = `
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn-action btn-action-view" onclick="openConfirmModal(${item.id}, 'approve', '${(item.full_name||'').replace(/'/g,"\\'")}', '${item.amount}', '${item.manual_utr_id || ''}', '${item.manual_payment_screenshot || ''}')" title="Approve Payment">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button class="btn-action btn-action-danger" onclick="openConfirmModal(${item.id}, 'reject', '${(item.full_name||'').replace(/'/g,"\\'")}', '${item.amount}', '${item.manual_utr_id || ''}', '${item.manual_payment_screenshot || ''}')" title="Reject Payment">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>`;
        }

        return `
        <tr>
            <td class="fw-bold">#${item.id}</td>
            <td>
                <div class="fw-bold">${item.full_name}</div>
                <div class="small text-muted">${item.phone || ''}</div>
            </td>
            <td class="fw-bold text-primary">₹${parseFloat(item.amount).toLocaleString()}</td>
            <td><code style="background: #f0f7ff; color: #0066cc; padding: 2px 6px; border-radius: 4px; font-weight: 600;">${item.manual_utr_id || '-'}</code></td>
            <td>
                ${item.manual_payment_screenshot ? `
                    <a href="../${item.manual_payment_screenshot}" target="_blank" class="text-decoration-none">
                        <img src="../${item.manual_payment_screenshot}" class="img-thumbnail" style="height: 40px; cursor: pointer;" title="Click to view full image">
                    </a>
                ` : '<span class="text-muted small">No image</span>'}
            </td>
            <td>${statusBadge}</td>
            <td data-order="${item.updated_at || item.created_at}">${new Date(item.updated_at || item.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})}</td>
            <td>${actionBtns}</td>
        </tr>
    `}).join('');

    // Initialize DataTable
    if ($.fn.dataTable.isDataTable('#verificationsTable')) {
        $('#verificationsTable').DataTable().destroy();
    }
    $('#verificationsTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[6, 'desc']],
        language: {
            emptyTable: '<div class="text-center py-4"><i class="bi bi-shield-check fs-2 d-block mb-2 text-muted"></i>No verifications found</div>',
            zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
        },
        columnDefs: [
            { orderable: false, targets: [4, 7] }
        ]
    });
}

function openConfirmModal(id, action, name, amount, utr, screenshot) {
    currentInvoice = id;
    currentAction = action;
    
    document.getElementById('modalUserName').textContent = name;
    document.getElementById('modalAmount').textContent = '₹' + parseFloat(amount).toLocaleString();
    document.getElementById('modalUTR').textContent = utr;

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
        document.getElementById('modalBodyText').textContent = 'Are you sure you want to reject this payment? The user will have to resubmit their UTR.';
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

    // Validate comment is required for rejection
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

document.addEventListener('DOMContentLoaded', loadVerifications);
</script>
