<?php
$page_title = 'Property Bids';
$header_title = 'Property Bids';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>All Property Bids</h2>
    </div>
    
    <div class="table-responsive">
        <table>
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
                        Loading bids...
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
    try {
        const response = await fetch('../api/admin/bids.php');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
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
                        <span class="status-badge ${bid.status === 'accepted' ? 'status-resolved' : bid.status === 'rejected' ? 'status-pending' : 'status-progress'}">
                            ${bid.status.charAt(0).toUpperCase() + bid.status.slice(1)}
                        </span>
                    </td>
                    <td>${new Date(bid.created_at).toLocaleString()}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn-action btn-action-view" onclick="updateBidStatus(${bid.id}, 'accepted')" title="Accept">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn-action btn-action-danger" onclick="updateBidStatus(${bid.id}, 'rejected')" title="Reject">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">No bids found.</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Failed to load bids.</td></tr>';
    }
}

async function updateBidStatus(bidId, status) {
    if (!confirm(`Are you sure you want to ${status} this bid?`)) return;

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
