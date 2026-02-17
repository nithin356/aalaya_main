<?php
$page_title = 'Enquiry Management';
$header_title = 'User Enquiries';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header">
        <h2>All Enquiries</h2>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Reference</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="enquiriesTableBody">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 48px 0;">
                        Loading enquiries...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="js/admin-enquiries.js"></script>
<?php require_once 'includes/footer.php'; ?>
