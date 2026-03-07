<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$uid      = $_SESSION['user_id'];
$reportId = intval($_GET['report_id'] ?? 0);

// Verify this report belongs to the current user
$rStmt = mysqli_prepare($conn, "SELECT * FROM found_reports WHERE id = ? AND finder_id = ?");
mysqli_stmt_bind_param($rStmt, 'ii', $reportId, $uid);
mysqli_stmt_execute($rStmt);
$report = mysqli_fetch_assoc(mysqli_stmt_get_result($rStmt));

if (!$report) {
    header("Location: dashboard.php");
    exit();
}

// Fetch all LOST items (these are the potential matches)
$lostItems = [];
$lRes = mysqli_query($conn,
    "SELECT i.*, u.name AS owner_name
     FROM items i
     LEFT JOIN users u ON i.owner_id = u.id
     WHERE i.status = 'lost'
     ORDER BY i.updated_at DESC, i.id DESC");
while ($row = mysqli_fetch_assoc($lRes)) {
    $lostItems[] = $row;
}

$pRes        = mysqli_query($conn, "SELECT name, profile_photo FROM users WHERE id='$uid'");
$pRow        = mysqli_fetch_assoc($pRes);
$encodedName = urlencode($pRow['name'] ?? 'User');
$fallbackAvatar = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=80";
$avatarSrc   = !empty($pRow['profile_photo']) ? htmlspecialchars($pRow['profile_photo']) : $fallbackAvatar;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Potential Matches — NMIMS Lost &amp; Found</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;}
header{background:#003366;color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;}
.header-left{display:flex;align-items:center;gap:12px;}
.header-right{display:flex;align-items:center;gap:15px;}
.avatar-link{display:flex;align-items:center;gap:8px;text-decoration:none;color:white;}
.avatar-link img{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);}

.page{max-width:1100px;margin:0 auto;padding:30px 20px 60px;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:#003366;text-decoration:none;font-size:0.9rem;margin-bottom:20px;}
.back-link:hover{text-decoration:underline;}

/* Your report summary */
.report-summary{background:white;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:20px;margin-bottom:28px;display:flex;gap:20px;align-items:flex-start;}
.report-summary img{width:100px;height:100px;object-fit:cover;border-radius:8px;flex-shrink:0;}
.report-summary .info h3{color:#003366;margin-bottom:6px;}
.report-summary .info p{color:#555;font-size:0.9rem;line-height:1.5;}
.report-summary .info .meta{font-size:0.78rem;color:#999;margin-top:6px;}

.section-title{color:#003366;font-size:1.2rem;font-weight:700;margin-bottom:16px;}
.count-badge{background:#003366;color:white;font-size:0.75rem;padding:2px 10px;border-radius:12px;margin-left:8px;}

/* Grid */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;}
.card{background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:0.2s;}
.card:hover{transform:translateY(-3px);}
.card img{width:100%;height:170px;object-fit:cover;}
.card-body{padding:14px;}
.card-title{font-size:1rem;font-weight:bold;margin-bottom:5px;color:#222;}
.card-desc{font-size:0.85rem;color:#666;margin-bottom:10px;line-height:1.4;}
.card-owner{font-size:0.78rem;color:#888;margin-bottom:10px;}
.btn{background:#003366;color:white;border:none;padding:9px 18px;cursor:pointer;border-radius:5px;font-size:0.85rem;text-decoration:none;display:inline-block;}
.btn:hover{background:#004080;}
.btn-outline{background:white;color:#003366;border:1px solid #003366;}
.btn-outline:hover{background:#003366;color:white;}
.status-badge{display:inline-block;padding:3px 9px;border-radius:12px;font-size:0.72rem;font-weight:bold;margin-bottom:8px;background:#ffebee;color:#c62828;}

.empty-state{text-align:center;padding:60px 20px;color:#999;background:white;border-radius:10px;}
.empty-state .icon{font-size:3rem;margin-bottom:10px;}

.info-box{background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:12px 16px;margin-bottom:24px;font-size:0.88rem;color:#795548;line-height:1.5;}
</style>
</head>
<body>
<header>
  <div class="header-left">
    <img src="nmims-university-logo.png" alt="NMIMS" style="height:42px;filter:brightness(0) invert(1);object-fit:contain;">
    <h3>NMIMS Lost &amp; Found</h3>
  </div>
  <div class="header-right">
    <a href="profile.php" class="avatar-link">
      <img src="<?php echo $avatarSrc; ?>"
           alt="Profile"
           onerror="this.onerror=null;this.src='<?php echo $fallbackAvatar; ?>'">
    </a>
    <a href="logout.php" style="color:white;text-decoration:none;">Logout</a>
  </div>
</header>

<div class="page">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

  <h2 style="color:#003366;margin-bottom:6px;">Your Found Report</h2>
  <p style="color:#888;font-size:0.9rem;margin-bottom:20px;">Your report has been saved. Below are all currently lost items — check if any match what you found.</p>

  <!-- Your report summary -->
  <div class="report-summary">
    <?php
    $imgSrc  = !empty($report['image_path']) ? htmlspecialchars($report['image_path']) : '';
    $imgFallback = "https://ui-avatars.com/api/?name=Found+Item&background=e9ecef&color=003366&size=200";
    ?>
    <img src="<?php echo $imgSrc ?: $imgFallback; ?>"
         onerror="this.onerror=null;this.src='<?php echo $imgFallback; ?>'">
    <div class="info">
      <h3>What you found:</h3>
      <p><?php echo htmlspecialchars($report['description']); ?></p>
      <p class="meta">Submitted: <?php echo date('d M Y, H:i', strtotime($report['created_at'])); ?></p>
    </div>
  </div>

  <div class="info-box">
    🔎 <strong>How to match:</strong> Browse the lost items below. If you see one that matches what you found, click <strong>"Chat with Owner"</strong> to contact them directly and arrange return.
  </div>

  <div class="section-title">
    All Lost Items
    <span class="count-badge"><?php echo count($lostItems); ?></span>
  </div>

  <?php if (empty($lostItems)): ?>
    <div class="empty-state">
      <div class="icon">🎉</div>
      <p>No items are currently reported as lost.</p>
      <p style="margin-top:8px;font-size:0.85rem;">Check back later or go to the dashboard.</p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($lostItems as $item):
        $imgPath  = !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : '';
        $fallback = "https://ui-avatars.com/api/?name=" . urlencode($item['title']) . "&background=e9ecef&color=003366&size=200";
        $safeTitle = htmlspecialchars($item['title'] ?? 'Item');
      ?>
      <div class="card">
        <img src="<?php echo $imgPath ?: $fallback; ?>"
             onerror="this.onerror=null;this.src='<?php echo $fallback; ?>'">
        <div class="card-body">
          <span class="status-badge">LOST</span>
          <div class="card-title"><?php echo $safeTitle; ?></div>
          <div class="card-desc"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 80)) . (strlen($item['description'] ?? '') > 80 ? '…' : ''); ?></div>
          <div class="card-owner">👤 Owner: <?php echo htmlspecialchars($item['owner_name'] ?? 'Unknown'); ?></div>
          <a href="dashboard.php#item-<?php echo $item['id']; ?>" class="btn btn-outline" style="margin-right:6px;font-size:0.8rem;">View</a>
          <button class="btn" onclick="openChat(<?php echo $item['id']; ?>, '<?php echo addslashes($safeTitle); ?>')" style="font-size:0.8rem;">💬 Chat with Owner</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Inline chat modal (same as dashboard) -->
<div id="chatModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;">
  <div style="background:white;width:450px;height:520px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;border-radius:10px;">
    <div style="background:#003366;color:white;padding:15px;display:flex;justify-content:space-between;border-radius:10px 10px 0 0;">
      <span id="chatTitle" style="font-weight:bold;">Chat</span>
      <button onclick="document.getElementById('chatModal').style.display='none'" style="background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;">&times;</button>
    </div>
    <div id="chatBody" style="flex:1;padding:15px;overflow-y:auto;background:#e5ddd5;"></div>
    <div style="padding:12px;border-top:1px solid #ddd;display:flex;gap:8px;">
      <input type="text" id="msgInput" placeholder="Type a message..." onkeydown="if(event.key==='Enter')sendMsg()"
             style="flex:1;padding:10px;border:1px solid #ddd;border-radius:20px;font-size:0.9rem;outline:none;">
      <button onclick="sendMsg()" style="background:#003366;color:white;border:none;border-radius:20px;padding:10px 18px;cursor:pointer;">Send</button>
    </div>
  </div>
</div>

<script>
const userId = <?php echo json_encode((int)$_SESSION['user_id']); ?>;
let currentItemId = null;

function openChat(id, title) {
    currentItemId = id;
    document.getElementById('chatModal').style.display = 'block';
    document.getElementById('chatTitle').innerText = '💬 ' + title;
    document.getElementById('chatBody').innerHTML = '<p style="text-align:center;color:#aaa;padding:20px;">Loading...</p>';
    loadMsgs();
}

function loadMsgs() {
    fetch('get_messages.php?item_id=' + currentItemId)
        .then(r => r.json())
        .then(msgs => {
            const body = document.getElementById('chatBody');
            if (!msgs.length) { body.innerHTML = '<p style="text-align:center;color:#aaa;padding:20px;">No messages yet. Say hello! 👋</p>'; return; }
            body.innerHTML = msgs.map(m => {
                const mine = parseInt(m.sender_id) === userId;
                return `<div style="padding:9px 13px;margin:6px 0;border-radius:15px;max-width:75%;word-wrap:break-word;font-size:0.9rem;
                    ${mine ? 'background:#dcf8c6;margin-left:auto;border-bottom-right-radius:4px;' : 'background:white;border-bottom-left-radius:4px;'}">
                    ${!mine ? `<div style="font-size:0.72rem;font-weight:bold;color:#003366;margin-bottom:3px;">${escHtml(m.sender_name||'User')}</div>` : ''}
                    ${escHtml(m.message)}
                </div>`;
            }).join('');
            body.scrollTop = body.scrollHeight;
        });
}

function sendMsg() {
    const msg = document.getElementById('msgInput').value.trim();
    if (!msg || !currentItemId) return;
    document.getElementById('msgInput').value = '';
    const fd = new FormData();
    fd.append('item_id', currentItemId);
    fd.append('message', msg);
    fetch('send_message.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => { if (d.status === 'success') loadMsgs(); });
}

function escHtml(s) {
    return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}
</script>
</body>
</html>