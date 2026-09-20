<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Admin - ShortLink</title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/app.css">
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
<a href="admin_settings.php"><i data-lucide="settings"></i> Settings / License</a>
<a href="admin.php?tab=users"><i data-lucide="users"></i> Users</a>
<a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>
</div>
</div>
</div>
<div class="container">
<?php if (isset($_GET['msg'])): ?><div class="msg"><?php $msgs=['created'=>'Link created!','updated'=>'Link updated!','deleted'=>'Link deleted!','user_created'=>'User created!','user_deleted'=>'User deleted!','license_created'=>'License created.','license_revoked'=>'License revoked']; echo $msgs[$_GET['msg']]??''; ?></div><?php endif; ?>
<?php if ($message): ?><div class="msg <?= $is_error?'error':'' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="card <?= ($tab==='home'||$tab==='')?'active':'' ?>">
<h2>Overview</h2>
<div class="stats">
<div class="stat-card"><div class="num"><?= $totalUsers ?></div><div class="label">Users</div></div>
<div class="stat-card"><div class="num"><?= $totalLinks ?></div><div class="label">Links</div></div>
<div class="stat-card"><div class="num"><?= $totalClicks ?></div><div class="label">Total Clicks</div></div>
<div class="stat-card"><div class="num"><?= $myLinksCount ?></div><div class="label">My Links</div></div>
</div>
<p style="color:var(--muted);font-size:13px;text-align:center;margin-bottom:16px;">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></p>
<p style="text-align:center;margin-bottom:18px;"><a href="admin.php?tab=analytics" class="btn-stats" style="display:inline-block;padding:10px 16px;">Open Analytics →</a></p>
</div>
<div class="card <?= $tab==='analytics'?'active':'' ?>"><h2>Analytics</h2>
<div class="stats">
<div class="stat-card"><div class="num"><?= $clicks_today ?></div><div class="label">Today</div></div>
<div class="stat-card"><div class="num"><?= $clicks_7d ?></div><div class="label">Last 7 Days</div></div>
<div class="stat-card"><div class="num"><?= $totalClicks ?></div><div class="label">All Time</div></div>
<div class="stat-card"><div class="num"><?= $totalLinks ?></div><div class="label">Links</div></div>
</div></div>
<div class="card <?= $tab==='create'?'active':'' ?>">
<h2><?= $edit_data?'Edit Link':'Create Short Link' ?></h2>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="create_link">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= (int)$edit_data['id'] ?>"><?php endif; ?>
<input type="text" name="long_url" placeholder="Destination URL" required value="<?= htmlspecialchars($edit_data['long_url']??'') ?>">
<input type="text" name="title" placeholder="Title" value="<?= htmlspecialchars($edit_data['title']??'') ?>">
<textarea name="description" placeholder="Description"><?= htmlspecialchars($edit_data['description']??'') ?></textarea>
<button type="submit" class="btn-primary"><?= $edit_data?'Update Link':'Shorten Now' ?></button>
</form>
</div>
<div class="card <?= $tab==='users'?'active':'' ?>">
<h2>Create New User</h2>
<form method="POST"><input type="hidden" name="action" value="create_user">
<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="password" placeholder="Password (min 6)" required>
<button type="submit" class="btn-primary">Create User</button></form>
</div>
<div class="card <?= $tab==='links'?'active':'' ?>"><h2>All Links</h2>
<?php if (empty($allLinks)): ?><div class="empty">No links found</div><?php endif; ?></div>
</div>
<nav class="bottom-nav">
<a href="admin.php?tab=home" class="nav-item <?= ($tab==='home'||$tab==='')?'active':'' ?>"><i data-lucide="home"></i>Home</a>
<a href="admin.php?tab=create" class="nav-item <?= $tab==='create'?'active':'' ?>"><i data-lucide="plus"></i>Create</a>
<a href="admin.php?tab=analytics" class="nav-item <?= ($tab==='analytics'||$tab==='stats')?'active':'' ?>"><i data-lucide="bar-chart-2"></i>Analytics</a>
<a href="admin.php?tab=users" class="nav-item <?= $tab==='users'?'active':'' ?>"><i data-lucide="users"></i>Users</a>
<a href="admin.php?tab=links" class="nav-item <?= $tab==='links'?'active':'' ?>"><i data-lucide="link"></i>Links</a>
<a href="admin_settings.php" class="nav-item <?= $tab==='settings'?'active':'' ?>"><i data-lucide="settings"></i>License</a>
</nav>
<script>
(function(){
  const root=document.documentElement;
  const saved=localStorage.getItem('sl_theme')||'light';
  root.setAttribute('data-theme',saved);
  function setIcon(theme){
    const icon=document.getElementById('themeIcon');
    if(!icon)return;
    icon.setAttribute('data-lucide',theme==='dark'?'sun':'moon');
    if(window.lucide)lucide.createIcons();
  }
  setIcon(saved);
  var tg=document.getElementById('themeToggle');
  if(tg) tg.addEventListener('click',function(){
    const next=root.getAttribute('data-theme')==='dark'?'light':'dark';
    root.setAttribute('data-theme',next);
    localStorage.setItem('sl_theme',next);
    setIcon(next);
  });
  const menuBtn=document.getElementById('menuBtn');
  const menuDrop=document.getElementById('menuDrop');
  if(menuBtn&&menuDrop){
    menuBtn.addEventListener('click',function(e){e.stopPropagation();menuDrop.classList.toggle('open');});
    document.addEventListener('click',function(){menuDrop.classList.remove('open');});
  }
  if(window.lucide)lucide.createIcons();
})();
function copyLink(btn,url){
  navigator.clipboard.writeText(url).then(()=>{ const o=btn.innerText;btn.innerText='Copied!'; setTimeout(()=>{btn.innerText=o;},1500); });
}
</script>
</body>
</html>
