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

    tbody.innerHTML = enquiries.map(enq => {
        const enquiryType = enq.enquiry_type || 'general';
        const referenceId = enq.reference_id ?? '-';
        const subject = enq.subject || 'No Subject';
        const userName = enq.user_name || 'Unknown User';
        const userEmail = enq.user_email || 'N/A';
        const status = enq.status || 'pending';
        const createdAt = enq.created_at ? new Date(enq.created_at).toLocaleDateString() : '-';
        const message = String(enq.message || 'No message provided').replace(/'/g, "\\'");

        return `
        <tr>
            <td>#${enq.id}</td>
            <td class="fw-bold">${userName}<br><small class="text-muted fw-normal">${userEmail}</small></td>
            <td><span class="text-capitalize">${enquiryType}</span> (#${referenceId})</td>
            <td>${subject}</td>
            <td>
                <select onchange="updateStatus(${enq.id}, this.value)" class="form-input py-1 px-2" style="font-size: 0.8rem; width: auto;">
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>Processing</option>
                    <option value="resolved" ${status === 'resolved' ? 'selected' : ''}>Resolved</option>
                    <option value="closed" ${status === 'closed' ? 'selected' : ''}>Closed</option>
                </select>
            </td>
            <td>${createdAt}</td>
            <td>
                <button class="btn-action btn-action-view" onclick="showToast.info('${message}', 'Inquiry Message')" title="View Message">
                    <i class="bi bi-chat-text"></i>
                </button>
            </td>

        </tr>
    `;
    }).join('');
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
