<?php
$page_title = 'Investment Invoices';
$header_title = 'Investment Invoices';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>Investment Payment Records</h2>
        <button class="btn-primary" onclick="loadInvoices()" style="margin-left: auto;">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-green">
                <i class="bi bi-currency-rupee"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Investments</span>
                <span class="value" id="statTotalInvestment">₹0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="bi bi-graph-up"></i>
            </div>
            <div class="stat-info">
                <span class="label">Investment Count</span>
                <span class="value" id="statInvestmentCount">0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(249, 105, 170, 0.1); color: #F969AA;">
                <i class="bi bi-coin"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Points Earned</span>
                <span class="value" id="statPointsEarned">0</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <span class="label">Active Investors</span>
                <span class="value" id="statInvestorCount">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="invoicesTable" class="table table-hover">
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
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
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
        // Investments already serve as invoices for this purpose
        const response = await fetch('../api/admin/investments.php');
        const result = await response.json();

        if (result.success) {
            // Update statistics
            if (result.stats) {
                const totalAmount = result.stats.total_amount ? parseFloat(result.stats.total_amount).toLocaleString('en-IN', {maximumFractionDigits: 0}) : 0;
                document.getElementById('statTotalInvestment').textContent = '₹' + totalAmount;
                document.getElementById('statInvestmentCount').textContent = result.stats.total_count || 0;
                document.getElementById('statPointsEarned').textContent = result.stats.total_points_earned ? Math.round(result.stats.total_points_earned) : 0;
                document.getElementById('statInvestorCount').textContent = result.stats.total_investors || 0;
            }

            if (result.data && result.data.length > 0) {
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
                        <td data-order="${inv.created_at}" class="text-muted">${new Date(inv.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})}</td>
                        <td>
                            <a href="print-invoice.php?type=investment&id=${inv.id}" target="_blank" class="btn-action btn-action-view" title="Print Invoice">
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
                    order: [[5, 'desc']] // Sort by date descending
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No investment invoices found.</td></tr>';
            }
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-5">Failed to load data.</td></tr>';
    }
}

document.addEventListener('DOMContentLoaded', loadInvoices);
</script>

<?php require_once 'includes/footer.php'; ?>
