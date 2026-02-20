<?php
$page_title = 'Points History';
$header_title = 'Points Transactions';
require_once 'includes/header.php';

echo '<div class="data-card"><div class="card-header"><h2>Transaction Tables Hidden</h2></div><div style="padding:24px; color: var(--text-muted);">Points transaction tables are currently hidden.</div></div>';
require_once 'includes/footer.php';
exit;
?>

<div class="data-card">
    <div class="card-header">
        <h2>Points Transactions</h2>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Referred User</th>
                    <th>Points</th>
                    <th>Type</th>
                    <th>Level</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="pointsTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading transactions...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const tbody = document.getElementById('pointsTableBody');
    try {
        const response = await fetch('../api/admin/points_history.php');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(item => `
                <tr>
                    <td>#${item.id}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold">${item.user_name}</span>
                            <span class="small text-muted">${item.user_code || ''}</span>
                        </div>
                    </td>
                    <td>
                        ${item.referred_user_name ? `
                        <div class="d-flex flex-column">
                            <span class="fw-bold">${item.referred_user_name}</span>
                            <span class="small text-muted">${item.referred_user_code || ''}</span>
                        </div>` : '<span class="text-muted">--</span>'}
                    </td>
                    <td class="fw-bold ${item.points_earned >= 0 ? 'text-success' : 'text-danger'}">
                        ${item.points_earned >= 0 ? '+' : ''}${parseFloat(item.points_earned).toLocaleString()}
                    </td>
                    <td>
                        <span class="status-badge ${item.transaction_type.includes('manual') ? 'status-pending' : 'status-resolved'}">
                            ${formatType(item.transaction_type)}
                        </span>
                    </td>
                    <td>${item.level == 0 ? '--' : 'L'+item.level}</td>
                    <td>${new Date(item.created_at).toLocaleString()}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">No transactions found.</td></tr>';
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Failed to load data.</td></tr>';
    }
});

function formatType(type) {
    if(!type) return 'Unknown';
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<?php require_once 'includes/footer.php'; ?>
