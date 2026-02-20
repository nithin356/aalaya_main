document.addEventListener('DOMContentLoaded', function() {
    fetchProperties();

    const propertyForm = document.getElementById('propertyForm');
    propertyForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const removeField = document.getElementById('removeMediaIds');
        if (removeField) {
            removeField.value = Array.from(removedMediaIds).join(',');
        }
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        try {
            const response = await fetch('../api/admin/properties.php', {
                method: 'POST',
                body: formData
            });
            let result;
            const responseText = await response.text();
            
            try {
                // Try direct parse first
                result = JSON.parse(responseText);
            } catch (e) {
                // If failed, try to find JSON object (ignoring PHP warnings)
                const jsonStart = responseText.indexOf('{');
                const jsonEnd = responseText.lastIndexOf('}');
                if (jsonStart !== -1 && jsonEnd !== -1) {
                    try {
                        const jsonStr = responseText.substring(jsonStart, jsonEnd + 1);
                        result = JSON.parse(jsonStr);
                    } catch (e2) {
                        console.error('Raw Response:', responseText);
                        showToast.error('Server Error: ' + responseText.substring(0, 100) + '...');
                        return;
                    }
                } else {
                    console.error('Raw Response:', responseText);
                    showToast.error('Server Error: ' + responseText.substring(0, 100) + '...');
                    return;
                }
            }

            if (result.success) {
                closeModal();
                fetchProperties();
                propertyForm.reset();
                showToast.success('Property saved successfully!');
            } else {
                showToast.error(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast.error('An unexpected network error occurred.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Property';
        }
    });
});

let currentProperties = [];
let removedMediaIds = new Set();

async function fetchProperties() {
    const tbody = document.getElementById('propertiesTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Loading properties...</td></tr>';

    try {
        const response = await fetch('../api/admin/properties.php');
        const result = await response.json();

        if (result.success) {
            currentProperties = result.data;
            renderProperties(result.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function renderProperties(properties) {
    const tbody = document.getElementById('propertiesTableBody');
    if (properties.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">No properties found.</td></tr>';
        return;
    }

    tbody.innerHTML = properties.map(prop => `
        <tr>
            <td>#${prop.id}</td>
            <td>
                <div style="position: relative; width: 60px; height: 45px;">
                    <img src="../${prop.image_path || 'assets/images/logo-placeholder.png'}" 
                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px; background: #eee;">
                    ${prop.media && prop.media.length > 1 ? `
                        <span style="position: absolute; bottom: -5px; right: -5px; background: rgba(0,0,0,0.7); color: #fff; font-size: 10px; padding: 2px 4px; border-radius: 4px;">
                            +${prop.media.length - 1}
                        </span>
                    ` : ''}
                </div>
            </td>
            <td>
                <div class="fw-bold">${prop.title}</div>
                <div class="small text-muted">${prop.owner_name || 'No Owner'} • ${prop.location || 'No Location'}</div>
            </td>
            <td class="text-capitalize">${prop.property_type}</td>
            <td>₹${parseFloat(prop.price).toLocaleString()}</td>
            <td>
                <span class="status-badge ${getStatusClass(prop.status)}">
                    ${prop.status}
                </span>
            </td>
            <td>
                <div class="d-flex gap-2">
                    <button class="btn-action btn-action-view" onclick="editProperty(${prop.id})" title="Edit Property">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-action btn-action-danger" onclick="deleteProperty(${prop.id})" title="Delete Property">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getStatusClass(status) {
    switch(status) {
        case 'available': return 'status-resolved';
        case 'sold': return 'status-pending'; // Red/Orange for sold
        case 'rented': return 'status-in-progress';
        default: return 'status-pending';
    }
}

async function deleteProperty(id) {
    if (!confirm('Permanently delete this property?')) return;

    try {
        const response = await fetch(`../api/admin/properties.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        if (result.success) {
            fetchProperties();
            showToast.success('Property deleted successfully.');
        } else {
            showToast.error(result.message || 'Failed to delete property.');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast.error('Server error during deletion.');
    }
}

function editProperty(id) {
    const prop = currentProperties.find(p => p.id == id);
    if (!prop) return;

    openModal('Edit Property');
    removedMediaIds.clear();
    const removeField = document.getElementById('removeMediaIds');
    if (removeField) removeField.value = '';
    
    const form = document.getElementById('propertyForm');
    form.property_id.value = prop.id;
    form.title.value = prop.title;
    form.owner_name.value = prop.owner_name || '';
    form.location.value = prop.location || '';
    form.property_type.value = prop.property_type;
    form.price.value = prop.price;
    form.status.value = prop.status;
    form.description.value = prop.description || '';
    form.bedrooms.value = prop.bedrooms || '';
    form.bathrooms.value = prop.bathrooms || '';
    form.area.value = prop.area || '';
    form.area_unit.value = prop.area_unit || 'sqft';

    // Show current documents info
    document.getElementById('currentLegal').innerHTML = prop.legal_opinion_path ? 
        `<a href="../${prop.legal_opinion_path}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> View Current Legal Opinion</a>` : 'No file uploaded';
    document.getElementById('currentEvaluation').innerHTML = prop.evaluation_path ? 
        `<a href="../${prop.evaluation_path}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> View Current Evaluation</a>` : 'No file uploaded';
    
    // Show thumbnail of images if possible
    if (prop.media && prop.media.length > 0) {
        document.getElementById('currentImages').innerHTML = prop.media.map(m => `
            <div data-media-id="${m.id}" style="position: relative; width: 60px; height: 45px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                ${m.file_type === 'image' ? `<img src="../${m.file_path}" style="width: 100%; height: 100%; object-fit: cover;">` : `<i class="bi bi-play-circle" style="display: block; text-align: center; line-height: 45px;"></i>`}
                <button type="button" onclick="markMediaForDeletion(${m.id}, this)" title="Remove" style="position:absolute; top:2px; right:2px; width:18px; height:18px; border:none; border-radius:50%; background:rgba(220,53,69,0.95); color:#fff; font-size:11px; line-height:18px; padding:0; cursor:pointer;">×</button>
            </div>
        `).join('');
    } else {
        document.getElementById('currentImages').innerHTML = '<small class="text-muted">No media uploaded</small>';
    }
}

function markMediaForDeletion(mediaId, btn) {
    removedMediaIds.add(Number(mediaId));
    const item = btn.closest('[data-media-id]');
    if (item) item.remove();

    const removeField = document.getElementById('removeMediaIds');
    if (removeField) removeField.value = Array.from(removedMediaIds).join(',');

    const container = document.getElementById('currentImages');
    if (container && container.children.length === 0) {
        container.innerHTML = '<small class="text-muted">No media uploaded</small>';
    }
}

function openModal(title = 'Add New Property') {
    document.querySelector('#propertyModal h2').textContent = title;
    document.getElementById('propertyModal').style.display = 'flex';
    if (title === 'Add New Property') {
        removedMediaIds.clear();
        const removeField = document.getElementById('removeMediaIds');
        if (removeField) removeField.value = '';
    }
}

function closeModal() {
    document.getElementById('propertyModal').style.display = 'none';
    const form = document.getElementById('propertyForm');
    form.reset();
    form.property_id.value = '';
    removedMediaIds.clear();
    const removeField = document.getElementById('removeMediaIds');
    if (removeField) removeField.value = '';
    document.getElementById('currentLegal').innerHTML = '';
    document.getElementById('currentEvaluation').innerHTML = '';
    document.getElementById('currentImages').innerHTML = '';
}
