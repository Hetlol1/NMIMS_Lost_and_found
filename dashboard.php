<?php
include 'config.php';

// Block admins from user dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$uid = $_SESSION['user_id'];
$pRes = mysqli_query($conn, "SELECT profile_photo, name FROM users WHERE id='$uid'");
$pRow = mysqli_fetch_assoc($pRes);
$avatarSrc      = (!empty($pRow['profile_photo'])) ? $pRow['profile_photo'] : '';
$encodedName    = urlencode($pRow['name'] ?? 'User');
$fallbackAvatar = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=80";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NMIMS Lost & Found Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;}
header{background:#003366;color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;}
.header-left{display:flex;align-items:center;gap:12px;}
.header-right{display:flex;align-items:center;gap:15px;}
.avatar-link{display:flex;align-items:center;gap:8px;text-decoration:none;color:white;}
.avatar-link img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);}
.avatar-link:hover img{border-color:white;}
.container{padding:20px;max-width:1200px;margin:auto;}
.upload-btn{background:#28a745;color:white;padding:12px 25px;border-radius:5px;text-decoration:none;display:inline-block;margin-bottom:20px;font-weight:bold;}
.upload-btn:hover{background:#218838;}
.tabs{display:flex;gap:0;margin-bottom:25px;border-bottom:2px solid #ddd;}
.tab-btn{padding:12px 28px;background:none;border:none;cursor:pointer;font-size:1rem;color:#666;font-family:'Segoe UI',sans-serif;border-bottom:3px solid transparent;margin-bottom:-2px;transition:0.2s;}
.tab-btn.active{color:#003366;border-bottom-color:#003366;font-weight:bold;}
.tab-btn:hover:not(.active){color:#003366;background:#f8f9fa;}
.tab-pane{display:none;}
.tab-pane.active{display:block;}
.search-filter-section{background:white;padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.search-row{display:flex;gap:15px;flex-wrap:wrap;align-items:center;}
.search-input,.filter-select{padding:12px;border:1px solid #ddd;border-radius:5px;font-size:1rem;}
.search-input{flex:1;min-width:250px;}
.search-btn{background:#007bff;color:white;padding:12px 20px;border:none;border-radius:5px;cursor:pointer;}
.search-btn:hover{background:#0056b3;}
.clear-btn{background:#dc3545;color:white;padding:12px 20px;border:none;border-radius:5px;cursor:pointer;}
.clear-btn:hover{background:#c82333;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;}
.card{background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:0.2s;}
.card:hover{transform:translateY(-5px);}
.card img{width:100%;height:180px;object-fit:cover;}
.card-body{padding:15px;}
.card-title{font-size:1.1rem;font-weight:bold;margin-bottom:8px;}
.card-desc{font-size:0.9rem;color:#666;margin-bottom:12px;}
.btn{background:#003366;color:white;border:none;padding:10px 20px;cursor:pointer;border-radius:5px;font-size:0.9rem;margin:5px 0;}
.btn:hover{background:#004080;}
.btn:disabled{background:#ccc;cursor:not-allowed;}
.btn-danger{background:#dc3545;}
.btn-danger:hover{background:#c82333;}
.btn-success{background:#28a745;}
.btn-success:hover{background:#218838;}
.status-badge{display:inline-block;padding:4px 10px;border-radius:12px;font-size:0.75rem;font-weight:bold;margin-bottom:8px;}
.status-lost{background:#ffebee;color:#c62828;}
.status-found{background:#e8f5e9;color:#2e7d32;}
.status-claimed{background:#e3f2fd;color:#1565c0;}
.status-pending{background:#fff8e1;color:#f57f17;}
.myitems-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.myitems-header h3{color:#003366;font-size:1.3rem;}
.empty-state{text-align:center;padding:60px 20px;color:#999;}
.empty-state .icon{font-size:3rem;margin-bottom:10px;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;}
.modal-content{background:white;width:450px;height:550px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;border-radius:10px;}
.chat-header{background:#003366;color:white;padding:15px;display:flex;justify-content:space-between;border-radius:10px 10px 0 0;}
.chat-body{flex:1;padding:15px;overflow-y:auto;background:#e5ddd5;}
.chat-footer{padding:15px;border-top:1px solid #ddd;display:flex;gap:10px;}
.msg{padding:10px 15px;margin:8px 0;border-radius:15px;max-width:75%;}
.msg.sent{background:#dcf8c6;margin-left:auto;}
.msg.received{background:white;}
</style>
</head>
<body>
<header>
  <div class="header-left">
    <img src="nmims-university-logo.png" alt="NMIMS Logo" style="height:45px;object-fit:contain;filter:brightness(0) invert(1);">
    <h3>NMIMS Lost &amp; Found</h3>
  </div>
  <div class="header-right">
    <span id="welcomeMsg"></span>
    <a href="profile.php" class="avatar-link" title="My Profile">
      <img
        src="<?php echo $avatarSrc ? htmlspecialchars($avatarSrc) : $fallbackAvatar; ?>"
        alt="Profile"
        onerror="this.onerror=null;this.src='<?php echo $fallbackAvatar; ?>';">
      <span>Profile</span>
    </a>
    <a href="logout.php" style="color:white;text-decoration:none;">Logout</a>
  </div>
</header>

<div class="container">
  <h2 style="margin-bottom:15px;">Items Database</h2>
  <a href="upload.php" class="upload-btn">+ Upload Item</a>

  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('all', this)">🗂 All Items</button>
    <button class="tab-btn" onclick="switchTab('myitems', this)">📦 My Items</button>
  </div>

  <!-- TAB: ALL ITEMS -->
  <div id="tab-all" class="tab-pane active">
    <div class="search-filter-section">
      <div class="search-row">
        <input type="text" id="searchInput" class="search-input" placeholder="Search by title...">
        <button onclick="applyFilters()" class="search-btn">Search</button>
        <select id="statusFilter" class="filter-select">
          <option value="">All Status</option>
          <option value="lost">Lost</option>
          <option value="found">Found</option>
          <option value="pending">Pending</option>
          <option value="claimed">Claimed</option>
        </select>
        <button onclick="clearFilters()" class="clear-btn">Clear</button>
      </div>
    </div>
    <div class="grid" id="itemGrid"></div>
  </div>

  <!-- TAB: MY ITEMS -->
  <div id="tab-myitems" class="tab-pane">
    <div class="myitems-header">
      <h3>📦 My Uploaded Items</h3>
      <a href="upload.php" class="upload-btn" style="margin-bottom:0;">+ Upload New</a>
    </div>
    <div class="grid" id="myItemGrid"></div>
  </div>
</div>

<!-- Chat Modal -->
<div id="chatModal" class="modal">
  <div class="modal-content">
    <div class="chat-header">
      <span id="chatTitle">Chat</span>
      <button onclick="closeChat()" style="background:none;border:none;color:white;font-size:1.5rem;">&times;</button>
    </div>
    <div class="chat-body" id="chatBody"></div>
    <div class="chat-footer">
      <input type="text" id="msgInput" style="flex:1;padding:10px;border:1px solid #ddd;border-radius:5px;">
      <button onclick="sendMessage()" class="btn">Send</button>
    </div>
  </div>
</div>

<script>
const userId   = <?php echo json_encode((int)$_SESSION['user_id']); ?>;
const userName = <?php echo json_encode($_SESSION['name'] ?? 'User'); ?>;
document.getElementById('welcomeMsg').innerText = "Welcome, " + userName;

let currentItemId = null;
let allItems = [];

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g,
        m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

fetch('get_items.php')
    .then(res => res.json())
    .then(data => {
        allItems = data;
        displayItems(data);
        // ── FIX: support both 'user_id' and 'owner_id' as the owner field ──
        displayMyItems(data.filter(i => {
            const ownerId = parseInt(i.user_id ?? i.owner_id ?? -1);
            return ownerId === userId;
        }));
    })
    .catch(() => {
        document.getElementById('itemGrid').innerHTML =
            '<div class="empty-state"><div class="icon">⚠️</div><p>Failed to load items.</p></div>';
    });

function getOwnerId(item) {
    // Normalise: DB column may be user_id or owner_id
    return parseInt(item.user_id ?? item.owner_id ?? -1);
}

function getFoundBy(item) {
    return parseInt(item.found_by ?? -1);
}

function buildCard(item, showDelete = false) {
    const ownerId   = getOwnerId(item);
    const foundBy   = getFoundBy(item);
    const status    = item.status || 'lost';
    const statusCls = 'status-' + status;
    const rawTitle  = item.title || 'Item';
    const safeTitle = rawTitle.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const imagePath = item.image_path ? item.image_path.replace(/^\/+/, '') : '';
    const imgFallback = "https://ui-avatars.com/api/?name=" + encodeURIComponent(rawTitle) + "&background=e9ecef&color=003366&size=200";

    let actionButton = '';

    if (status === 'claimed') {
        actionButton = `<button class="btn" disabled>Claimed</button>`;

    } else if (status === 'lost') {
        if (ownerId === userId) {
            actionButton = `<button class="btn" disabled style="background:#6c757d;cursor:not-allowed;">You Lost This</button>`;
        } else {
            // ── This is the "I Found This" button ──
            actionButton = `<button class="btn btn-success" onclick="reportFound(${item.id})">I Found This</button>`;
        }

    } else if (status === 'pending') {
        if (ownerId === userId) {
            actionButton = `<button class="btn" style="background:#fd7e14;" onclick="confirmClaim(${item.id})">Confirm Claim ✓</button>`;
        } else if (foundBy === userId) {
            actionButton = `<button class="btn" disabled style="background:#fd7e14;cursor:not-allowed;">Pending Confirmation</button>`;
        } else {
            actionButton = `<button class="btn" disabled>Pending</button>`;
        }

    } else if (status === 'found') {
        if (ownerId === userId) {
            actionButton = `<button class="btn" disabled style="background:#6c757d;cursor:not-allowed;">You Found This</button>`;
        } else {
            actionButton = `<button class="btn" onclick="claimItem(${item.id})">Claim Item</button>`;
        }
    }

    return `
    <div class="card">
        <img src="${imagePath ? escapeHtml(imagePath) : imgFallback}"
             onerror="this.onerror=null;this.src='${imgFallback}'">
        <div class="card-body">
            <span class="status-badge ${statusCls}">${escapeHtml(status.toUpperCase())}</span>
            <div class="card-title">${escapeHtml(rawTitle)}</div>
            <div class="card-desc">${escapeHtml(item.description || '')}</div>
            <button class="btn" onclick="openChat(${item.id},'${safeTitle}')">Chat</button>
            ${actionButton}
            ${showDelete ? `<button class="btn btn-danger" onclick="deleteItem(${item.id})">Delete</button>` : ''}
        </div>
    </div>`;
}

function displayItems(items) {
    const grid = document.getElementById('itemGrid');
    if (!items.length) {
        grid.innerHTML = '<div class="empty-state"><div class="icon">📭</div><p>No items found.</p></div>';
        return;
    }
    grid.innerHTML = items.map(i => buildCard(i, false)).join('');
}

function displayMyItems(items) {
    const grid = document.getElementById('myItemGrid');
    if (!items.length) {
        grid.innerHTML = '<div class="empty-state"><div class="icon">📦</div><p>You haven\'t uploaded any items yet.</p></div>';
        return;
    }
    grid.innerHTML = items.map(i => buildCard(i, true)).join('');
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    displayItems(allItems.filter(i =>
        (!search || (i.title || '').toLowerCase().includes(search)) &&
        (!status || i.status === status)
    ));
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    displayItems(allItems);
}

// ── I Found This ────────────────────────────────────────────────────────────
function reportFound(id) {
    if (!confirm("Confirm that you found this item?")) return;
    fetch('report_found.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + encodeURIComponent(id)
    })
    .then(r => r.text())          // get raw text first to debug if needed
    .then(raw => {
        let d;
        try { d = JSON.parse(raw); }
        catch(e) { alert("Server error:\n" + raw); return; }
        alert(d.message || d.error || 'Done');
        if (d.status === 'success') location.reload();
    })
    .catch(err => alert("Network error: " + err));
}

// ── Claim / Confirm ──────────────────────────────────────────────────────────
function confirmClaim(id) {
    if (!confirm("Confirm this person has returned your item?")) return;
    fetch('claim_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(d => { alert(d.message); if (d.status === 'success') location.reload(); });
}

function claimItem(id) {
    if (!confirm("Claim this item?")) return;
    fetch('claim_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(d => { alert(d.message); if (d.status === 'success') location.reload(); });
}

function deleteItem(id) {
    if (!confirm("Delete this item?")) return;
    fetch('delete_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(d => { alert(d.message); location.reload(); });
}

// ── Chat ─────────────────────────────────────────────────────────────────────
function openChat(id, title) {
    currentItemId = id;
    document.getElementById('chatModal').style.display = 'block';
    document.getElementById('chatTitle').innerText = "Chat: " + title;
    loadMessages();
}
function closeChat() { document.getElementById('chatModal').style.display = 'none'; }

function sendMessage() {
    const msg = document.getElementById('msgInput').value.trim();
    if (!msg || !currentItemId) return;
    const fd = new FormData();
    fd.append('item_id', currentItemId);
    fd.append('message', msg);
    fetch('send_message.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                document.getElementById('msgInput').value = '';
                loadMessages();
            } else {
                alert('Failed to send message');
            }
        }).catch(() => alert('Error sending message'));
}

function loadMessages() {
    if (!currentItemId) return;
    fetch(`get_messages.php?item_id=${currentItemId}`)
        .then(r => r.json())
        .then(msgs => {
            const body = document.getElementById('chatBody');
            body.innerHTML = '';
            msgs.forEach(m => {
                const div = document.createElement('div');
                div.className = `msg ${parseInt(m.sender_id) === userId ? 'sent' : 'received'}`;
                div.innerText = m.message;
                body.appendChild(div);
            });
            body.scrollTop = body.scrollHeight;
        }).catch(() => {
            document.getElementById('chatBody').innerHTML = '<p style="color:red;">Error loading messages</p>';
        });
}
</script>
</body>
</html>