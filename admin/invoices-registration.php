<?php
$page_title = 'Registration Invoices';
$header_title = 'Registration Invoices';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>User Registration Payments</h2>
        <button class="btn-primary" onclick="loadInvoices()" style="margin-left: auto;">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Invoices</span>
                <span class="value" id="statTotalInvoices">0</span>
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
                <span class="label">Pending</span>
                <span class="value" id="statPending">0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Revenue</span>
                <span class="value" id="statTotalRevenue">₹0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="invoicesTable" class="table table-hover">
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
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading invoices...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadInvoices() {
    const tbody = document.getElementById('invoicesTableBody');
    try {
        const response = await fetch('../api/admin/invoices.php?type=registration');
        const result = await response.json();

        if (result.success) {
            // Update statistics
            if (result.stats) {
                document.getElementById('statTotalInvoices').textContent = result.stats.total_count || 0;
                document.getElementById('statPaid').textContent = result.stats.paid_count || 0;
                document.getElementById('statPending').textContent = result.stats.pending_count || 0;
                const totalRevenue = result.stats.total_paid_amount ? parseFloat(result.stats.total_paid_amount).toLocaleString('en-IN', {maximumFractionDigits: 0}) : 0;
                document.getElementById('statTotalRevenue').textContent = '₹' + totalRevenue;
            }

            if (result.data && result.data.length > 0) {
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
                
                // Initialize or reinitialize DataTable
                if ($.fn.dataTable.isDataTable('#invoicesTable')) {
                    $('#invoicesTable').DataTable().destroy();
                }
                $('#invoicesTable').DataTable({
                    ordering: true,
                    processing: false,
                    serverSide: false,
                    responsive: true,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    pageLength: 10,
                    order: [[4, 'desc']] // Sort by date descending
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No registration invoices found.</td></tr>';
            }
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Failed to load data.</td></tr>';
    }
}

document.addEventListener('DOMContentLoaded', loadInvoices);
</script>

<?php require_once 'includes/footer.php'; ?>
