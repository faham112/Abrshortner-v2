<?php
require 'config.php';
requireLogin();

if (isAdmin()) {
    header("Location: admin.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$is_error = false;
$edit_data = null;
$host = $_SERVER['HTTP_HOST'];
$tab = $_GET['tab'] ?? 'create';
$stats_link_id = isset($_GET['stats']) ? (int)$_GET['stats'] : 0;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try { $pdo->prepare("DELETE FROM clicks WHERE url_id = ?")->execute([$id]); } catch (Exception $e) {}
    $pdo->prepare("DELETE FROM urls WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
    header("Location: dashboard.php?tab=links&msg=deleted");
    exit;
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM urls WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['edit'], $user_id]);
    $edit_data = $stmt->fetch();
    $tab = 'create';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $long_url    = trim($_POST['long_url'] ?? '');
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');
    $preview_enabled = isset($_POST['preview_enabled']) ? 1 : 0;
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (!empty($_FILES['image_file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext, $allowed) && $_FILES['image_file']['size'] < 5 * 1024 * 1024) {
            $newName = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
                $image_url = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $newName;
            }
        }
    }

    if (empty($long_url)) {
        $message = "Destination URL required!";
        $is_error = true;
        $tab = 'create';
    } else {
        if (!preg_match("~^(?:f|ht)tps?://~i", $long_url)) {
            $long_url = "https://" . $long_url;
        }
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE urls SET long_url=?, title=?, image_url=?, description=?, preview_enabled=? WHERE id=? AND user_id=?");
                $stmt->execute([$long_url, $title ?: null, $image_url ?: null, $description ?: null, $preview_enabled, $id, $user_id]);
                header("Location: dashboard.php?tab=links&msg=updated");
                exit;
            } else {
                $short_code = generateShortCode();
                $attempts = 0;
                $created = false;
                while ($attempts < 5) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO urls (user_id, short_code, long_url, title, image_url, description, preview_enabled) VALUES (?,?,?,?,?,?,?)");
                        $stmt->execute([$user_id, $short_code, $long_url, $title ?: null, $image_url ?: null, $description ?: null, $preview_enabled]);
                        $created = true;
                        break;
                    } catch (PDOException $e) {
                        $short_code = generateShortCode();
                        $attempts++;
                    }
                }
                if ($created) {
                    header("Location: dashboard.php?tab=links&msg=created");
                    exit;
                }
                $message = "Error creating link. Try again.";
                $is_error = true;
                $tab = 'create';
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $is_error = true;
            $tab = 'create';
        }
    }
}

$search = trim($_GET['search'] ?? '');
try {
    if ($search) {
        $stmt = $pdo->prepare("SELECT * FROM urls WHERE user_id = ? AND (short_code LIKE ? OR long_url LIKE ? OR title LIKE ?) ORDER BY id DESC");
        $like = "%$search%";
        $stmt->execute([$user_id, $like, $like, $like]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM urls WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
    }
    $links = $stmt->fetchAll();
} catch (Exception $e) {
    $links = [];
    $message = "Links load error: " . $e->getMessage();
    $is_error = true;
}

$totalLinks = count($links);
$totalClicks = 0;
foreach ($links as $l) $totalClicks += (int)($l['clicks'] ?? 0);

$by_device = []; $by_browser = []; $by_country = []; $by_os = [];
$top_links = []; $recent_clicks_global = [];
$clicks_today = 0; $clicks_7d = 0; $analytics_error = '';
try {
    $by_device = $pdo->prepare("SELECT COALESCE(device,'Unknown') as device, COUNT(*) as c FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 GROUP BY device ORDER BY c DESC");
    $by_device->execute([$user_id]); $by_device = $by_device->fetchAll();
    $by_browser = $pdo->prepare("SELECT COALESCE(browser,'Unknown') as browser, COUNT(*) as c FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 GROUP BY browser ORDER BY c DESC");
    $by_browser->execute([$user_id]); $by_browser = $by_browser->fetchAll();
    $by_country = $pdo->prepare("SELECT COALESCE(country,'Unknown') as country, COUNT(*) as c FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 GROUP BY country ORDER BY c DESC LIMIT 15");
    $by_country->execute([$user_id]); $by_country = $by_country->fetchAll();
    $by_os = $pdo->prepare("SELECT COALESCE(os,'Unknown') as os, COUNT(*) as c FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 GROUP BY os ORDER BY c DESC");
    $by_os->execute([$user_id]); $by_os = $by_os->fetchAll();
    $top_links = $pdo->prepare("SELECT short_code, title, clicks FROM urls WHERE user_id = ? ORDER BY clicks DESC LIMIT 10");
    $top_links->execute([$user_id]); $top_links = $top_links->fetchAll();
    $recent_clicks_global = $pdo->prepare("SELECT c.*, u.short_code FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 ORDER BY c.id DESC LIMIT 20");
    $recent_clicks_global->execute([$user_id]); $recent_clicks_global = $recent_clicks_global->fetchAll();
    $st = $pdo->prepare("SELECT COUNT(*) FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 AND DATE(c.created_at) = CURDATE()");
    $st->execute([$user_id]); $clicks_today = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT COUNT(*) FROM clicks c JOIN urls u ON u.id = c.url_id WHERE u.user_id = ? AND c.is_bot = 0 AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $st->execute([$user_id]); $clicks_7d = (int)$st->fetchColumn();
} catch (Exception $e) {
    $analytics_error = $e->getMessage();
}

$stats = null;
$recent_clicks = [];
$s_device = []; $s_browser = []; $s_country = []; $s_referer = [];
$stats_error = '';
if ($stats_link_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM urls WHERE id = ? AND user_id = ?");
    $stmt->execute([$stats_link_id, $user_id]);
    $stats = $stmt->fetch();
    if ($stats) {
        $tab = 'stats';
        try {
            $q = $pdo->prepare("SELECT * FROM clicks WHERE url_id = ? AND is_bot = 0 ORDER BY id DESC LIMIT 80");
            $q->execute([$stats_link_id]); $recent_clicks = $q->fetchAll();
            $q = $pdo->prepare("SELECT COALESCE(device,'Unknown') as device, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY device ORDER BY c DESC");
            $q->execute([$stats_link_id]); $s_device = $q->fetchAll();
            $q = $pdo->prepare("SELECT COALESCE(browser,'Unknown') as browser, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY browser ORDER BY c DESC");
            $q->execute([$stats_link_id]); $s_browser = $q->fetchAll();
            $q = $pdo->prepare("SELECT COALESCE(country,'Unknown') as country, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY country ORDER BY c DESC LIMIT 15");
            $q->execute([$stats_link_id]); $s_country = $q->fetchAll();
            $q = $pdo->prepare("SELECT CASE WHEN referer IS NULL OR referer = '' THEN 'Direct' ELSE referer END as ref, COUNT(*) as c FROM clicks WHERE url_id = ? AND is_bot = 0 GROUP BY ref ORDER BY c DESC LIMIT 20");
            $q->execute([$stats_link_id]); $s_referer = $q->fetchAll();
        } catch (Exception $e) {
            $stats_error = $e->getMessage();
        }
    }
}

if (isset($_GET['msg']) && in_array($_GET['msg'], ['created','updated','deleted'], true) && !isset($_GET['tab'])) {
    $tab = 'links';
}
if (isset($_GET['tab'])) $tab = $_GET['tab'];
if ($stats_link_id > 0 && $stats) $tab = 'stats';

function barMax($rows) {
    if (empty($rows)) return 1;
    $m = max(array_map(function($r){ return (int)$r['c']; }, $rows));
    return $m > 0 ? $m : 1;
}
