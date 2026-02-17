document.addEventListener('DOMContentLoaded', function() {
    fetchAds();

    const adForm = document.getElementById('adForm');
    adForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Launching...';

        try {
            const response = await fetch('../api/admin/advertisements.php', {
                method: 'POST',
                body: formData
            });
            let result;
            const responseText = await response.text();
            
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                const jsonStart = responseText.indexOf('{');
                const jsonEnd = responseText.lastIndexOf('}');
                if (jsonStart !== -1 && jsonEnd !== -1) {
                    try {
                        const jsonStr = responseText.substring(jsonStart, jsonEnd + 1);
                        result = JSON.parse(jsonStr);
                    } catch (e2) {
                        console.error('Raw Response:', responseText);
                        showToast.error('Server Error: ' + responseText.substring(0, 100));
                        return;
                    }
                } else {
                    console.error('Raw Response:', responseText);
                    showToast.error('Server Error: ' + responseText.substring(0, 100));
                    return;
                }
            }

            if (result.success) {
                closeModal();
                fetchAds();
                adForm.reset();
                showToast.success('Campaign launched successfully!');
            } else {
                showToast.error(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = document.getElementById('adId').value ? 'Save Changes' : 'Launch Ad';
        }
    });
});

async function fetchAds() {
    const tbody = document.getElementById('adsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">Loading ads...</td></tr>';

    try {
        const response = await fetch('../api/admin/advertisements.php');
        const result = await response.json();

        if (result.success) {
            renderAds(result.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function renderAds(ads) {
    const tbody = document.getElementById('adsTableBody');
    if (ads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5">No active campaigns.</td></tr>';
        return;
    }

    tbody.innerHTML = ads.map(ad => `
        <tr>
            <td>#${ad.id}</td>
            <td>
                <div style="position: relative; width: 100px; height: 33px;">
                    <img src="../${ad.image_path || 'assets/images/logo-placeholder.png'}" 
                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px; background: #eee;">
                    ${ad.media && ad.media.length > 1 ? `
                         <span style="position: absolute; bottom: -5px; right: -5px; background: rgba(0,0,0,0.7); color: #fff; font-size: 10px; padding: 2px 4px; border-radius: 4px;">
                            +${ad.media.length - 1}
                        </span>
                    ` : ''}
                </div>
            </td>
            <td class="fw-bold">${ad.title}</td>
            <td>${ad.company_name || 'N/A'}</td>
            <td><span class="text-capitalize">${ad.ad_type}</span></td>
            <td>${ad.end_date || 'Ongoing'}</td>
            <td>
                <div class="d-flex gap-2">
                    <button class="btn-action btn-action-view" onclick="editAd(${ad.id})" title="Edit Campaign">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-action btn-action-danger" onclick="deleteAd(${ad.id})" title="Delete Campaign">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function editAd(id) {
    try {
        const response = await fetch(`../api/admin/advertisements.php?id=${id}`);
        const result = await response.json();

        if (result.success) {
            const ad = result.data;
            document.getElementById('adId').value = ad.id;
            document.getElementById('adModalTitle').textContent = 'Edit Advertisement';
            document.querySelector('#adForm button[type="submit"]').textContent = 'Save Changes';
            
            const form = document.getElementById('adForm');
            form.title.value = ad.title;
            form.company_name.value = ad.company_name || '';
            form.ad_type.value = ad.ad_type;
            form.start_date.value = ad.start_date || '';
            form.end_date.value = ad.end_date || '';

            // Show media preview
            const preview = document.getElementById('currentAdImages');
            if (ad.media && ad.media.length > 0) {
                preview.innerHTML = ad.media.map(m => `
                    <div style="width: 60px; height: 45px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; position: relative;">
                        ${m.file_type === 'image' ? 
                            `<img src="../${m.file_path}" style="width: 100%; height: 100%; object-fit: cover;">` : 
                            `<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                <i class="bi bi-play-btn-fill text-primary"></i>
                             </div>`
                        }
                    </div>
                `).join('');
            } else {
                preview.innerHTML = '';
            }

            document.getElementById('adModal').style.display = 'flex';
        } else {
            showToast.error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast.error('Failed to fetch advertisement details.');
    }
}

async function deleteAd(id) {
    if (!confirm('Permanently delete this advertisement?')) return;

    try {
        const response = await fetch(`../api/admin/advertisements.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        if (result.success) {
            fetchAds();
            showToast.success('Campaign deleted successfully.');
        } else {
            showToast.error(result.message || 'Failed to delete campaign.');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast.error('Server error during deletion.');
    }
}

function openModal() {
    document.getElementById('adId').value = '';
    document.getElementById('adModalTitle').textContent = 'Create New Advertisement';
    document.querySelector('#adForm button[type="submit"]').textContent = 'Launch Campaign';
    document.getElementById('currentAdImages').innerHTML = '';
    document.getElementById('adModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('adModal').style.display = 'none';
    document.getElementById('adForm').reset();
    document.getElementById('adId').value = '';
    document.getElementById('currentAdImages').innerHTML = '';
}
