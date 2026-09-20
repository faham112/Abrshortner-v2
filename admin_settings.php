<?php
require __DIR__ . '/admin_logic.php';
if (($tab ?? '') !== 'settings' && !isset($_GET['download_pack'])) {
    // allow direct open
}
$host = $_SERVER['HTTP_HOST'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>License Settings</title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="header">
<h1>License HQ</h1>
<div class="header-right">
<span class="badge">Admin</span>
<div class="menu-wrap">
<button type="button" class="icon-btn" id="menuBtn" aria-label="Menu"><i data-lucide="menu"></i></button>
<div class="menu-drop" id="menuDrop">
<a href="admin.php"><i data-lucide="home"></i> Admin panel</a>
<a href="admin.php?tab=users"><i data-lucide="users"></i> Users</a>
<a href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>
</div>
</div>
</div>
<div class="container">
<?php if (isset($_GET['msg'])): ?><div class="msg"><?php $msgs=['license_created'=>'License created. Share key + token and send starter zip.','license_revoked'=>'License revoked']; echo $msgs[$_GET['msg']]??''; ?></div><?php endif; ?>
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
<a class="btn-stats" href="admin.php?download_pack=<?= (int)$lic['id'] ?>">Download 50% zip</a>
<?php if ($lic['status'] !== 'revoked'): ?><a class="btn-del" href="admin.php?revoke_license=<?= (int)$lic['id'] ?>" onclick="return confirm('Revoke?')">Revoke</a><?php endif; ?>
</div>
</div>
<?php endforeach; endif; ?>
</div>
</div>
<script>
(function(){
  const root=document.documentElement;
  root.setAttribute('data-theme', localStorage.getItem('sl_theme')||'light');
  const menuBtn=document.getElementById('menuBtn');
  const menuDrop=document.getElementById('menuDrop');
  if(menuBtn&&menuDrop){
    menuBtn.addEventListener('click',function(e){e.stopPropagation();menuDrop.classList.toggle('open');});
    document.addEventListener('click',function(){menuDrop.classList.remove('open');});
  }
  if(window.lucide)lucide.createIcons();
})();
</script>
</body>
</html>
