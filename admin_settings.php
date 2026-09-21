<?php
require __DIR__ . '/admin_logic.php';
$host = $_SERVER['HTTP_HOST'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>License HQ - ShortLink</title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/app.css?v=20260921a">
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
<a href="admin_settings.php" class="active"><i data-lucide="key-round"></i> License HQ</a>
<a href="admin.php?tab=settings"><i data-lucide="settings"></i> Settings</a>
<button type="button" class="drawer-link" id="themeToggle"><i data-lucide="moon" id="themeIcon"></i> Toggle theme</button>
<div class="drawer-foot">
<a class="danger" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>
</aside>

<div class="header">
<div class="header-left">
<button type="button" class="icon-btn" id="menuBtn" aria-label="Open menu"><i data-lucide="menu"></i></button>
<h1>License HQ</h1>
</div>
<div class="header-right">
<span class="badge">Admin</span>
</div>
</div>

<div class="container">
<?php if (isset($_GET['msg'])): ?><div class="msg"><?php $msgs=['license_created'=>'License created. Share key + token and send starter zip.','license_revoked'=>'License revoked. Ab isi domain par naya license generate kar sakte ho.','license_deleted'=>'License deleted. Ab naya license generate kar sakte ho.']; echo $msgs[$_GET['msg']]??''; ?></div><?php endif; ?>
<?php if (!empty($message)): ?><div class="msg <?= !empty($is_error)?'error':'' ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="card active">
<h2>Get license</h2>
<p style="color:var(--muted);font-size:13px;margin-bottom:14px;">Customer ko 1-domain license do. Key + token share karo, 50% zip download karke unhe bhejo. Woh public_html mein daal kar /install.php kholen — baqi files is server se aayengi.</p>
<form method="POST" action="admin.php?tab=settings">
<input type="hidden" name="action" value="create_license">
<input type="text" name="customer_name" placeholder="Customer name" required>
<input type="text" name="site_url" placeholder="Site URL (example.com)" required>
<button type="submit" class="btn-primary">Generate license key</button>
</form>
<h3>Licenses (<?= count($licenses ?? []) ?>)</h3>
<?php if (empty($licenses)): ?><div class="empty">No licenses yet</div>
<?php else: foreach ($licenses as $lic): ?>
<div class="link-item">
<div style="font-weight:600;"><?= htmlspecialchars($lic['customer_name']) ?></div>
<div class="meta-line"><?= htmlspecialchars($lic['domain']) ?> · <?= htmlspecialchars($lic['status']) ?></div>
<div class="meta-line">Key: <strong><?= htmlspecialchars($lic['license_key']) ?></strong></div>
<div class="meta-line">Token: <strong><?= htmlspecialchars($lic['token']) ?></strong></div>
<div class="actions">
<button class="btn-copy" type="button" onclick="copyLink(this,'<?= htmlspecialchars($lic['license_key'], ENT_QUOTES) ?>')">Copy key</button>
<button class="btn-copy" type="button" onclick="copyLink(this,'<?= htmlspecialchars($lic['token'], ENT_QUOTES) ?>')">Copy token</button>
<a class="btn-stats" href="admin.php?download_pack=<?= (int)$lic['id'] ?>">Download 50% zip</a>
<?php if ($lic['status'] !== 'revoked'): ?><a class="btn-del" href="admin.php?revoke_license=<?= (int)$lic['id'] ?>" onclick="return confirm('Revoke? Domain phir naya license le sakta hai.')">Revoke</a><?php endif; ?>
<a class="btn-del" href="admin.php?delete_license=<?= (int)$lic['id'] ?>" onclick="return confirm('Delete permanently? Uske baad isi domain ka naya license generate ho sakta hai.')">Delete</a>
</div>
</div>
<?php endforeach; endif; ?>
</div>
</div>

<nav class="bottom-nav">
<a href="admin.php?tab=home" class="nav-item"><i data-lucide="home"></i>Home</a>
<a href="admin.php?tab=create" class="nav-item"><i data-lucide="plus"></i>Create</a>
<a href="admin.php?tab=analytics" class="nav-item"><i data-lucide="bar-chart-2"></i>Analytics</a>
<a href="admin.php?tab=users" class="nav-item"><i data-lucide="users"></i>Users</a>
<a href="admin.php?tab=links" class="nav-item"><i data-lucide="link"></i>Links</a>
<a href="admin_settings.php" class="nav-item active"><i data-lucide="settings"></i>Settings</a>
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
