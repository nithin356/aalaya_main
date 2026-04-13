document.addEventListener('DOMContentLoaded', function() {
    fetchDashboardStats();
});

async function fetchDashboardStats() {
    // Show loading state if needed
    const statsContainers = document.querySelectorAll('.stat-info .value');
    statsContainers.forEach(container => container.textContent = '...');

    try {
        const response = await fetch('../api/admin/dashboard.php');
        const result = await response.json();

        if (result.success) {
            updateStats(result.stats);
            updateRecentRegistrations(result.recent_registrations);
            updateRecentEnquiries(result.recent_enquiries);
            renderCharts(result.analytics);
        } else {
            console.error('Stats fetch failed:', result.message);
        }
    } catch (error) {
        console.error('Network error fetching stats:', error);
    }
}

function updateStats(stats) {
    document.getElementById('statUsers').textContent = stats.total_users;
    document.getElementById('statProperties').textContent = stats.active_properties;
    document.getElementById('statAds').textContent = stats.active_ads;
    document.getElementById('statEnquiries').textContent = stats.pending_enquiries;
    document.getElementById('statIncome').textContent = '₹' + parseFloat(stats.total_investments || 0).toLocaleString();
    
    // Update Total Subscription Amount if element exists
    const statPoints = document.getElementById('statPoints');
    if (statPoints) {
        statPoints.textContent = '₹' + parseFloat(stats.total_points || 0).toLocaleString();
    }
}

function renderCharts(analytics) {
    if (!analytics) return;

    // Income Chart
    const incomeCtx = document.getElementById('incomeChart').getContext('2d');
    new Chart(incomeCtx, {
        type: 'line',
        data: {
            labels: analytics.income_trend.map(d => d.date),
            datasets: [{
                label: 'Income (₹)',
                data: analytics.income_trend.map(d => d.total),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Growth Chart
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'bar',
        data: {
            labels: analytics.user_growth.map(d => d.date),
            datasets: [{
                label: 'New Users',
                data: analytics.user_growth.map(d => d.count),
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

function updateRecentRegistrations(users) {
    const tbody = document.getElementById('dashboardRegistrationsBody');
    if (!users || users.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:48px 0;"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No registrations yet</td></tr>`;
        return;
    }

    const statusMap = {
        paid: { cls: 'status-active', label: 'Paid' },
        pending: { cls: 'status-pending', label: 'Pending' },
        pending_verification: { cls: 'status-info', label: 'Verifying' },
        rejected: { cls: 'status-banned', label: 'Rejected' },
    };

    tbody.innerHTML = users.map(u => {
        const s = statusMap[u.payment_status] || { cls: 'status-pending', label: u.payment_status || 'No Invoice' };
        const created = new Date(u.account_created).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        const createdTime = new Date(u.account_created).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
        const payDate = u.payment_date && u.payment_status === 'paid'
            ? new Date(u.payment_date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
            : '—';
        const payTime = u.payment_date && u.payment_status === 'paid'
            ? new Date(u.payment_date).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
            : '';
        const amount = u.payment_amount ? '₹' + parseFloat(u.payment_amount).toLocaleString('en-IN') : '—';

        return `<tr>
            <td>#${u.id}</td>
            <td><div style="font-weight:600;">${u.full_name || '—'}</div></td>
            <td><div class="small">${u.phone}</div></td>
            <td data-order="${u.account_created}">
                <div>${created}</div>
                <div class="small text-muted">${createdTime}</div>
            </td>
            <td><span class="status-badge ${s.cls}">${s.label}</span></td>
            <td>${amount}</td>
            <td data-order="${u.payment_date || ''}">
                <div>${payDate}</div>
                ${payTime ? `<div class="small text-muted">${payTime}</div>` : ''}
            </td>
        </tr>`;
    }).join('');

    if ($.fn.dataTable.isDataTable('#dashboardRegistrationsTable')) {
        $('#dashboardRegistrationsTable').DataTable().destroy();
    }
    $('#dashboardRegistrationsTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[3, 'desc']],
        columnDefs: [{ orderable: false, targets: [] }],
        language: {
            emptyTable: '<div class="text-center py-4"><i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>No registrations found</div>',
            zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
        }
    });
}

function updateRecentEnquiries(enquiries) {
    const tbody = document.getElementById('dashboardEnquiriesBody');
    if (!enquiries || enquiries.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                    <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                    No recent enquiries found
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = enquiries.map(enq => {
        const statusClass = enq.status === 'pending' ? 'status-pending' : enq.status === 'in_progress' ? 'status-info' : 'status-resolved';
        const statusLabel = enq.status === 'in_progress' ? 'Processing' : enq.status.charAt(0).toUpperCase() + enq.status.slice(1);
        return `
            <tr>
                <td>#${enq.id}</td>
                <td>${enq.user_name}</td>
                <td><span class="text-capitalize">${enq.enquiry_type}</span></td>
                <td>${enq.subject || '-'}</td>
                <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                <td data-order="${enq.created_at}">${new Date(enq.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'})}</td>
                <td>
                    <button class="btn-action btn-action-view" onclick="viewEnquiry(${enq.id})" title="View Details">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // Initialize DataTable
    if ($.fn.dataTable.isDataTable('#dashboardEnquiriesTable')) {
        $('#dashboardEnquiriesTable').DataTable().destroy();
    }
    $('#dashboardEnquiriesTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 5,
        lengthMenu: [[5, 10, 25], [5, 10, 25]],
        order: [[5, 'desc']],
        language: {
            emptyTable: '<div class="text-center py-4"><i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>No enquiries found</div>',
            zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
        }
    });
}

function viewEnquiry(id) {
    // Lead to enquiries page with filter or show modal
    window.location.href = `enquiries.php?id=${id}`;
}
