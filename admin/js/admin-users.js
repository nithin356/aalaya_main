document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

async function loadUsers() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 mb-0">Loading users...</p></td></tr>';

    try {
        const response = await fetch('../api/admin/users.php');
        const result = await response.json();

        if (result.success) {
            // Update statistics
            if (result.stats) {
                document.getElementById('statTotalUsers').textContent = result.stats.total_users || 0;
                document.getElementById('statVerified').textContent = result.stats.verified_users || 0;
                document.getElementById('statPending').textContent = result.stats.pending_users || 0;
                document.getElementById('statBanned').textContent = result.stats.banned_users || 0;
            }
            
            renderUsers(result.data);
            
            // Initialize or reinitialize DataTable
            if ($.fn.dataTable.isDataTable('#usersTable')) {
                $('#usersTable').DataTable().destroy();
            }
            $('#usersTable').DataTable({
                ordering: true,
                processing: false,
                serverSide: false,
                responsive: true,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                pageLength: 10,
                order: [[9, 'desc']] // Sort by joined date descending
            });
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        console.error('Fetch error:', error);
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-danger">Error loading users</td></tr>';
    }
}

function renderUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5">No users found.</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(user => `
        <tr>
            <td>#${user.id}</td>
            <td class="fw-bold">${user.full_name}</td>
            <td>${user.email || '<span class="text-muted">N/A</span>'}</td>
            <td>${user.phone || '<span class="text-muted">N/A</span>'}</td>
            <td>${user.pan_number || '<span class="text-muted">--</span>'}</td>
            <td>${user.aadhaar_number || '<span class="text-muted">--</span>'}</td>
            <td class="fw-bold text-primary">${user.total_points ? parseFloat(user.total_points).toLocaleString() : '0'}</td>
            <td class="fw-bold text-success text-center">${user.total_shares || 0}</td>
            <td>
                ${(() => {
                    if (user.is_banned == 1) {
                        return `<span class="status-badge status-danger">Banned</span>`;
                    }
                    switch (user.payment_status) {
                        case 'paid':
                            return `<span class="status-badge status-resolved">Active</span>`;
                        case 'pending_verification':
                            return `<span class="status-badge status-pending">Pending Verif.</span>`;
                        case 'pending':
                            return `<span class="status-badge status-muted">Unpaid</span>`;
                        default:
                            return `<span class="status-badge status-muted">Inactive</span>`;
                    }
                })()}
            </td>
            <td>${new Date(user.created_at).toLocaleDateString()}</td>
            <td>
                <div class="d-flex gap-2">
                    <button class="btn-action btn-action-view" onclick="openAdjustModal(${user.id}, '${user.full_name}')" title="Adjust Points">
                        <i class="bi bi-plus-slash-minus"></i>
                    </button>
                    <button class="btn-action btn-action-view" onclick="toggleBan(${user.id}, ${user.is_banned})" title="${user.is_banned == 1 ? 'Unban' : 'Ban'}">
                        <i class="bi ${user.is_banned == 1 ? 'bi-check-circle' : 'bi-slash-circle'}"></i>
                    </button>
                    <button class="btn-action btn-action-danger" onclick="deleteUser(${user.id})" title="Delete User">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function toggleBan(id, currentStatus) {
    if (!confirm(`Are you sure you want to ${currentStatus == 1 ? 'unban' : 'ban'} this user?`)) return;

    try {
        const response = await fetch('../api/admin/users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_ban', id: id })
        });
        const result = await response.json();
        if (result.success) {
            loadUsers();
            showToast.success(`User status updated.`);
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action is reversible by admin only.')) return;

    try {
        const response = await fetch(`../api/admin/users.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        if (result.success) {
            loadUsers();
            showToast.success('User deleted successfully.');
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Points Adjustment
function openAdjustModal(id, name) {
    document.getElementById('adjustUserId').value = id;
    document.getElementById('adjustUserName').textContent = name;
    document.getElementById('adjustPointsModal').classList.add('active');
}

function closeAdjustModal() {
    document.getElementById('adjustPointsModal').classList.remove('active');
    document.getElementById('adjustPointsForm').reset();
}

document.getElementById('adjustPointsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

    try {
        const formData = new FormData(this);
        const response = await fetch('../api/admin/adjust_points.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            showToast.success(result.message);
            closeAdjustModal();
            loadUsers();
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        showToast.error("Request failed.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
});
