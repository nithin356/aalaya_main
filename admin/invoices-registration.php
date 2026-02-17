<?php
$page_title = 'Registration Invoices';
$header_title = 'Registration Invoices';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>User Registration Payments</h2>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>User</th>
                    <th>Total Fee</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="invoicesTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading invoices...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const tbody = document.getElementById('invoicesTableBody');
    try {
        const response = await fetch('../api/admin/invoices.php?type=registration');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(inv => `
                <tr>
                    <td class="fw-bold">#INV-${inv.id}</td>
                    <td>
                        <div>
                            <span class="d-block fw-bold">${inv.full_name || 'N/A'}</span>
                            <small class="text-muted">${inv.email || ''}</small>
                        </div>
                    </td>
                    <td class="fw-bold text-primary">₹${parseFloat(inv.amount).toLocaleString()}</td>
                    <td>
                        ${inv.status === 'paid' 
                            ? '<span class="status-badge status-resolved">Paid</span>' 
                            : '<span class="status-badge status-pending">Pending</span>'
                        }
                    </td>
                    <td class="text-muted">${new Date(inv.created_at).toLocaleDateString()}</td>
                    <td>
                        <a href="print-invoice.php?type=registration&id=${inv.id}" target="_blank" class="btn-action btn-action-view" title="Print Invoice">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">No registration invoices found.</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-5">Failed to load data.</td></tr>';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
