<?php
session_start();

// 1. ล้างค่าตัวแปร Session ทั้งหมด
$_SESSION = array();

// 2. ลบ Cookie ของ Session ถ้าระบบมีการสร้างไว้
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. ทำลาย Session ในระบบ
session_destroy();

// 4. ส่งผู้ใช้งานกลับไปยังหน้าล็อกอินหลัก
header("Location: login.php");
exit();