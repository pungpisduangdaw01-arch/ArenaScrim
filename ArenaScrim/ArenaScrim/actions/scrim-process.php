<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();
$action = $_GET['action'] ?? '';
$back = "../scrim/index.php";

// ทุกคำสั่งในไฟล์นี้แก้ไขข้อมูล จึงต้องเป็น POST ที่มี CSRF token ถูกต้องเท่านั้น
verify_csrf($back);

// --- 1. สร้างประกาศหาทีมซ้อม ---
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่าเป็นหัวหน้าทีมจริงหรือไม่ (ใช้ทีมของตัวเองเสมอ ไม่เชื่อ team_id จากฟอร์ม)
    $team = get_led_team($pdo, $user_id);
    if (!$team) {
        redirect_with($back, 'error', "เฉพาะโค้ชเท่านั้นที่สามารถโพสต์หาทีมซ้อมได้");
    }

    $avg_rank = trim($_POST['avg_rank'] ?? '');
    $match_format = $_POST['match_format'] ?? 'BO3';
    $scrim_time = $_POST['scrim_time'] ?? '';
    $note = trim($_POST['note'] ?? '');

    $time = DateTime::createFromFormat('Y-m-d\TH:i', $scrim_time);
    if (!in_array($avg_rank, RANKS, true)) {
        redirect_with("../scrim/create.php", 'error', "กรุณาเลือก Rank เฉลี่ยของทีม");
    }
    if (!in_array($match_format, ['BO1', 'BO3', 'BO5'], true)) {
        redirect_with("../scrim/create.php", 'error', "รูปแบบการซ้อมไม่ถูกต้อง");
    }
    if (!$time || $time < new DateTime()) {
        redirect_with("../scrim/create.php", 'error', "กรุณาเลือกวัน-เวลาซ้อมที่ยังไม่ผ่านมา");
    }
    if (mb_strlen($note) > 1000) {
        redirect_with("../scrim/create.php", 'error', "หมายเหตุยาวเกิน 1,000 ตัวอักษร");
    }

    $stmt = $pdo->prepare("INSERT INTO scrim_posts (team_id, avg_rank, match_format, scrim_time, note, status) VALUES (?, ?, ?, ?, ?, 'open')");
    $stmt->execute([$team['team_id'], $avg_rank, $match_format, $time->format('Y-m-d H:i:s'), $note ?: null]);

    redirect_with($back, 'success', "ลงประกาศหาทีมซ้อมเรียบร้อย!");
}

// --- 2. ปิดประกาศ (ได้ทีมซ้อมแล้ว) — เฉพาะหัวหน้าทีมเจ้าของโพสต์ ---
if ($action === 'close') {
    $post_id = (int)($_POST['post_id'] ?? 0);

    $stmt = $pdo->prepare("UPDATE scrim_posts sp JOIN teams t ON sp.team_id = t.team_id
                           SET sp.status = 'matched'
                           WHERE sp.post_id = ? AND t.leader_id = ? AND sp.status = 'open'");
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        redirect_with($back, 'success', "อัปเดตสถานะเป็นได้ทีมซ้อมแล้ว");
    }
    redirect_with($back, 'error', "ไม่พบประกาศนี้ หรือคุณไม่มีสิทธิ์แก้ไข");
}

header("Location: $back");
exit;
