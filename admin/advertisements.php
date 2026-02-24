<?php
$page_title = 'Advertisement Management';
$header_title = 'Advertisement Management';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>Campaign Management</h2>
        <button class="btn-primary" onclick="openModal()">
            <i class="bi bi-plus-lg"></i> Launch New Campaign
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-purple">
                <i class="bi bi-megaphone"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Campaigns</span>
                <span class="value" id="statTotalAds">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                <i class="bi bi-broadcast"></i>
            </div>
            <div class="stat-info">
                <span class="label">Active</span>
                <span class="value" id="statActiveAds">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div class="stat-info">
                <span class="label">Expired</span>
                <span class="value" id="statExpiredAds">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-blue">
                <i class="bi bi-image"></i>
            </div>
            <div class="stat-info">
                <span class="label">Banner Ads</span>
                <span class="value" id="statBannerAds">0</span>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table id="adsTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Banner</th>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>Expiry</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="adsTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading advertisements...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Refined Advertisement Modal -->
<div id="adModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h2 id="adModalTitle">Create New Advertisement</h2>
            <button class="btn-icon-close" onclick="closeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form id="adForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="adId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Campaign Title *</label>
                    <input type="text" name="title" class="form-input" required placeholder="e.g. Summer Property Expo 2026">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-input" placeholder="e.g. Zokli Real Estate">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ad Placement Type</label>
                        <select name="ad_type" class="form-input">
                            <option value="banner">Main Banner</option>
                            <option value="featured">Featured Section</option>
                            <option value="standard">Standard Sidebar</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Campaign Start</label>
                        <input type="date" name="start_date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Campaign End</label>
                        <input type="date" name="end_date" class="form-input">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Campaign Media (Images & Videos)</label>
                    <div id="currentAdImages" class="d-flex gap-2 mb-3 mt-2 flex-wrap"></div>
                    <input type="file" name="ad_images[]" class="form-input" multiple accept="image/*,video/*">
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple files.</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Launch Campaign</button>
            </div>
        </form>
    </div>
</div>

<script src="js/admin-advertisements.js"></script>
<?php require_once 'includes/footer.php'; ?>
