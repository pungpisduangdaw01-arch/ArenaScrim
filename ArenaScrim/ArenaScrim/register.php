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
    <title>สมัครสมาชิก - ArenaScrim</title>
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

        <form action="actions/auth-process.php?action=register" method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username (ชื่อผู้ใช้เข้าสู่ระบบ)</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="ตัวอย่าง: proplayer123">
            </div>

            <div class="form-group">
                <label for="display_name">Display Name (ชื่อแสดงบนเว็บ)</label>
                <input type="text" name="display_name" id="display_name" class="form-control" required placeholder="ตัวอย่าง: GodOfValhein">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="name@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password (รหัสผ่าน)</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password (ยืนยันรหัสผ่าน)</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-cyber" style="width: 100%; margin-top: 10px;">สมัครสมาชิก</button>
        </form>

        <div class="auth-footer">
            มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a>
        </div>
    </div>
</div>

</body>
</html>