<?php
function abrEnsureLicensesTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `licenses` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `customer_name` varchar(150) NOT NULL,
      `site_url` varchar(255) NOT NULL,
      `domain` varchar(180) NOT NULL,
      `license_key` varchar(64) NOT NULL,
      `token` varchar(64) NOT NULL,
      `status` enum('pending','active','revoked') NOT NULL DEFAULT 'pending',
      `activated_at` datetime DEFAULT NULL,
      `last_check` datetime DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `license_key` (`license_key`),
      KEY `domain` (`domain`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function abrNormDomain($url) {
    $url = trim($url);
    if ($url === '') return '';
    if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) $host = preg_replace('~^www\.~i', '', strtolower($url));
    $host = strtolower($host);
    return preg_replace('~^www\.~i', '', $host);
}
function abrGenLicenseKey() {
    return 'ABR-' . strtoupper(bin2hex(random_bytes(3))) . '-' . strtoupper(bin2hex(random_bytes(3)));
}
function abrLicenseServerBase() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'test.link666xx.com');
}
function abrPayloadFiles() {
    return ['config.php','login.php','logout.php','index.php','redirect.php','.htaccess','admin.php','admin_logic.php','admin_view.php','dashboard.php','dashboard_logic.php','dashboard_view.php','database.sql','assets/app.css','uploads/.gitkeep'];
}
function abrBuildStarterZip($license, $serverBase) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP zip extension missing');
    $tmp = sys_get_temp_dir() . '/abr_starter_' . $license['id'] . '_' . bin2hex(random_bytes(3)) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create zip');
    $zip->addFromString('install.php', abrInstallTemplate($serverBase));
    $zip->addFromString('index.php', "<?php\nheader('Location: install.php');\nexit;\n");
    $zip->addFromString('.htaccess', "DirectoryIndex install.php index.php\n<FilesMatch \"\\.(env|json)$\">\nRequire all denied\n</FilesMatch>\n");
    $zip->addFromString('README.txt', "Upload to public_html. Open /install.php. Step1 license. Step2 Download resources. Step3 Login.\nKey: {$license['license_key']}\nToken: {$license['token']}\nDomain: {$license['domain']}\n");
    $zip->close();
    return $tmp;
}
function abrBuildPayloadZip($root) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP zip extension missing');
    $tmp = sys_get_temp_dir() . '/abr_full_' . bin2hex(random_bytes(4)) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create payload zip');
    foreach (abrPayloadFiles() as $rel) { if (is_file($root . '/' . $rel)) $zip->addFile($root . '/' . $rel, $rel); }
    $zip->close();
    return $tmp;
}
function abrInstallTemplate($serverBase) {
    $serverBase = rtrim($serverBase, '/');
    $php = <<<'PHP'
<?php
$LICENSE_SERVER = 'SERVER_BASE_PLACEHOLDER/license_api.php';
$lock = __DIR__ . '/license.json';
$stateFile = __DIR__ . '/install_state.json';
if (is_file($lock) && is_file(__DIR__ . '/login.php') && !isset($_GET['reinstall'])) { header('Location: login.php'); exit; }
$err = '';
$state = is_file($stateFile) ? (json_decode(file_get_contents($stateFile), true) ?: []) : [];
$step = $state['step'] ?? 1;
function abrCallLicense($url, $action, $key, $token, $domain) {
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => http_build_query(['action'=>$action,'license_key'=>$key,'token'=>$token,'domain'=>$domain]), 'timeout' => 90], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    return json_decode(@file_get_contents($url, false, $ctx), true);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = $_POST['do'] ?? '';
    if ($do === 'license') {
        $key = trim($_POST['license_key'] ?? ''); $token = trim($_POST['token'] ?? ''); $domain = trim($_POST['site_url'] ?? '');
        $dbHost = trim($_POST['db_host'] ?? 'localhost'); $dbName = trim($_POST['db_name'] ?? ''); $dbUser = trim($_POST['db_user'] ?? ''); $dbPass = $_POST['db_pass'] ?? '';
        $adminName = trim($_POST['admin_name'] ?? 'Admin'); $adminEmail = trim($_POST['admin_email'] ?? ''); $adminPass = $_POST['admin_password'] ?? '';
        if ($key===''||$token===''||$domain===''||$dbName===''||$dbUser===''||$adminEmail===''||$adminPass==='') { $err='All required fields must be filled.'; $step=1; }
        else {
            $json = abrCallLicense($LICENSE_SERVER, 'check', $key, $token, $domain);
            if (!is_array($json) || empty($json['ok'])) { $err = $json['error'] ?? 'License rejected.'; $step=1; }
            else {
                $state = compact('key') + ['step'=>2,'license_key'=>$key,'token'=>$token,'domain'=>$domain,'db_host'=>$dbHost,'db_name'=>$dbName,'db_user'=>$dbUser,'db_pass'=>$dbPass,'admin_name'=>$adminName,'admin_email'=>$adminEmail,'admin_password'=>$adminPass];
                file_put_contents($stateFile, json_encode($state)); $step=2;
            }
        }
    } elseif ($do === 'download') {
        $step=2;
        if (empty($state['license_key'])) { $err='Complete license step first.'; $step=1; }
        else {
            $json = abrCallLicense($LICENSE_SERVER, 'activate', $state['license_key'], $state['token'], $state['domain']);
            if (!is_array($json) || empty($json['ok'])) { $err = $json['error'] ?? 'Resource download failed.'; }
            else {
                $zipData = base64_decode($json['zip'] ?? '');
                if ($zipData === false || strlen($zipData) < 100) { $err='Payload download failed.'; }
                elseif (!class_exists('ZipArchive')) { $err='Enable PHP zip extension.'; }
                else {
                    $tmp = sys_get_temp_dir() . '/abr_in_' . bin2hex(random_bytes(3)) . '.zip';
                    file_put_contents($tmp, $zipData);
                    $zip = new ZipArchive();
                    if ($zip->open($tmp) !== true) { $err='Cannot open resource zip.'; }
                    else {
                        $zip->extractTo(__DIR__); $zip->close(); @unlink($tmp);
                        file_put_contents(__DIR__.'/.env', "DB_HOST={$state['db_host']}\nDB_NAME={$state['db_name']}\nDB_USER={$state['db_user']}\nDB_PASS={$state['db_pass']}\nDB_CHARSET=utf8mb4\n\nADMIN_NAME={$state['admin_name']}\nADMIN_EMAIL={$state['admin_email']}\nADMIN_PASSWORD={$state['admin_password']}\n");
                        @chmod(__DIR__.'/.env', 0640);
                        file_put_contents($lock, json_encode(['license_key'=>$state['license_key'],'domain'=>$state['domain'],'activated_at'=>date('c'),'server'=>$LICENSE_SERVER], JSON_PRETTY_PRINT));
                        if (!is_dir(__DIR__.'/uploads')) mkdir(__DIR__.'/uploads', 0755, true);
                        $state['step']=3; file_put_contents($stateFile, json_encode($state)); $step=3;
                    }
                }
            }
        }
    }
}
$guess = $_SERVER['HTTP_HOST'] ?? '';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Install</title>
<style>body{margin:0;font-family:system-ui;background:#0f0a1f;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}.box{width:100%;max-width:440px;background:rgba(255,255,255,.05);border:1px solid rgba(167,139,250,.2);border-radius:18px;padding:24px}h1{margin:0 0 6px;font-size:22px;color:#e9d5ff}p{color:#94a3b8;font-size:13px}label{display:block;font-size:12px;margin:10px 0 4px;color:#c4b5fd}input{width:100%;box-sizing:border-box;padding:11px 12px;border-radius:10px;border:1px solid rgba(167,139,250,.25);background:rgba(15,10,31,.6);color:#fff}button,.btn{display:block;width:100%;margin-top:16px;padding:12px;border:0;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;font-weight:600;text-align:center;text-decoration:none;box-sizing:border-box}.err{background:rgba(248,113,113,.15);color:#f87171;padding:10px;border-radius:10px;margin-bottom:12px}.ok{background:rgba(34,197,94,.15);color:#4ade80;padding:10px;border-radius:10px;margin-bottom:12px}.steps{display:flex;gap:6px;margin-bottom:16px;font-size:11px}.steps span{flex:1;text-align:center;padding:6px;border-radius:8px;background:rgba(255,255,255,.04)}.steps span.on{background:rgba(124,58,237,.35);color:#e9d5ff}</style></head>
<body><div class="box"><div class="steps"><span class="<?= $step===1?'on':'' ?>">1. Install</span><span class="<?= $step===2?'on':'' ?>">2. Resources</span><span class="<?= $step===3?'on':'' ?>">3. Login</span></div>
<?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($step===1): ?>
<h1>License required</h1><p>Enter license, then download resources.</p>
<form method="post"><input type="hidden" name="do" value="license">
<label>License key</label><input name="license_key" required placeholder="ABR-XXXXXX-XXXXXX">
<label>Share token</label><input name="token" required>
<label>Site URL</label><input name="site_url" required value="<?= htmlspecialchars($guess) ?>">
<label>DB host</label><input name="db_host" value="localhost">
<label>DB name</label><input name="db_name" required>
<label>DB user</label><input name="db_user" required>
<label>DB password</label><input name="db_pass" type="password">
<label>Admin name</label><input name="admin_name" value="Admin">
<label>Admin email</label><input name="admin_email" type="email" required>
<label>Admin password</label><input name="admin_password" type="password" required>
<button type="submit">Continue</button></form>
<?php elseif ($step===2): ?>
<h1>Download resources</h1><p>License accepted. Download remaining files from the license server.</p>
<form method="post"><input type="hidden" name="do" value="download"><button type="submit">Download resources</button></form>
<?php else: ?>
<h1>Install complete</h1><div class="ok">Resources downloaded.</div>
<a class="btn" href="login.php">Open login form</a>
<?php endif; ?></div></body></html>
PHP;
    return str_replace('SERVER_BASE_PLACEHOLDER', $serverBase, $php);
}
