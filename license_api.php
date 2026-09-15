<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/license_lib.php';

header('Content-Type: application/json; charset=utf-8');
abrEnsureLicensesTable($pdo);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$key = trim($_POST['license_key'] ?? $_GET['license_key'] ?? '');
$token = trim($_POST['token'] ?? $_GET['token'] ?? '');
$domain = abrNormDomain($_POST['domain'] ?? $_GET['domain'] ?? '');

if ($action !== 'activate' && $action !== 'check') {
    echo json_encode(['ok' => false, 'error' => 'Invalid action']);
    exit;
}
if ($key === '' || $token === '' || $domain === '') {
    echo json_encode(['ok' => false, 'error' => 'license_key, token and domain required']);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM licenses WHERE license_key = ? LIMIT 1');
$stmt->execute([$key]);
$row = $stmt->fetch();
if (!$row || !hash_equals($row['token'], $token)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid license or token']);
    exit;
}
if ($row['status'] === 'revoked') {
    echo json_encode(['ok' => false, 'error' => 'License revoked']);
    exit;
}
if ($row['domain'] !== $domain) {
    echo json_encode(['ok' => false, 'error' => 'This license is locked to ' . $row['domain']]);
    exit;
}

$pdo->prepare('UPDATE licenses SET status = ?, activated_at = COALESCE(activated_at, NOW()), last_check = NOW() WHERE id = ?')
    ->execute(['active', $row['id']]);

if ($action === 'check') {
    echo json_encode(['ok' => true, 'status' => 'active', 'domain' => $row['domain']]);
    exit;
}

try {
    $zipPath = abrBuildPayloadZip(__DIR__);
    $bin = file_get_contents($zipPath);
    @unlink($zipPath);
    echo json_encode([
        'ok' => true,
        'domain' => $row['domain'],
        'zip' => base64_encode($bin),
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
