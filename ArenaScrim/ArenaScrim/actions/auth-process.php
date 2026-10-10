<?php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

// --- สมัครสมาชิก ---
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf("../register.php");
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ตรวจสอบความถูกต้องของข้อมูล
    if ($username === '' || $display_name === '' || $email === '' || $password === '') {
        redirect_with("../register.php", 'error', "กรุณากรอกข้อมูลให้ครบทุกช่อง");
    }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        redirect_with("../register.php", 'error', "Username ต้องเป็นภาษาอังกฤษ/ตัวเลข/_ . - ความยาว 3-50 ตัวอักษร");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with("../register.php", 'error', "รูปแบบอีเมลไม่ถูกต้อง");
    }
    if (mb_strlen($display_name) > 100) {
        redirect_with("../register.php", 'error', "ชื่อแสดงผลยาวเกิน 100 ตัวอักษร");
    }
    if (strlen($password) < 6) {
        redirect_with("../register.php", 'error', "รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร");
    }

    // ตรวจสอบรหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        redirect_with("../register.php", 'error', "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
    }

    // ตรวจสอบว่า Username หรือ Email ซ้ำหรือไม่
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        redirect_with("../register.php", 'error', "Username หรือ Email นี้ถูกใช้งานแล้ว");
    }

    // เข้ารหัสรหัสผ่านด้วย password_hash
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // บันทึกลงฐานข้อมูล
    $stmt = $pdo->prepare("INSERT INTO users (username, display_name, email, password) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$username, $display_name, $email, $hashed_password])) {
        // ดึงข้อมูลเข้า Session ทันทีหลังสมัครเสร็จ
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        $_SESSION['username'] = $username;
        $_SESSION['display_name'] = $display_name;
        $_SESSION['role'] = 'user';

        header("Location: ../user/profile.php");
        exit;
    }
    redirect_with("../register.php", 'error', "เกิดข้อผิดพลาดในการระบบ กรุณาลองใหม่อีกครั้ง");
}

// --- เข้าสู่ระบบ ---
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf("../login.php");
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ค้นหาจาก Username หรือ Email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['role'] = $user['role'];

        header($user['role'] === 'admin' ? "Location: ../admin/index.php" : "Location: ../user/profile.php");
        exit;
    }
    redirect_with("../login.php", 'error', "Username/Email หรือ รหัสผ่านไม่ถูกต้อง");
}

// --- เปลี่ยนรหัสผ่าน (ต้องกรอกรหัสผ่านเดิม) ---
if ($action === 'change_password') {
    $back = "../user/profile-edit.php";
    $user_id = require_login();
    verify_csrf($back);
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if (!password_verify($current, (string)$stmt->fetchColumn())) {
        redirect_with($back, 'error', "รหัสผ่านเดิมไม่ถูกต้อง");
    }
    if (strlen($new) < 6) {
        redirect_with($back, 'error', "รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร");
    }
    if ($new !== $confirm) {
        redirect_with($back, 'error', "รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน");
    }

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
    session_regenerate_id(true);
    redirect_with($back, 'success', "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว");
}

// --- ออกจากระบบ ---
if ($action === 'logout') {
    header("Location: ../logout.php");
    exit;
}

header("Location: ../login.php");
exit;
