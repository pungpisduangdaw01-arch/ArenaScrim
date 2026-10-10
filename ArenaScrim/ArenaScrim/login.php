<?php
require_once __DIR__ . '/config/functions.php';
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$page_css = "auth";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ArenaScrim</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h2 class="auth-title">ARENA SCRIM</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-danger">
                <?= htmlspecialchars($_SESSION["error"]); unset($_SESSION["error"]); ?>
            </div>
        <?php endif; ?>

        <form action="actions/auth-process.php?action=login" method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username หรือ Email</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="กรอก Username หรือ Email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-cyber" style="width: 100%; margin-top: 10px;">เข้าสู่ระบบ</button>
        </form>

        <div class="auth-footer">
            ยังไม่มีบัญชีสมาชิก? <a href="register.php">สมัครสมาชิกใหม่</a>
        </div>
    </div>
</div>

</body>
</html>