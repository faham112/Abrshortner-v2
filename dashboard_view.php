<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Dashboard - ShortLink</title>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="header">
<h1>ShortLink</h1>
<div class="header-right">
<button type="button" class="icon-btn" id="themeToggle" title="Toggle theme"><i data-lucide="moon" id="themeIcon"></i></button>
<span class="user"><?= htmlspecialchars($_SESSION['name']) ?></span>
<a href="logout.php" class="btn-logout">Logout</a>
</div>
</div>
<div class="container">
<?php if (isset($_GET['msg'])): ?>
<div class="msg">
<?php
if ($_GET['msg'] === 'created') echo "Link created successfully!";
elseif ($_GET['msg'] === 'updated') echo "Link updated!";
elseif ($_GET['msg'] === 'deleted') echo "Link deleted!";
?>
</div>
<?php endif; ?>
<?php if ($message): ?>
<div class="msg <?= $is_error ? 'msg-err' : '' ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<div class="card <?= $tab === 'create' ? 'active' : '' ?>">
<h2><?= $edit_data ? 'Edit Link' : 'Create Short Link' ?></h2>
<?php
$editPreviewOn = true;
if ($edit_data && array_key_exists('preview_enabled', $edit_data)) {
    $editPreviewOn = ((int)$edit_data['preview_enabled'] === 1);
}
?>
<?php if ($edit_data && !$editPreviewOn): ?>
<div class="msg msg-warn">Preview is OFF</div>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data">
<?php if ($edit_data): ?><input type="hidden" name="id" value="<?= (int)$edit_data['id'] ?>"><?php endif; ?>
<input type="text" name="long_url" placeholder="Destination URL" required value="<?= htmlspecialchars($edit_data['long_url'] ?? '') ?>">
<input type="text" name="title" placeholder="Title" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>">
<textarea name="description" placeholder="Description"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
<label class="file-label">Preview Image</label>
<input type="file" name="image_file" accept="image/*" class="file-input">
<?php if (!empty($edit_data['image_url'])): ?>
<div style="margin-bottom:12px;font-size:12px;color:var(--muted);">Current: <a href="<?= htmlspecialchars($edit_data['image_url']) ?>" target="_blank" style="color:var(--accent);">View Image</a></div>
<?php endif; ?>
<input type="text" name="image_url" placeholder="Or Image URL" value="<?= htmlspecialchars($edit_data['image_url'] ?? '') ?>">
<div class="toggle-row">
<div>
<div class="toggle-text">Link Preview</div>
<div class="toggle-sub">ON = show · OFF = hide</div>
</div>
<label class="switch">
<input type="checkbox" name="preview_enabled" value="1" <?= $editPreviewOn ? 'checked' : '' ?> onchange="togglePreviewAlert(this)">
<span class="slider"></span>
</label>
</div>
<div id="previewOffAlert" class="msg msg-warn" style="display:<?= $editPreviewOn ? 'none' : 'block' ?>;">Preview OFF</div>
<button type="submit" class="btn-primary"><?= $edit_data ? 'Update Link' : 'Shorten Now' ?></button>
<?php if ($edit_data): ?><a href="dashboard.php?tab=create" class="cancel">Cancel Edit</a><?php endif; ?>
</form>
</div>
<div class="card <?= $tab === 'links' ? 'active' : '' ?>">
<div class="stats">
<div class="stat-card"><div class="num"><?= (int)$totalLinks ?></div><div class="label">My Links</div></div>
<div class="stat-card"><div class="num"><?= (int)$totalClicks ?></div><div class="label">Total Clicks</div></div>
</div>
<h2>Your Links</h2>
<form method="GET" class="search-bar">
<input type="hidden" name="tab" value="links">
<input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button>
</form>
<?php if (empty($links)): ?>
<div class="empty">No links yet. Create your first short link!</div>
<?php else: ?>
<?php foreach ($links as $link):
    $pOn = true;
    if (array_key_exists('preview_enabled', $link)) $pOn = ((int)$link['preview_enabled'] === 1);
?>
<div class="link-item">
<div class="short-url">
<a href="https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?>" target="_blank">https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?></a>
<span class="clicks"><?= (int)($link['clicks'] ?? 0) ?> clicks</span>
<?php if ($pOn): ?><span class="badge-on">ON</span><?php else: ?><span class="badge-off">OFF</span><?php endif; ?>
</div>
<div class="long-url"><?= htmlspecialchars($link['long_url']) ?></div>
<div class="actions">
<button class="btn-copy" onclick="copyLink(this, 'https://<?= $host ?>/<?= htmlspecialchars($link['short_code']) ?>')">Copy</button>
<a href="?stats=<?= (int)$link['id'] ?>" class="btn-stats">Stats</a>
<a href="?tab=create&edit=<?= (int)$link['id'] ?>" class="btn-edit">Edit</a>
<a href="?delete=<?= (int)$link['id'] ?>" class="btn-del" onclick="return confirm('Delete this link?')">Del</a>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<div class="card <?= $tab === 'analytics' ? 'active' : '' ?>">
<h2>Analytics</h2>
<div class="stats">
<div class="stat-card"><div class="num"><?= $clicks_today ?></div><div class="label">Today</div></div>
<div class="stat-card"><div class="num"><?= $clicks_7d ?></div><div class="label">Last 7 Days</div></div>
<div class="stat-card"><div class="num"><?= $totalClicks ?></div><div class="label">All Time</div></div>
<div class="stat-card"><div class="num"><?= $totalLinks ?></div><div class="label">Links</div></div>
</div>
<?php if ($analytics_error): ?><div class="msg msg-err"><?= htmlspecialchars($analytics_error) ?></div><?php endif; ?>
<h3>Traffic by Device</h3>
<?php $maxd = barMax($by_device); foreach ($by_device as $row): $pct = round(((int)$row['c'] / $maxd) * 100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['device']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_device)): ?><p class="empty" style="padding:8px 0;">No clicks yet</p><?php endif; ?>
<div class="chart-grid">
<div class="chart-box"><h4>Browser</h4>
<?php $maxb = barMax($by_browser); foreach (array_slice($by_browser, 0, 6) as $row): $pct = round(((int)$row['c'] / $maxb) * 100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['browser']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar blue" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_browser)): ?><p class="empty" style="padding:6px 0;font-size:12px;">No data</p><?php endif; ?>
</div>
<div class="chart-box"><h4>OS</h4>
<?php $maxo = barMax($by_os); foreach (array_slice($by_os, 0, 6) as $row): $pct = round(((int)$row['c'] / $maxo) * 100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['os']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar orange" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_os)): ?><p class="empty" style="padding:6px 0;font-size:12px;">No data</p><?php endif; ?>
</div>
</div>
<h3>By Country</h3>
<?php $maxc = barMax($by_country); foreach ($by_country as $row): $pct = round(((int)$row['c'] / $maxc) * 100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['country']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar green" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($by_country)): ?><p class="empty" style="padding:8px 0;">No data yet</p><?php endif; ?>
<h3>Top Links</h3>
<?php if (empty($top_links)): ?><p class="empty" style="padding:8px 0;">No links</p>
<?php else: $maxt = max(1, max(array_column($top_links, 'clicks'))); foreach ($top_links as $tl): $pct = round(((int)$tl['clicks'] / $maxt) * 100); ?>
<div class="stat-row"><span style="max-width:70%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">/<?= htmlspecialchars($tl['short_code']) ?></span><span><?= (int)$tl['clicks'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; endif; ?>
<h3>Recent Clicks</h3>
<?php foreach ($recent_clicks_global as $c): ?>
<div class="click-row"><strong>/<?= htmlspecialchars($c['short_code'] ?? '?') ?></strong> · <?= htmlspecialchars($c['device'] ?? '-') ?> · <?= htmlspecialchars($c['browser'] ?? '-') ?><?php if (!empty($c['country'])): ?> · <?= htmlspecialchars($c['country']) ?><?php endif; ?><br><?= htmlspecialchars($c['ip'] ?? '-') ?> · <?= htmlspecialchars($c['created_at'] ?? '') ?></div>
<?php endforeach; if (empty($recent_clicks_global)): ?><p class="empty" style="padding:8px 0;">No clicks logged yet</p><?php endif; ?>
</div>
<div class="card <?= $tab === 'stats' ? 'active' : '' ?>">
<?php if ($stats): ?>
<h2>Link Traffic</h2>
<p style="font-size:13px;color:var(--accent);margin-bottom:8px;word-break:break-all;">https://<?= $host ?>/<?= htmlspecialchars($stats['short_code']) ?></p>
<p style="font-size:12px;color:var(--muted);margin-bottom:8px;"><?= (int)($stats['clicks'] ?? 0) ?> clicks
<?php $sp = true; if (array_key_exists('preview_enabled', $stats)) $sp = ((int)$stats['preview_enabled'] === 1); ?>
<?php if ($sp): ?><span class="badge-on">Preview ON</span><?php else: ?><span class="badge-off">Preview OFF</span><?php endif; ?>
</p>
<?php if ($stats_error): ?><div class="msg msg-err"><?= htmlspecialchars($stats_error) ?></div><?php endif; ?>
<h3>By Device</h3>
<?php $maxd = barMax($s_device); foreach ($s_device as $row): $pct = round(((int)$row['c'] / $maxd) * 100); ?>
<div class="stat-row"><span><?= htmlspecialchars($row['device']) ?></span><span><?= (int)$row['c'] ?></span></div>
<div class="stat-bar-wrap"><div class="stat-bar" style="width:<?= $pct ?>%"></div></div>
<?php endforeach; if (empty($s_device)): ?><p class="empty" style="padding:8px 0;">No click data yet</p><?php endif; ?>
<h3>Browser</h3>
<?php foreach ($s_browser as $row): ?><div class="stat-row"><span><?= htmlspecialchars($row['browser']) ?></span><span><?= (int)$row['c'] ?></span></div><?php endforeach; ?>
<?php if (empty($s_browser)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Country</h3>
<?php foreach ($s_country as $row): ?><div class="stat-row"><span><?= htmlspecialchars($row['country']) ?></span><span><?= (int)$row['c'] ?></span></div><?php endforeach; ?>
<?php if (empty($s_country)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Traffic Sources</h3>
<?php foreach ($s_referer as $row): ?>
<div class="stat-row"><span style="max-width:70%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(mb_substr($row['ref'], 0, 55)) ?></span><span><?= (int)$row['c'] ?></span></div>
<?php endforeach; ?>
<?php if (empty($s_referer)): ?><p class="empty" style="padding:8px 0;">No data</p><?php endif; ?>
<h3>Recent Clicks</h3>
<?php foreach ($recent_clicks as $c): ?>
<div class="click-row"><strong><?= htmlspecialchars($c['device'] ?? '-') ?></strong> · <?= htmlspecialchars($c['browser'] ?? '-') ?> · <?= htmlspecialchars($c['os'] ?? '-') ?><?php if (!empty($c['country'])): ?> · <?= htmlspecialchars($c['country']) ?><?php endif; ?><br>IP: <?= htmlspecialchars($c['ip'] ?? '-') ?> · <?= htmlspecialchars($c['created_at'] ?? '') ?></div>
<?php endforeach; if (empty($recent_clicks)): ?><p class="empty" style="padding:8px 0;">No clicks yet</p><?php endif; ?>
<a href="dashboard.php?tab=links" class="cancel" style="margin-top:16px;">← Back to Links</a>
<?php else: ?>
<div class="empty">Select a link and tap Stats</div>
<a href="dashboard.php?tab=links" class="cancel">← Back to Links</a>
<?php endif; ?>
</div>
</div>
<nav class="bottom-nav">
<a href="dashboard.php?tab=create" class="nav-item <?= $tab === 'create' ? 'active' : '' ?>"><i data-lucide="plus"></i>Create</a>
<a href="dashboard.php?tab=links" class="nav-item <?= ($tab === 'links' || $tab === 'stats') ? 'active' : '' ?>"><i data-lucide="link"></i>Links</a>
<a href="dashboard.php?tab=analytics" class="nav-item <?= $tab === 'analytics' ? 'active' : '' ?>"><i data-lucide="bar-chart-2"></i>Analytics</a>
</nav>
<script>
(function(){
  const root = document.documentElement;
  const saved = localStorage.getItem('sl_theme') || 'dark';
  root.setAttribute('data-theme', saved);
  function setIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (!icon) return;
    icon.setAttribute('data-lucide', theme === 'dark' ? 'sun' : 'moon');
    if (window.lucide) lucide.createIcons();
  }
  setIcon(saved);
  document.getElementById('themeToggle').addEventListener('click', function() {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('sl_theme', next);
    setIcon(next);
  });
  if (window.lucide) lucide.createIcons();
})();
function copyLink(btn, url) {
  navigator.clipboard.writeText(url).then(() => {
    const o = btn.innerText;
    btn.innerText = "Copied!";
    btn.style.background = "#22c55e";
    setTimeout(() => { btn.innerText = o; btn.style.background = ""; }, 1500);
  }).catch(() => alert("Copy failed"));
}
function togglePreviewAlert(el) {
  var a = document.getElementById('previewOffAlert');
  if (a) a.style.display = el.checked ? 'none' : 'block';
}
</script>
</body>
</html>
