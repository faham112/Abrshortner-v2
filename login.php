<?php
require 'config.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? "admin.php" : "dashboard.php"));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = "Email aur Password dono zaroori hain";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Galat Email ya Password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - ShortLink</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(1200px 600px at 8% -12%, rgba(255,255,255,.9), transparent 55%),
                linear-gradient(180deg, #f2f2f7 0%, #e5e5ea 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .glass {
            background: rgba(255, 255, 255, 0.74);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: 24px;
            padding: 40px 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(15,15,20,.06);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #1c1c1e; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; }
        .logo p { color: #8e8e93; font-size: 14px; margin-top: 6px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; color: #6e6e73; font-size: 13px; margin-bottom: 6px; font-weight: 500; }
        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(60,60,67,.18);
            background: rgba(255,255,255,.92);
            color: #1c1c1e;
            font-size: 16px;
            outline: none;
        }
        input::placeholder { color: #aeaeb2; }
        input:focus { border-color: #1c1c1e; }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 14px;
            background: #1c1c1e;
            color: #f5f5f7;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
        }
        .error {
            background: rgba(255, 59, 48, 0.12);
            border: 1px solid rgba(215, 0, 21, 0.25);
            color: #d70015;
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 18px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="glass">
        <div class="logo">
            <h1>ShortLink</h1>
            <p>Sign in to continue</p>
        </div>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@example.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>
