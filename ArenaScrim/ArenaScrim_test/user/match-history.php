<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "ประวัติการซ้อม - ArenaScrim";

// ดึงทีมของผู้ใช้ (หัวหน้าทีมเท่านั้นที่แก้ไข/ลบได้)
$team = get_user_team($pdo, $user_id);
$team_id = (int)($team['team_id'] ?? 0);
$is_leader = $team && (int)$team['leader_id'] === $user_id;

// ผลรวมของแต่ละซีรีส์ (เช่น 2–1) และซีรีส์ที่ยังไม่มีเกม (สร้างจากคำขอซ้อมที่รับแล้ว รอบันทึกผล)
$series = $team ? series_scores($pdo, (int)$team['team_id']) : [];
$waiting_series = array_filter($series, function ($s) { return (int)$s['games'] === 0; });

// รับค่าค้นหา / ตัวกรอง
$search = trim($_GET['search'] ?? '');
$result_filter = $_GET['result'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';
$valid_date = function ($d) {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : '';
};
$date_from = $valid_date($date_from);
$date_to = $valid_date($date_to);

// สร้าง Dynamic Query พร้อม K/D/A รวมของทีมในแต่ละแมตช์
$where = "m.team_id = ?";
$params = [$team_id];
if ($search !== '') {
    $where .= " AND m.opponent_name LIKE ?";
    $params[] = "%$search%";
}
if (in_array($result_filter, ['WIN', 'LOSS'], true)) {
    $where .= " AND m.match_result = ?";
    $params[] = $result_filter;
}
if (in_array($type_filter, MATCH_TYPES, true)) {
    $where .= " AND m.match_type = ?";
    $params[] = $type_filter;
}
if ($date_from) {
    $where .= " AND m.match_date >= ?";
    $params[] = "$date_from 00:00:00";
}
if ($date_to) {
    $where .= " AND m.match_date <= ?";
    $params[] = "$date_to 23:59:59";
}

$stmt_matches = $pdo->prepare("SELECT m.*,
        COALESCE(SUM(s.kills), 0) AS t_kills, COALESCE(SUM(s.deaths), 0) AS t_deaths, COALESCE(SUM(s.assists), 0) AS t_assists,
        COUNT(s.stat_id) AS player_count
    FROM scrim_matches m
    LEFT JOIN match_player_stats s ON s.match_id = m.match_id
    WHERE $where
    GROUP BY m.match_id
    ORDER BY m.match_date DESC, m.match_id DESC");
$stmt_matches->execute($params);
$matches = $stmt_matches->fetchAll();

// สรุปผลตามตัวกรองปัจจุบัน
$wins = count(array_filter($matches, function ($m) { return $m['match_result'] === 'WIN'; }));
$total = count($matches);
$losses = $total - $wins;
$win_rate = $total ? round($wins / $total * 100, 1) : 0;
$opponents = count(array_unique(array_map('mb_strtolower', array_column($matches, 'opponent_name'))));

// ฟอร์ม 5 เกมล่าสุด และสถิติชนะ/แพ้ติดกันปัจจุบัน
$recent = array_slice($matches, 0, 5);
$streak = 0;
$streak_type = $matches[0]['match_result'] ?? null;
foreach ($matches as $m) {
    if ($m['match_result'] !== $streak_type) break;
    $streak++;
}

// เวลาจบเกมเฉลี่ย (รูปแบบ mm:ss)
$secs = [];
foreach ($matches as $m) {
    if (preg_match('/^(\d{1,3})(?::(\d{2}))?$/', (string)$m['match_duration'], $t)) {
        $secs[] = (int)$t[1] * 60 + (int)($t[2] ?? 0);
    }
}
$avg_duration = $secs ? sprintf('%d:%02d', intdiv((int)(array_sum($secs) / count($secs)), 60), (int)(array_sum($secs) / count($secs)) % 60) : '-';

$filtered = $search !== '' || $result_filter || $type_filter || $date_from || $date_to;
$input = "padding: 10px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;";
$box = "background: #1a1d28; padding: 14px; border-radius: 8px; text-align: center;";

// แบ่งหน้าตาราง (การ์ดสรุปด้านบนยังคำนวณจากทุกรายการตามตัวกรอง)
[$matches_page, $page, $pages] = paginate_rows($matches);

include '../includes/header.php';
?>

<div style="max-width: 1150px; margin: 30px auto; background: #12141d; padding: 25px; border-radius: 12px; color: #fff; border: 1px solid #222;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="color: #00e5ff; margin: 0;"><i class="fa-solid fa-history"></i> ประวัติการซ้อม (Match History)</h3>
        <?php if ($is_leader): ?>
            <a href="match-add.php" class="btn-cyber" style="text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-plus"></i> บันทึกผลการซ้อม</a>
        <?php endif; ?>
    </div>

    <?php show_flash(); ?>

    <?php if (!$team): ?>
        <p style="color: #aaa;">คุณยังไม่มีทีม <a href="team-manage.php" style="color: #00e5ff;">สร้างทีม</a> ก่อนเพื่อเริ่มบันทึกผลการซ้อม</p>
    <?php else: ?>

    <?php if ($waiting_series): ?>
        <div style="background: rgba(0,229,255,0.06); border: 1px solid #00e5ff55; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px;">
            <div style="color: #00e5ff; font-weight: bold; margin-bottom: 6px;"><i class="fa-solid fa-hourglass-half"></i> ซีรีส์ที่รอบันทึกผล</div>
            <?php foreach ($waiting_series as $sid => $s): ?>
                <div style="display: flex; justify-content: space-between; gap: 10px; padding: 4px 0; flex-wrap: wrap;">
                    <span>vs <b><?= e($s['opponent_name']) ?></b> · Bo<?= (int)$s['best_of'] ?> · <?= $s['series_date'] ? date('d/m/Y H:i', strtotime($s['series_date'])) : '-' ?><?= $s['request_id'] ? ' · จากคำขอซ้อม' : '' ?></span>
                    <?php if ($is_leader): ?><a href="match-add.php?series_id=<?= (int)$sid ?>" style="color: #00e5ff;"><i class="fa-solid fa-plus"></i> บันทึกเกม</a><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- การ์ดสรุป -->
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 20px;">
        <div style="<?= $box ?>"><div style="color: #aaa; font-size: 0.8rem;">แมตช์<?= $filtered ? ' (ตามตัวกรอง)' : 'ทั้งหมด' ?></div><div style="font-size: 1.6rem; font-weight: bold;"><?= $total ?></div></div>
        <div style="<?= $box ?>"><div style="color: #aaa; font-size: 0.8rem;">ชนะ / แพ้</div><div style="font-size: 1.6rem; font-weight: bold;"><span style="color: #4caf50;"><?= $wins ?>W</span> - <span style="color: #f44336;"><?= $losses ?>L</span></div></div>
        <div style="<?= $box ?>"><div style="color: #aaa; font-size: 0.8rem;">Win Rate</div><div style="font-size: 1.6rem; font-weight: bold; color: #00e5ff;"><?= $win_rate ?>%</div></div>
        <div style="<?= $box ?>"><div style="color: #aaa; font-size: 0.8rem;">ฟอร์มล่าสุด</div>
            <div style="display: flex; gap: 4px; justify-content: center; margin-top: 8px;">
                <?php foreach ($recent as $m): ?>
                    <span title="vs <?= e($m['opponent_name']) ?>" style="width: 22px; height: 22px; line-height: 22px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; background: <?= $m['match_result'] === 'WIN' ? '#00c853' : '#d50000' ?>;"><?= $m['match_result'] === 'WIN' ? 'W' : 'L' ?></span>
                <?php endforeach; ?>
                <?php if (!$recent): ?><span style="color: #777;">-</span><?php endif; ?>
            </div>
        </div>
        <div style="<?= $box ?>"><div style="color: #aaa; font-size: 0.8rem;">สถิติติดกัน</div><div style="font-size: 1.3rem; font-weight: bold; margin-top: 4px; color: <?= $streak_type === 'WIN' ? '#4caf50' : '#f44336' ?>;"><?= $streak ? ($streak_type === 'WIN' ? "ชนะ $streak" : "แพ้ $streak") : '-' ?></div></div>
        <div style="<?= $box ?>"><div style="color: #aaa; font-size: 0.8rem;">เวลาเกมเฉลี่ย / คู่ซ้อม</div><div style="font-size: 1.3rem; font-weight: bold; margin-top: 4px;"><?= $avg_duration ?> <span style="color: #777; font-size: 0.9rem;">/ <?= $opponents ?> ทีม</span></div></div>
    </div>

    <!-- ฟอร์มค้นหา & ตัวกรอง -->
    <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="ค้นหาชื่อทีมคู่แข่ง..." style="flex: 2; min-width: 180px; <?= $input ?>">
        <select name="result" style="flex: 1; <?= $input ?>">
            <option value="">ทุกผลการแข่ง</option>
            <option value="WIN" <?= $result_filter === 'WIN' ? 'selected' : '' ?>>ชนะ (WIN)</option>
            <option value="LOSS" <?= $result_filter === 'LOSS' ? 'selected' : '' ?>>แพ้ (LOSS)</option>
        </select>
        <select name="type" style="flex: 1; <?= $input ?>">
            <option value="">ทุกรูปแบบ</option>
            <?php foreach (MATCH_TYPES as $t): ?>
                <option value="<?= $t ?>" <?= $type_filter === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= e($date_from) ?>" title="ตั้งแต่วันที่" style="<?= $input ?>">
        <span style="color: #777;">ถึง</span>
        <input type="date" name="to" value="<?= e($date_to) ?>" title="ถึงวันที่" style="<?= $input ?>">
        <button type="submit" style="padding: 10px 20px; background: #00e5ff; border: none; font-weight: bold; cursor: pointer; border-radius: 6px;">ค้นหา</button>
        <?php if ($filtered): ?>
            <a href="match-history.php" style="color: #aaa; font-size: 0.85rem;">ล้างตัวกรอง</a>
        <?php endif; ?>
    </form>

    <!-- ตารางแสดงรายการแมตช์ -->
    <div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 950px;">
        <thead>
            <tr style="background: #1a1d28; color: #00e5ff;">
                <th style="padding: 12px;">วันที่</th>
                <th style="padding: 12px;">คู่แข่ง</th>
                <th style="padding: 12px;">รูปแบบ</th>
                <th style="padding: 12px; text-align: center;">ซีรีส์</th>
                <th style="padding: 12px; text-align: center;">ผล</th>
                <th style="padding: 12px; text-align: center;">เวลา</th>
                <th style="padding: 12px; text-align: center; white-space: nowrap;">K / D / A ทีม</th>
                <th style="padding: 12px; text-align: center;">Objects (D/S/GS/T)</th>
                <th style="padding: 12px; text-align: center;">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($matches)): ?>
                <tr>
                    <td colspan="9" style="padding: 20px; text-align: center; color: #777;">ไม่พบประวัติการซ้อม</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($matches_page as $m): ?>
                <tr style="border-bottom: 1px solid #222;">
                    <td style="padding: 12px; color: #aaa; white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($m['match_date'])) ?></td>
                    <td style="padding: 12px; font-weight: bold;">
                        <a href="match-detail.php?id=<?= (int)$m['match_id'] ?>" style="color: #fff; text-decoration: none;"><?= e($m['opponent_name']) ?></a>
                        <?php if ($m['match_note']): ?><i class="fa-regular fa-note-sticky" title="มีโน้ตวิเคราะห์" style="color: #777; font-size: 0.8rem; margin-left: 4px;"></i><?php endif; ?>
                        <?php if ($m['replay_url']): ?><i class="fa-solid fa-play" title="มีรีเพลย์" style="color: #777; font-size: 0.8rem; margin-left: 4px;"></i><?php endif; ?>
                    </td>
                    <td style="padding: 12px; white-space: nowrap;"><?= e($m['match_type']) ?> (Game <?= (int)$m['game_number'] ?>)</td>
                    <?php $sr = $series[(int)$m['series_id']] ?? null; ?>
                    <td style="padding: 12px; text-align: center; white-space: nowrap; font-weight: bold;" title="ผลรวมของซีรีส์ (นับจากเกมที่บันทึก)">
                        <?= $sr ? (int)$sr['wins'] . '–' . (int)$sr['losses'] . ' <span style="color: #777; font-weight: normal; font-size: 0.8rem;">(' . (int)$sr['games'] . '/' . (int)$sr['best_of'] . ')</span>' : '-' ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="padding: 4px 8px; border-radius: 4px; font-weight: bold; background: <?= $m['match_result'] === 'WIN' ? '#00c853' : '#d50000' ?>;"><?= e($m['match_result']) ?></span>
                    </td>
                    <td style="padding: 12px; text-align: center; color: #aaa;"><?= e($m['match_duration'] ?: '-') ?></td>
                    <td style="padding: 12px; text-align: center; color: #ffeb3b; white-space: nowrap;">
                        <?= $m['player_count'] ? (int)$m['t_kills'] . ' / ' . (int)$m['t_deaths'] . ' / ' . (int)$m['t_assists'] : '<span style="color:#777;">-</span>' ?>
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 0.85rem; color: #ffd700; white-space: nowrap;">
                        🐉 <?= (int)$m['team_dragons'] ?> | ⚔️ <?= (int)$m['team_slayers'] ?> | 👑 <?= (int)$m['team_god_slayers'] ?> | 🏰 <?= (int)$m['team_towers'] ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                            <a href="match-detail.php?id=<?= (int)$m['match_id'] ?>" title="ดูรายละเอียด" style="padding: 6px 10px; background: #1a1d28; color: #00e5ff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; border: 1px solid #00e5ff;"><i class="fa-solid fa-eye"></i></a>
                            <a href="../actions/export-pdf.php?match_id=<?= (int)$m['match_id'] ?>" target="_blank" title="โหลด PDF" style="padding: 6px 10px; background: #ff4d4d; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem;"><i class="fa-solid fa-file-pdf"></i></a>
                            <?php if ($is_leader): ?>
                                <a href="match-add.php?id=<?= (int)$m['match_id'] ?>" title="แก้ไข" style="padding: 6px 10px; background: #1a1d28; color: #ffd700; text-decoration: none; border-radius: 4px; font-size: 0.85rem; border: 1px solid #ffd700;"><i class="fa-solid fa-pen"></i></a>
                                <?= post_button('../actions/match-delete.php', ['id' => (int)$m['match_id']], '<i class="fa-solid fa-trash" title="ลบ"></i>',
                                    'padding: 6px 10px; background: #333; color: #ff4d4d; border-radius: 4px; font-size: 0.85rem; border: 1px solid #ff4d4d;',
                                    'คุณต้องการลบประวัติการซ้อมนี้ใช่หรือไม่?') ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?= pagination_links($page, $pages) ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
