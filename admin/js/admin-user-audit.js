let allUsers = [];
let deleteTargetId = null;
let dataTable = null;

document.addEventListener('DOMContentLoaded', function() {
    fetchAuditData();
});

async function fetchAuditData() {
    try {
        const response = await fetch('../api/admin/user_audit.php');
        const result = await response.json();

        if (result.success) {
            allUsers = result.data;
            updateStats(result.stats);
            renderTable(allUsers);
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

function updateStats(stats) {
    document.getElementById('statTotal').textContent = stats.total;
    document.getElementById('statPaid').textContent = stats.paid;
    document.getElementById('statPendingVerify').textContent = stats.pending_verification;
    document.getElementById('statNotPaid').textContent = stats.pending + stats.no_invoice;
}

function getStatusBadge(user) {
    const status = user.payment_status;
    if (!status || !user.invoice_id) {
        return `<span class="status-badge status-danger">No Invoice</span>`;
    }
    switch (status) {
        case 'paid':
            return `<span class="status-badge status-active">Paid</span>`;
        case 'pending_verification':
            return `<span class="status-badge status-warning">Pending Verification</span>`;
        case 'pending':
            return `<span class="status-badge status-danger">Pending</span>`;
        case 'cancelled':
            return `<span class="status-badge status-danger">Cancelled</span>`;
        default:
            return `<span class="status-badge">${status}</span>`;
    }
}

function getPaymentMethod(user) {
    if (!user.payment_method) return '<span class="text-muted">—</span>';
    if (user.payment_method === 'cashfree') {
        return `<span class="badge bg-primary-subtle text-primary">Cashfree</span>`;
    } else if (user.payment_method === 'manual') {
        return `<span class="badge bg-secondary-subtle text-secondary">Manual</span>`;
    }
    return user.payment_method;
}

function getStatusCategory(user) {
    if (!user.payment_status || !user.invoice_id) return 'no_invoice';
    return user.payment_status;
}

function canDelete(user) {
    // Can only delete users who haven't paid
    const status = user.payment_status;
    return status !== 'paid';
}

function renderTable(users) {
    // Destroy existing DataTable if present
    if (dataTable) {
        dataTable.destroy();
    }

    const tbody = document.getElementById('auditTableBody');
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5">No users found.</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(user => `
        <tr data-status="${getStatusCategory(user)}">
            <td>#${user.id}</td>
            <td>
                <strong>${user.full_name || 'N/A'}</strong>
                ${user.is_banned == 1 ? '<br><span class="status-badge status-danger" style="font-size:10px;">Banned</span>' : ''}
            </td>
            <td>${user.phone || '<span class="text-muted">N/A</span>'}</td>
            <td>${user.referred_by_name ? user.referred_by_name + ' <small class="text-muted">(#' + user.referred_by + ')</small>' : '<span class="text-muted">Direct</span>'}</td>
            <td>${getStatusBadge(user)}</td>
            <td>${getPaymentMethod(user)}</td>
            <td>${user.invoice_amount ? '₹' + parseFloat(user.invoice_amount).toLocaleString() : '<span class="text-muted">—</span>'}</td>
            <td class="fw-bold text-primary">${user.total_points ? parseFloat(user.total_points).toLocaleString() : '0'}</td>
            <td class="fw-bold text-success text-center">${user.total_shares || 0}</td>
            <td><small>${user.created_at ? new Date(user.created_at).toLocaleDateString('en-IN') : '—'}</small></td>
            <td>
                ${canDelete(user) ? `
                    <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal(${user.id}, '${(user.full_name || 'N/A').replace(/'/g, "\\'")}', '${user.phone || 'N/A'}')" title="Delete permanently">
                        <i class="bi bi-trash"></i>
                    </button>
                ` : `
                    <span class="text-muted" title="Paid users cannot be deleted"><i class="bi bi-lock"></i></span>
                `}
            </td>
        </tr>
    `).join('');

    // Initialize DataTable
    dataTable = $('#auditTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_ users'
        }
    });
}

function filterTable(status, btn) {
    // Update active button
    document.querySelectorAll('.d-flex.gap-2 button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (status === 'all') {
        renderTable(allUsers);
    } else {
        const filtered = allUsers.filter(u => getStatusCategory(u) === status);
        renderTable(filtered);
    }
}

function openDeleteModal(id, name, phone) {
    deleteTargetId = id;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteUserId').textContent = id;
    document.getElementById('deleteUserPhone').textContent = phone;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    deleteTargetId = null;
}

async function confirmDelete() {
    if (!deleteTargetId) return;

    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Deleting...';

    try {
        const response = await fetch(`../api/admin/user_audit.php?id=${deleteTargetId}`, {
            method: 'DELETE'
        });
        const result = await response.json();

        if (result.success) {
            showToast.success(result.message);
            closeDeleteModal();
            fetchAuditData(); // Refresh
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        showToast.error('Network error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Permanently';
    }
}
