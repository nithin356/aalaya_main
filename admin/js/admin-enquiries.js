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

    // Calculate stats
    let pending = 0, inProgress = 0, resolved = 0;
    enquiries.forEach(e => {
        if (e.status === 'pending') pending++;
        else if (e.status === 'in_progress') inProgress++;
        else if (e.status === 'resolved' || e.status === 'closed') resolved++;
    });
    const el = (id, val) => { const e = document.getElementById(id); if(e) e.textContent = val; };
    el('statTotalEnquiries', enquiries.length);
    el('statPendingEnquiries', pending);
    el('statProgressEnquiries', inProgress);
    el('statResolvedEnquiries', resolved);

    tbody.innerHTML = enquiries.map(enq => {
        const enquiryType = enq.enquiry_type || 'general';
        const referenceId = enq.reference_id ?? '-';
        const subject = enq.subject || 'No Subject';
        const userName = enq.user_name || 'Unknown User';
        const userEmail = enq.user_email || 'N/A';
        const status = enq.status || 'pending';
        const createdAt = enq.created_at || '';
        const message = String(enq.message || 'No message provided').replace(/'/g, "\\'");

        return `
        <tr>
            <td>#${enq.id}</td>
            <td class="fw-bold">${userName}<br><small class="text-muted fw-normal">${userEmail}</small></td>
            <td><span class="text-capitalize">${enquiryType}</span> (#${referenceId})</td>
            <td>${subject}</td>
            <td>
                <select onchange="updateStatus(${enq.id}, this.value)" class="form-input py-1 px-2" style="font-size: 0.8rem; width: auto; min-width:110px;">
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>Processing</option>
                    <option value="resolved" ${status === 'resolved' ? 'selected' : ''}>Resolved</option>
                    <option value="closed" ${status === 'closed' ? 'selected' : ''}>Closed</option>
                </select>
            </td>
            <td data-order="${createdAt}">${createdAt ? new Date(createdAt).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'}) : '-'}</td>
            <td>
                <button class="btn-action btn-action-view" onclick="showToast.info('${message}', 'Inquiry Message')" title="View Message">
                    <i class="bi bi-chat-text"></i>
                </button>
            </td>

        </tr>
    `;
    }).join('');

    // Initialize DataTable
    if ($.fn.dataTable.isDataTable('#enquiriesTable')) {
        $('#enquiriesTable').DataTable().destroy();
    }
    $('#enquiriesTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        order: [[5, 'desc']],
        language: {
            emptyTable: '<div class="text-center py-4"><i class="bi bi-chat-left-dots fs-2 d-block mb-2 text-muted"></i>No enquiries found</div>',
            zeroRecords: '<div class="text-center py-4"><i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>No matching records</div>'
        },
        columnDefs: [
            { orderable: false, targets: [4, 6] }
        ]
    });
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
