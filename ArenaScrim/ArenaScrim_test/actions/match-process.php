<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../user/match-history.php");
    exit;
}
verify_csrf("../user/match-history.php");

// บันทึกผลได้เฉพาะทีมที่ตัวเองเป็นหัวหน้า
$team = get_led_team($pdo, $user_id);
if (!$team) {
    redirect_with("../user/team-manage.php", 'error', "คุณต้องเป็นโค้ชถึงจะสามารถบันทึกผลการแข่งได้");
}
$team_id = (int)$team['team_id'];

// โหมดแก้ไข: ต้องเป็นแมตช์ของทีมตัวเองเท่านั้น
$match_id = (int)($_POST['match_id'] ?? 0);
$form_url = "../user/match-add.php" . ($match_id > 0 ? "?id=$match_id" : "");
if ($match_id > 0) {
    $chk = $pdo->prepare("SELECT match_id FROM scrim_matches WHERE match_id = ? AND team_id = ?");
    $chk->execute([$match_id, $team_id]);
    if (!$chk->fetch()) {
        redirect_with("../user/match-history.php", 'error', "ไม่พบแมตช์นี้ หรือคุณไม่มีสิทธิ์แก้ไข");
    }
}

$opponent_name = trim($_POST['opponent_name'] ?? '');
$match_type = $_POST['match_type'] ?? 'Bo3';
$game_number = max(1, min(7, (int)($_POST['game_number'] ?? 1)));
$match_result = $_POST['match_result'] ?? '';
$match_duration = trim($_POST['match_duration'] ?? '');

if ($opponent_name === '' || mb_strlen($opponent_name) > 100) {
    redirect_with($form_url, 'error', "กรุณากรอกชื่อทีมคู่แข่ง (ไม่เกิน 100 ตัวอักษร)");
}
if (!in_array($match_type, MATCH_TYPES, true)) {
    $match_type = 'Bo3';
}

// ซีรีส์: เลือกซีรีส์เดิมของทีม หรือ "new" = สร้างซีรีส์ใหม่จากคู่แข่ง/รูปแบบที่กรอก
$series_choice = $_POST['series_id'] ?? 'new';
$series_id = null;
if ($series_choice !== 'new') {
    $stmt = $pdo->prepare("SELECT series_id, opponent_name, best_of FROM scrim_series WHERE series_id = ? AND team_id = ?");
    $stmt->execute([(int)$series_choice, $team_id]);
    $series = $stmt->fetch();
    if (!$series) {
        redirect_with($form_url, 'error', "ไม่พบซีรีส์ที่เลือก");
    }
    // เกมในซีรีส์ใช้คู่แข่งและรูปแบบเดียวกับซีรีส์เสมอ
    $series_id = (int)$series['series_id'];
    $opponent_name = $series['opponent_name'];
    $match_type = 'Bo' . (int)$series['best_of'];
}
if (!in_array($match_result, ['WIN', 'LOSS'], true)) {
    redirect_with($form_url, 'error', "กรุณาเลือกผลการแข่งขัน");
}
// วัน-เวลาที่ซ้อม (ห้ามเป็นอนาคต)
$date = DateTime::createFromFormat('Y-m-d\TH:i', $_POST['match_date'] ?? '');
if (!$date || $date > new DateTime('+5 minutes')) {
    redirect_with($form_url, 'error', "กรุณาเลือกวัน-เวลาที่ซ้อม (ต้องไม่เป็นเวลาในอนาคต)");
}
$match_date = $date->format('Y-m-d H:i:s');

if ($match_duration !== '' && !preg_match('/^\d{1,3}(:\d{2})?$/', $match_duration)) {
    redirect_with($form_url, 'error', "รูปแบบเวลาจบเกมไม่ถูกต้อง (ตัวอย่าง 15:30)");
}

// ข้อมูล Object Control
$team_dragons = max(0, min(99, (int)($_POST['team_dragons'] ?? 0)));
$team_slayers = max(0, min(99, (int)($_POST['team_slayers'] ?? 0)));
$team_god_slayers = max(0, min(99, (int)($_POST['team_god_slayers'] ?? 0)));
$team_towers = max(0, min(9, (int)($_POST['team_towers'] ?? 0)));
$first_dragon = isset($_POST['first_dragon']) ? 1 : 0;
$first_tower = isset($_POST['first_tower']) ? 1 : 0;

// ฮีโร่ฝั่งตรงข้าม 5 ตัว
$enemy_heroes = [];
for ($i = 1; $i <= 5; $i++) {
    $enemy_heroes[] = trim($_POST["enemy_hero_$i"] ?? '') ?: null;
}
foreach ($enemy_heroes as $h) {
    if ($h !== null && mb_strlen($h) > 50) {
        redirect_with($form_url, 'error', "ชื่อฮีโร่ฝั่งตรงข้ามไม่ถูกต้อง");
    }
}

// โน้ต & สื่อแนบ (ลิงก์ต้องเป็น http/https)
$match_image_url = clean_url($_POST['match_image_url'] ?? '');
$replay_url = clean_url($_POST['replay_url'] ?? '');
$match_note = trim($_POST['match_note'] ?? '') ?: null;
if (mb_strlen((string)$match_note) > 5000) {
    redirect_with($form_url, 'error', "โน้ตวิเคราะห์เกมยาวเกิน 5,000 ตัวอักษร");
}
if (mb_strlen((string)$match_image_url) > 255 || mb_strlen((string)$replay_url) > 255) {
    redirect_with($form_url, 'error', "ลิงก์สื่อแนบยาวเกิน 255 ตัวอักษร");
}

// รายชื่อสมาชิกที่อยู่ในทีมนี้จริง (กันการส่ง member_id ของทีมอื่นเข้ามา)
$stmt_mem = $pdo->prepare("SELECT member_id FROM team_members WHERE team_id = ?");
$stmt_mem->execute([$team_id]);
$valid_members = array_map('intval', $stmt_mem->fetchAll(PDO::FETCH_COLUMN));

$stats = $_POST['stats'] ?? [];

// จำกัดค่าเปอร์เซ็นต์ให้อยู่ในช่วง 0-100
$pct = function ($v) {
    return max(0.0, min(100.0, (float)$v));
};
// จำกัดค่าจำนวนเต็มให้อยู่ในช่วง 0-$max (กันค่าเกินขนาดคอลัมน์ INT)
$int = function ($v, $max) {
    return max(0, min($max, (int)$v));
};

// ผู้เล่นที่ลงเล่น (เลือกฮีโร่) ต้องกรอก Gold, MVP, ดาเมจที่ทำ และดาเมจที่รับมากกว่า 0 ส่วน K/D/A เป็น 0 ได้
if (is_array($stats)) {
    foreach ($stats as $player) {
        if (!in_array((int)($player['member_id'] ?? 0), $valid_members, true) || trim($player['hero'] ?? '') === '') continue;
        foreach (['gold', 'mvp_score', 'damage_dealt', 'damage_taken'] as $f) {
            if ((float)($player[$f] ?? 0) <= 0) {
                redirect_with($form_url, 'error', "ผู้เล่นที่ลงเล่นต้องกรอก Gold, MVP, ดาเมจที่ทำ และดาเมจที่รับมากกว่า 0");
            }
        }
    }
}

try {
    $pdo->beginTransaction();

    // สร้างซีรีส์ใหม่เมื่อไม่ได้เลือกซีรีส์เดิม
    if ($series_id === null) {
        $pdo->prepare("INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date) VALUES (?, ?, ?, ?)")
            ->execute([$team_id, $opponent_name, (int)substr($match_type, 2), $match_date]);
        $series_id = (int)$pdo->lastInsertId();
    }

    $match_values = array_merge(
        [$opponent_name, $match_date, $match_type, $game_number, $match_result, $match_duration ?: null,
         $team_dragons, $team_slayers, $team_god_slayers, $team_towers, $first_dragon, $first_tower],
        $enemy_heroes,
        [$match_image_url, $replay_url, $match_note, $series_id]
    );

    if ($match_id > 0) {
        // 1a. แก้ไขข้อมูลแมตช์เดิม แล้วลบสถิติผู้เล่นเดิมเพื่อบันทึกใหม่ทั้งชุด
        $stmt = $pdo->prepare("UPDATE scrim_matches SET
            opponent_name = ?, match_date = ?, match_type = ?, game_number = ?, match_result = ?, match_duration = ?,
            team_dragons = ?, team_slayers = ?, team_god_slayers = ?, team_towers = ?, first_dragon = ?, first_tower = ?,
            enemy_hero_1 = ?, enemy_hero_2 = ?, enemy_hero_3 = ?, enemy_hero_4 = ?, enemy_hero_5 = ?,
            match_image_url = ?, replay_url = ?, match_note = ?, series_id = ?
            WHERE match_id = ? AND team_id = ?");
        $stmt->execute(array_merge($match_values, [$match_id, $team_id]));
        $pdo->prepare("DELETE FROM match_player_stats WHERE match_id = ?")->execute([$match_id]);
    } else {
        // 1b. บันทึกแมตช์ใหม่ลงตาราง scrim_matches
        $stmt = $pdo->prepare("INSERT INTO scrim_matches (
            opponent_name, match_date, match_type, game_number, match_result, match_duration,
            team_dragons, team_slayers, team_god_slayers, team_towers, first_dragon, first_tower,
            enemy_hero_1, enemy_hero_2, enemy_hero_3, enemy_hero_4, enemy_hero_5,
            match_image_url, replay_url, match_note, series_id, team_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array_merge($match_values, [$team_id]));
        $match_id = (int)$pdo->lastInsertId();
    }

    // 2. บันทึกสถิติสมาชิกแต่ละคน (เฉพาะคนที่เลือกฮีโร่ = ได้ลงเล่น)
    if (is_array($stats)) {
        $stmt_stat = $pdo->prepare("INSERT INTO match_player_stats (
            match_id, member_id, hero_name, kills, deaths, assists, mvp_score,
            gold, damage_dealt, damage_taken,
            participation_rate, gold_share, damage_share, taken_share
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $saved = [];
        foreach ($stats as $player) {
            $member_id = (int)($player['member_id'] ?? 0);
            $hero = trim($player['hero'] ?? '');
            if (!in_array($member_id, $valid_members, true) || $hero === '' || mb_strlen($hero) > 50 || isset($saved[$member_id])) continue;
            $saved[$member_id] = true;

            $stmt_stat->execute([
                $match_id,
                $member_id,
                $hero,
                $int($player['kills'] ?? 0, 999),
                $int($player['deaths'] ?? 0, 999),
                $int($player['assists'] ?? 0, 999),
                max(0.0, min(16.0, (float)($player['mvp_score'] ?? 0))),
                $int($player['gold'] ?? 0, 9999999),
                $int($player['damage_dealt'] ?? 0, 99999999),
                $int($player['damage_taken'] ?? 0, 99999999),
                $pct($player['participation_rate'] ?? 0),
                $pct($player['gold_share'] ?? 0),
                $pct($player['damage_share'] ?? 0),
                $pct($player['taken_share'] ?? 0),
            ]);
        }
    }

    // ย้ายเกมออกจากซีรีส์เดิมแล้วซีรีส์ว่าง ให้ลบทิ้ง
    cleanup_empty_series($pdo, $team_id);

    $pdo->commit();
    redirect_with("../user/match-detail.php?id=$match_id", 'success', $form_url === "../user/match-add.php" ? "บันทึกผลการซ้อมเรียบร้อยแล้ว!" : "บันทึกการแก้ไขเรียบร้อยแล้ว!");
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("match-process: " . $e->getMessage());
    redirect_with($form_url, 'error', "เกิดข้อผิดพลาดในการบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง");
}
