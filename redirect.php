<?php
require 'config.php';

$code = $_GET['code'] ?? '';
if (empty($code) || !preg_match('/^[a-zA-Z0-9]+$/', $code)) {
    http_response_code(404);
    exit('Not found');
}

$stmt = $pdo->prepare("SELECT short_code, long_url, title, image_url, description, preview_enabled FROM urls WHERE short_code = ? LIMIT 1");
try {
    $stmt->execute([$code]);
    $link = $stmt->fetch();
} catch (PDOException $e) {
    $stmt = $pdo->prepare("SELECT short_code, long_url, title, image_url, description FROM urls WHERE short_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $link = $stmt->fetch();
}

if (!$link) {
    http_response_code(404);
    exit('Not found');
}

$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$isBot = (strlen($ua) < 25);
if (!$isBot) {
    foreach (['whatsapp','facebookexternalhit','facebot','twitterbot','telegrambot','linkedinbot','slackbot','discordbot','googlebot','bingbot','bot','crawler','spider','preview','meta-external'] as $bot) {
        if (strpos($ua, $bot) !== false) { $isBot = true; break; }
    }
}

$previewOn = true;
if (array_key_exists('preview_enabled', $link)) {
    $previewOn = ((int)$link['preview_enabled'] === 1);
}

if ($isBot) {
    header_remove('X-Powered-By');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    if (!$previewOn) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex"><title></title></head><body></body></html>';
        exit;
    }
    $title = !empty($link['title']) ? $link['title'] : 'Breaking News';
    $description = !empty($link['description']) ? $link['description'] : 'Latest updates and full story';
    $image = !empty($link['image_url']) ? $link['image_url'] : '';
    $shortUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/' . $link['short_code'];
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>'.htmlspecialchars($title).'</title>';
    echo '<meta property="og:title" content="'.htmlspecialchars($title).'">';
    echo '<meta property="og:description" content="'.htmlspecialchars($description).'">';
    echo '<meta property="og:url" content="'.htmlspecialchars($shortUrl).'">';
    if ($image) echo '<meta property="og:image" content="'.htmlspecialchars($image).'">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="robots" content="noindex,nofollow"></head><body></body></html>';
    exit;
}

header_remove('X-Powered-By');
header('Location: ' . $link['long_url'], true, 302);
exit;
