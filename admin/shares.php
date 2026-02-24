<?php
$page_title = 'Share Transactions';
$header_title = 'Share History';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>Share Conversions & Transactions</h2>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-green">
                <i class="bi bi-pie-chart"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Shares Issued</span>
                <span class="value" id="statTotalShares">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <span class="label">Shareholders</span>
                <span class="value" id="statShareholders">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-purple">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <span class="label">Transactions</span>
                <span class="value" id="statShareTxns">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="sharesTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Shares Added</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="sharesTableBody">
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading transactions...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const tbody = document.getElementById('sharesTableBody');
    try {
        const response = await fetch('../api/admin/shares.php');
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            // Calculate stats
            let totalShares = 0;
            const shareholders = new Set();
            result.data.forEach(item => {
                totalShares += parseInt(item.shares_added) || 0;
                shareholders.add(item.user_id || item.full_name);
            });
            const el = (id, val) => { const e = document.getElementById(id); if(e) e.textContent = val; };
            el('statTotalShares', totalShares);
            el('statShareholders', shareholders.size);
            el('statShareTxns', result.data.length);

            tbody.innerHTML = result.data.map(item => `
                <tr>
                    <td>#${item.id}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">${item.full_name}</span>
                            <span class="small text-muted">${item.phone || ''}</span>
                        </div>
                    </td>
                    <td class="fw-bold text-success">+${item.shares_added}</td>
                    <td>${item.reason}</td>
                    <td data-order="${item.created_at}">${new Date(item.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})}</td>
                </tr>
            `).join('');

            // Initialize DataTable
            $('#sharesTable').DataTable({
                ordering: true,
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[4, 'desc']],
                language: {
                    emptyTable: '<div class="text-center py-4"><i class="bi bi-pie-chart fs-2 d-block mb-2 text-muted"></i>No share transactions found</div>',
                    zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
                }
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><i class="bi bi-pie-chart text-muted fs-1 d-block mb-2"></i>No share transactions found.</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Failed to load data.</td></tr>';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
