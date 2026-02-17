<?php
$page_title = 'Property Management';
$header_title = 'Property Management';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>Property Listings</h2>
        <button class="btn-primary" onclick="openModal()">
            <i class="bi bi-plus-lg"></i> Add New Property
        </button>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="propertiesTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading properties...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Refined Property Modal -->
<div id="propertyModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h2>Add New Property</h2>
            <button class="btn-icon-close" onclick="closeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form id="propertyForm" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" id="propertyId" name="property_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Property Title *</label>
                        <input type="text" name="title" class="form-input" required placeholder="e.g. Luxury 3BHK Apartment">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Owner's Name</label>
                        <input type="text" name="owner_name" class="form-input" placeholder="e.g. Mr. John Doe">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" class="form-input" required placeholder="e.g. Hitech City, Hyderabad">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Property Type</label>
                        <select name="property_type" class="form-input">
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="land">Land</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" name="price" class="form-input" placeholder="5,500,000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="available">Available</option>
                            <option value="sold">Sold</option>
                            <option value="rented">Rented</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Enter property details, amenities, etc."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Total Area</label>
                        <input type="number" name="area" class="form-input" placeholder="1200">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <select name="area_unit" class="form-input">
                            <option value="sqft">Sq. Ft.</option>
                            <option value="sqm">Sq. Mt.</option>
                            <option value="acre">Acre</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Bedrooms (BHK)</label>
                        <input type="number" name="bedrooms" class="form-input" placeholder="3">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" name="bathrooms" class="form-input" placeholder="2">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Legal Opinion (PDF/DOC)</label>
                        <input type="file" name="legal_opinion" class="form-input" accept=".pdf,.doc,.docx">
                        <div id="currentLegal" class="small text-muted mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Evaluation Report (PDF/DOC)</label>
                        <input type="file" name="evaluation" class="form-input" accept=".pdf,.doc,.docx">
                        <div id="currentEvaluation" class="small text-muted mt-1"></div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Property Images & Videos</label>
                    <input type="file" name="property_images[]" class="form-input" multiple accept="image/*,video/*">
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple files.</small>
                    <div id="currentImages" class="d-flex flex-wrap gap-2 mt-2"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Property</button>
            </div>
        </form>
    </div>
</div>

<script src="js/admin-properties.js"></script>
<?php require_once 'includes/footer.php'; ?>
