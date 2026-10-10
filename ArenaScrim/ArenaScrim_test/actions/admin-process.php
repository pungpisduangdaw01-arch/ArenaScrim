<?php
session_start();
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ Admin
require_admin();

$action = $_GET['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$back = "../admin/index.php";

// ทุกคำสั่งของ Admin แก้ไขข้อมูล จึงต้องเป็น POST ที่มี CSRF token ถูกต้องเท่านั้น
verify_csrf($back);

// --- 1. ลบสมาชิก ---
// ทีมที่สมาชิกคนนี้เป็นหัวหน้า รวมถึงข้อมูลที่เกี่ยวข้อง จะถูกลบตาม (ON DELETE CASCADE)
if ($action === 'delete_user') {
    $back = "../admin/users.php";
    // ห้าม Admin ลบตัวเอง
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with($back, 'error', "ไม่สามารถลบบัญชีตัวเองได้");
    }
    $pdo->beginTransaction();
    // ถ้าเป็นโค้ช: ทีม ผู้เล่น และผลซ้อมยังอยู่ (ไม่มีโค้ช) แต่ปิดประกาศที่เปิดอยู่และคำขอซ้อมที่ค้างของทีมนั้น
    $team = get_led_team($pdo, $id);
    if ($team) {
        $tid = (int)$team['team_id'];
        $pdo->prepare("UPDATE scrim_requests r JOIN scrim_posts p ON p.post_id = r.post_id SET r.status = 'rejected', r.responded_at = NOW()
            WHERE p.team_id = ? AND r.status = 'pending'")->execute([$tid]);
        $pdo->prepare("UPDATE scrim_requests SET status = 'cancelled', responded_at = NOW() WHERE team_id = ? AND status = 'pending'")->execute([$tid]);
        $pdo->prepare("UPDATE scrim_posts SET status = 'expired' WHERE team_id = ? AND status = 'open'")->execute([$tid]);
    }
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
    $pdo->commit();
    redirect_with($back, 'success', $team ? "ลบสมาชิกแล้ว · ทีม {$team['team_name']} และผลการซ้อมยังอยู่ (ไม่มีโค้ช — กำหนดโค้ชใหม่ได้ที่หน้ารายละเอียดทีม)" : "ลบสมาชิกออกจากระบบเรียบร้อย");
}

// --- 1.1 กำหนดโค้ชใหม่ให้ทีมที่ไม่มีโค้ช (ข้อยกเว้นเดียวของหน้าดูทีมที่อ่านอย่างเดียว) ---
if ($action === 'assign_coach') {
    $team_id = (int)($_POST['team_id'] ?? 0);
    $back = "../admin/team-detail.php?id=$team_id";
    $coach = (int)($_POST['user_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT team_id FROM teams WHERE team_id = ? AND leader_id IS NULL");
    $stmt->execute([$team_id]);
    if (!$stmt->fetch()) {
        redirect_with($back, 'error', "ทีมนี้มีโค้ชอยู่แล้ว หรือไม่พบทีม");
    }
    $stmt = $pdo->prepare("SELECT u.username FROM users u LEFT JOIN teams t ON t.leader_id = u.user_id
        WHERE u.user_id = ? AND u.role = 'user' AND t.team_id IS NULL");
    $stmt->execute([$coach]);
    $username = $stmt->fetchColumn();
    if ($username === false) {
        redirect_with($back, 'error', "เลือกได้เฉพาะบัญชีผู้ใช้ทั่วไปที่ยังไม่มีทีม");
    }
    $pdo->prepare("UPDATE teams SET leader_id = ? WHERE team_id = ? AND leader_id IS NULL")->execute([$coach, $team_id]);
    redirect_with($back, 'success', "กำหนด @$username เป็นโค้ชของทีมแล้ว");
}

// --- 2. สลับสิทธิ์ user <-> admin ---
if ($action === 'toggle_role') {
    $back = "../admin/users.php";
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with($back, 'error', "ไม่สามารถเปลี่ยนสิทธิ์ของตัวเองได้");
    }
    $stmt = $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE user_id = ?");
    $stmt->execute([$id]);
    redirect_with($back, 'success', "เปลี่ยนสิทธิ์ผู้ใช้เรียบร้อย");
}

// --- 2.1 ตั้งรหัสผ่านใหม่ให้ผู้ใช้ที่ลืมรหัส (สุ่มรหัสชั่วคราว ให้ admin แจ้งผู้ใช้เอง) ---
if ($action === 'reset_password') {
    $back = "../admin/users.php";
    if ($id === (int)$_SESSION['user_id']) {
        redirect_with($back, 'error', "เปลี่ยนรหัสผ่านของตัวเองได้ที่หน้าข้อมูลส่วนตัว");
    }
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    $username = $stmt->fetchColumn();
    if ($username === false) {
        redirect_with($back, 'error', "ไม่พบผู้ใช้นี้");
    }
    // ตัดตัวอักษรที่อ่านสับสนออก (0/O, 1/l/I)
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $temp = '';
    for ($i = 0; $i < 10; $i++) {
        $temp .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([password_hash($temp, PASSWORD_DEFAULT), $id]);
    redirect_with($back, 'success', "ตั้งรหัสผ่านใหม่ให้ @$username แล้ว: $temp (แจ้งผู้ใช้ และให้เปลี่ยนรหัสหลังเข้าสู่ระบบ)");
}

// --- 3. ลบทีม ---
if ($action === 'delete_team') {
    $back = "../admin/teams.php";
    $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = ?");
    $stmt->execute([$id]);
    redirect_with($back, 'success', "ลบทีมเรียบร้อย");
}

// --- 4. ลบประกาศ Scrim ---
if ($action === 'delete_scrim') {
    $back = "../admin/scrims.php";
    $stmt = $pdo->prepare("DELETE FROM scrim_posts WHERE post_id = ?");
    $stmt->execute([$id]);
    redirect_with($back, 'success', "ลบโพสต์ Scrim เรียบร้อย");
}

// --- 5. จัดการฮีโร่ (เพิ่ม / แก้ไข / ลบ) ---
$hero_back = "../admin/heroes.php";

// ตรวจชื่อและตำแหน่งฮีโร่จากฟอร์ม
function read_hero_form($url)
{
    $name = trim(preg_replace('/\s+/u', ' ', $_POST['hero_name'] ?? ''));
    $role = $_POST['hero_role'] ?? '';
    if ($name === '' || mb_strlen($name) > 50) {
        redirect_with($url, 'error', "กรุณากรอกชื่อฮีโร่ (ไม่เกิน 50 ตัวอักษร)");
    }
    if (!in_array($role, HERO_ROLES, true)) {
        redirect_with($url, 'error', "กรุณาเลือกตำแหน่งของฮีโร่");
    }
    return [$name, $role];
}

if ($action === 'add_hero' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    [$name, $role] = read_hero_form($hero_back);
    $dup = $pdo->prepare("SELECT hero_id FROM heroes WHERE hero_name = ?");
    $dup->execute([$name]);
    if ($dup->fetch()) {
        redirect_with($hero_back, 'error', "มีฮีโร่ชื่อ \"$name\" อยู่ในระบบแล้ว");
    }
    $pdo->prepare("INSERT INTO heroes (hero_name, hero_role) VALUES (?, ?)")->execute([$name, $role]);
    redirect_with($hero_back, 'success', "เพิ่มฮีโร่ \"$name\" ($role) เรียบร้อย ผู้ใช้เลือกใช้ได้ทันที");
}

if ($action === 'edit_hero' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hero_id = (int)($_POST['hero_id'] ?? 0);
    [$name, $role] = read_hero_form("$hero_back?edit=$hero_id");

    $stmt = $pdo->prepare("SELECT hero_name FROM heroes WHERE hero_id = ?");
    $stmt->execute([$hero_id]);
    $old = $stmt->fetchColumn();
    if ($old === false) {
        redirect_with($hero_back, 'error', "ไม่พบฮีโร่ที่ต้องการแก้ไข");
    }
    $dup = $pdo->prepare("SELECT hero_id FROM heroes WHERE hero_name = ? AND hero_id <> ?");
    $dup->execute([$name, $hero_id]);
    if ($dup->fetch()) {
        redirect_with("$hero_back?edit=$hero_id", 'error', "มีฮีโร่ชื่อ \"$name\" อยู่ในระบบแล้ว");
    }

    // เปลี่ยนชื่อในประวัติการซ้อมเดิมให้ตรงกันด้วย (กรณีแก้ชื่อที่พิมพ์ผิด)
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE heroes SET hero_name = ?, hero_role = ? WHERE hero_id = ?")->execute([$name, $role, $hero_id]);
    if ($old !== $name) {
        $pdo->prepare("UPDATE match_player_stats SET hero_name = ? WHERE hero_name = ?")->execute([$name, $old]);
        for ($i = 1; $i <= 5; $i++) {
            $pdo->prepare("UPDATE scrim_matches SET enemy_hero_$i = ? WHERE enemy_hero_$i = ?")->execute([$name, $old]);
        }
    }
    $pdo->commit();
    redirect_with($hero_back, 'success', $old !== $name ? "แก้ไขฮีโร่ \"$old\" เป็น \"$name\" และอัปเดตประวัติการซ้อมเดิมแล้ว" : "แก้ไขฮีโร่ \"$name\" เรียบร้อย");
}

// ลบออกจากรายการให้เลือกเท่านั้น ประวัติการซ้อมเดิมยังเก็บชื่อฮีโร่ไว้ตามเดิม
if ($action === 'delete_hero') {
    $stmt = $pdo->prepare("SELECT hero_name FROM heroes WHERE hero_id = ?");
    $stmt->execute([$id]);
    $name = $stmt->fetchColumn();
    $pdo->prepare("DELETE FROM heroes WHERE hero_id = ?")->execute([$id]);
    redirect_with($hero_back, 'success', $name !== false ? "ลบฮีโร่ \"$name\" ออกจากรายการแล้ว" : "ไม่พบฮีโร่นี้");
}

header("Location: $back");
exit;
