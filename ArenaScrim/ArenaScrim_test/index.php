<?php
session_start();

// หน้าแรก: ส่งผู้ใช้ไปยังหน้าที่เหมาะสมตามสถานะการล็อกอิน
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
} elseif (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin/index.php");
} else {
    header("Location: user/profile.php");
}
exit;
