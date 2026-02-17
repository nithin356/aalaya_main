<?php
$page_title = 'System Settings';
$header_title = 'Platform Configuration';
require_once 'includes/header.php';
?>

<div class="row g-4 justify-content-center">
    <div class="col-md-10">
        <div class="data-card">
            <div class="card-header">
                <h2>Referral & Rewards Configuration</h2>
            </div>
            <div class="modal-body">
                <form id="settingsForm">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Level 1 Referral (%)</label>
                            <input type="number" name="referral_level1_percentage" id="level1" class="form-input" placeholder="e.g. 20" required>
                            <small class="text-muted">Percentage earned from direct referrals (registration).</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Level 2 Referral (%)</label>
                            <input type="number" name="referral_level2_percentage" id="level2" class="form-input" placeholder="e.g. 10" required>
                            <small class="text-muted">Percentage earned from secondary referrals (registration).</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <hr>
                            <h5 class="text-muted mb-3"><i class="bi bi-trophy"></i> Rewards Configuration</h5>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Share Threshold (Points)</label>
                            <input type="number" name="share_threshold" id="shareThreshold" class="form-input" placeholder="e.g. 111111" required>
                            <small class="text-muted">Points required to earn <strong>1 Share</strong>. Excess points carry over.</small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <hr>
                            <h5 class="text-muted mb-3"><i class="bi bi-gear"></i> General Settings</h5>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Registration Fee (₹)</label>
                            <input type="number" name="registration_fee" id="regFee" class="form-input" placeholder="e.g. 100">
                            <small class="text-muted">Fee charged for user registration (Inclusive of 18% GST).</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Maximum Referral Levels</label>
                            <input type="number" name="referral_max_levels" id="maxLevels" class="form-input" placeholder="e.g. 2" required>
                            <small class="text-muted">Strict depth of the referral chain.</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Minimum Payout (Points)</label>
                            <input type="number" name="min_payout" id="minPayout" class="form-input" placeholder="e.g. 500" required>
                            <small class="text-muted">Minimum points required to request a withdrawal.</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn-primary" id="saveSettingsBtn">
                            <i class="bi bi-cloud-check-fill"></i>
                            Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="js/admin-settings.js"></script>
<?php require_once 'includes/footer.php'; ?>
