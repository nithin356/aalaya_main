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
    
    // Update Total Points if element exists
    const statPoints = document.getElementById('statPoints');
    if (statPoints) {
        statPoints.textContent = parseFloat(stats.total_points || 0).toLocaleString();
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

function updateRecentEnquiries(enquiries) {
    const tbody = document.querySelector('table tbody');
    if (enquiries.length === 0) {
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
        const statusClass = enq.status === 'pending' ? 'status-pending' : 'status-resolved';
        return `
            <tr>
                <td>#${enq.id}</td>
                <td>${enq.user_name}</td>
                <td><span class="text-capitalize">${enq.enquiry_type}</span></td>
                <td>-</td> <!-- Message preview could go here -->
                <td><span class="status-badge ${statusClass}">${enq.status}</span></td>
                <td>${new Date(enq.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn-action btn-action-view" onclick="viewEnquiry(${enq.id})" title="View Details">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>

            </tr>
        `;
    }).join('');
}

function viewEnquiry(id) {
    // Lead to enquiries page with filter or show modal
    window.location.href = `enquiries.php?id=${id}`;
}
