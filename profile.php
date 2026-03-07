<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.html"); exit(); }

$uid = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != '$uid'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'That email is already in use by another account.';
        } else {
            mysqli_query($conn, "UPDATE users SET name='$name', email='$email' WHERE id='$uid'");
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully.';
        }
    }

    if ($action === 'update_password') {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $res = mysqli_query($conn, "SELECT password FROM users WHERE id='$uid'");
        $row = mysqli_fetch_assoc($res);
        if (!password_verify($current, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hash' WHERE id='$uid'");
            $success = 'Password updated successfully.';
        }
    }

    if ($action === 'update_photo') {
        $target_dir = "uploads/avatars/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 2000000) {
            $filename = "avatar_" . $uid . "_" . time() . "." . $ext;
            $target   = $target_dir . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                mysqli_query($conn, "UPDATE users SET profile_photo='$target' WHERE id='$uid'");
                $success = 'Profile photo updated.';
            } else { $error = 'Failed to upload photo.'; }
        } else { $error = 'Invalid file. JPG/PNG/GIF only, max 2MB.'; }
    }
}

$res  = mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'");
$user = mysqli_fetch_assoc($res);

// Build fallback URL once, reuse everywhere — no more loops
$encodedName  = urlencode($user['name'] ?? 'User');
$fallbackSmall  = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=80";
$fallbackLarge  = "https://ui-avatars.com/api/?name={$encodedName}&background=003366&color=fff&size=200";
$avatarSrc      = (!empty($user['profile_photo'])) ? htmlspecialchars($user['profile_photo']) : $fallbackLarge;

$statsRes = mysqli_query($conn, "
    SELECT
        SUM(status='lost')    AS lost_count,
        SUM(status='found')   AS found_count,
        SUM(status='claimed') AS claimed_count,
        SUM(status='pending') AS pending_count
    FROM items WHERE owner_id='$uid'
");
$stats = mysqli_fetch_assoc($statsRes);

$itemsRes = mysqli_query($conn, "SELECT * FROM items WHERE owner_id='$uid' ORDER BY id DESC");
$myItems  = [];
while ($row = mysqli_fetch_assoc($itemsRes)) $myItems[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — NMIMS Lost & Found</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;min-height:100vh;}
header{background:#003366;color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;}
.header-left{display:flex;align-items:center;gap:12px;}
.container{max-width:960px;margin:30px auto;padding:0 20px;}
.profile-hero{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);padding:30px;display:flex;gap:30px;align-items:flex-start;margin-bottom:25px;flex-wrap:wrap;}
.avatar-wrap{position:relative;flex-shrink:0;}
.avatar-wrap img{width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid #003366;}
.avatar-upload-btn{position:absolute;bottom:0;right:0;background:#003366;color:white;border:none;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;}
.profile-info{flex:1;}
.profile-info h2{font-size:1.6rem;color:#003366;margin-bottom:4px;}
.profile-info p{color:#666;margin-bottom:4px;}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:25px;}
.stat-card{background:white;border-radius:10px;padding:20px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.07);}
.stat-num{font-size:2rem;font-weight:bold;color:#003366;}
.stat-label{font-size:0.8rem;color:#888;margin-top:4px;text-transform:uppercase;letter-spacing:0.5px;}
.stat-card.lost .stat-num{color:#c62828;}
.stat-card.found .stat-num{color:#2e7d32;}
.stat-card.claimed .stat-num{color:#1565c0;}
.stat-card.pending .stat-num{color:#f57f17;}
.panel{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.07);margin-bottom:25px;overflow:hidden;}
.panel-header{background:#003366;color:white;padding:15px 20px;font-size:1rem;font-weight:bold;}
.panel-body{padding:25px;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;margin-bottom:6px;color:#444;font-weight:600;font-size:0.9rem;}
.form-group input{width:100%;padding:11px 14px;border:1px solid #ddd;border-radius:6px;font-size:1rem;}
.form-group input:focus{outline:none;border-color:#003366;}
.btn{background:#003366;color:white;border:none;padding:11px 25px;border-radius:6px;cursor:pointer;font-size:0.95rem;}
.btn:hover{background:#004080;}
.alert{padding:12px 16px;border-radius:6px;margin-bottom:20px;font-size:0.95rem;}
.alert-success{background:#d4edda;color:#155724;}
.alert-error{background:#f8d7da;color:#721c24;}
.item-table{width:100%;border-collapse:collapse;}
.item-table th{text-align:left;padding:10px 12px;background:#f8f9fa;color:#555;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.4px;border-bottom:2px solid #eee;}
.item-table td{padding:12px;border-bottom:1px solid #f0f0f0;font-size:0.9rem;vertical-align:middle;}
.item-table tr:hover td{background:#fafafa;}
.item-thumb{width:48px;height:48px;object-fit:cover;border-radius:6px;}
.status-badge{display:inline-block;padding:3px 10px;border-radius:10px;font-size:0.75rem;font-weight:bold;}
.status-lost{background:#ffebee;color:#c62828;}
.status-found{background:#e8f5e9;color:#2e7d32;}
.status-claimed{background:#e3f2fd;color:#1565c0;}
.status-pending{background:#fff8e1;color:#f57f17;}
.back-link{color:#003366;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:20px;font-weight:600;}
.back-link:hover{text-decoration:underline;}
@media(max-width:600px){.stats-row{grid-template-columns:repeat(2,1fr);}}
</style>
</head>
<body>
<header>
  <div class="header-left">
    <img src="nmims-university-logo.png" alt="NMIMS" style="height:45px;object-fit:contain;filter:brightness(0) invert(1);">
    <h3>NMIMS Lost &amp; Found</h3>
  </div>
  <div>
    <a href="dashboard.php" style="color:white;text-decoration:none;margin-right:15px;">Dashboard</a>
    <a href="logout.php" style="color:white;text-decoration:none;">Logout</a>
  </div>
</header>

<div class="container">
  <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="profile-hero">
    <div class="avatar-wrap">
      <img
        src="<?php echo $avatarSrc; ?>"
        id="avatarPreview"
        alt="Avatar"
        onerror="this.onerror=null;this.src='<?php echo $fallbackLarge; ?>';">
      <button class="avatar-upload-btn" onclick="document.getElementById('photoInput').click()" title="Change photo">✏️</button>
    </div>
    <div class="profile-info">
      <h2><?php echo htmlspecialchars($user['name']); ?></h2>
      <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
      <p style="margin-top:8px;color:#999;font-size:0.85rem;">Member · NMIMS Lost &amp; Found</p>
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data" id="photoForm">
    <input type="hidden" name="action" value="update_photo">
    <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;"
           onchange="previewAndSubmit(this);">
  </form>

  <div class="stats-row">
    <div class="stat-card lost"><div class="stat-num"><?php echo intval($stats['lost_count']); ?></div><div class="stat-label">Items Lost</div></div>
    <div class="stat-card found"><div class="stat-num"><?php echo intval($stats['found_count']); ?></div><div class="stat-label">Items Found</div></div>
    <div class="stat-card claimed"><div class="stat-num"><?php echo intval($stats['claimed_count']); ?></div><div class="stat-label">Claimed</div></div>
    <div class="stat-card pending"><div class="stat-num"><?php echo intval($stats['pending_count']); ?></div><div class="stat-label">Pending</div></div>
  </div>

  <div class="panel">
    <div class="panel-header">✏️ Edit Profile Info</div>
    <div class="panel-body">
      <form method="POST">
        <input type="hidden" name="action" value="update_info">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <button type="submit" class="btn">Save Changes</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">🔒 Change Password</div>
    <div class="panel-body">
      <form method="POST">
        <input type="hidden" name="action" value="update_password">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
        <button type="submit" class="btn">Update Password</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">📋 My Item History</div>
    <div class="panel-body" style="padding:0;">
      <?php if (empty($myItems)): ?>
        <p style="padding:25px;color:#999;text-align:center;">You haven't uploaded any items yet.</p>
      <?php else: ?>
      <table class="item-table">
        <thead>
          <tr><th>Photo</th><th>Title</th><th>Description</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($myItems as $item):
            $img         = !empty($item['image_path']) ? ltrim($item['image_path'], '/') : '';
            $thumbFallback = "https://ui-avatars.com/api/?name=" . urlencode($item['title'] ?? 'Item') . "&background=e9ecef&color=003366&size=100";
          ?>
          <tr>
            <td>
              <img
                src="<?php echo $img ? htmlspecialchars($img) : $thumbFallback; ?>"
                class="item-thumb"
                onerror="this.onerror=null;this.src='<?php echo $thumbFallback; ?>';">
            </td>
            <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
            <td style="color:#666;"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 60)); ?>...</td>
            <td><span class="status-badge status-<?php echo $item['status']; ?>"><?php echo strtoupper($item['status']); ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Preview new photo locally before form submits — stops any flash
function previewAndSubmit(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatarPreview').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
    // Small delay so preview renders before page reloads
    setTimeout(() => document.getElementById('photoForm').submit(), 300);
}
</script>
</body>
</html>