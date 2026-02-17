document.addEventListener('DOMContentLoaded', function() {
    fetchSettings();

    document.getElementById('settingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveSettingsBtn');
        const originalHTML = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        try {
            const formData = new FormData(this);
            const response = await fetch('../api/admin/config.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                showToast.success(result.message);
            } else {
                showToast.error(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast.error('Failed to save settings.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
});

async function fetchSettings() {
    try {
        const response = await fetch('../api/admin/config.php');
        const result = await response.json();

        if (result.success) {
            const data = result.data;
            if (data.referral_level1_percentage) document.getElementById('level1').value = data.referral_level1_percentage;
            if (data.referral_level2_percentage) document.getElementById('level2').value = data.referral_level2_percentage;
            if (data.referral_max_levels) document.getElementById('maxLevels').value = data.referral_max_levels;
            if (data.min_payout) document.getElementById('minPayout').value = data.min_payout;
            if (data.registration_fee) document.getElementById('regFee').value = data.registration_fee;
            // New Reward Fields
            if (data.share_threshold) document.getElementById('shareThreshold').value = data.share_threshold;
        }
    } catch (error) {
        console.error('Error fetching settings:', error);
    }
}
