<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();
verify_csrf("../user/match-history.php");
$match_id = (int)($_POST['id'] ?? 0);

// ลบได้เฉพาะแมตช์ของทีมที่ตัวเองเป็นหัวหน้า
// (สถิติผู้เล่นใน match_player_stats จะถูกลบตามอัตโนมัติด้วย ON DELETE CASCADE)
$stmt = $pdo->prepare("DELETE m FROM scrim_matches m JOIN teams t ON m.team_id = t.team_id WHERE m.match_id = ? AND t.leader_id = ?");
$stmt->execute([$match_id, $user_id]);

if ($stmt->rowCount() > 0) {
    $team = get_led_team($pdo, $user_id);
    cleanup_empty_series($pdo, (int)$team['team_id']);
    redirect_with("../user/match-history.php", 'success', "ลบประวัติการซ้อมเรียบร้อยแล้ว");
}
redirect_with("../user/match-history.php", 'error', "ไม่พบแมตช์นี้ หรือคุณไม่มีสิทธิ์ลบ");
