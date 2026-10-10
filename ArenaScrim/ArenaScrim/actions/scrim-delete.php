<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();
verify_csrf("../scrim/index.php");
$post_id = (int)($_POST['id'] ?? 0);

// ลบได้เฉพาะโพสต์ของทีมที่ตัวเองเป็นหัวหน้า
$stmt = $pdo->prepare("DELETE sp FROM scrim_posts sp JOIN teams t ON sp.team_id = t.team_id WHERE sp.post_id = ? AND t.leader_id = ?");
$stmt->execute([$post_id, $user_id]);

if ($stmt->rowCount() > 0) {
    redirect_with("../scrim/index.php", 'success', "ลบโพสต์หาทีมซ้อมเรียบร้อย");
}
redirect_with("../scrim/index.php", 'error', "ไม่พบโพสต์นี้ หรือคุณไม่มีสิทธิ์ลบ");
