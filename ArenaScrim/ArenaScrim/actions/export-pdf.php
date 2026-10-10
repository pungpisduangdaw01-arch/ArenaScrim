<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();
$match_id = (int)($_GET['match_id'] ?? 0);

// 1. ดึงข้อมูลแมตช์ (ดูได้เฉพาะแมตช์ของทีมตัวเอง ยกเว้น Admin)
$sql_match = "SELECT m.*, t.team_name, t.leader_id
              FROM scrim_matches m
              JOIN teams t ON m.team_id = t.team_id
              WHERE m.match_id = ?";
$stmt = $pdo->prepare($sql_match);
$stmt->execute([$match_id]);
$match = $stmt->fetch();

$my_team = get_user_team($pdo, $user_id);
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
if (!$match || (!$is_admin && (int)($my_team['team_id'] ?? 0) !== (int)$match['team_id'])) {
    redirect_with("../user/match-history.php", 'error', "ไม่พบแมตช์นี้ หรือคุณไม่มีสิทธิ์เข้าถึง");
}

// 2. ดึงสถิติผู้เล่นพร้อมชื่อในเกมจาก team_members
$sql_stats = "SELECT s.*, COALESCE(tm.in_game_name, CONCAT('Player ', s.member_id)) AS display_name, tm.main_role
              FROM match_player_stats s
              LEFT JOIN team_members tm ON s.member_id = tm.member_id
              WHERE s.match_id = ?
              ORDER BY s.stat_id";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute([$match_id]);
$player_stats = $stmt_stats->fetchAll();

// 3. สร้าง HTML ของรายงาน แล้วแปลงเป็นไฟล์ PDF ด้วย dompdf (ใช้ฟอนต์ Sarabun ใน assets/fonts สำหรับภาษาไทย)
ob_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Match Report - ArenaScrim</title>
    <style><?= file_get_contents(__DIR__ . '/../assets/css/pdf-style.css') ?></style>
</head>
<body>

    <div class="header">
        <h1>Match Report - ArenaScrim</h1>
        <h3>
            <?= htmlspecialchars($match['team_name']) ?> 
            VS 
            <?= htmlspecialchars($match['opponent_name']) ?> 
            (<span class="<?= $match['match_result'] === 'WIN' ? 'badge-win' : 'badge-loss' ?>"><?= htmlspecialchars($match['match_result']) ?></span>)
        </h3>
    </div>

    <!-- Match Overview & Objectives -->
    <div class="box">
        <div class="box-title">Match Details & Objectives</div>
        <div class="info-grid">
            <div class="info-item"><strong>Match Type:</strong> <?= htmlspecialchars($match['match_type']) ?></div>
            <div class="info-item"><strong>Game No:</strong> <?= htmlspecialchars($match['game_number']) ?></div>
            <div class="info-item"><strong>Duration:</strong> <?= htmlspecialchars($match['match_duration'] ?? '-') ?></div>
            <div class="info-item"><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?></div>
        </div>
        <hr class="divider">
        <div class="info-grid">
            <div class="info-item"><strong>Team Dragons:</strong> <?= (int)$match['team_dragons'] ?></div>
            <div class="info-item"><strong>Team Slayers:</strong> <?= (int)$match['team_slayers'] ?></div>
            <div class="info-item"><strong>God Slayers:</strong> <?= (int)$match['team_god_slayers'] ?></div>
            <div class="info-item"><strong>Towers:</strong> <?= (int)$match['team_towers'] ?></div>
            <div class="info-item"><strong>First Dragon:</strong> <?= $match['first_dragon'] ? 'Yes' : 'No' ?></div>
            <div class="info-item"><strong>First Tower:</strong> <?= $match['first_tower'] ? 'Yes' : 'No' ?></div>
        </div>
    </div>

    <!-- Enemy Composition -->
    <?php 
    $enemy_heroes = array_filter([
        $match['enemy_hero_1'], $match['enemy_hero_2'], $match['enemy_hero_3'], 
        $match['enemy_hero_4'], $match['enemy_hero_5']
    ]);
    if (!empty($enemy_heroes)): 
    ?>
    <div class="box">
        <div class="box-title">Enemy Lineup</div>
        <div><?= htmlspecialchars(implode(', ', $enemy_heroes)) ?></div>
    </div>
    <?php endif; ?>

    <!-- Player Performance Table -->
    <div class="box-title" style="margin-bottom:8px;">Player Performance</div>
    <table>
        <thead>
            <tr>
                <th>Player</th>
                <th>Hero</th>
                <th>K / D / A</th>
                <th>MVP</th>
                <th>% Gold</th>
                <th>% Damage</th>
                <th>% Taken</th>
                <th>% Fight</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($player_stats)): ?>
                <?php foreach ($player_stats as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['display_name']) ?></td>
                    <td><?= htmlspecialchars($p['hero_name'] ?: ($p['hero'] ?? '-')) ?></td>
                    <td><?= (int)$p['kills'] ?>/<?= (int)$p['deaths'] ?>/<?= (int)$p['assists'] ?></td>
                    <td><?= number_format((float)$p['mvp_score'], 1) ?></td>
                    <td><?= number_format((float)$p['gold_share'], 1) ?>%</td>
                    <td><?= number_format((float)$p['damage_share'], 1) ?>%</td>
                    <td><?= number_format((float)$p['taken_share'], 1) ?>%</td>
                    <td><?= number_format((float)$p['participation_rate'], 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No player statistics found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Media Links -->
    <?php if (!empty($match['replay_url']) || !empty($match['match_image_url'])): ?>
    <div class="box">
        <div class="box-title">Media</div>
        <?php if (!empty($match['replay_url'])): ?>
            <div><strong>Replay:</strong> <?= htmlspecialchars($match['replay_url']) ?></div>
        <?php endif; ?>
        <?php if (!empty($match['match_image_url'])): ?>
            <div><strong>Screenshot:</strong> <?= htmlspecialchars($match['match_image_url']) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Match Note -->
    <?php if (!empty($match['match_note'])): ?>
    <div class="box">
        <div class="box-title">Match Notes / Feedback</div>
        <div><?= nl2br(htmlspecialchars($match['match_note'])) ?></div>
    </div>
    <?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

require_once __DIR__ . '/../vendor/autoload.php';
$root = realpath(__DIR__ . '/..');
// dompdf ต้องเขียนไฟล์ชั่วคราวตอนตัดฟอนต์ เลือกโฟลเดอร์ชั่วคราวแรกที่ผู้ใช้ของเว็บเซิร์ฟเวอร์เขียนได้
$temp_dir = sys_get_temp_dir();
foreach ([sys_get_temp_dir(), ini_get('upload_tmp_dir'), '/tmp'] as $dir) {
    if ($dir && is_dir($dir) && is_writable($dir)) {
        $temp_dir = $dir;
        break;
    }
}
$dompdf = new \Dompdf\Dompdf(new \Dompdf\Options([
    'tempDir'         => $temp_dir,
    'fontDir'         => $root . '/assets/fonts',
    'fontCache'       => $root . '/assets/fonts',
    'chroot'          => $root,
    'defaultFont'     => 'Sarabun',
    'isRemoteEnabled' => false,
]));
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('ArenaScrim-match-' . $match_id . '.pdf', ['Attachment' => false]);