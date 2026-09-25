<?php
require 'config.php';

$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login disabled</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            background:linear-gradient(135deg,#4c1d95,#7c3aed); color:#fff; }
        .box { text-align:center; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
            border-radius:20px; padding:36px 28px; max-width:380px; }
        h1 { margin:0 0 8px; font-size:22px; }
        p { margin:0; opacity:.8; font-size:14px; }
    </style>
</head>
<body>
<div class="box">
    <h1>Login disabled</h1>
    <p>This site is closed. New logins are not allowed.</p>
</div>
</body>
</html>
