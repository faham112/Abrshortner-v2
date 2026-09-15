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
    $host = preg_replace('~^www\.~i', '', $host);
    return $host;
}

function abrGenLicenseKey() {
    $a = strtoupper(bin2hex(random_bytes(3)));
    $b = strtoupper(bin2hex(random_bytes(3)));
    return 'ABR-' . $a . '-' . $b;
}

function abrLicenseServerBase() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'test.link666xx.com';
    return $scheme . '://' . $host;
}

function abrPayloadFiles() {
    return [
        'config.php',
        'login.php',
        'logout.php',
        'index.php',
        'redirect.php',
        '.htaccess',
        'admin.php',
        'admin_logic.php',
        'admin_view.php',
        'dashboard.php',
        'dashboard_logic.php',
        'dashboard_view.php',
        'database.sql',
        'assets/app.css',
        'uploads/.gitkeep',
    ];
}

function abrBuildStarterZip($license, $serverBase) {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP zip extension missing. Run: sudo apt install php8.5-zip');
    }
    $tmp = sys_get_temp_dir() . '/abr_starter_' . $license['id'] . '_' . bin2hex(random_bytes(3)) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create zip');
    }
    $install = abrInstallTemplate($serverBase);
    $zip->addFromString('install.php', $install);
    $zip->addFromString('index.php', "<?php\nheader('Location: install.php');\nexit;\n");
    $zip->addFromString('.htaccess', "DirectoryIndex install.php index.php\n<FilesMatch \"\\.(env|json)$\">\nRequire all denied\n</FilesMatch>\n");
    $readme = "ABR Shortener \xe2\x80\x94 Starter Pack (50%)\n\nCustomer: {$license['customer_name']}\nLicensed domain: {$license['domain']}\nLicense key: {$license['license_key']}\nToken: {$license['token']}\n\n1. Upload ALL files from this zip into public_html\n2. Open https://{$license['domain']}/install.php\n3. Paste License Key + Token\n4. Enter MySQL details\n5. Remaining files download from license server\n6. Login and use on ONE domain only\n";
    $zip->addFromString('README.txt', $readme);
    $zip->close();
    return $tmp;
}

function abrBuildPayloadZip($root) {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP zip extension missing');
    }
    $tmp = sys_get_temp_dir() . '/abr_full_' . bin2hex(random_bytes(4)) . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create payload zip');
    }
    foreach (abrPayloadFiles() as $rel) {
        $path = $root . '/' . $rel;
        if (is_file($path)) {
            $zip->addFile($path, $rel);
        }
    }
    $zip->close();
    return $tmp;
}

function abrInstallTemplate($serverBase) {
    $serverBase = rtrim($serverBase, '/');
    return <<<PHP
<?php
\$LICENSE_SERVER = '{$serverBase}/license_api.php';
\$lock = __DIR__ . '/license.json';
if (is_file(\$lock) && !isset(\$_GET['reinstall'])) {
    header('Location: login.php');
    exit;
}
\$err = '';
if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    \$key = trim(\$_POST['license_key'] ?? '');
    \$token = trim(\$_POST['token'] ?? '');
    \$domain = trim(\$_POST['site_url'] ?? '');
    \$dbHost = trim(\$_POST['db_host'] ?? 'localhost');
    \$dbName = trim(\$_POST['db_name'] ?? '');
    \$dbUser = trim(\$_POST['db_user'] ?? '');
    \$dbPass = \$_POST['db_pass'] ?? '';
    \$adminName = trim(\$_POST['admin_name'] ?? 'Admin');
    \$adminEmail = trim(\$_POST['admin_email'] ?? '');
    \$adminPass = \$_POST['admin_password'] ?? '';
    if (\$key === '' || \$token === '' || \$domain === '' || \$dbName === '' || \$dbUser === '' || \$adminEmail === '' || \$adminPass === '') {
        \$err = 'All required fields must be filled.';
    } else {
        \$payload = http_build_query([
            'action' => 'activate',
            'license_key' => \$key,
            'token' => \$token,
            'domain' => \$domain,
        ]);
        \$ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\\r\\n",
            'content' => \$payload,
            'timeout' => 60,
        ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        \$raw = @file_get_contents(\$LICENSE_SERVER, false, \$ctx);
        \$json = json_decode(\$raw, true);
        if (!is_array(\$json) || empty(\$json['ok'])) {
            \$err = \$json['error'] ?? 'License server rejected this key / domain.';
        } else {
            \$zipData = base64_decode(\$json['zip'] ?? '');
            if (\$zipData === false || strlen(\$zipData) < 100) {
                \$err = 'Payload download failed.';
            } elseif (!class_exists('ZipArchive')) {
                \$err = 'Enable PHP zip extension on this hosting.';
            } else {
                \$tmp = sys_get_temp_dir() . '/abr_in_' . bin2hex(random_bytes(3)) . '.zip';
                file_put_contents(\$tmp, \$zipData);
                \$zip = new ZipArchive();
                if (\$zip->open(\$tmp) !== true) {
                    \$err = 'Cannot open payload zip.';
                } else {
                    \$zip->extractTo(__DIR__);
                    \$zip->close();
                    @unlink(\$tmp);
                    \$env = "DB_HOST={\$dbHost}\\nDB_NAME={\$dbName}\\nDB_USER={\$dbUser}\\nDB_PASS={\$dbPass}\\nDB_CHARSET=utf8mb4\\n\\nADMIN_NAME={\$adminName}\\nADMIN_EMAIL={\$adminEmail}\\nADMIN_PASSWORD={\$adminPass}\\n";
                    file_put_contents(__DIR__ . '/.env', \$env);
                    @chmod(__DIR__ . '/.env', 0640);
                    file_put_contents(\$lock, json_encode([
                        'license_key' => \$key,
                        'domain' => \$domain,
                        'activated_at' => date('c'),
                        'server' => \$LICENSE_SERVER,
                    ], JSON_PRETTY_PRINT));
                    if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);
                    header('Location: login.php?installed=1');
                    exit;
                }
            }
        }
    }
}
\$guess = \$_SERVER['HTTP_HOST'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>License Required \xe2\x80\x94 ABR Shortener</title>
<style>
body{margin:0;font-family:system-ui,sans-serif;background:#0f0a1f;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{width:100%;max-width:440px;background:rgba(255,255,255,.05);border:1px solid rgba(167,139,250,.2);border-radius:18px;padding:24px}
h1{margin:0 0 6px;font-size:22px;color:#e9d5ff}
p{color:#94a3b8;font-size:13px;margin:0 0 16px}
label{display:block;font-size:12px;margin:10px 0 4px;color:#c4b5fd}
input{width:100%;box-sizing:border-box;padding:11px 12px;border-radius:10px;border:1px solid rgba(167,139,250,.25);background:rgba(15,10,31,.6);color:#fff}
button{width:100%;margin-top:16px;padding:12px;border:0;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;font-weight:600;cursor:pointer}
.err{background:rgba(248,113,113,.15);color:#f87171;padding:10px;border-radius:10px;margin-bottom:12px;font-size:13px}
</style>
</head>
<body>
<div class="box">
<h1>License required</h1>
<p>If you are interested to use this script, enter the license key shared by ABR Admin. Files for this one domain will download after verification.</p>
<?php if (\$err): ?><div class="err"><?= htmlspecialchars(\$err) ?></div><?php endif; ?>
<form method="post">
<label>License key</label>
<input name="license_key" required placeholder="ABR-XXXXXX-XXXXXX">
<label>Share token</label>
<input name="token" required placeholder="Token from admin">
<label>Site URL / domain</label>
<input name="site_url" required value="<?= htmlspecialchars(\$guess) ?>" placeholder="client.com">
<label>DB host</label>
<input name="db_host" value="localhost">
<label>DB name</label>
<input name="db_name" required>
<label>DB user</label>
<input name="db_user" required>
<label>DB password</label>
<input name="db_pass" type="password">
<label>Admin name</label>
<input name="admin_name" value="Admin">
<label>Admin email</label>
<input name="admin_email" type="email" required>
<label>Admin password</label>
<input name="admin_password" type="password" required>
<button type="submit">Activate license &amp; install</button>
</form>
</div>
</body>
</html>
PHP;
}
