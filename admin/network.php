<?php
$page_title = 'Referral Network';
$header_title = 'Network Hierarchy';
require_once 'includes/header.php';
require_once '../includes/db.php';
$pdo = getDB();



// Fetch all users with referral info
$stmt = $pdo->query("SELECT id, full_name, referral_code, referred_by, created_at FROM users WHERE is_deleted = 0 ORDER BY created_at ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build tree structure
$tree = [];
$userMap = [];

foreach ($users as $user) {
    $userMap[$user['id']] = $user;
    $userMap[$user['id']]['children'] = [];
}

foreach ($users as $user) {
    if ($user['referred_by'] && isset($userMap[$user['referred_by']])) {
        $userMap[$user['referred_by']]['children'][] = &$userMap[$user['id']];
    } else {
        $tree[] = &$userMap[$user['id']];
    }
}

// Recursive function to render tree as org chart
function renderOrgChart($nodes, $isRoot = false) {
    if (empty($nodes)) return;
    $rootClass = $isRoot ? 'org-children-root' : '';
    ?>
    <ul class="<?php echo $rootClass; ?>">
    <?php foreach ($nodes as $node): 
        $hasChildren = !empty($node['children']);
        $childCount = count($node['children']);
    ?>
        <li>
            <div class="org-node">
                <div class="org-avatar"><?php echo strtoupper(substr($node['full_name'], 0, 1)); ?></div>
                <div class="org-info">
                    <span class="org-name"><?php echo htmlspecialchars($node['full_name']); ?></span>
                    <span class="org-code"><?php echo $node['referral_code']; ?></span>
                </div>
                <?php if ($childCount > 0): ?>
                <span class="org-badge"><?php echo $childCount; ?></span>
                <?php endif; ?>
            </div>
            <?php if ($hasChildren): ?>
                <?php renderOrgChart($node['children']); ?>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php
}
?>

<style>
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
}

.stat-card h3 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
}

.stat-card p {
    color: var(--text-muted);
    margin: 8px 0 0;
    font-size: 0.9rem;
    font-weight: 500;
}

.org-container {
    background: var(--card-bg);
    border-radius: 20px;
    border: 1px solid var(--border);
    overflow: hidden;
    position: relative;
    cursor: grab;
    height: 70vh;
    user-select: none;
}
.org-container.dragging { cursor: grabbing; }
.org-canvas {
    position: absolute;
    top: 0; left: 0;
    padding: 40px 20px;
    transform-origin: 0 0;
    will-change: transform;
}

/* ========== ORG CHART TREE ========== */
.org-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: max-content;
}

.org-tree ul {
    padding-top: 40px;
    position: relative;
    display: flex;
    justify-content: center;
    list-style: none;
    margin: 0;
}

/* Vertical line down from parent node */
.org-tree ul::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    border-left: 2px solid #94a3b8;
    height: 40px;
}

.org-tree li {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    padding: 40px 12px 0 12px;
}

/* Horizontal line across siblings */
.org-tree li::before,
.org-tree li::after {
    content: '';
    position: absolute;
    top: 0;
    right: 50%;
    border-top: 2px solid #94a3b8;
    width: 50%;
    height: 40px;
}

.org-tree li::after {
    right: auto;
    left: 50%;
    border-left: 2px solid #94a3b8;
}

/* Only child - just vertical line */
.org-tree li:only-child::before,
.org-tree li:only-child::after {
    display: none;
}

.org-tree li:only-child {
    padding-top: 40px;
}

/* First child - no left border */
.org-tree li:first-child::before {
    border: none;
}

/* Last child - no right border */
.org-tree li:last-child::after {
    border-top: none;
}

/* Root children connect to center vertical line */
.org-children-root {
    padding-top: 0 !important;
}

.org-children-root::before {
    display: none;
}

.org-children-root > li {
    padding-top: 40px;
}

.org-children-root > li::before,
.org-children-root > li::after {
    top: 0;
    height: 40px;
}

/* ========== ROOT NODE (AALAYA) ========== */
.org-root {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    padding-bottom: 40px;
}

/* Vertical line from AALAYA to horizontal bar */
.org-root::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 2px;
    height: 40px;
    background: #94a3b8;
}

.org-root-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 28px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    position: relative;
    z-index: 2;
}

.org-root-logo {
    width: 56px;
    height: 56px;
    background: white;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
}

.org-root-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.org-root-info h2 {
    color: white;
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
}

.org-root-info p {
    color: rgba(255,255,255,0.6);
    margin: 4px 0 0;
    font-size: 0.85rem;
}

/* ========== USER NODES ========== */
.org-node {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    border: 2px solid #e2e8f0;
    padding: 12px 18px;
    border-radius: 14px;
    min-width: 160px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.25s ease;
    position: relative;
    z-index: 1;
}

.org-node:hover {
    border-color: #3b82f6;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
    transform: translateY(-3px);
}

.org-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
}

.org-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.org-name {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.85rem;
    white-space: nowrap;
}

.org-code {
    font-size: 0.7rem;
    color: #64748b;
    font-family: monospace;
}

.org-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #2563eb;
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4);
}

@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .org-container {
        padding: 20px 10px;
        max-width: 100%; /* Force scroll */
    }
    
    /* Compact Tree for Mobile */
    .org-tree ul { padding-top: 20px; }
    .org-tree ul::before { height: 20px; }
    .org-tree li { padding: 20px 8px 0 8px; }
    .org-tree li::before, .org-tree li::after { height: 20px; }
    .org-children-root > li { padding-top: 20px; }
    .org-children-root > li::before, .org-children-root > li::after { height: 20px; }
    .org-root { padding-bottom: 20px; }
    .org-root::after { height: 20px; }
    
    .modal-dialog {
        margin: 10px;
        width: auto;
        max-width: none;
    }
}
</style>

<div class="stats-row">
    <div class="stat-card">
        <h3><?php echo count($users); ?></h3>
        <p>Total Users</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($tree); ?></h3>
        <p>Direct Joins</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($users) - count($tree); ?></h3>
        <p>Referred Users</p>
    </div>
</div>

<!-- Zoom + Pan Controls -->
<div style="display:flex; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap;">
    <button onclick="zoomTree(-0.1)" class="btn btn-sm btn-secondary"><i class="bi bi-zoom-out"></i> Zoom Out</button>
    <button onclick="zoomTree(0.1)"  class="btn btn-sm btn-secondary"><i class="bi bi-zoom-in"></i> Zoom In</button>
    <button onclick="resetView()"    class="btn btn-sm btn-outline-secondary"><i class="bi bi-fullscreen-exit"></i> Reset</button>
    <span id="zoomLabel" style="font-size:0.8rem; color:var(--text-muted);">100%</span>
    <span style="font-size:0.78rem; color:var(--text-muted); margin-left:8px;"><i class="bi bi-hand-index"></i> Drag to pan</span>
</div>

<div class="org-container" id="orgContainer">
    <div class="org-canvas" id="orgCanvas">
    <div class="org-tree" id="orgTree">
        <!-- Root Node -->
        <div class="org-root">
            <div class="org-root-card">
                <div class="org-root-logo">
                    <img src="../assets/images/logo.png" alt="Aalaya">
                </div>
                <div class="org-root-info">
                    <h2>AALAYA</h2>
                    <p><?php echo count($tree); ?> direct members</p>
                </div>
            </div>
        </div>

        <!-- User Hierarchy -->
        <?php renderOrgChart($tree, true); ?>
    </div>
    </div><!-- /org-canvas -->
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ---- Pan + Zoom ----
const container = document.getElementById('orgContainer');
const canvas    = document.getElementById('orgCanvas');
let scale = 1, panX = 0, panY = 0;
let dragging = false, startX = 0, startY = 0, startPanX = 0, startPanY = 0;

function applyTransform() {
    canvas.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
    document.getElementById('zoomLabel').textContent = Math.round(scale * 100) + '%';
}

function zoomTree(delta) {
    scale = Math.min(2, Math.max(0.2, scale + delta));
    applyTransform();
}

function resetView() {
    scale = 1; panX = 0; panY = 0;
    applyTransform();
}

// Mouse drag
container.addEventListener('mousedown', e => {
    if (e.button !== 0) return;
    dragging = true;
    startX = e.clientX; startY = e.clientY;
    startPanX = panX;   startPanY = panY;
    container.classList.add('dragging');
});
window.addEventListener('mousemove', e => {
    if (!dragging) return;
    panX = startPanX + (e.clientX - startX);
    panY = startPanY + (e.clientY - startY);
    applyTransform();
});
window.addEventListener('mouseup', () => {
    dragging = false;
    container.classList.remove('dragging');
});

// Touch drag
container.addEventListener('touchstart', e => {
    const t = e.touches[0];
    dragging = true;
    startX = t.clientX; startY = t.clientY;
    startPanX = panX;   startPanY = panY;
}, { passive: true });
container.addEventListener('touchmove', e => {
    if (!dragging) return;
    const t = e.touches[0];
    panX = startPanX + (t.clientX - startX);
    panY = startPanY + (t.clientY - startY);
    applyTransform();
}, { passive: true });
container.addEventListener('touchend', () => { dragging = false; });

// Scroll to zoom
container.addEventListener('wheel', e => {
    e.preventDefault();
    zoomTree(e.deltaY < 0 ? 0.05 : -0.05);
}, { passive: false });

</script>

<?php require_once 'includes/footer.php'; ?>
