<?php
require 'config.php';
if (is_file(__DIR__ . '/license_lib.php')) {
    require_once __DIR__ . '/license_lib.php';
}
requireAdmin();
if (function_exists('abrEnsureLicensesTable')) {
    abrEnsureLicensesTable($pdo);
}

$message = '';
$is_error = false;
$edit_data = null;
$admin_id = $_SESSION['user_id'];
$host = $_SERVER['HTTP_HOST'];
$tab = $_GET['tab'] ?? 'home';
$stats_link_id = isset($_GET['stats']) ? (int)$_GET['stats'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_link') {
    $long_url = trim($_POST['long_url'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $preview_enabled = isset($_POST['preview_enabled']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!empty($_FILES['image_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed) && $_FILES['image_file']['size'] < 5 * 1024 * 1024) {
            $newName = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newName)) {
                $image_url = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $newName;
            }
        }
    }
    if (empty($long_url)) { $message = 'Destination URL required!'; $is_error = true; $tab = 'create'; }
    else {
        if (!preg_match('~^(?:f|ht)tps?://~i', $long_url)) $long_url = 'https://' . $long_url;
        try {
            if ($id > 0) {
                $pdo->prepare('UPDATE urls SET long_url=?, title=?, image_url=?, description=?, preview_enabled=? WHERE id=?')
                    ->execute([$long_url, $title ?: null, $image_url ?: null, $description ?: null, $preview_enabled, $id]);
                header('Location: admin.php?tab=links&msg=updated'); exit;
            } else {
                $short_code = generateShortCode(); $ok = false;
                for ($i = 0; $i < 5; $i++) {
                    try {
                        $pdo->prepare('INSERT INTO urls (user_id, short_code, long_url, title, image_url, description, preview_enabled) VALUES (?,?,?,?,?,?,?)')
                            ->execute([$admin_id, $short_code, $long_url, $title ?: null, $image_url ?: null, $description ?: null, $preview_enabled]);
                        $ok = true; break;
                    } catch (PDOException $e) { $short_code = generateShortCode(); }
                }
                if ($ok) { header('Location: admin.php?tab=links&msg=created'); exit; }
                $message = 'Error creating link'; $is_error = true; $tab = 'create';
            }
        } catch (PDOException $e) { $message = 'DB error: ' . $e->getMessage(); $is_error = true; $tab = 'create'; }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $pass = $_POST['password'] ?? '';
    if (empty($name) || empty($email) || empty($pass)) { $message = 'All fields required'; $is_error = true; $tab = 'users'; }
    elseif (strlen($pass) < 6) { $message = 'Password min 6 characters'; $is_error = true; $tab = 'users'; }
    else {
        try {
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')")
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            header('Location: admin.php?tab=home&msg=user_created'); exit;
        } catch (PDOException $e) { $message = 'Email already exists!'; $is_error = true; $tab = 'users'; }
    }
}

if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    if ($id != $admin_id) {
        try { $pdo->prepare('DELETE FROM clicks WHERE url_id IN (SELECT id FROM urls WHERE user_id = ?)')->execute([$id]); } catch (Exception $e) {}
        $pdo->prepare('DELETE FROM urls WHERE user_id = ?')->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$id]);
    }
    header('Location: admin.php?tab=users&msg=user_deleted'); exit;
}
if (isset($_GET['delete_link'])) {
    $id = (int)$_GET['delete_link'];
    try { $pdo->prepare('DELETE FROM clicks WHERE url_id = ?')->execute([$id]); } catch (Exception $e) {}
    $pdo->prepare('DELETE FROM urls WHERE id = ?')->execute([$id]);
    header('Location: admin.php?tab=links&msg=deleted'); exit;
}

if (function_exists('abrNormDomain') && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_license') {
    $cname = trim($_POST['customer_name'] ?? '');
    $site = trim($_POST['site_url'] ?? '');
    $domain = abrNormDomain($site);
    $tab = 'settings';
    if ($cname === '' || $domain === '') {
        $message = 'Customer name and site URL required';
        $is_error = true;
    } else {
        try {
            $key = abrGenLicenseKey();
            $token = bin2hex(random_bytes(16));
            $pdo->prepare('INSERT INTO licenses (customer_name, site_url, domain, license_key, token, status) VALUES (?,?,?,?,?,?)')
                ->execute([$cname, $site, $domain, $key, $token, 'pending']);
            header('Location: admin_settings.php?msg=license_created');
            exit;
        } catch (Exception $e) {
            $message = 'Could not create license';
            $is_error = true;
        }
    }
}

if (isset($_GET['revoke_license'])) {
    $lid = (int)$_GET['revoke_license'];
    $pdo->prepare("UPDATE licenses SET status='revoked' WHERE id=?")->execute([$lid]);
    header('Location: admin_settings.php?msg=license_revoked');
    exit;
}

if (function_exists('abrBuildStarterZip') && isset($_GET['download_pack'])) {
    $lid = (int)$_GET['download_pack'];
    $st = $pdo->prepare('SELECT * FROM licenses WHERE id=?');
    $st->execute([$lid]);
    $lic = $st->fetch();
    if ($lic) {
        try {
            $path = abrBuildStarterZip($lic, abrLicenseServerBase());
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="ABR-starter-' . $lic['domain'] . '.zip"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            @unlink($path);
            exit;
        } catch (Exception $e) {
            $message = $e->getMessage();
            $is_error = true;
            $tab = 'settings';
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit_data = $stmt->fetch();
    $tab = 'create';
}

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalLinks = (int)$pdo->query('SELECT COUNT(*) FROM urls')->fetchColumn();
$totalClicks = (int)$pdo->query('SELECT COALESCE(SUM(clicks),0) FROM urls')->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM urls WHERE user_id = ?'); $st->execute([$admin_id]); $myLinksCount = (int)$st->fetchColumn();
$users = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM urls WHERE user_id = u.id) as link_count FROM users u WHERE role = 'user' ORDER BY id DESC")->fetchAll();
$recentUsers = array_slice($users, 0, 6);
$search = trim($_GET['search'] ?? '');
if ($search && (($_GET['tab'] ?? '') === 'links')) {
    $stmt = $pdo->prepare('SELECT urls.*, users.name as user_name FROM urls JOIN users ON urls.user_id = users.id WHERE short_code LIKE ? OR long_url LIKE ? OR title LIKE ? ORDER BY urls.id DESC');
    $like = "%$search%"; $stmt->execute([$like, $like, $like]); $allLinks = $stmt->fetchAll();
} else {
    $allLinks = $pdo->query('SELECT urls.*, users.name as user_name FROM urls JOIN users ON urls.user_id = users.id ORDER BY urls.id DESC LIMIT 100')->fetchAll();
}
$recent5 = $pdo->query('SELECT urls.*, users.name as user_name FROM urls JOIN users ON urls.user_id = users.id ORDER BY urls.id DESC LIMIT 5')->fetchAll();

$by_device=[]; $by_browser=[]; $by_country=[]; $by_os=[]; $top_links=[]; $recent_clicks_global=[]; $clicks_today=0; $clicks_7d=0; $analytics_error='';
try {
    $by_device = $pdo->query("SELECT COALESCE(device,'Unknown') as device, COUNT(*) as c FROM clicks WHERE is_bot = 0 GROUP BY device ORDER BY c DESC")->fetchAll();
    $by_browser = $pdo->query("SELECT COALESCE(browser,'Unknown') as browser, COUNT(*) as c FROM clicks WHERE is_bot = 0 GROUP BY browser ORDER BY c DESC")->fetchAll();
    $by_country = $pdo->query("SELECT COALESCE(country,'Unknown') as country, COUNT(*) as c FROM clicks WHERE is_bot = 0 GROUP BY country ORDER BY c DESC LIMIT 15")->fetchAll();
    $by_os = $pdo->query("SELECT COALESCE(os,'Unknown') as os, COUNT(*) as c FROM clicks WHERE is_bot = 0 GROUP BY os ORDER BY c DESC")->fetchAll();
    $top_links = $pdo->query('SELECT short_code, title, clicks FROM urls ORDER BY clicks DESC LIMIT 10')->fetchAll();
    $recent_clicks_global = $pdo->query('SELECT c.*, u.short_code FROM clicks c LEFT JOIN urls u ON u.id = c.url_id WHERE c.is_bot = 0 ORDER BY c.id DESC LIMIT 20')->fetchAll();
    $clicks_today = (int)$pdo->query('SELECT COUNT(*) FROM clicks WHERE is_bot = 0 AND DATE(created_at) = CURDATE()')->fetchColumn();
    $clicks_7d = (int)$pdo->query('SELECT COUNT(*) FROM clicks WHERE is_bot = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
} catch (Exception $e) { $analytics_error = $e->getMessage(); }

$stats=null; $recent_clicks=[]; $s_device=[]; $s_browser=[]; $s_country=[]; $s_referer=[]; $stats_error='';
if ($stats_link_id > 0) {
    $stmt = $pdo->prepare('SELECT urls.*, users.name as user_name FROM urls LEFT JOIN users ON urls.user_id = users.id WHERE urls.id = ?');
    $stmt->execute([$stats_link_id]); $stats = $stmt->fetch();
    if ($stats) {
        $tab = 'stats';
        try {
            $q = $pdo->prepare('SELECT * FROM clicks WHERE url_id = ? AND is_bot = 0 ORDER BY id DESC LIMIT 80'); $q->execute([$stats_link_id]); $recent_clicks = $q->fetchAll();
            $q = $pdo->prepare("SELECT COALESCE(device,'Unknown') as device, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY device ORDER BY c DESC"); $q->execute([$stats_link_id]); $s_device = $q->fetchAll();
            $q = $pdo->prepare("SELECT COALESCE(browser,'Unknown') as browser, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY browser ORDER BY c DESC"); $q->execute([$stats_link_id]); $s_browser = $q->fetchAll();
            $q = $pdo->prepare("SELECT COALESCE(country,'Unknown') as country, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY country ORDER BY c DESC LIMIT 15"); $q->execute([$stats_link_id]); $s_country = $q->fetchAll();
            $q = $pdo->prepare("SELECT CASE WHEN referer IS NULL OR referer = '' THEN 'Direct' ELSE referer END as ref, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY ref ORDER BY c DESC LIMIT 20"); $q->execute([$stats_link_id]); $s_referer = $q->fetchAll();
        } catch (Exception $e) { $stats_error = $e->getMessage(); }
    }
}
if (isset($_GET['tab'])) $tab = $_GET['tab'];
if ($stats_link_id > 0 && $stats) $tab = 'stats';
$licenses = [];
try { $licenses = $pdo->query('SELECT * FROM licenses ORDER BY id DESC')->fetchAll(); } catch (Exception $e) {}
function barMax($rows) { if (empty($rows)) return 1; $m = max(array_map(function($r){return (int)$r['c'];}, $rows)); return $m > 0 ? $m : 1; }
