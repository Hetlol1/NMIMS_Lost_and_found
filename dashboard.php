<?php
include 'config.php';

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
header{background:#003366;color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;}
.header-left{display:flex;align-items:center;gap:12px;}
.header-right{display:flex;align-items:center;gap:15px;}
.avatar-link{display:flex;align-items:center;gap:8px;text-decoration:none;color:white;}
.avatar-link img{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);}
.avatar-link:hover img{border-color:white;}

.page-body{display:flex;gap:0;height:calc(100vh - 70px);}
.main-content{flex:1;overflow-y:auto;padding:20px;transition:margin-right 0.3s;}
.main-content.chat-open{margin-right:360px;}

/* Side Chat Panel */
.chat-panel{position:fixed;right:0;top:70px;width:360px;height:calc(100vh - 70px);background:white;box-shadow:-3px 0 15px rgba(0,0,0,0.12);display:flex;flex-direction:column;transform:translateX(100%);transition:transform 0.3s ease;z-index:99;}
.chat-panel.open{transform:translateX(0);}
.chat-panel-header{background:#003366;color:white;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;}
.chat-panel-header .chat-title{font-weight:bold;font-size:0.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;}
.chat-close-btn{background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;padding:0 4px;line-height:1;}
.chat-panel-body{flex:1;padding:15px;overflow-y:auto;background:#e5ddd5;}
.chat-panel-footer{padding:12px;border-top:1px solid #ddd;display:flex;gap:8px;flex-shrink:0;}
.chat-panel-footer input{flex:1;padding:10px;border:1px solid #ddd;border-radius:20px;font-size:0.9rem;outline:none;}
.chat-panel-footer input:focus{border-color:#003366;}
.chat-panel-footer button{background:#003366;color:white;border:none;border-radius:20px;padding:10px 18px;cursor:pointer;font-size:0.9rem;}
.chat-panel-footer button:hover{background:#004080;}
.msg{padding:9px 13px;margin:6px 0;border-radius:15px;max-width:78%;word-wrap:break-word;font-size:0.9rem;line-height:1.4;}
.msg.sent{background:#dcf8c6;margin-left:auto;border-bottom-right-radius:4px;}
.msg.received{background:white;border-bottom-left-radius:4px;box-shadow:0 1px 2px rgba(0,0,0,0.08);}
.msg .msg-sender{font-size:0.72rem;font-weight:bold;color:#003366;margin-bottom:3px;}
.msg .msg-time{font-size:0.68rem;color:#999;margin-top:3px;text-align:right;}
.chat-empty{text-align:center;color:#aaa;padding:40px 20px;font-size:0.9rem;}

.container{max-width:1200px;margin:auto;}
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
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;}
.card{background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:0.2s;}
.card:hover{transform:translateY(-3px);}
.card img{width:100%;height:180px;object-fit:cover;}
.card-body{padding:15px;}
.card-title{font-size:1.05rem;font-weight:bold;margin-bottom:6px;}
.card-desc{font-size:0.88rem;color:#666;margin-bottom:12px;}
.btn{background:#003366;color:white;border:none;padding:9px 18px;cursor:pointer;border-radius:5px;font-size:0.88rem;margin:3px 0;}
.btn:hover{background:#004080;}
.btn:disabled{background:#ccc;cursor:not-allowed;}
.btn-danger{background:#dc3545;}
.btn-danger:hover{background:#c82333;}
.btn-success{background:#28a745;}
.btn-success:hover{background:#218838;}
.btn-chat{background:#003366;}
.btn-chat:hover{background:#004080;}
.btn-chat.active-chat{background:#28a745;}
.status-badge{display:inline-block;padding:4px 10px;border-radius:12px;font-size:0.75rem;font-weight:bold;margin-bottom:8px;}
.status-lost{background:#ffebee;color:#c62828;}
.status-found{background:#e8f5e9;color:#2e7d32;}
.status-claimed{background:#e3f2fd;color:#1565c0;}
.status-pending{background:#fff8e1;color:#f57f17;}
.myitems-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.myitems-header h3{color:#003366;font-size:1.3rem;}
.empty-state{text-align:center;padding:60px 20px;color:#999;}
.empty-state .icon{font-size:3rem;margin-bottom:10px;}

/* Pagination */
.pagination-wrap{display:flex;justify-content:space-between;align-items:center;margin-top:25px;flex-wrap:wrap;gap:10px;}
.pagination{display:flex;gap:6px;flex-wrap:wrap;}
.page-btn{padding:8px 14px;border:1px solid #ddd;background:white;border-radius:5px;cursor:pointer;font-size:0.9rem;color:#003366;transition:0.15s;}
.page-btn:hover{background:#003366;color:white;border-color:#003366;}
.page-btn.active{background:#003366;color:white;border-color:#003366;font-weight:bold;}
.page-btn:disabled{background:#f8f9fa;color:#aaa;cursor:not-allowed;}
.page-info{font-size:0.88rem;color:#666;}
.items-per-page{display:flex;align-items:center;gap:8px;font-size:0.88rem;color:#666;}
.items-per-page select{padding:6px 10px;border:1px solid #ddd;border-radius:5px;font-size:0.88rem;}

@media(max-width:768px){
    .chat-panel{width:100%;top:70px;height:calc(100vh - 70px);}
    .main-content.chat-open{margin-right:0;}
    .pagination-wrap{flex-direction:column;align-items:flex-start;}
}
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
      <img src="<?php echo $avatarSrc ? htmlspecialchars($avatarSrc) : $fallbackAvatar; ?>"
           alt="Profile"
           onerror="this.onerror=null;this.src='<?php echo $fallbackAvatar; ?>';">
      <span>Profile</span>
    </a>
    <a href="logout.php" style="color:white;text-decoration:none;">Logout</a>
  </div>
</header>

<!-- Side Chat Panel -->
<div class="chat-panel" id="chatPanel">
  <div class="chat-panel-header">
    <span class="chat-title" id="chatTitle">Chat</span>
    <button class="chat-close-btn" onclick="closeChat()" title="Close">&times;</button>
  </div>
  <div class="chat-panel-body" id="chatBody">
    <div class="chat-empty">Select an item to start chatting</div>
  </div>
  <div class="chat-panel-footer">
    <input type="text" id="msgInput" placeholder="Type a message..." onkeydown="if(event.key==='Enter')sendMessage()">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<div class="main-content" id="mainContent">
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
      <div class="pagination-wrap" id="allPaginationWrap">
        <div class="items-per-page">
          Show
          <select id="allPerPage" onchange="allPage=1;displayItems(filteredItems)">
            <option value="8">8</option>
            <option value="12" selected>12</option>
            <option value="24">24</option>
            <option value="48">48</option>
          </select>
          per page
        </div>
        <div class="page-info" id="allPageInfo"></div>
        <div class="pagination" id="allPagination"></div>
      </div>
    </div>

    <!-- TAB: MY ITEMS -->
    <div id="tab-myitems" class="tab-pane">
      <div class="myitems-header">
        <h3>📦 My Uploaded Items</h3>
        <a href="upload.php" class="upload-btn" style="margin-bottom:0;">+ Upload New</a>
      </div>
      <div class="grid" id="myItemGrid"></div>
      <div class="pagination-wrap" id="myPaginationWrap">
        <div class="items-per-page">
          Show
          <select id="myPerPage" onchange="myPage=1;displayMyItems(myItems)">
            <option value="8">8</option>
            <option value="12" selected>12</option>
            <option value="24">24</option>
            <option value="48">48</option>
          </select>
          per page
        </div>
        <div class="page-info" id="myPageInfo"></div>
        <div class="pagination" id="myPagination"></div>
      </div>
    </div>
  </div>
</div>

<script>
const userId   = <?php echo json_encode((int)$_SESSION['user_id']); ?>;
const userName = <?php echo json_encode($_SESSION['name'] ?? 'User'); ?>;
document.getElementById('welcomeMsg').innerText = "Welcome, " + userName;

let currentItemId    = null;
let allItems         = [];
let filteredItems    = [];
let myItems          = [];
let allPage          = 1;
let myPage           = 1;
let pollInterval     = null;

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
        allItems      = data;
        filteredItems = data;
        myItems       = data.filter(i => parseInt(i.owner_id ?? i.user_id ?? -1) === userId);
        displayItems(filteredItems);
        displayMyItems(myItems);
    })
    .catch(() => {
        document.getElementById('itemGrid').innerHTML =
            '<div class="empty-state"><div class="icon">⚠️</div><p>Failed to load items.</p></div>';
    });

function getOwnerId(item) { return parseInt(item.owner_id ?? item.user_id ?? -1); }
function getFoundBy(item) { return parseInt(item.found_by ?? -1); }

// ── Pagination helper ─────────────────────────────────────────────────────────
function paginate(items, page, perPage) {
    const total = items.length;
    const pages = Math.max(1, Math.ceil(total / perPage));
    const safePage = Math.min(Math.max(1, page), pages);
    const start = (safePage - 1) * perPage;
    return {
        slice:    items.slice(start, start + perPage),
        page:     safePage,
        pages,
        total,
        start:    start + 1,
        end:      Math.min(start + perPage, total)
    };
}

function renderPagination(containerId, infoId, currentPage, totalPages, total, start, end, onPageChange) {
    document.getElementById(infoId).textContent =
        total === 0 ? '' : `Showing ${start}–${end} of ${total} item${total !== 1 ? 's' : ''}`;

    const container = document.getElementById(containerId);
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    let html = '';

    // Prev
    html += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''}
                onclick="(${onPageChange})(${currentPage - 1})">&#8592;</button>`;

    // Page numbers with ellipsis
    const range = [];
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            range.push(i);
        } else if (range[range.length - 1] !== '...') {
            range.push('...');
        }
    }

    range.forEach(p => {
        if (p === '...') {
            html += `<button class="page-btn" disabled>…</button>`;
        } else {
            html += `<button class="page-btn ${p === currentPage ? 'active' : ''}"
                         onclick="(${onPageChange})(${p})">${p}</button>`;
        }
    });

    // Next
    html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''}
                onclick="(${onPageChange})(${currentPage + 1})">&#8594;</button>`;

    container.innerHTML = html;
}

// ── Display: All Items ────────────────────────────────────────────────────────
function displayItems(items) {
    const perPage = parseInt(document.getElementById('allPerPage').value);
    const p       = paginate(items, allPage, perPage);
    allPage       = p.page;

    const grid = document.getElementById('itemGrid');
    if (!items.length) {
        grid.innerHTML = '<div class="empty-state"><div class="icon">📭</div><p>No items found.</p></div>';
        document.getElementById('allPageInfo').textContent = '';
        document.getElementById('allPagination').innerHTML = '';
        return;
    }
    grid.innerHTML = p.slice.map(i => buildCard(i, false)).join('');

    renderPagination(
        'allPagination', 'allPageInfo',
        p.page, p.pages, p.total, p.start, p.end,
        'function(n){allPage=n;displayItems(filteredItems);document.getElementById("mainContent").scrollTo(0,0);}'
    );
}

// ── Display: My Items ─────────────────────────────────────────────────────────
function displayMyItems(items) {
    const perPage = parseInt(document.getElementById('myPerPage').value);
    const p       = paginate(items, myPage, perPage);
    myPage        = p.page;

    const grid = document.getElementById('myItemGrid');
    if (!items.length) {
        grid.innerHTML = '<div class="empty-state"><div class="icon">📦</div><p>You haven\'t uploaded any items yet.</p></div>';
        document.getElementById('myPageInfo').textContent = '';
        document.getElementById('myPagination').innerHTML = '';
        return;
    }
    grid.innerHTML = p.slice.map(i => buildCard(i, true)).join('');

    renderPagination(
        'myPagination', 'myPageInfo',
        p.page, p.pages, p.total, p.start, p.end,
        'function(n){myPage=n;displayMyItems(myItems);document.getElementById("mainContent").scrollTo(0,0);}'
    );
}

function buildCard(item, showDelete = false) {
    const ownerId     = getOwnerId(item);
    const foundBy     = getFoundBy(item);
    const status      = item.status || 'lost';
    const rawTitle    = item.title || 'Item';
    const safeTitle   = rawTitle.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const imagePath   = item.image_path ? item.image_path.replace(/^\/+/, '') : '';
    const imgFallback = "https://ui-avatars.com/api/?name=" + encodeURIComponent(rawTitle) + "&background=e9ecef&color=003366&size=200";
    const isActiveChat = currentItemId == item.id;

    let actionButton = '';
    if (status === 'claimed') {
        actionButton = `<button class="btn" disabled>Claimed</button>`;
    } else if (status === 'lost') {
        actionButton = ownerId === userId
            ? `<button class="btn" disabled style="background:#6c757d;cursor:not-allowed;">You Lost This</button>`
            : `<button class="btn btn-success" onclick="reportFound(${item.id})">I Found This</button>`;
    } else if (status === 'pending') {
        if (ownerId === userId) {
            actionButton = `<button class="btn" style="background:#fd7e14;" onclick="confirmClaim(${item.id})">Confirm Claim ✓</button>`;
        } else if (foundBy === userId) {
            actionButton = `<button class="btn" disabled style="background:#fd7e14;cursor:not-allowed;">Pending Confirmation</button>`;
        } else {
            actionButton = `<button class="btn" disabled>Pending</button>`;
        }
    } else if (status === 'found') {
        actionButton = ownerId === userId
            ? `<button class="btn" disabled style="background:#6c757d;cursor:not-allowed;">You Found This</button>`
            : `<button class="btn" onclick="claimItem(${item.id})">Claim Item</button>`;
    }

    return `
    <div class="card" id="card-${item.id}">
        <img src="${imagePath ? escapeHtml(imagePath) : imgFallback}"
             onerror="this.onerror=null;this.src='${imgFallback}'">
        <div class="card-body">
            <span class="status-badge status-${status}">${escapeHtml(status.toUpperCase())}</span>
            <div class="card-title">${escapeHtml(rawTitle)}</div>
            <div class="card-desc">${escapeHtml(item.description || '')}</div>
            <button class="btn btn-chat ${isActiveChat ? 'active-chat' : ''}"
                    id="chatbtn-${item.id}"
                    onclick="openChat(${item.id},'${safeTitle}')">
                💬 ${isActiveChat ? 'Chatting...' : 'Chat'}
            </button>
            ${actionButton}
            ${showDelete ? `<button class="btn btn-danger" onclick="deleteItem(${item.id})">Delete</button>` : ''}
        </div>
    </div>`;
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    filteredItems = allItems.filter(i =>
        (!search || (i.title || '').toLowerCase().includes(search)) &&
        (!status || i.status === status)
    );
    allPage = 1; // reset to page 1 on new filter
    displayItems(filteredItems);
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    filteredItems = allItems;
    allPage = 1;
    displayItems(filteredItems);
}

// ── Chat ──────────────────────────────────────────────────────────────────────
function openChat(id, title) {
    if (currentItemId === id) { closeChat(); return; }
    if (currentItemId) {
        const prev = document.getElementById('chatbtn-' + currentItemId);
        if (prev) { prev.classList.remove('active-chat'); prev.innerText = '💬 Chat'; }
    }
    currentItemId = id;
    document.getElementById('chatTitle').innerText = '💬 ' + title;
    document.getElementById('chatBody').innerHTML  = '<div class="chat-empty">Loading...</div>';
    const btn = document.getElementById('chatbtn-' + id);
    if (btn) { btn.classList.add('active-chat'); btn.innerText = '💬 Chatting...'; }
    document.getElementById('chatPanel').classList.add('open');
    document.getElementById('mainContent').classList.add('chat-open');
    loadMessages();
    clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 4000);
    setTimeout(() => document.getElementById('msgInput').focus(), 300);
}

function closeChat() {
    document.getElementById('chatPanel').classList.remove('open');
    document.getElementById('mainContent').classList.remove('chat-open');
    clearInterval(pollInterval);
    if (currentItemId) {
        const btn = document.getElementById('chatbtn-' + currentItemId);
        if (btn) { btn.classList.remove('active-chat'); btn.innerText = '💬 Chat'; }
    }
    currentItemId = null;
}

function loadMessages() {
    if (!currentItemId) return;
    fetch(`get_messages.php?item_id=${currentItemId}`)
        .then(r => r.json())
        .then(msgs => {
            const body = document.getElementById('chatBody');
            if (!msgs.length) { body.innerHTML = '<div class="chat-empty">No messages yet. Say hello! 👋</div>'; return; }
            const current = body.querySelectorAll('.msg').length;
            if (current !== msgs.length) {
                body.innerHTML = msgs.map(m => {
                    const isMine = parseInt(m.sender_id) === userId;
                    const time   = m.created_at ? new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}) : '';
                    return `<div class="msg ${isMine ? 'sent' : 'received'}">
                        ${!isMine ? `<div class="msg-sender">${escapeHtml(m.sender_name || 'User')}</div>` : ''}
                        ${escapeHtml(m.message)}
                        <div class="msg-time">${time}</div>
                    </div>`;
                }).join('');
                body.scrollTop = body.scrollHeight;
            }
        })
        .catch(() => {
            document.getElementById('chatBody').innerHTML = '<div class="chat-empty" style="color:red;">Error loading messages.</div>';
        });
}

function sendMessage() {
    const msg = document.getElementById('msgInput').value.trim();
    if (!msg || !currentItemId) return;
    document.getElementById('msgInput').value = '';
    const fd = new FormData();
    fd.append('item_id', currentItemId);
    fd.append('message', msg);
    fetch('send_message.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (d.status === 'success') loadMessages(); })
        .catch(() => alert('Error sending message'));
}

// ── Actions ───────────────────────────────────────────────────────────────────
function reportFound(id) {
    if (!confirm("Confirm that you found this item?")) return;
    fetch('report_found.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'item_id=' + encodeURIComponent(id)
    }).then(r => r.text()).then(raw => {
        let d; try { d = JSON.parse(raw); } catch(e) { alert("Server error:\n" + raw); return; }
        alert(d.message || 'Done');
        if (d.status === 'success') location.reload();
    }).catch(err => alert("Network error: " + err));
}

function confirmClaim(id) {
    if (!confirm("Confirm this person has returned your item?")) return;
    fetch('claim_item.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'item_id=' + encodeURIComponent(id)
    }).then(r => r.json()).then(d => { alert(d.message); if (d.status === 'success') location.reload(); });
}

function claimItem(id) {
    if (!confirm("Claim this item?")) return;
    fetch('claim_item.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'item_id=' + encodeURIComponent(id)
    }).then(r => r.json()).then(d => { alert(d.message); if (d.status === 'success') location.reload(); });
}

function deleteItem(id) {
    if (!confirm("Delete this item?")) return;
    fetch('delete_item.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'item_id=' + encodeURIComponent(id)
    }).then(r => r.json()).then(d => { alert(d.message); location.reload(); });
}
</script>
</body>
</html>