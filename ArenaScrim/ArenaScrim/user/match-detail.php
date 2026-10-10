<?php
require_once '../config/db.php';
$user_id = require_login();

$match_id = (int)($_GET['id'] ?? 0);

// ดึงข้อมูลแมตช์ (ดูได้เฉพาะแมตช์ของทีมตัวเอง ยกเว้น Admin)
$stmt = $pdo->prepare("SELECT m.*, t.team_name, t.team_tag, t.leader_id
                       FROM scrim_matches m JOIN teams t ON m.team_id = t.team_id
                       WHERE m.match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

$my_team = get_user_team($pdo, $user_id);
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
if (!$match || (!$is_admin && (int)($my_team['team_id'] ?? 0) !== (int)$match['team_id'])) {
    redirect_with("match-history.php", 'error', "ไม่พบแมตช์นี้ หรือคุณไม่มีสิทธิ์เข้าถึง");
}
$is_leader = (int)$match['leader_id'] === $user_id;

// สถิติผู้เล่นในแมตช์นี้
$stmt = $pdo->prepare("SELECT s.*, tm.in_game_name, tm.main_role
                       FROM match_player_stats s
                       LEFT JOIN team_members tm ON s.member_id = tm.member_id
                       WHERE s.match_id = ?
                       ORDER BY s.stat_id");
$stmt->execute([$match_id]);
$players = $stmt->fetchAll();

// ผลรวมของทีม และ MVP ของเกม
$totals = ['kills' => 0, 'deaths' => 0, 'assists' => 0, 'gold' => 0, 'damage_dealt' => 0, 'damage_taken' => 0];
$mvp = null;
foreach ($players as $p) {
    foreach ($totals as $k => $_) {
        $totals[$k] += (int)$p[$k];
    }
    if ($mvp === null || (float)$p['mvp_score'] > (float)$mvp['mvp_score']) {
        $mvp = $p;
    }
}
$team_kda = $totals['deaths'] > 0 ? round(($totals['kills'] + $totals['assists']) / $totals['deaths'], 2) : ($totals['kills'] + $totals['assists']);

$enemy_heroes = array_values(array_filter([
    $match['enemy_hero_1'], $match['enemy_hero_2'], $match['enemy_hero_3'], $match['enemy_hero_4'], $match['enemy_hero_5'],
]));
$is_win = $match['match_result'] === 'WIN';
$is_image = $match['match_image_url'] && preg_match('/\.(png|jpe?g|gif|webp)(\?.*)?$/i', $match['match_image_url']);

$page_title = "แมตช์ #$match_id vs " . $match['opponent_name'] . " - ArenaScrim";
$card = "background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px;";
$stat_box = "background: #1a1d29; padding: 12px; border-radius: 8px; border: 1px solid #333; text-align: center;";

include '../includes/header.php';
?>

<div style="max-width: 1100px; margin: 30px auto; color: #fff;">
    <div style="margin-bottom: 15px;">
        <a href="match-history.php" style="color: #00e5ff; text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-arrow-left"></i> กลับไปประวัติการซ้อม</a>
    </div>

    <?php show_flash(); ?>

    <!-- หัวข้อแมตช์ -->
    <div style="<?= $card ?> border-left: 4px solid <?= $is_win ? '#00c853' : '#d50000' ?>; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="color: #aaa; font-size: 0.85rem;">
                แมตช์ #<?= (int)$match['match_id'] ?> · <?= date('d/m/Y H:i', strtotime($match['match_date'])) ?> น.
                · <?= e($match['match_type']) ?> เกมที่ <?= (int)$match['game_number'] ?>
            </div>
            <h2 style="margin: 6px 0 0 0;">
                <span style="color: #00e5ff;"><?= e($match['team_name']) ?></span>
                <span style="color: #777; font-size: 1rem; margin: 0 8px;">VS</span>
                <span><?= e($match['opponent_name']) ?></span>
            </h2>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span style="padding: 8px 18px; border-radius: 6px; font-weight: bold; font-size: 1.2rem; background: <?= $is_win ? '#00c853' : '#d50000' ?>;"><?= e($match['match_result']) ?></span>
            <a href="../actions/export-pdf.php?match_id=<?= (int)$match['match_id'] ?>" target="_blank" style="padding: 8px 14px; background: #ff4d4d; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem;"><i class="fa-solid fa-file-pdf"></i> PDF</a>
            <?php if ($is_leader): ?>
                <a href="match-add.php?id=<?= (int)$match['match_id'] ?>" class="btn-cyber" style="padding: 8px 14px; text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-pen"></i> แก้ไข</a>
                <?= post_button('../actions/match-delete.php', ['id' => (int)$match['match_id']], '<i class="fa-solid fa-trash"></i> ลบ',
                    'padding: 8px 14px; background: #333; color: #ff4d4d; border-radius: 6px; font-size: 0.85rem; border: 1px solid #ff4d4d;',
                    'คุณต้องการลบประวัติการซ้อมนี้ใช่หรือไม่?') ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- สรุปภาพรวมของทีมในเกมนี้ -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px;">
        <div style="<?= $stat_box ?>"><div style="color: #aaa; font-size: 0.8rem;">เวลาจบเกม</div><div style="font-size: 1.4rem; font-weight: bold;"><?= e($match['match_duration'] ?: '-') ?></div></div>
        <div style="<?= $stat_box ?>"><div style="color: #aaa; font-size: 0.8rem;">K / D / A ทีม</div><div style="font-size: 1.4rem; font-weight: bold; color: #ffeb3b;"><?= $totals['kills'] ?> / <?= $totals['deaths'] ?> / <?= $totals['assists'] ?></div></div>
        <div style="<?= $stat_box ?>"><div style="color: #aaa; font-size: 0.8rem;">KDA ทีม</div><div style="font-size: 1.4rem; font-weight: bold; color: #00e5ff;"><?= $team_kda ?></div></div>
        <div style="<?= $stat_box ?>"><div style="color: #aaa; font-size: 0.8rem;">Gold รวม</div><div style="font-size: 1.4rem; font-weight: bold; color: #ffd700;"><?= $totals['gold'] ? number_format($totals['gold']) : '-' ?></div></div>
        <div style="<?= $stat_box ?>"><div style="color: #aaa; font-size: 0.8rem;">MVP ของเกม</div><div style="font-size: 1.1rem; font-weight: bold; color: #ffd700; margin-top: 4px;"><?= $mvp ? '👑 ' . e($mvp['in_game_name'] ?? '-') . ' (' . number_format((float)$mvp['mvp_score'], 1) . ')' : '-' ?></div></div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Objectives -->
        <div style="<?= $card ?>">
            <h4 style="margin-top: 0; color: #ffd700;"><i class="fa-solid fa-dragon"></i> Objectives</h4>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center;">
                <div><div style="font-size: 1.5rem;">🐉 <?= (int)$match['team_dragons'] ?></div><small style="color: #aaa;">มังกร</small></div>
                <div><div style="font-size: 1.5rem;">⚔️ <?= (int)$match['team_slayers'] ?></div><small style="color: #aaa;">Dark Slayer</small></div>
                <div><div style="font-size: 1.5rem;">👑 <?= (int)$match['team_god_slayers'] ?></div><small style="color: #aaa;">God Slayer</small></div>
                <div><div style="font-size: 1.5rem;">🏰 <?= (int)$match['team_towers'] ?></div><small style="color: #aaa;">ป้อม</small></div>
            </div>
            <div style="margin-top: 12px; display: flex; gap: 10px; font-size: 0.85rem;">
                <span style="padding: 4px 10px; border-radius: 4px; border: 1px solid <?= $match['first_dragon'] ? '#00c853' : '#444' ?>; color: <?= $match['first_dragon'] ? '#00c853' : '#777' ?>;"><?= $match['first_dragon'] ? '✔' : '✘' ?> First Dragon</span>
                <span style="padding: 4px 10px; border-radius: 4px; border: 1px solid <?= $match['first_tower'] ? '#00c853' : '#444' ?>; color: <?= $match['first_tower'] ? '#00c853' : '#777' ?>;"><?= $match['first_tower'] ? '✔' : '✘' ?> First Tower</span>
            </div>
        </div>

        <!-- Enemy lineup -->
        <div style="<?= $card ?>">
            <h4 style="margin-top: 0; color: #ff4d4d;"><i class="fa-solid fa-skull"></i> ฮีโร่ฝั่งตรงข้าม</h4>
            <?php if ($enemy_heroes): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($enemy_heroes as $h): ?>
                        <span style="padding: 6px 12px; background: rgba(255,77,77,0.1); border: 1px solid #ff4d4d55; border-radius: 6px;"><?= e($h) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #777; margin: 0;">ไม่ได้บันทึกฮีโร่ฝั่งตรงข้าม</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ตารางสถิติผู้เล่น -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #00e5ff;"><i class="fa-solid fa-users"></i> สถิติผู้เล่นฝั่งเรา</h4>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; min-width: 900px;">
                <thead>
                    <tr style="border-bottom: 1px solid #333; color: #aaa;">
                        <th style="padding: 10px;">ผู้เล่น</th>
                        <th style="padding: 10px;">ฮีโร่</th>
                        <th style="padding: 10px; text-align: center;">K / D / A</th>
                        <th style="padding: 10px; text-align: center;">MVP</th>
                        <th style="padding: 10px; text-align: right;">Gold</th>
                        <th style="padding: 10px; text-align: right;">ดาเมจทำ</th>
                        <th style="padding: 10px; text-align: right;">ดาเมจรับ</th>
                        <th style="padding: 10px; text-align: center;">% เงิน</th>
                        <th style="padding: 10px; text-align: center;">% ดาเมจ</th>
                        <th style="padding: 10px; text-align: center;">% รับ</th>
                        <th style="padding: 10px; text-align: center;">% ไฟต์</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$players): ?>
                        <tr><td colspan="11" style="padding: 20px; text-align: center; color: #777;">ไม่มีสถิติผู้เล่นในแมตช์นี้</td></tr>
                    <?php endif; ?>
                    <?php foreach ($players as $p): ?>
                        <?php $is_mvp = $mvp && $p['stat_id'] === $mvp['stat_id'] && (float)$p['mvp_score'] > 0; ?>
                        <tr style="border-bottom: 1px solid #222; <?= $is_mvp ? 'background: rgba(255,215,0,0.06);' : '' ?>">
                            <td style="padding: 10px;">
                                <a href="member-stats.php?member_id=<?= (int)$p['member_id'] ?>" style="color: #fff; font-weight: bold; text-decoration: none;"><?= e($p['in_game_name'] ?? 'ผู้เล่นที่ถูกลบ') ?></a>
                                <?= $is_mvp ? ' <span title="MVP">👑</span>' : '' ?>
                                <div style="color: #777; font-size: 0.78rem;"><?= e($p['main_role']) ?></div>
                            </td>
                            <td style="padding: 10px;"><?= e($p['hero_name']) ?></td>
                            <td style="padding: 10px; text-align: center; color: #ffeb3b; font-weight: bold;"><?= (int)$p['kills'] ?> / <?= (int)$p['deaths'] ?> / <?= (int)$p['assists'] ?></td>
                            <td style="padding: 10px; text-align: center; color: #ffd700;"><?= number_format((float)$p['mvp_score'], 1) ?></td>
                            <td style="padding: 10px; text-align: right;"><?= $p['gold'] ? number_format($p['gold']) : '-' ?></td>
                            <td style="padding: 10px; text-align: right;"><?= $p['damage_dealt'] ? number_format($p['damage_dealt']) : '-' ?></td>
                            <td style="padding: 10px; text-align: right;"><?= $p['damage_taken'] ? number_format($p['damage_taken']) : '-' ?></td>
                            <td style="padding: 10px; text-align: center; color: #ffeb3b;"><?= number_format((float)$p['gold_share'], 1) ?>%</td>
                            <td style="padding: 10px; text-align: center; color: #00e5ff;"><?= number_format((float)$p['damage_share'], 1) ?>%</td>
                            <td style="padding: 10px; text-align: center; color: #ff4d4d;"><?= number_format((float)$p['taken_share'], 1) ?>%</td>
                            <td style="padding: 10px; text-align: center;"><?= number_format((float)$p['participation_rate'], 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- โน้ตวิเคราะห์ & สื่อแนบ -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #00e5ff;"><i class="fa-solid fa-clipboard-list"></i> การวิเคราะห์เกม & สื่อแนบ</h4>
        <?php if ($match['match_note']): ?>
            <div style="background: #090a0f; padding: 12px; border-radius: 6px; line-height: 1.6; margin-bottom: 12px;"><?= nl2br(e($match['match_note'])) ?></div>
        <?php else: ?>
            <p style="color: #777;">ไม่มีโน้ตวิเคราะห์เกม</p>
        <?php endif; ?>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($match['replay_url']): ?>
                <a href="<?= e($match['replay_url']) ?>" target="_blank" rel="noopener" class="btn-cyber" style="text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-play"></i> ดูรีเพลย์</a>
            <?php endif; ?>
            <?php if ($match['match_image_url'] && !$is_image): ?>
                <a href="<?= e($match['match_image_url']) ?>" target="_blank" rel="noopener" class="btn-cyber" style="text-decoration: none; font-size: 0.85rem;"><i class="fa-solid fa-image"></i> ดูสกรีนช็อต</a>
            <?php endif; ?>
        </div>
        <?php if ($is_image): ?>
            <a href="<?= e($match['match_image_url']) ?>" target="_blank" rel="noopener">
                <img src="<?= e($match['match_image_url']) ?>" alt="สกรีนช็อตผลการแข่ง" style="max-width: 100%; border-radius: 8px; margin-top: 12px; border: 1px solid #333;">
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
