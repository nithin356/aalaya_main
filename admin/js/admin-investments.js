document.addEventListener('DOMContentLoaded', function() {
    loadInvestments();
    loadUsers();
    loadConfiguration(); // Load dynamic threshold

    // Modal Logic
    window.openModal = function() {
        document.getElementById('investmentModal').style.display = 'flex';
    };

    window.closeModal = function() {
        document.getElementById('investmentModal').style.display = 'none';
        document.getElementById('investmentForm').reset();
    };

    // Close on outside click
    document.getElementById('investmentModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Form Submission
    document.getElementById('investmentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        const formData = new FormData(this);

        try {
            const response = await fetch('../api/admin/investments.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast.success(result.message);
                closeModal();
                loadInvestments();
            } else {
                showToast.error(result.message || 'Failed to add investment');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast.error('An error occurred. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
});

async function loadInvestments() {
    const tbody = document.getElementById('investmentsTableBody');
    try {
        const response = await fetch('../api/admin/investments.php');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(item => `
                <tr>
                    <td>#${item.id}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                <i class="bi bi-person text-secondary"></i>
                            </div>
                            <div>
                                <span class="d-block fw-bold">${item.full_name}</span>
                                <small class="text-muted">${item.phone}</small>
                            </div>
                        </div>
                    </td>
                    <td class="fw-bold">₹${parseFloat(item.amount).toLocaleString()}</td>
                    <td>
                        ${item.points_earned > 0 
                            ? `<span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">+${item.points_earned} Points</span>` 
                            : `<span class="text-muted">-</span>`
                        }
                    </td>
                    <td class="text-muted">${new Date(item.created_at).toLocaleDateString()}</td>
                    <td class="small text-muted">${item.admin_name || 'System'}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted">No investments found.</td></tr>`;
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-5">Failed to load data.</td></tr>`;
    }
}

async function loadUsers() {
    const select = document.getElementById('userSelect');
    try {
        const response = await fetch('../api/admin/users.php'); // Reusing existing users API
        const result = await response.json();

        if (result.success) {
            select.innerHTML = '<option value="">Select a user...</option>' + 
                result.data.map(user => `<option value="${user.id}">${user.full_name} (${user.phone})</option>`).join('');
        }
    } catch (error) {
        select.innerHTML = '<option value="">Error loading users</option>';
    }
}

async function loadConfiguration() {
    try {
        const response = await fetch('../api/admin/config.php');
        const result = await response.json();
        if (result.success && result.data.investment_threshold) {
             const threshold = parseFloat(result.data.investment_threshold);
             document.getElementById('thresholdDisplay').innerText = threshold.toLocaleString();
             // Store for validation/logic if needed, though mostly backend handles it
             document.getElementById('investmentForm').dataset.threshold = threshold; 
        }
    } catch (e) {
        console.error('Failed to load config', e);
    }
}
