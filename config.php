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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password, $pdoOpts);
} catch (PDOException $e) {
    $retryHost = ($host === 'localhost') ? '127.0.0.1' : 'localhost';
    try {
        $pdo = new PDO("mysql:host=$retryHost;dbname=$dbname;charset=$charset", $username, $password, $pdoOpts);
    } catch (PDOException $e2) {
        die("Database connection failed: " . $e2->getMessage() . " — .env mein DB_HOST=127.0.0.1 rakho");
    }
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `email` varchar(150) NOT NULL,
      `password` varchar(255) NOT NULL,
      `role` enum('admin','user') NOT NULL DEFAULT 'user',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `urls` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `short_code` varchar(20) NOT NULL,
      `long_url` text NOT NULL,
      `title` varchar(255) DEFAULT NULL,
      `image_url` text DEFAULT NULL,
      `description` text DEFAULT NULL,
      `preview_enabled` tinyint(1) NOT NULL DEFAULT 1,
      `clicks` int(11) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `short_code` (`short_code`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM urls LIKE 'preview_enabled'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE urls ADD COLUMN preview_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER description");
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

try {
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
} catch (Exception $e) {}

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
