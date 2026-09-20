<?php
require 'config.php';
if (isLoggedIn()) { header("Location: " . (isAdmin() ? "admin.php" : "dashboard.php")); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (empty($email) || empty($pass)) { $error = "Email aur Password dono zaroori hain"; }
    else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header("Location: " . ($user['role'] === 'admin' ? "admin.php" : "dashboard.php"));
            exit;
        } else { $error = "Galat Email ya Password"; }
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login - ShortLink</title>
<style>
body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;min-height:100vh;margin:0;background:linear-gradient(180deg,#f2f2f7,#e5e5ea);display:flex;align-items:center;justify-content:center}
.glass{background:rgba(255,255,255,.74);backdrop-filter:blur(22px);border:1px solid rgba(255,255,255,.85);border-radius:24px;padding:40px 30px;width:100%;max-width:400px}
h1{color:#1c1c1e;margin:0}p{color:#8e8e93}label{color:#6e6e73;font-size:13px;display:block;margin:12px 0 6px}
input{width:100%;padding:14px;border-radius:14px;border:1px solid rgba(60,60,67,.18);box-sizing:border-box}
.btn{width:100%;padding:15px;border:0;border-radius:14px;background:#1c1c1e;color:#f5f5f7;font-weight:700;margin-top:12px}
.error{background:rgba(255,59,48,.12);color:#d70015;padding:12px;border-radius:12px}
</style></head><body>
<div class="glass"><h1>ShortLink</h1><p>Sign in to continue</p>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST"><label>Email</label><input type="email" name="email" required>
<label>Password</label><input type="password" name="password" required>
<button class="btn" type="submit">Login</button></form></div></body></html>
