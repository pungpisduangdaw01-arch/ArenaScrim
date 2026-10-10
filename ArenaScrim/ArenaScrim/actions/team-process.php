<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();
$action = $_GET['action'] ?? '';
$back = "../user/team-manage.php";

// ทุกคำสั่งในไฟล์นี้แก้ไขข้อมูล จึงต้องเป็น POST ที่มี CSRF token ถูกต้องเท่านั้น
verify_csrf($back);

// ตรวจข้อมูลทีมที่ส่งมาจากฟอร์มสร้าง/แก้ไขทีม
function read_team_form()
{
    $data = [
        'team_name'     => trim($_POST['team_name'] ?? ''),
        'team_tag'      => strtoupper(trim($_POST['team_tag'] ?? '')),
        'discord_link'  => clean_url($_POST['discord_link'] ?? ''),
        'facebook_link' => clean_url($_POST['facebook_link'] ?? ''),
        'line_id'       => trim($_POST['line_id'] ?? '') ?: null,
    ];
    if ($data['team_name'] === '' || mb_strlen($data['team_name']) > 100) {
        return [null, "กรุณากรอกชื่อทีม (ไม่เกิน 100 ตัวอักษร)"];
    }
    if ($data['team_tag'] === '' || mb_strlen($data['team_tag']) > 10) {
        return [null, "กรุณากรอกตัวย่อทีม (ไม่เกิน 10 ตัวอักษร)"];
    }
    if (mb_strlen($data['line_id'] ?? '') > 100) {
        return [null, "Line ID ยาวเกิน 100 ตัวอักษร"];
    }
    if (mb_strlen($data['discord_link'] ?? '') > 255 || mb_strlen($data['facebook_link'] ?? '') > 255) {
        return [null, "ลิงก์ยาวเกิน 255 ตัวอักษร"];
    }
    if (trim($_POST['discord_link'] ?? '') !== '' && !$data['discord_link']) {
        return [null, "ลิงก์ Discord ต้องขึ้นต้นด้วย http:// หรือ https://"];
    }
    if (trim($_POST['facebook_link'] ?? '') !== '' && !$data['facebook_link']) {
        return [null, "ลิงก์ Facebook ต้องขึ้นต้นด้วย http:// หรือ https://"];
    }
    return [$data, null];
}

// ตรวจว่าชื่อทีมซ้ำกับทีมอื่นหรือไม่
function team_name_taken(PDO $pdo, $name, $exclude_team_id = 0)
{
    $stmt = $pdo->prepare("SELECT team_id FROM teams WHERE team_name = ? AND team_id <> ?");
    $stmt->execute([$name, $exclude_team_id]);
    return (bool)$stmt->fetch();
}

// --------------------------------------------------
// 1. สร้างทีม (Create Team)
// --------------------------------------------------
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (get_led_team($pdo, $user_id)) {
        redirect_with($back, 'error', "คุณมีทีมอยู่แล้ว (1 บัญชีสร้างได้ 1 ทีม)");
    }
    [$data, $err] = read_team_form();
    if ($err) {
        redirect_with($back, 'error', $err);
    }
    if (team_name_taken($pdo, $data['team_name'])) {
        redirect_with($back, 'error', "ชื่อทีมนี้ถูกใช้งานแล้ว กรุณาใช้ชื่ออื่น");
    }

    $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_tag, leader_id, discord_link, facebook_link, line_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['team_name'], $data['team_tag'], $user_id, $data['discord_link'], $data['facebook_link'], $data['line_id']]);
    redirect_with($back, 'success', "สร้างทีมเรียบร้อยแล้ว! เพิ่มสมาชิกในทีมได้เลย");
}

// --------------------------------------------------
// 2. เพิ่มสมาชิก (Add Member)
// --------------------------------------------------
if ($action === 'add_member' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $team = get_led_team($pdo, $user_id);
    if (!$team) {
        redirect_with($back, 'error', "เฉพาะโค้ชเท่านั้นที่เพิ่มสมาชิกได้");
    }
    $ign = trim($_POST['ign'] ?? '');
    $role = trim($_POST['role'] ?? '');
    if ($ign === '' || mb_strlen($ign) > 100) {
        redirect_with("../user/member-add.php", 'error', "กรุณากรอกชื่อในเกม (ไม่เกิน 100 ตัวอักษร)");
    }
    if (!array_key_exists($role, MEMBER_ROLES)) {
        redirect_with("../user/member-add.php", 'error', "กรุณาเลือกตำแหน่งการเล่น");
    }

    $dup = $pdo->prepare("SELECT member_id FROM team_members WHERE team_id = ? AND in_game_name = ?");
    $dup->execute([$team['team_id'], $ign]);
    if ($dup->fetch()) {
        redirect_with("../user/member-add.php", 'error', "มีสมาชิกชื่อ \"$ign\" อยู่ในทีมแล้ว");
    }

    // สมาชิกเป็นรายชื่อในทีม (roster) ผูกกับบัญชีหัวหน้าทีมเพื่อให้ผ่าน Foreign Key
    $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, in_game_name, main_role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$team['team_id'], $user_id, $ign, $role]);
    redirect_with($back, 'success', "เพิ่มสมาชิก $ign เข้าทีมเรียบร้อย");
}

// --------------------------------------------------
// 3. ลบสมาชิก (Delete Member) — ลบได้เฉพาะสมาชิกในทีมของตัวเอง
// --------------------------------------------------
if ($action === 'delete_member') {
    $member_id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("DELETE tm FROM team_members tm JOIN teams t ON tm.team_id = t.team_id WHERE tm.member_id = ? AND t.leader_id = ?");
    $stmt->execute([$member_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        redirect_with($back, 'success', "ลบสมาชิกออกจากทีมเรียบร้อย");
    }
    redirect_with($back, 'error', "ไม่พบสมาชิก หรือคุณไม่มีสิทธิ์ลบสมาชิกคนนี้");
}

// --------------------------------------------------
// 4. ยุบทีม / ลบทีม (Delete Team)
// ข้อมูลสมาชิก ผลการซ้อม และประกาศ Scrim ของทีมจะถูกลบตาม (ON DELETE CASCADE)
// --------------------------------------------------
if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM teams WHERE leader_id = ?");
    $stmt->execute([$user_id]);
    redirect_with($back, 'success', "ยุบทีมเรียบร้อยแล้ว");
}

// --------------------------------------------------
// 5. แก้ไขข้อมูลทีม (Edit Team)
// --------------------------------------------------
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $team = get_led_team($pdo, $user_id);
    if (!$team) {
        redirect_with($back, 'error', "คุณไม่มีสิทธิ์แก้ไขทีมนี้");
    }
    [$data, $err] = read_team_form();
    if ($err) {
        redirect_with("../user/team-edit.php", 'error', $err);
    }
    if (team_name_taken($pdo, $data['team_name'], $team['team_id'])) {
        redirect_with("../user/team-edit.php", 'error', "ชื่อทีมนี้ถูกใช้งานแล้ว กรุณาใช้ชื่ออื่น");
    }

    $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, team_tag = ?, discord_link = ?, facebook_link = ?, line_id = ? WHERE team_id = ? AND leader_id = ?");
    $stmt->execute([$data['team_name'], $data['team_tag'], $data['discord_link'], $data['facebook_link'], $data['line_id'], $team['team_id'], $user_id]);
    redirect_with($back, 'success', "บันทึกข้อมูลทีมเรียบร้อย");
}

header("Location: $back");
exit;
