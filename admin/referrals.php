<?php
$page_title = 'Referrals Management';
$header_title = 'Referrals Per User';
require_once 'includes/header.php';
?>

<div class="data-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2>Referrals Management</h2>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" id="searchInput" class="form-input py-1 px-2"
                   style="font-size:0.85rem; width:auto; min-width:220px;"
                   placeholder="Search name / phone / code..."
                   oninput="debounceLoad()">
            <select id="filterSelect" class="form-input py-1 px-2"
                    style="font-size:0.85rem; width:auto; min-width:180px;"
                    onchange="loadReferrals()">
                <option value="all">All Users</option>
                <option value="has_referrals">Has Referrals</option>
                <option value="no_referrals">No Referrals</option>
                <option value="has_referrer">Has a Referrer</option>
                <option value="no_referrer">Direct (No Referrer)</option>
            </select>
            <button class="btn-primary" onclick="loadReferrals()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid mb-0">
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <span class="label">Total Users</span>
                <span class="value" id="statTotal">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,.1);color:#22c55e;">
                <i class="bi bi-person-check"></i>
            </div>
            <div class="stat-info">
                <span class="label">Referred Sign-ups</span>
                <span class="value" id="statReferred">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(99,102,241,.1);color:#6366f1;">
                <i class="bi bi-person-plus"></i>
            </div>
            <div class="stat-info">
                <span class="label">Direct Sign-ups</span>
                <span class="value" id="statDirect">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(249,115,22,.1);color:#f97316;">
                <i class="bi bi-share"></i>
            </div>
            <div class="stat-info">
                <span class="label">Total Referral Links Used</span>
                <span class="value" id="statLinks">0</span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="referralsTable" class="table table-hover" style="width:100%;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Referral Code</th>
                    <th>Referred By</th>
                    <th>Their Referrals</th>
                    <th>Points from Referrals</th>
                    <th>Joined</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="referralsTableBody">
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Referred Users Drawer / Detail Panel -->
<div id="referralDetailPanel" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-diagram-2 me-2"></i>
                    Referrals by <span id="detailUserName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="detailReferralsList" class="p-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Change Referrer Modal -->
<div id="changeReferrerModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Change Referrer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Changing referrer for:</p>
                <div class="p-3 rounded mb-3" style="background:var(--bg-secondary,#f8f9fa);">
                    <div class="fw-bold" id="crUserName"></div>
                    <div class="small text-muted">ID: <span id="crUserId"></span> | Code: <span id="crUserCode"></span></div>
                    <div class="small text-muted mt-1">Current referrer: <span class="fw-semibold" id="crCurrentReferrer">—</span></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">New Referrer</label>
                    <div class="position-relative">
                        <input type="text" id="crSearchInput" class="form-control"
                               placeholder="Search by name, phone, or referral code..."
                               autocomplete="off" oninput="searchReferrerCandidates()">
                        <div id="crSearchResults" class="position-absolute w-100 bg-white border rounded shadow-sm"
                             style="z-index:1060; max-height:220px; overflow-y:auto; display:none;"></div>
                    </div>
                    <div id="crSelectedBox" class="mt-2" style="display:none;">
                        <div class="p-2 rounded d-flex justify-content-between align-items-center"
                             style="background:rgba(99,102,241,.08); border:1px solid rgba(99,102,241,.3);">
                            <div>
                                <span class="fw-semibold" id="crSelectedName"></span>
                                <span class="small text-muted ms-2" id="crSelectedMeta"></span>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2"
                                    onclick="clearSelectedReferrer()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="crRemoveReferrer">
                    <label class="form-check-label text-danger small" for="crRemoveReferrer">
                        Remove referrer entirely (set to Direct / no referrer)
                    </label>
                </div>

                <div class="alert alert-warning small mb-0 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Note:</strong> This changes the <code>referred_by</code> field only. Historical
                    referral transaction points are <strong>not</strong> recalculated automatically.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="crConfirmBtn" onclick="confirmChangeReferrer()">
                    <i class="bi bi-check2 me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let referralsData = [];
let crSelectedUserId = null;
let crTargetUserId = null;
let detailModal, changeRefModal;
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', function () {
    detailModal    = new bootstrap.Modal(document.getElementById('referralDetailPanel'));
    changeRefModal = new bootstrap.Modal(document.getElementById('changeReferrerModal'));

    document.getElementById('crRemoveReferrer').addEventListener('change', function () {
        const box = document.getElementById('crSearchInput').closest('.mb-3');
        box.style.opacity = this.checked ? '0.4' : '1';
        box.style.pointerEvents = this.checked ? 'none' : '';
        if (this.checked) clearSelectedReferrer();
    });

    loadReferrals();
});

function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadReferrals, 400);
}

async function loadReferrals() {
    const filter = document.getElementById('filterSelect').value;
    const search = document.getElementById('searchInput').value.trim();
    const tbody  = document.getElementById('referralsTableBody');

    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

    if ($.fn.dataTable.isDataTable('#referralsTable')) {
        $('#referralsTable').DataTable().destroy();
    }

    try {
        const url = `../api/admin/referrals.php?filter=${filter}&search=${encodeURIComponent(search)}`;
        const res  = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">${json.message}</td></tr>`;
            return;
        }

        referralsData = json.data || [];

        const s = json.stats || {};
        document.getElementById('statTotal').textContent    = s.total_users    || 0;
        document.getElementById('statReferred').textContent = s.total_referred  || 0;
        document.getElementById('statDirect').textContent   = s.direct_signups  || 0;
        document.getElementById('statLinks').textContent    = s.total_referral_links || 0;

        renderTable(referralsData);
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-danger">Server Error</td></tr>';
    }
}

function renderTable(data) {
    const tbody = document.getElementById('referralsTableBody');

    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No users found</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(u => {
        const safeName  = (u.full_name || '').replace(/'/g, "\\'");
        const safeCode  = (u.referral_code || '').replace(/'/g, "\\'");
        const refName   = u.referrer_name
            ? `<div class="fw-semibold">${escHtml(u.referrer_name)}</div><div class="small text-muted">${escHtml(u.referrer_code || '')} · ${escHtml(u.referrer_phone || '')}</div>`
            : '<span class="badge bg-secondary">Direct</span>';
        const date = new Date(u.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});

        const refCountBadge = u.referral_count > 0
            ? `<button class="btn btn-sm btn-outline-primary" onclick='openDetail(${u.id})'>
                   <i class="bi bi-people me-1"></i>${u.referral_count} referral${u.referral_count > 1 ? 's' : ''}
               </button>`
            : `<span class="text-muted small">0</span>`;

        const pts = parseInt(u.points_earned_from_referrals || 0).toLocaleString();

        return `
        <tr>
            <td class="fw-bold">#${u.id}</td>
            <td>
                <div class="fw-bold">${escHtml(u.full_name || '—')}</div>
                <div class="small text-muted">${escHtml(u.phone || '')} ${u.email ? '· ' + escHtml(u.email) : ''}</div>
            </td>
            <td><code class="text-primary">${escHtml(u.referral_code || '—')}</code></td>
            <td>${refName}</td>
            <td>${refCountBadge}</td>
            <td class="fw-bold text-success">${pts}</td>
            <td class="small">${date}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary"
                        onclick="openChangeReferrer(${u.id}, '${safeName}', '${safeCode}', ${u.referred_by || 'null'}, '${escHtml(u.referrer_name || '')}')">
                    <i class="bi bi-person-gear me-1"></i>Change Referrer
                </button>
            </td>
        </tr>`;
    }).join('');

    $('#referralsTable').DataTable({
        ordering: true,
        responsive: true,
        pageLength: 25,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        order: [[4, 'desc']],
        columnDefs: [{ orderable: false, targets: [7] }]
    });
}

/* ── Detail panel: who did this user refer ─────────────────────────────── */
function openDetail(userId) {
    const user = referralsData.find(u => u.id == userId);
    if (!user) return;

    document.getElementById('detailUserName').textContent = user.full_name;

    // Filter referralsData for users referred by this person
    const referredUsers = referralsData.filter(u => u.referred_by == userId);

    const listEl = document.getElementById('detailReferralsList');

    if (!referredUsers.length) {
        listEl.innerHTML = '<p class="text-muted text-center py-4">No referrals found in current view.<br><small>Try refreshing with "All Users" filter.</small></p>';
    } else {
        listEl.innerHTML = `
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Referral Code</th>
                    <th>Joined</th>
                    <th>Points Earned</th>
                </tr>
            </thead>
            <tbody>
                ${referredUsers.map(u => {
                    const d = new Date(u.created_at).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
                    return `
                    <tr>
                        <td>#${u.id}</td>
                        <td class="fw-semibold">${escHtml(u.full_name || '—')}</td>
                        <td class="small">${escHtml(u.phone || '—')}</td>
                        <td><code>${escHtml(u.referral_code || '—')}</code></td>
                        <td class="small">${d}</td>
                        <td class="text-success fw-bold">${parseInt(u.total_points || 0).toLocaleString()}</td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>`;
    }

    detailModal.show();
}

/* ── Change Referrer modal ─────────────────────────────────────────────── */
function openChangeReferrer(userId, userName, userCode, currentReferrerId, currentReferrerName) {
    crTargetUserId  = userId;
    crSelectedUserId = null;

    document.getElementById('crUserName').textContent        = userName;
    document.getElementById('crUserId').textContent          = userId;
    document.getElementById('crUserCode').textContent        = userCode;
    document.getElementById('crCurrentReferrer').textContent = currentReferrerName || 'None (Direct)';
    document.getElementById('crSearchInput').value           = '';
    document.getElementById('crSearchResults').style.display = 'none';
    document.getElementById('crSelectedBox').style.display   = 'none';
    document.getElementById('crRemoveReferrer').checked      = false;

    const searchBox = document.getElementById('crSearchInput').closest('.mb-3');
    searchBox.style.opacity       = '';
    searchBox.style.pointerEvents = '';

    changeRefModal.show();
}

let searchTimer = null;
function searchReferrerCandidates() {
    clearTimeout(searchTimer);
    const q = document.getElementById('crSearchInput').value.trim();
    const resultsEl = document.getElementById('crSearchResults');

    if (q.length < 2) {
        resultsEl.style.display = 'none';
        return;
    }

    searchTimer = setTimeout(async () => {
        try {
            const res  = await fetch(`../api/admin/referrals.php?action=search_users&q=${encodeURIComponent(q)}`);
            const json = await res.json();
            const items = (json.data || []).filter(u => u.id != crTargetUserId);

            if (!items.length) {
                resultsEl.innerHTML = '<div class="px-3 py-2 text-muted small">No users found</div>';
            } else {
                resultsEl.innerHTML = items.map(u => `
                    <div class="px-3 py-2 cursor-pointer referrer-option"
                         style="cursor:pointer;"
                         onmouseover="this.style.background='#f0f0f0'"
                         onmouseout="this.style.background=''"
                         onclick="selectReferrer(${u.id}, '${(u.full_name||'').replace(/'/g,"\\'")}', '${u.referral_code}', '${u.phone||''}')">
                        <div class="fw-semibold">${escHtml(u.full_name || '—')}</div>
                        <div class="small text-muted">${escHtml(u.phone || '')} · Code: ${escHtml(u.referral_code)}</div>
                    </div>`).join('');
            }
            resultsEl.style.display = 'block';
        } catch (e) {
            console.error(e);
        }
    }, 300);
}

function selectReferrer(id, name, code, phone) {
    crSelectedUserId = id;
    document.getElementById('crSearchInput').value           = '';
    document.getElementById('crSearchResults').style.display = 'none';
    document.getElementById('crSelectedName').textContent    = name;
    document.getElementById('crSelectedMeta').textContent    = `Code: ${code} · ${phone}`;
    document.getElementById('crSelectedBox').style.display   = 'block';
}

function clearSelectedReferrer() {
    crSelectedUserId = null;
    document.getElementById('crSelectedBox').style.display = 'none';
    document.getElementById('crSelectedName').textContent  = '';
    document.getElementById('crSelectedMeta').textContent  = '';
}

// Close search results when clicking outside
document.addEventListener('click', function (e) {
    const box = document.getElementById('crSearchResults');
    if (box && !box.contains(e.target) && e.target.id !== 'crSearchInput') {
        box.style.display = 'none';
    }
});

async function confirmChangeReferrer() {
    const removeFlag = document.getElementById('crRemoveReferrer').checked;

    if (!removeFlag && crSelectedUserId === null) {
        alert('Please select a new referrer, or check "Remove referrer entirely".');
        return;
    }

    const btn = document.getElementById('crConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    try {
        const res  = await fetch('../api/admin/referrals.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                action:          'change_referrer',
                user_id:         crTargetUserId,
                new_referrer_id: removeFlag ? null : crSelectedUserId
            })
        });
        const json = await res.json();

        if (json.success) {
            changeRefModal.hide();
            showToast.success(json.message);
            await loadReferrals();
        } else {
            alert('Error: ' + json.message);
        }
    } catch (err) {
        alert('Server error');
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Save Changes';
    }
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>
