<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - ShortLink</title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/app.css?v=20260920b">
<style>
:root{--bg:#f2f2f7;--bg2:#fff;--text:#1c1c1e;--muted:#8e8e93;--line:rgba(60,60,67,.12);--btn:#1c1c1e;--btnt:#f5f5f7}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f2f2f7;color:var(--text);min-height:100vh;padding-bottom:96px}
.header{position:sticky;top:0;z-index:50;display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:rgba(242,242,247,.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--line)}
.header h1{font-size:18px}
.header-right{display:flex;align-items:center;gap:8px}
.badge{background:#1c1c1e;color:#fff;font-size:11px;padding:3px 10px;border-radius:20px}
.icon-btn{width:36px;height:36px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer}
.menu-wrap{position:relative}
.menu-drop{display:none;position:absolute;right:0;top:44px;min-width:220px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:8px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.menu-drop.open{display:block}
.menu-drop a{display:flex;gap:8px;padding:10px 12px;text-decoration:none;color:#1c1c1e;border-radius:12px;font-size:13px}
.container{max-width:700px;margin:0 auto;padding:16px}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.stat-card,.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px}
.stat-card{text-align:center}
.stat-card .num{font-size:24px;font-weight:700}
.stat-card .label{font-size:12px;color:var(--muted)}
.card{display:none;margin-bottom:16px;padding:20px}
.card.active{display:block}
.card h2{font-size:17px;margin-bottom:14px}
input,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid var(--line);margin-bottom:10px;font-size:15px}
.btn-primary{width:100%;padding:13px;border:0;border-radius:14px;background:#1c1c1e;color:#fff;font-weight:600}
.btn-stats{display:inline-block;padding:8px 12px;border-radius:12px;background:#f2f2f7;color:#1c1c1e;text-decoration:none;font-size:13px}
.bottom-nav{position:fixed;bottom:0;left:0;right:0;background:rgba(242,242,247,.92);backdrop-filter:blur(16px);border-top:1px solid var(--line);display:flex;justify-content:space-around;padding:8px 0 12px}
.nav-item{display:flex;flex-direction:column;align-items:center;text-decoration:none;color:#8e8e93;font-size:10px;gap:2px;min-width:48px}
.nav-item.active{color:#1c1c1e}
.msg{background:#e8f8ee;color:#248a3d;padding:12px;border-radius:12px;margin-bottom:12px;text-align:center}
.error{background:#fde8ea;color:#d70015}
.empty{color:#8e8e93;text-align:center;padding:16px 0}
</style>
</head>
<body>
<div class="header">
<h1>Admin Panel</h1>
<div class="header-right">
<button type="button" class="icon-btn" id="themeToggle" title="Toggle theme"><i data-lucide="moon" id="themeIcon"></i></button>
<span class="badge">Admin</span>
<div class="menu-wrap">
<button type="button" class="icon-btn" id="menuBtn" aria-label="Menu"><i data-lucide="menu"></i></button>
<div class="menu-drop" id="menuDrop">
<a href="admin_settings.php">Settings / License</a>
<a href="admin.php?tab=users">Users</a>
<a href="logout.php">Logout</a>
</div>
</div>
</div>
</div>
<div class="container">
<?php if (isset($_GET['msg'])): ?><div class="msg"><?php $msgs=['created'=>'Link created!','updated'=>'Link updated!','deleted'=>'Link deleted!','user_created'=>'User created!','user_deleted'=>'User deleted!','license_created'=>'License created.','license_revoked'=>'License revoked']; echo $msgs[$_GET['msg']]??''; ?></div><?php endif; ?>
<?php if (!empty($message)): ?><div class="msg <?= !empty($is_error)?'error':'' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="card <?= ($tab==='home'||$tab==='')?'active':'' ?>">
<h2>Overview</h2>
<div class="stats">
<div class="stat-card"><div class="num"><?= (int)($totalUsers??0) ?></div><div class="label">Users</div></div>
<div class="stat-card"><div class="num"><?= (int)($totalLinks??0) ?></div><div class="label">Links</div></div>
<div class="stat-card"><div class="num"><?= (int)($totalClicks??0) ?></div><div class="label">Total Clicks</div></div>
<div class="stat-card"><div class="num"><?= (int)($myLinksCount??0) ?></div><div class="label">My Links</div></div>
</div>
<p style="color:#8e8e93;font-size:13px;text-align:center;margin-bottom:16px;">Welcome, <?= htmlspecialchars($_SESSION['name']??'') ?></p>
</div>
<div class="card <?= ($tab??'')==='create'?'active':'' ?>">
<h2>Create Short Link</h2>
<form method="POST">
<input type="hidden" name="action" value="create_link">
<input type="text" name="long_url" placeholder="Destination URL" required>
<input type="text" name="title" placeholder="Title">
<button type="submit" class="btn-primary">Shorten Now</button>
</form>
</div>
<div class="card <?= ($tab??'')==='users'?'active':'' ?>">
<h2>Create New User</h2>
<form method="POST"><input type="hidden" name="action" value="create_user">
<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="password" placeholder="Password" required>
<button type="submit" class="btn-primary">Create User</button></form>
</div>
<div class="card <?= ($tab??'')==='links'?'active':'' ?>"><h2>All Links</h2><div class="empty">Open Links tab</div></div>
<div class="card <?= ($tab??'')==='analytics'?'active':'' ?>"><h2>Analytics</h2>
<div class="stats">
<div class="stat-card"><div class="num"><?= (int)($clicks_today??0) ?></div><div class="label">Today</div></div>
<div class="stat-card"><div class="num"><?= (int)($clicks_7d??0) ?></div><div class="label">Last 7 Days</div></div>
</div></div>
</div>
<nav class="bottom-nav">
<a href="admin.php?tab=home" class="nav-item">Home</a>
<a href="admin.php?tab=create" class="nav-item">Create</a>
<a href="admin.php?tab=analytics" class="nav-item">Analytics</a>
<a href="admin.php?tab=users" class="nav-item">Users</a>
<a href="admin.php?tab=links" class="nav-item">Links</a>
<a href="admin_settings.php" class="nav-item">License</a>
</nav>
<script>
(function(){
  var menuBtn=document.getElementById('menuBtn');
  var menuDrop=document.getElementById('menuDrop');
  if(menuBtn&&menuDrop){
    menuBtn.addEventListener('click',function(e){e.stopPropagation();menuDrop.classList.toggle('open');});
    document.addEventListener('click',function(){menuDrop.classList.remove('open');});
  }
  if(window.lucide)lucide.createIcons();
})();
</script>
</body>
</html>
