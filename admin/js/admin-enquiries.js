document.addEventListener('DOMContentLoaded', function() {
    fetchEnquiries();
});

async function fetchEnquiries() {
    const tbody = document.getElementById('enquiriesTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Loading enquiries...</td></tr>';

    try {
        const response = await fetch('../api/admin/enquiries.php');
        const result = await response.json();

        if (result.success) {
            renderEnquiries(result.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function renderEnquiries(enquiries) {
    const tbody = document.getElementById('enquiriesTableBody');
    if (enquiries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">No enquiries found.</td></tr>';
        return;
    }

    tbody.innerHTML = enquiries.map(enq => `
        <tr>
            <td>#${enq.id}</td>
            <td class="fw-bold">${enq.user_name}<br><small class="text-muted fw-normal">${enq.user_email}</small></td>
            <td><span class="text-capitalize">${enq.enquiry_type}</span> (#${enq.reference_id})</td>
            <td>${enq.subject || 'No Subject'}</td>
            <td>
                <select onchange="updateStatus(${enq.id}, this.value)" class="form-input py-1 px-2" style="font-size: 0.8rem; width: auto;">
                    <option value="pending" ${enq.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${enq.status === 'in_progress' ? 'selected' : ''}>Processing</option>
                    <option value="resolved" ${enq.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                    <option value="closed" ${enq.status === 'closed' ? 'selected' : ''}>Closed</option>
                </select>
            </td>
            <td>${new Date(enq.created_at).toLocaleDateString()}</td>
            <td>
                <button class="btn-action btn-action-view" onclick="showToast.info('${enq.message.replace(/'/g, "\\'")}', 'Inquiry Message')" title="View Message">
                    <i class="bi bi-chat-text"></i>
                </button>
            </td>

        </tr>
    `).join('');
}

async function updateStatus(id, status) {
    try {
        const response = await fetch('../api/admin/enquiries.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: status })
        });
        const result = await response.json();
        if (result.success) {
            showToast.success(`Status updated to ${status.replace('_', ' ')}.`);
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
