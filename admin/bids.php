<?php
$page_title = 'Property Bids';
$header_title = 'Property Bids';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>All Property Bids</h2>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-hammer"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Bids</span>
                <span class="value" id="statTotalBids">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-info">
                <span class="label">Pending</span>
                <span class="value" id="statPendingBids">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Accepted</span>
                <span class="value" id="statAcceptedBids">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="stat-info">
                <span class="label">Rejected</span>
                <span class="value" id="statRejectedBids">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="bidsTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property</th>
                    <th>User</th>
                    <th>Bid Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="bidsTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading bids...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', fetchBids);

async function fetchBids() {
    const tbody = document.getElementById('bidsTableBody');

    // Destroy DataTable first
    if ($.fn.dataTable.isDataTable('#bidsTable')) {
        $('#bidsTable').DataTable().destroy();
    }

    try {
        const response = await fetch('../api/admin/bids.php');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            // Calculate stats
            let pending = 0, accepted = 0, rejected = 0;
            result.data.forEach(b => {
                if (b.status === 'accepted') accepted++;
                else if (b.status === 'rejected') rejected++;
                else pending++;
            });
            const el = (id, val) => { const e = document.getElementById(id); if(e) e.textContent = val; };
            el('statTotalBids', result.data.length);
            el('statPendingBids', pending);
            el('statAcceptedBids', accepted);
            el('statRejectedBids', rejected);

            tbody.innerHTML = result.data.map(bid => `
                <tr>
                    <td>#${bid.id}</td>
                    <td>
                        <strong>${bid.property_title}</strong><br>
                        <small class="text-muted">${bid.property_location}</small>
                    </td>
                    <td>
                        ${bid.user_name}<br>
                        <small class="text-muted">${bid.user_phone}</small>
                    </td>
                    <td class="fw-bold text-success">₹${parseFloat(bid.bid_amount).toLocaleString()}</td>
                    <td>
                        <span class="status-badge ${bid.status === 'accepted' ? 'status-resolved' : bid.status === 'rejected' ? 'status-danger' : 'status-pending'}">
                            ${bid.status.charAt(0).toUpperCase() + bid.status.slice(1)}
                        </span>
                    </td>
                    <td data-order="${bid.created_at}">${new Date(bid.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})}</td>
                    <td>
                        ${bid.status === 'pending' ? `
                        <div class="d-flex gap-1">
                            <button class="btn-action btn-action-view" onclick="updateBidStatus(${bid.id}, 'accepted')" title="Accept">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn-action btn-action-danger" onclick="updateBidStatus(${bid.id}, 'rejected')" title="Reject">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        ` : `<span class="text-muted small">${bid.status.charAt(0).toUpperCase() + bid.status.slice(1)}</span>`}
                    </td>
                </tr>
            `).join('');

            // Initialize DataTable
            $('#bidsTable').DataTable({
                ordering: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[5, 'desc']],
                language: {
                    emptyTable: '<div class="text-center py-4"><i class="bi bi-hammer fs-2 d-block mb-2 text-muted"></i>No bids found</div>',
                    zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
                },
                columnDefs: [
                    { orderable: false, targets: [6] }
                ]
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><i class="bi bi-hammer text-muted fs-1 d-block mb-2"></i>No bids found.</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Failed to load bids.</td></tr>';
    }
}

async function updateBidStatus(bidId, status) {
    if (!confirm(`Are you sure you want to ${status === 'accepted' ? 'accept' : 'reject'} this bid?`)) return;

    try {
        const response = await fetch('../api/admin/bids.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bid_id: bidId, status: status })
        });
        const result = await response.json();

        if (result.success) {
            showToast.success(result.message);
            fetchBids();
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        showToast.error("Failed to update bid status.");
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
