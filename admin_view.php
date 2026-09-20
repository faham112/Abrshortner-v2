<!DOCTYPE html>
<html lang="en" data-theme="dark">
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
<a href="logout.php" class="btn-logout">Logout</a>
</div>
</div>
<div class="container">
<?php if (isset($_GET['msg'])): ?><div class="msg"><?php $msgs=['created'=>'Link created!','updated'=>'Link updated!','deleted'=>'Link deleted!','user_created'=>'User created!','user_deleted'=>'User deleted!']; echo $msgs[$_GET['msg']]??''; ?></div><?php endif; ?>
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
<h3>Recent Users</h3>
<?php if (empty($recentUsers)): ?><div class="empty" style="padding:12px 0;">No users yet</div>
<?php else: foreach ($recentUsers as $u): ?>
<div class="user-item"><div class="user-info"><div class="name"><?= htmlspecialchars($u['name']) ?></div><div class="email"><?= htmlspecialchars($u['email']) ?></div><div class="meta"><?= (int)$u['link_count'] ?> links</div></div>
<a href="?delete_user=<?= (int)$u['id'] ?>" class="del-btn" onclick="return confirm('Delete?')">Delete</a></div>
<?php endforeach; ?><a href="admin.php?tab=users" class="cancel">View all users →</a><?php endif; ?>
</div>
<div class="card <?= $tab==='analytics'?'active':'' ?>">
<h2>Analytics</h2>
<div class="stats">
<div class="stat-card"><div class="num"><?= $clicks_today ?></div><div class="label">Today</div></div>
<div class="stat-card"><div class="num"><?= $clicks_7d ?></div><div class="label">Last 7 Days</div></div>
<div class="stat-card"><div class="num"><?= $totalClicks ?></div><div class="label">All Time</div></div>
<div class="stat-card"><div class="num"><?= $totalLinks ?></div><div class="label">Links</div></div>
</div>
<?php if ($analytics_error): ?><div class="msg error"><?= htmlspecialchars($analytics_error) ?></div><?php endif; ?>
<h3>Traffic by Device</h3>
<?php $maxd=barMax($by_device); foreach ($by_device as $row): $pct=round(((int)$row['c']/$maxd)*100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['device']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_device)): ?><p class="empty" style="padding:8px 0;">No clicks yet</p><?php endif; ?>
</div>
<div class="card <?= $tab==='create'?'active':'' ?>">
<h2><?= $edit_data?'Edit Link':'Create Short Link' ?></h2>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="create_link">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= (int)$edit_data['id'] ?>"><?php endif; ?>
<input type="text" name="long_url" placeholder="Destination URL" required value="<?= htmlspecialchars($edit_data['long_url']??'') ?>">
<input type="text" name="title" placeholder="Title" value="<?= htmlspecialchars($edit_data['title']??'') ?>">
<textarea name="description" placeholder="Description"><?= htmlspecialchars($edit_data['description']??'') ?></textarea>
<label class="file-label">Preview Image</label>
<input type="file" name="image_file" accept="image/*" class="file-input">
<input type="text" name="image_url" placeholder="Image URL (Optional)" value="<?= htmlspecialchars($edit_data['image_url']??'') ?>">
<div class="toggle-row"><div><div class="toggle-text">Link Preview</div><div class="toggle-sub">ON = show · OFF = hide</div></div>
<label class="switch"><input type="checkbox" name="preview_enabled" value="1" checked><span class="slider"></span></label></div>
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
<div class="card <?= $tab==='links'?'active':'' ?>">
<h2>All Links</h2>
<form method="GET" class="search-bar"><input type="hidden" name="tab" value="links">
<input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button></form>
<?php if (empty($allLinks)): ?><div class="empty">No links found</div><?php endif; ?>
</div>
</div>
<nav class="bottom-nav">
<a href="admin.php?tab=home" class="nav-item <?= ($tab==='home'||$tab==='')?'active':'' ?>"><i data-lucide="home"></i>Home</a>
<a href="admin.php?tab=create" class="nav-item <?= $tab==='create'?'active':'' ?>"><i data-lucide="plus"></i>Create</a>
<a href="admin.php?tab=analytics" class="nav-item <?= ($tab==='analytics'||$tab==='stats')?'active':'' ?>"><i data-lucide="bar-chart-2"></i>Analytics</a>
<a href="admin.php?tab=users" class="nav-item <?= $tab==='users'?'active':'' ?>"><i data-lucide="users"></i>Users</a>
<a href="admin.php?tab=links" class="nav-item <?= $tab==='links'?'active':'' ?>"><i data-lucide="link"></i>Links</a>
</nav>
<script>
(function(){
  const root=document.documentElement;
  const saved=localStorage.getItem('sl_theme')||'dark';
  root.setAttribute('data-theme',saved);
  function setIcon(theme){
    const icon=document.getElementById('themeIcon');
    if(!icon)return;
    icon.setAttribute('data-lucide',theme==='dark'?'sun':'moon');
    if(window.lucide)lucide.createIcons();
  }
  setIcon(saved);
  document.getElementById('themeToggle').addEventListener('click',function(){
    const next=root.getAttribute('data-theme')==='dark'?'light':'dark';
    root.setAttribute('data-theme',next);
    localStorage.setItem('sl_theme',next);
    setIcon(next);
  });
  if(window.lucide)lucide.createIcons();
})();
function copyLink(btn,url){
  navigator.clipboard.writeText(url).then(()=>{ const o=btn.innerText;btn.innerText='Copied!'; setTimeout(()=>{btn.innerText=o;},1500); });
}
</script>
</body>
</html>
