<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "วิเคราะห์คู่แข่ง - ArenaScrim";

$team = get_user_team($pdo, $user_id);
$team_id = (int)($team['team_id'] ?? 0);

// ทุกตัวเลขนับต่อเกม · ชื่อคู่แข่งที่ต่างแค่ตัวพิมพ์เล็ก-ใหญ่/ช่องว่างหัวท้าย นับเป็นทีมเดียวกัน
$opponents = [];
$enemy_heroes = [];
$heroes_by_opp = [];

if ($team_id) {
    // 1. ผลเจอกันกับคู่แข่งแต่ละทีม
    $stmt = $pdo->prepare("SELECT LOWER(TRIM(m.opponent_name)) AS opp_key, MAX(m.opponent_name) AS name,
            COUNT(*) AS games, SUM(m.match_result = 'WIN') AS wins,
            COALESCE(SUM(t.k), 0) AS k, COALESCE(SUM(t.d), 0) AS d, COALESCE(SUM(t.a), 0) AS a,
            SUM(t.match_id IS NOT NULL) AS stat_games, MAX(m.match_date) AS last_date
        FROM scrim_matches m
        LEFT JOIN (SELECT match_id, SUM(kills) AS k, SUM(deaths) AS d, SUM(assists) AS a
                   FROM match_player_stats GROUP BY match_id) t ON t.match_id = m.match_id
        WHERE m.team_id = ?
        GROUP BY opp_key
        ORDER BY games DESC, last_date DESC");
    $stmt->execute([$team_id]);
    $opponents = $stmt->fetchAll();

    // ฮีโร่ฝั่งตรงข้ามทั้ง 5 ช่อง รวมเป็นรายการเดียว (1 แถวต่อฮีโร่ต่อเกม)
    $enemy_sql = implode(' UNION ALL ', array_map(function ($i) {
        return "SELECT enemy_hero_$i AS hero, match_result, LOWER(TRIM(opponent_name)) AS opp_key
                FROM scrim_matches WHERE team_id = ? AND enemy_hero_$i IS NOT NULL AND enemy_hero_$i <> ''";
    }, range(1, 5)));

    // 2. ฮีโร่ฝั่งตรงข้ามที่เจอบ่อย + Win Rate ของเราเมื่อเจอฮีโร่นั้น
    $stmt = $pdo->prepare("SELECT hero, COUNT(*) AS games, SUM(match_result = 'WIN') AS wins
        FROM ($enemy_sql) x GROUP BY hero ORDER BY games DESC, hero LIMIT 20");
    $stmt->execute(array_fill(0, 5, $team_id));
    $enemy_heroes = $stmt->fetchAll();

    // 3. ฮีโร่ฝั่งตรงข้ามแยกตามคู่แข่ง (5 ตัวที่เจอบ่อยที่สุดของแต่ละทีม)
    $stmt = $pdo->prepare("SELECT opp_key, hero, COUNT(*) AS games
        FROM ($enemy_sql) x GROUP BY opp_key, hero ORDER BY opp_key, games DESC, hero");
    $stmt->execute(array_fill(0, 5, $team_id));
    foreach ($stmt->fetchAll() as $r) {
        if (count($heroes_by_opp[$r['opp_key']] ?? []) < 5) {
            $heroes_by_opp[$r['opp_key']][] = $r;
        }
    }
}

$card = "background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px;";
$th = "padding: 10px;";
$td = "padding: 10px; border-bottom: 1px solid #222;";
$pct = function ($wins, $games) {
    return $games ? number_format($wins / $games * 100, 1) . '%' : '-';
};

include '../includes/header.php';
?>

<div style="max-width: 1100px; margin: 30px auto; color: #fff;">
    <h2 style="color: #00e5ff; margin: 0 0 6px 0;"><i class="fa-solid fa-crosshairs"></i> วิเคราะห์คู่แข่ง<?= $team ? ': ' . e($team['team_name']) : '' ?></h2>
    <p style="color: #888; margin: 0 0 20px 0; font-size: 0.85rem;">นับต่อเกม · ชื่อคู่แข่งที่ต่างแค่ตัวพิมพ์เล็ก-ใหญ่หรือช่องว่างหัวท้าย นับเป็นทีมเดียวกัน</p>

    <?php show_flash(); ?>

    <?php if (!$team): ?>
        <div style="<?= $card ?> color: #aaa;">คุณยังไม่มีทีม <a href="team-manage.php" style="color: #00e5ff;">สร้างทีม</a> ก่อนเพื่อดูการวิเคราะห์</div>
    <?php else: ?>

    <!-- 1. ผลเจอกันกับคู่แข่ง -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #00e5ff;"><i class="fa-solid fa-people-arrows"></i> ผลเจอกันกับคู่แข่งแต่ละทีม</h4>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; min-width: 760px;">
                <thead>
                    <tr style="color: #aaa; text-align: left; border-bottom: 1px solid #333;">
                        <th style="<?= $th ?>">คู่แข่ง</th>
                        <th style="<?= $th ?> text-align: center;">เกม</th>
                        <th style="<?= $th ?> text-align: center;">ชนะ / แพ้</th>
                        <th style="<?= $th ?> text-align: center;">Win Rate</th>
                        <th style="<?= $th ?> text-align: center;">KDA ทีม</th>
                        <th style="<?= $th ?>">ฮีโร่ที่คู่แข่งใช้บ่อย</th>
                        <th style="<?= $th ?>">เจอล่าสุด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$opponents): ?>
                        <tr><td colspan="7" style="<?= $td ?> text-align: center; color: #777;">ยังไม่มีผลการซ้อม</td></tr>
                    <?php endif; ?>
                    <?php foreach ($opponents as $o): ?>
                        <tr>
                            <td style="<?= $td ?> font-weight: bold;"><?= e($o['name']) ?></td>
                            <td style="<?= $td ?> text-align: center;"><?= (int)$o['games'] ?></td>
                            <td style="<?= $td ?> text-align: center;"><span style="color: #4caf50;"><?= (int)$o['wins'] ?>W</span> - <span style="color: #f44336;"><?= $o['games'] - $o['wins'] ?>L</span></td>
                            <td style="<?= $td ?> text-align: center; color: #00e5ff; font-weight: bold;"><?= $pct($o['wins'], $o['games']) ?></td>
                            <td style="<?= $td ?> text-align: center; color: #ffeb3b;"><?= $o['stat_games'] ? number_format(($o['k'] + $o['a']) / max(1, $o['d']), 2) : '-' ?></td>
                            <td style="<?= $td ?> color: #ccc; font-size: 0.85rem;">
                                <?php $hs = $heroes_by_opp[$o['opp_key']] ?? []; ?>
                                <?= $hs ? implode(', ', array_map(function ($h) { return e($h['hero']) . ' (' . (int)$h['games'] . ')'; }, $hs)) : '<span style="color: #666;">ไม่ได้บันทึก</span>' ?>
                            </td>
                            <td style="<?= $td ?> color: #aaa; white-space: nowrap;"><?= date('d/m/Y', strtotime($o['last_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. ฮีโร่ฝั่งตรงข้ามที่เจอบ่อย -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #ff4d4d;"><i class="fa-solid fa-skull"></i> ฮีโร่ฝั่งตรงข้ามที่เจอบ่อย และ Win Rate ของเราเมื่อเจอ</h4>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; min-width: 500px;">
                <thead>
                    <tr style="color: #aaa; text-align: left; border-bottom: 1px solid #333;">
                        <th style="<?= $th ?>">ฮีโร่</th>
                        <th style="<?= $th ?> text-align: center;">เจอ (เกม)</th>
                        <th style="<?= $th ?> text-align: center;">เราชนะ / แพ้</th>
                        <th style="<?= $th ?> text-align: center;">Win Rate ของเรา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$enemy_heroes): ?>
                        <tr><td colspan="4" style="<?= $td ?> text-align: center; color: #777;">ยังไม่ได้บันทึกฮีโร่ฝั่งตรงข้าม</td></tr>
                    <?php endif; ?>
                    <?php foreach ($enemy_heroes as $h): ?>
                        <tr>
                            <td style="<?= $td ?> font-weight: bold;"><?= e($h['hero']) ?></td>
                            <td style="<?= $td ?> text-align: center;"><?= (int)$h['games'] ?></td>
                            <td style="<?= $td ?> text-align: center;"><span style="color: #4caf50;"><?= (int)$h['wins'] ?>W</span> - <span style="color: #f44336;"><?= $h['games'] - $h['wins'] ?>L</span></td>
                            <td style="<?= $td ?> text-align: center; font-weight: bold; color: <?= $h['wins'] / $h['games'] >= 0.5 ? '#00e5ff' : '#ff4d4d' ?>;"><?= $pct($h['wins'], $h['games']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
