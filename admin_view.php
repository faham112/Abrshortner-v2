<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Admin - ShortLink</title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/app.css?v=20260920d">
</head>
<body>
<div class="drawer-overlay" id="drawerOverlay"></div>
<aside class="drawer" id="drawer" aria-hidden="true">
<div class="drawer-head">
<h2>Menu</h2>
<button type="button" class="icon-btn" id="drawerClose" aria-label="Close"><i data-lucide="x"></i></button>
</div>
<div class="drawer-user"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></div>
<a href="admin.php?tab=home"><i data-lucide="home"></i> Home</a>
<a href="admin.php?tab=create"><i data-lucide="plus"></i> Create link</a>
<a href="admin.php?tab=analytics"><i data-lucide="bar-chart-2"></i> Analytics</a>
<a href="admin.php?tab=users"><i data-lucide="users"></i> Users</a>
<a href="admin.php?tab=links"><i data-lucide="link"></i> All links</a>
<a href="admin_settings.php"><i data-lucide="key-round"></i> License HQ</a>
<a href="admin.php?tab=settings"><i data-lucide="settings"></i> Settings</a>
<button type="button" class="drawer-link" id="themeToggle"><i data-lucide="moon" id="themeIcon"></i> Toggle theme</button>
<div class="drawer-foot">
<a class="danger" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>
</aside>
<div class="header">
<div class="header-left">
<button type="button" class="icon-btn" id="menuBtn" aria-label="Open menu"><i data-lucide="menu"></i></button>
<h1>Admin Panel</h1>
</div>
<div class="header-right">
<span class="badge">Admin</span>
</div>
</div>
<div class="container">
<?php if (isset($_GET['msg'])): ?><div class="msg"><?php $msgs=['created'=>'Link created!','updated'=>'Link updated!','deleted'=>'Link deleted!','user_created'=>'User created!','user_deleted'=>'User deleted!','license_created'=>'License created. Share key + token, then send starter zip.','license_revoked'=>'License revoked']; echo $msgs[$_GET['msg']]??''; ?></div><?php endif; ?>
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
<div class="chart-grid">
<div class="chart-box"><h4>Browser</h4>
<?php $maxb=barMax($by_browser); foreach (array_slice($by_browser,0,6) as $row): $pct=round(((int)$row['c']/$maxb)*100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['browser']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar blue" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_browser)): ?><p class="empty" style="padding:6px 0;font-size:12px;">No data</p><?php endif; ?></div>
<div class="chart-box"><h4>OS</h4>
<?php $maxo=barMax($by_os); foreach (array_slice($by_os,0,6) as $row): $pct=round(((int)$row['c']/$maxo)*100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['os']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar orange" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_os)): ?><p class="empty" style="padding:6px 0;font-size:12px;">No data</p><?php endif; ?></div>
</div>
<h3>By Country</h3>
<?php $maxc=barMax($by_country); foreach ($by_country as $row): $pct=round(((int)$row['c']/$maxc)*100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['country']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar green" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_country)): ?><p class="empty" style="padding:8px 0;">No data yet</p><?php endif; ?>
<h3>Top Links</h3>
<?php if (empty($top_links)): ?><p class="empty" style="padding:8px 0;">No links</p>
<?php else: $maxt=max(1,max(array_column($top_links,'clicks'))); foreach ($top_links as $tl): $pct=round(((int)$tl['clicks']/$maxt)*100); ?>
<div class="stat-row"><span style="max-width:70%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">/<?= htmlspecialchars($tl['short_code']) ?></span><span><?= (int)$tl['clicks'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; endif; ?>
<h3>Recent Clicks</h3>
<?php foreach ($recent_clicks_global as $c): ?>
<div class="click-row"><strong>/<?= htmlspecialchars($c['short_code']??'?') ?></strong> · <?= htmlspecialchars($c['device']??'-') ?> · <?= htmlspecialchars($c['browser']??'-') ?><?php if (!empty($c['country'])): ?> · <?= htmlspecialchars($c['country']) ?><?php endif; ?><br><?= htmlspecialchars($c['ip']??'-') ?> · <?= htmlspecialchars($c['created_at']??'') ?></div>
<?php endforeach; if (empty($recent_clicks_global)): ?><p class="empty" style="padding:8px 0;">No clicks logged yet</p><?php endif; ?>
</div>
<div class="card <?= $tab==='create'?'active':'' ?>">
<h2><?= $edit_data?'Edit Link':'Create Short Link' ?></h2>
<?php $editPreviewOn=true; if ($edit_data && array_key_exists('preview_enabled',$edit_data)) $editPreviewOn=((int)$edit_data['preview_enabled']===1); ?>
<?php if ($edit_data && !$editPreviewOn): ?><div class="msg msg-warn">Preview is OFF</div><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="create_link">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= (int)$edit_data['id'] ?>"><?php endif; ?>
<input type="text" name="long_url" placeholder="Destination URL" required value="<?= htmlspecialchars($edit_data['long_url']??'') ?>">
<input type="text" name="title" placeholder="Title (News style)" value="<?= htmlspecialchars($edit_data['title']??'') ?>">
<textarea name="description" placeholder="Description"><?= htmlspecialchars($edit_data['description']??'') ?></textarea>
<label class="file-label">Preview Image</label>
<input type="file" name="image_file" accept="image/*" class="file-input">
<input type="text" name="image_url" placeholder="Image URL (Optional)" value="<?= htmlspecialchars($edit_data['image_url']??'') ?>">
<div class="toggle-row"><div><div class="toggle-text">Link Preview</div><div class="toggle-sub">ON = show · OFF = hide</div></div>
<label class="switch"><input type="checkbox" name="preview_enabled" value="1" <?= $editPreviewOn?'checked':'' ?> onchange="var a=document.getElementById('previewOffAlert');if(a)a.style.display=this.checked?'none':'block';"><span class="slider"></span></label></div>
<div id="previewOffAlert" class="msg msg-warn" style="display:<?= $editPreviewOn?'none':'block' ?>;">Preview OFF</div>
<button type="submit" class="btn-primary"><?= $edit_data?'Update Link':'Shorten Now' ?></button>
<?php if ($edit_data): ?><a href="admin.php?tab=create" class="cancel">Cancel</a><?php endif; ?>
</form>
<?php if (!$edit_data): ?>
<h3>Recently Made (Last 5)</h3>
<?php if (empty($recent5)): ?><div class="empty" style="padding:12px 0;">No links yet</div>
<?php else: foreach ($recent5 as $link):
$pOn=!array_key_exists('preview_enabled',$link)||(int)$link['preview_enabled']===1; ?>
<div class="link-item">
<div class="short-url"><a href="https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?>" target="_blank">https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?></a>
<span class="clicks"><?= (int)$link['clicks'] ?> clicks</span>
<?php if ($pOn): ?><span class="badge-on">ON</span><?php else: ?><span class="badge-off">OFF</span><?php endif; ?></div>
<div class="long-url"><?= htmlspecialchars($link['long_url']) ?></div>
<div class="meta-line">by <?= htmlspecialchars($link['user_name']??'') ?></div>
<div class="actions">
<button class="btn-copy" onclick="copyLink(this,'https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?>')">Copy</button>
<a href="?stats=<?= (int)$link['id'] ?>" class="btn-stats">Stats</a>
<a href="?tab=create&edit=<?= (int)$link['id'] ?>" class="btn-edit">Edit</a>
<a href="?delete_link=<?= (int)$link['id'] ?>" class="btn-del" onclick="return confirm('Delete?')">Del</a>
</div></div>
<?php endforeach; endif; endif; ?>
</div>
<div class="card <?= $tab==='users'?'active':'' ?>">
<h2>Create New User</h2>
<form method="POST"><input type="hidden" name="action" value="create_user">
<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="password" placeholder="Password (min 6)" required>
<button type="submit" class="btn-primary">Create User</button></form>
<h2 style="margin-top:24px;">All Users (<?= count($users) ?>)</h2>
<?php if (empty($users)): ?><div class="empty">No users yet</div>
<?php else: foreach ($users as $u): ?>
<div class="user-item"><div class="user-info"><div class="name"><?= htmlspecialchars($u['name']) ?></div><div class="email"><?= htmlspecialchars($u['email']) ?></div><div class="meta"><?= (int)$u['link_count'] ?> links</div></div>
<a href="?delete_user=<?= (int)$u['id'] ?>" class="del-btn" onclick="return confirm('Delete?')">Delete</a></div>
<?php endforeach; endif; ?>
</div>
<div class="card <?= $tab==='links'?'active':'' ?>">
<h2>All Links</h2>
<form method="GET" class="search-bar"><input type="hidden" name="tab" value="links">
<input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button></form>
<?php if (empty($allLinks)): ?><div class="empty">No links found</div>
<?php else: foreach ($allLinks as $link):
$pOn=!array_key_exists('preview_enabled',$link)||(int)$link['preview_enabled']===1; ?>
<div class="link-item">
<div class="short-url"><a href="https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?>" target="_blank">https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?></a>
<span class="clicks"><?= (int)$link['clicks'] ?> clicks</span>
<?php if ($pOn): ?><span class="badge-on">ON</span><?php else: ?><span class="badge-off">OFF</span><?php endif; ?></div>
<div class="long-url"><?= htmlspecialchars($link['long_url']) ?></div>
<div class="meta-line">by <?= htmlspecialchars($link['user_name']??'') ?></div>
<div class="actions">
<button class="btn-copy" onclick="copyLink(this,'https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?>')">Copy</button>
<a href="?stats=<?= (int)$link['id'] ?>" class="btn-stats">Stats</a>
<a href="?tab=create&edit=<?= (int)$link['id'] ?>" class="btn-edit">Edit</a>
<a href="?delete_link=<?= (int)$link['id'] ?>" class="btn-del" onclick="return confirm('Delete?')">Del</a>
</div></div>
<?php endforeach; endif; ?>
</div>
<div class="card <?= $tab==='stats'?'active':'' ?>">
<?php if ($stats): ?>
<h2>Link Analytics</h2>
<p style="font-size:13px;color:var(--accent);margin-bottom:6px;word-break:break-all;">https://<?= $host ?>/<?= htmlspecialchars($stats['short_code']) ?></p>
<p style="font-size:12px;color:var(--muted);margin-bottom:14px;"><?= (int)$stats['clicks'] ?> clicks · by <?= htmlspecialchars($stats['user_name']??'-') ?></p>
<?php if ($stats_error): ?><div class="msg error"><?= htmlspecialchars($stats_error) ?></div><?php endif; ?>
<h3>Device</h3>
<?php $maxd=barMax($s_device); foreach ($s_device as $row): $pct=round(((int)$row['c']/$maxd)*100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['device']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($s_device)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Browser</h3>
<?php foreach ($s_browser as $row): ?><div class="stat-row"><span><?= htmlspecialchars($row['browser']) ?></span><span><?= (int)$row['c'] ?></span></div><?php endforeach; ?>
<?php if (empty($s_browser)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Country</h3>
<?php foreach ($s_country as $row): ?><div class="stat-row"><span><?= htmlspecialchars($row['country']) ?></span><span><?= (int)$row['c'] ?></span></div><?php endforeach; ?>
<?php if (empty($s_country)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Traffic Sources</h3>
<?php foreach ($s_referer as $row): ?><div class="stat-row"><span style="max-width:70%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(mb_substr($row['ref'],0,55)) ?></span><span><?= (int)$row['c'] ?></span></div><?php endforeach; ?>
<?php if (empty($s_referer)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Recent Clicks</h3>
<?php foreach ($recent_clicks as $c): ?>
<div class="click-row"><strong><?= htmlspecialchars($c['device']??'-') ?></strong> · <?= htmlspecialchars($c['browser']??'-') ?> · <?= htmlspecialchars($c['os']??'-') ?><?php if (!empty($c['country'])): ?> · <?= htmlspecialchars($c['country']) ?><?php endif; ?><br>IP: <?= htmlspecialchars($c['ip']??'-') ?> · <?= htmlspecialchars($c['created_at']??'') ?></div>
<?php endforeach; if (empty($recent_clicks)): ?><p class="empty" style="padding:8px 0;">No clicks yet</p><?php endif; ?>
<a href="admin.php?tab=links" class="cancel" style="margin-top:16px;">← Back to Links</a>
<?php else: ?><div class="empty">Select a link and tap Stats</div><a href="admin.php?tab=links" class="cancel">← Back</a><?php endif; ?>
</div>
<div class="card <?= $tab==='settings'?'active':'' ?>">
<h2>Settings — Get license</h2>
<p style="color:var(--muted);font-size:13px;margin-bottom:14px;">Create a 1-domain license. Share key + token. Download starter zip.</p>
<form method="POST">
<input type="hidden" name="action" value="create_license">
<input type="text" name="customer_name" placeholder="Customer name" required>
<input type="text" name="site_url" placeholder="Site URL (example.com)" required>
<button type="submit" class="btn-primary">Generate license key</button>
</form>
<h3>Licenses (<?= count($licenses) ?>)</h3>
<?php if (empty($licenses)): ?><div class="empty">No licenses yet</div>
<?php else: foreach ($licenses as $lic): ?>
<div class="link-item">
<div class="name" style="font-weight:600;"><?= htmlspecialchars($lic['customer_name']) ?></div>
<div class="meta-line"><?= htmlspecialchars($lic['domain']) ?> · <?= htmlspecialchars($lic['status']) ?></div>
<div class="meta-line">Key: <strong><?= htmlspecialchars($lic['license_key']) ?></strong></div>
<div class="meta-line">Token: <strong><?= htmlspecialchars($lic['token']) ?></strong></div>
<div class="actions">
<button class="btn-copy" type="button" onclick="copyLink(this,'<?= htmlspecialchars($lic['license_key'], ENT_QUOTES) ?>')">Copy key</button>
<button class="btn-copy" type="button" onclick="copyLink(this,'<?= htmlspecialchars($lic['token'], ENT_QUOTES) ?>')">Copy token</button>
<a class="btn-stats" href="?download_pack=<?= (int)$lic['id'] ?>">Download 50% zip</a>
<?php if ($lic['status'] !== 'revoked'): ?><a class="btn-del" href="?revoke_license=<?= (int)$lic['id'] ?>" onclick="return confirm('Revoke this license?')">Revoke</a><?php endif; ?>
</div></div>
<?php endforeach; endif; ?>
</div>
</div>
<nav class="bottom-nav">
<a href="admin.php?tab=home" class="nav-item <?= ($tab==='home'||$tab==='')?'active':'' ?>"><i data-lucide="home"></i>Home</a>
<a href="admin.php?tab=create" class="nav-item <?= $tab==='create'?'active':'' ?>"><i data-lucide="plus"></i>Create</a>
<a href="admin.php?tab=analytics" class="nav-item <?= ($tab==='analytics'||$tab==='stats')?'active':'' ?>"><i data-lucide="bar-chart-2"></i>Analytics</a>
<a href="admin.php?tab=users" class="nav-item <?= $tab==='users'?'active':'' ?>"><i data-lucide="users"></i>Users</a>
<a href="admin.php?tab=links" class="nav-item <?= $tab==='links'?'active':'' ?>"><i data-lucide="link"></i>Links</a>
<a href="admin.php?tab=settings" class="nav-item <?= $tab==='settings'?'active':'' ?>"><i data-lucide="settings"></i>Settings</a>
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
  const themeBtn=document.getElementById('themeToggle');
  if(themeBtn) themeBtn.addEventListener('click',function(){
    const next=root.getAttribute('data-theme')==='dark'?'light':'dark';
    root.setAttribute('data-theme',next);
    localStorage.setItem('sl_theme',next);
    setIcon(next);
  });
  const drawer=document.getElementById('drawer');
  const overlay=document.getElementById('drawerOverlay');
  function openDrawer(){
    if(!drawer)return;
    drawer.classList.add('open');
    if(overlay) overlay.classList.add('open');
    document.body.classList.add('drawer-open');
    drawer.setAttribute('aria-hidden','false');
  }
  function closeDrawer(){
    if(!drawer)return;
    drawer.classList.remove('open');
    if(overlay) overlay.classList.remove('open');
    document.body.classList.remove('drawer-open');
    drawer.setAttribute('aria-hidden','true');
  }
  const menuBtn=document.getElementById('menuBtn');
  const closeBtn=document.getElementById('drawerClose');
  if(menuBtn) menuBtn.addEventListener('click',function(e){e.stopPropagation();openDrawer();});
  if(closeBtn) closeBtn.addEventListener('click',closeDrawer);
  if(overlay) overlay.addEventListener('click',closeDrawer);
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeDrawer(); });
  if(window.lucide)lucide.createIcons();
})();
function copyLink(btn,url){
  navigator.clipboard.writeText(url).then(()=>{
    const o=btn.innerText;btn.innerText='Copied!';btn.style.background='#22c55e';
    setTimeout(()=>{btn.innerText=o;btn.style.background='';},1500);
  }).catch(()=>alert('Copy failed'));
}
</script>
</body>
</html>
