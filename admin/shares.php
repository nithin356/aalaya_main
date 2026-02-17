<?php
$page_title = 'Share Transactions';
$header_title = 'Share History';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>Share Conversions & Transactions</h2>
    </div>
    
    <div class="table-responsive">
        <table>
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
        
        if (result.success && result.data.length > 0) {
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
                    <td>${new Date(item.created_at).toLocaleString()}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5">No share transactions found.</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Failed to load data.</td></tr>';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
