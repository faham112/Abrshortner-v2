<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        die(".env file not found. Please create .env file.");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/.env');

$host     = $_ENV['DB_HOST']    ?? 'localhost';
$dbname   = $_ENV['DB_NAME']    ?? '';
$username = $_ENV['DB_USER']    ?? '';
$password = $_ENV['DB_PASS']    ?? '';
$charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

if (empty($dbname) || empty($username)) {
    die("Please configure database details in .env file");
}

$pdoOpts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $pdoOpts);
} catch (PDOException $e) {
    $retryHost = ($host === '127.0.0.1') ? 'localhost' : '127.0.0.1';
    try {
        $dsn = "mysql:host=$retryHost;dbname=$dbname;charset=$charset";
        $pdo = new PDO($dsn, $username, $password, $pdoOpts);
    } catch (PDOException $e2) {
        die("Database connection failed: " . $e2->getMessage() . " — .env mein DB_HOST=127.0.0.1 rakho");
    }
}

try {
    foreach ([
        "preview_enabled" => "ALTER TABLE urls ADD COLUMN preview_enabled TINYINT(1) NOT NULL DEFAULT 1",
        "title" => "ALTER TABLE urls ADD COLUMN title VARCHAR(255) DEFAULT NULL",
        "image_url" => "ALTER TABLE urls ADD COLUMN image_url TEXT DEFAULT NULL",
        "description" => "ALTER TABLE urls ADD COLUMN description TEXT DEFAULT NULL",
        "clicks" => "ALTER TABLE urls ADD COLUMN clicks INT(11) NOT NULL DEFAULT 0",
    ] as $col => $sql) {
        $exists = $pdo->query("SHOW COLUMNS FROM urls LIKE " . $pdo->quote($col))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) {}

try {
    $pdo->query("SELECT 1 FROM clicks LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `clicks` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `url_id` int(11) NOT NULL,
          `ip` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `referer` text DEFAULT NULL,
          `country` varchar(100) DEFAULT NULL,
          `city` varchar(100) DEFAULT NULL,
          `device` varchar(50) DEFAULT NULL,
          `browser` varchar(50) DEFAULT NULL,
          `os` varchar(50) DEFAULT NULL,
          `is_bot` tinyint(1) NOT NULL DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `url_id` (`url_id`),
          KEY `created_at` (`created_at`),
          KEY `is_bot` (`is_bot`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e2) {}
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $adminEmail = trim($_ENV['ADMIN_EMAIL'] ?? '');
    $adminPass  = $_ENV['ADMIN_PASSWORD'] ?? '';
    $adminName  = trim($_ENV['ADMIN_NAME'] ?? 'Admin');
    if ($adminEmail !== '' && $adminPass !== '') {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$adminEmail]);
        $row = $stmt->fetch();
        if ($row) {
            if (!password_verify($adminPass, $row['password'])) {
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET name = ?, password = ?, role = 'admin' WHERE id = ?")
                    ->execute([$adminName, $hash, $row['id']]);
            } else {
                $pdo->prepare("UPDATE users SET name = ?, role = 'admin' WHERE id = ?")
                    ->execute([$adminName, $row['id']]);
            }
        } else {
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
                ->execute([$adminName, $adminEmail, $hash]);
        }
    }
} catch (Exception $e) {}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit;
    }
}

function generateShortCode($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function abrUrlColumns(PDO $pdo) {
    static $cols = null;
    if ($cols !== null) return $cols;
    $cols = [];
    try {
        foreach ($pdo->query("SHOW COLUMNS FROM urls") as $c) {
            $cols[strtolower($c['Field'])] = $c['Field'];
        }
    } catch (Exception $e) {
        $cols = [];
    }
    return $cols;
}

function abrCreateShortUrl(PDO $pdo, $user_id, $long_url, $title = null, $image_url = null, $description = null, $preview_enabled = 1) {
    $cols = abrUrlColumns($pdo);
    if (!$cols) {
        throw new RuntimeException('urls table not found');
    }
    $shortCol = $cols['short_code'] ?? $cols['code'] ?? $cols['slug'] ?? null;
    $longCol  = $cols['long_url'] ?? $cols['url'] ?? $cols['original_url'] ?? $cols['destination'] ?? null;
    if (!$shortCol || !$longCol) {
        throw new RuntimeException('urls columns mismatch: ' . implode(',', array_keys($cols)));
    }
    $last = '';
    for ($i = 0; $i < 8; $i++) {
        $code = generateShortCode();
        $fields = [];
        $vals = [];
        if (isset($cols['user_id'])) { $fields[] = $cols['user_id']; $vals[] = $user_id; }
        $fields[] = $shortCol; $vals[] = $code;
        $fields[] = $longCol; $vals[] = $long_url;
        if (isset($cols['title'])) { $fields[] = $cols['title']; $vals[] = $title ?: null; }
        if (isset($cols['image_url'])) { $fields[] = $cols['image_url']; $vals[] = $image_url ?: null; }
        if (isset($cols['description'])) { $fields[] = $cols['description']; $vals[] = $description ?: null; }
        if (isset($cols['preview_enabled'])) { $fields[] = $cols['preview_enabled']; $vals[] = (int)$preview_enabled; }
        $ph = implode(',', array_fill(0, count($fields), '?'));
        $sql = 'INSERT INTO urls (' . implode(',', $fields) . ') VALUES (' . $ph . ')';
        try {
            $pdo->prepare($sql)->execute($vals);
            return $code;
        } catch (PDOException $e) {
            $last = $e->getMessage();
            if (strpos($last, '1062') === false && stripos($last, 'Duplicate') === false) {
                throw $e;
            }
        }
    }
    throw new RuntimeException($last ?: 'could not insert url');
}
