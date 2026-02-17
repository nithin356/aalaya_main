<?php
$page_title = 'Investment Invoices';
$header_title = 'Investment Invoices';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>Investment Payment Records</h2>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>User</th>
                    <th>Investment Amount</th>
                    <th>Points Earned</th>
                    <th>Recorded By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="invoicesTableBody">
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
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
        // Investments already serve as invoices for this purpose
        const response = await fetch('../api/admin/investments.php');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(inv => `
                <tr>
                    <td class="fw-bold">#INV-I${inv.id}</td>
                    <td>
                        <div>
                            <span class="d-block fw-bold">${inv.full_name}</span>
                            <small class="text-muted">${inv.phone || ''}</small>
                        </div>
                    </td>
                    <td class="fw-bold text-primary">₹${parseFloat(inv.amount).toLocaleString()}</td>
                    <td>
                        ${inv.points_earned > 0 
                            ? `<span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">+${inv.points_earned} Points</span>` 
                            : '<span class="text-muted">-</span>'
                        }
                    </td>
                    <td class="small text-muted">${inv.admin_name || 'System'}</td>
                    <td class="text-muted">${new Date(inv.created_at).toLocaleDateString()}</td>
                    <td>
                        <a href="print-invoice.php?type=investment&id=${inv.id}" target="_blank" class="btn-action btn-action-view" title="Print Invoice">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No investment invoices found.</td></tr>';
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Failed to load data.</td></tr>';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
