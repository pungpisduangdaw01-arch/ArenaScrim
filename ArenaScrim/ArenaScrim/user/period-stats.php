<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "สถิติตามช่วงเวลา - ArenaScrim";

$team = get_user_team($pdo, $user_id);
$team_id = (int)($team['team_id'] ?? 0);

$TH_MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

// ---------- ช่วงเวลา: สัปดาห์ (จันทร์-อาทิตย์) หรือ เดือน ----------
$mode = ($_GET['mode'] ?? 'week') === 'month' ? 'month' : 'week';

// แปลง key ของช่วงเวลา (2026-W40 / 2026-10) เป็นวันเริ่ม-สิ้นสุด และชื่อที่แสดง
function period_range($mode, $key)
{
    global $TH_MONTHS;
    if ($mode === 'week') {
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $key, $m)) return null;
        $start = (new DateTime())->setISODate((int)$m[1], (int)$m[2])->setTime(0, 0);
        $end = (clone $start)->modify('+7 days');
        $last = (clone $end)->modify('-1 day');
        $label = $start->format('j') . ' ' . $TH_MONTHS[(int)$start->format('n')] . ' – ' . $last->format('j') . ' ' . $TH_MONTHS[(int)$last->format('n')] . ' ' . ($last->format('Y') + 543);
    } else {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $key, $m) || (int)$m[2] < 1 || (int)$m[2] > 12) return null;
        $start = new DateTime("$key-01 00:00:00");
        $end = (clone $start)->modify('+1 month');
        $label = $TH_MONTHS[(int)$start->format('n')] . ' ' . ($start->format('Y') + 543);
    }
    return ['key' => $key, 'start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s'), 'label' => $label];
}

// key ของช่วงเวลาปัจจุบัน ย้อนหลัง $back ช่วง
function period_key($mode, $back)
{
    $d = new DateTime();
    if ($mode === 'week') {
        $d->modify("-$back weeks");
        return $d->format('o-\WW');
    }
    $d->modify('first day of this month')->modify("-$back months");
    return $d->format('Y-m');
}

// ---------- คำนวณสถิติของทีมในช่วงเวลา ----------
function period_stats(PDO $pdo, $team_id, $p)
{
    $m = $pdo->prepare("SELECT COUNT(*) AS matches, COALESCE(SUM(match_result = 'WIN'), 0) AS wins,
            AVG(team_dragons) AS dragons, AVG(team_slayers) AS slayers, AVG(team_towers) AS towers,
            AVG(first_dragon) * 100 AS first_dragon, AVG(first_tower) * 100 AS first_tower,
            GROUP_CONCAT(match_duration) AS durations
        FROM scrim_matches WHERE team_id = ? AND match_date >= ? AND match_date < ?");
    $m->execute([$team_id, $p['start'], $p['end']]);
    $r = $m->fetch();

    $s = $pdo->prepare("SELECT COUNT(DISTINCT s.match_id) AS stat_matches,
            COALESCE(SUM(s.kills), 0) AS k, COALESCE(SUM(s.deaths), 0) AS d, COALESCE(SUM(s.assists), 0) AS a,
            AVG(NULLIF(s.gold, 0)) AS gold, AVG(s.mvp_score) AS mvp
        FROM match_player_stats s JOIN scrim_matches m ON s.match_id = m.match_id
        WHERE m.team_id = ? AND m.match_date >= ? AND m.match_date < ?");
    $s->execute([$team_id, $p['start'], $p['end']]);
    $ps = $s->fetch();

    // เวลาเกมเฉลี่ย (วินาที)
    $secs = [];
    foreach (array_filter(explode(',', (string)$r['durations'])) as $dur) {
        if (preg_match('/^(\d{1,3})(?::(\d{2}))?$/', $dur, $t)) $secs[] = (int)$t[1] * 60 + (int)($t[2] ?? 0);
    }

    $matches = (int)$r['matches'];
    $sm = (int)$ps['stat_matches'];
    return [
        'matches'      => $matches,
        'wins'         => (int)$r['wins'],
        'losses'       => $matches - (int)$r['wins'],
        'win_rate'     => $matches ? (int)$r['wins'] / $matches * 100 : null,
        'kda'          => $sm ? ($ps['k'] + $ps['a']) / max(1, $ps['d']) : null,
        'kills'        => $sm ? $ps['k'] / $sm : null,
        'deaths'       => $sm ? $ps['d'] / $sm : null,
        'gold'         => $ps['gold'] !== null ? (float)$ps['gold'] : null,
        'mvp'          => $ps['mvp'] !== null ? (float)$ps['mvp'] : null,
        'duration'     => $secs ? array_sum($secs) / count($secs) : null,
        'dragons'      => $matches ? (float)$r['dragons'] : null,
        'slayers'      => $matches ? (float)$r['slayers'] : null,
        'towers'       => $matches ? (float)$r['towers'] : null,
        'first_dragon' => $matches ? (float)$r['first_dragon'] : null,
        'first_tower'  => $matches ? (float)$r['first_tower'] : null,
    ];
}

// สถิติรายผู้เล่นในช่วงเวลา (key = member_id)
function period_players(PDO $pdo, $team_id, $p)
{
    $st = $pdo->prepare("SELECT s.member_id, COUNT(*) AS games, SUM(m.match_result = 'WIN') AS wins,
            SUM(s.kills) AS k, SUM(s.deaths) AS d, SUM(s.assists) AS a,
            AVG(NULLIF(s.gold, 0)) AS gold, AVG(s.mvp_score) AS mvp
        FROM match_player_stats s JOIN scrim_matches m ON s.match_id = m.match_id
        WHERE m.team_id = ? AND m.match_date >= ? AND m.match_date < ?
        GROUP BY s.member_id");
    $st->execute([$team_id, $p['start'], $p['end']]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $row['win_rate'] = $row['games'] ? $row['wins'] / $row['games'] * 100 : null;
        $row['kda'] = ($row['k'] + $row['a']) / max(1, $row['d']);
        $out[(int)$row['member_id']] = $row;
    }
    return $out;
}

// ---------- รูปแบบการแสดงผลตัวเลข ----------
function fmt($v, $type)
{
    if ($v === null) return '-';
    switch ($type) {
        case 'pct':  return number_format($v, 1) . '%';
        case 'int':  return number_format($v);
        case 'time': return sprintf('%d:%02d', intdiv((int)round($v), 60), (int)round($v) % 60);
        case 'dec2': return number_format($v, 2);
        default:     return number_format($v, 1);
    }
}

// แสดงการเปลี่ยนแปลง A เทียบกับ B: ลูกศร + ค่า + สี (ดี/แย่) ตามทิศทางที่ดีของตัวชี้วัด
function delta($a, $b, $type, $better)
{
    if ($a === null || $b === null) return '<span style="color:#777;">-</span>';
    // เทียบจากค่าที่ปัดทศนิยมเท่ากับที่แสดงบนหน้าจอ: ตัวเลขที่เห็นต่างกันจะแสดงลูกศรเสมอ
    $dec = ['pct' => 1, 'dec2' => 2, 'int' => 0, 'time' => 0][$type] ?? 1;
    $diff = round($a, $dec) - round($b, $dec);
    if ($diff == 0) return '<span style="color:#aaa;">= เท่าเดิม</span>';
    $arrow = $diff > 0 ? '▲' : '▼';
    $good = $better === 'none' ? null : (($diff > 0) === ($better === 'higher'));
    $color = $good === null ? '#aaa' : ($good ? '#0ca5bd' : '#f03e3e');
    $word = $good === null ? '' : ($good ? ' ดีขึ้น' : ' แย่ลง');
    $val = $type === 'pct' ? number_format(abs($diff), 1) . ' จุด' : ($type === 'time' ? fmt(abs($diff), 'time') . ' นาที' : fmt(abs($diff), $type));
    return '<span style="color:' . $color . '; font-weight:600;">' . $arrow . ' ' . $val . $word . '</span>';
}

// ---------- รายการช่วงเวลาให้เลือก (12 ช่วงล่าสุด) ----------
$options = [];
for ($i = 0; $i < 12; $i++) {
    $p = period_range($mode, period_key($mode, $i));
    $p['name'] = $i === 0 ? ($mode === 'week' ? 'สัปดาห์นี้' : 'เดือนนี้') : ($i === 1 ? ($mode === 'week' ? 'สัปดาห์ที่แล้ว' : 'เดือนที่แล้ว') : '');
    $options[$p['key']] = $p;
}

$key_a = $_GET['a'] ?? period_key($mode, 0);
$key_b = $_GET['b'] ?? period_key($mode, 1);
$pa = period_range($mode, $key_a) ?: $options[period_key($mode, 0)];
$pb = period_range($mode, $key_b) ?: $options[period_key($mode, 1)];
foreach ([$pa, $pb] as $p) {
    if (!isset($options[$p['key']])) $options[$p['key']] = $p + ['name' => ''];
}

$sa = $sb = $pla = $plb = [];
$members = [];
$cur_week = $cur_month = null;
$trend = [];
if ($team_id) {
    $sa = period_stats($pdo, $team_id, $pa);
    $sb = period_stats($pdo, $team_id, $pb);
    $pla = period_players($pdo, $team_id, $pa);
    $plb = period_players($pdo, $team_id, $pb);

    $stmt = $pdo->prepare("SELECT member_id, in_game_name, main_role FROM team_members WHERE team_id = ? ORDER BY member_id");
    $stmt->execute([$team_id]);
    $members = $stmt->fetchAll();

    // การ์ดด่วน: สัปดาห์นี้ / เดือนนี้
    $cur_week = period_stats($pdo, $team_id, period_range('week', period_key('week', 0)));
    $cur_month = period_stats($pdo, $team_id, period_range('month', period_key('month', 0)));

    // แนวโน้มย้อนหลัง 8 สัปดาห์ / 6 เดือน (เก่า -> ใหม่)
    $n = $mode === 'week' ? 8 : 6;
    for ($i = $n - 1; $i >= 0; $i--) {
        $p = period_range($mode, period_key($mode, $i));
        $st = period_stats($pdo, $team_id, $p);
        $trend[] = ['label' => $p['label'], 'wins' => $st['wins'], 'losses' => $st['losses'], 'win_rate' => $st['win_rate']];
    }
}

// ตัวชี้วัดที่ใช้เทียบ: [key, ชื่อ, รูปแบบ, ทิศทางที่ดี]
$metrics = [
    ['matches', 'จำนวนแมตช์ที่ซ้อม', 'int', 'none'],
    ['win_rate', 'Win Rate', 'pct', 'higher'],
    ['kda', 'KDA ทีม ((K+A) ÷ D)', 'dec2', 'higher'],
    ['kills', 'Kill ทีมเฉลี่ยต่อเกม', 'dec', 'higher'],
    ['deaths', 'Death ทีมเฉลี่ยต่อเกม', 'dec', 'lower'],
    ['gold', 'Gold เฉลี่ยต่อคนต่อเกม', 'int', 'higher'],
    ['mvp', 'MVP Score เฉลี่ย', 'dec', 'higher'],
    ['duration', 'เวลาเกมเฉลี่ย', 'time', 'none'],
    ['dragons', 'มังกรต่อเกม', 'dec', 'higher'],
    ['slayers', 'Dark Slayer ต่อเกม', 'dec', 'higher'],
    ['towers', 'ป้อมที่พังต่อเกม', 'dec', 'higher'],
    ['first_dragon', 'ได้มังกรตัวแรก', 'pct', 'higher'],
    ['first_tower', 'ได้ป้อมแรก', 'pct', 'higher'],
];

$card = "background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px;";
$input = "padding: 9px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;";
$tab = function ($m) use ($mode) {
    $on = $m === $mode;
    return "padding: 8px 18px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.9rem; border: 1px solid " . ($on ? '#00e5ff; background: #00e5ff; color: #000;' : '#333; background: #1a1d29; color: #fff;');
};

include '../includes/header.php';
?>

<div style="max-width: 1100px; margin: 30px auto; color: #fff;">
    <h2 style="color: #00e5ff; margin: 0 0 20px 0;"><i class="fa-solid fa-calendar-week"></i> สถิติตามช่วงเวลา<?= $team ? ': ' . e($team['team_name']) : '' ?></h2>

    <?php show_flash(); ?>

    <?php if (!$team): ?>
        <div style="<?= $card ?> color: #aaa;">คุณยังไม่มีทีม <a href="team-manage.php" style="color: #00e5ff;">สร้างทีม</a> ก่อนเพื่อดูสถิติ</div>
    <?php else: ?>

    <!-- การ์ดด่วน สัปดาห์นี้ / เดือนนี้ -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <?php foreach ([['สัปดาห์นี้', $cur_week, period_range('week', period_key('week', 0))], ['เดือนนี้', $cur_month, period_range('month', period_key('month', 0))]] as [$title, $st, $p]): ?>
            <div style="<?= $card ?> margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <h4 style="margin: 0;"><?= $title ?></h4>
                    <small style="color: #777;"><?= e($p['label']) ?></small>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 12px; text-align: center;">
                    <div><div style="color: #aaa; font-size: 0.78rem;">แมตช์</div><div style="font-size: 1.5rem; font-weight: bold;"><?= $st['matches'] ?></div></div>
                    <div><div style="color: #aaa; font-size: 0.78rem;">ชนะ / แพ้</div><div style="font-size: 1.5rem; font-weight: bold;"><?= $st['wins'] ?>W - <?= $st['losses'] ?>L</div></div>
                    <div><div style="color: #aaa; font-size: 0.78rem;">Win Rate</div><div style="font-size: 1.5rem; font-weight: bold; color: #00e5ff;"><?= fmt($st['win_rate'], 'pct') ?></div></div>
                    <div><div style="color: #aaa; font-size: 0.78rem;">KDA ทีม</div><div style="font-size: 1.5rem; font-weight: bold; color: #ffeb3b;"><?= fmt($st['kda'], 'dec2') ?></div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ตัวเลือก: รายสัปดาห์ / รายเดือน และช่วงที่จะเทียบ -->
    <div style="<?= $card ?>">
        <form method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <a href="?mode=week" style="<?= $tab('week') ?>">รายสัปดาห์</a>
            <a href="?mode=month" style="<?= $tab('month') ?>">รายเดือน</a>
            <input type="hidden" name="mode" value="<?= $mode ?>">
            <span style="color: #777; margin-left: 10px;">เทียบ</span>
            <select name="a" style="<?= $input ?>" onchange="this.form.submit()">
                <?php foreach ($options as $k => $o): ?>
                    <option value="<?= e($k) ?>" <?= $k === $pa['key'] ? 'selected' : '' ?>><?= e(($o['name'] ? $o['name'] . ' · ' : '') . $o['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <span style="color: #777;">กับ</span>
            <select name="b" style="<?= $input ?>" onchange="this.form.submit()">
                <?php foreach ($options as $k => $o): ?>
                    <option value="<?= e($k) ?>" <?= $k === $pb['key'] ? 'selected' : '' ?>><?= e(($o['name'] ? $o['name'] . ' · ' : '') . $o['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" style="<?= $input ?>">เทียบ</button></noscript>
        </form>
    </div>

    <!-- ตารางเทียบสถิติทีม -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #00e5ff;"><i class="fa-solid fa-scale-balanced"></i> เปรียบเทียบสถิติทีม</h4>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem; min-width: 600px;">
                <thead>
                    <tr style="border-bottom: 1px solid #333; color: #aaa; text-align: left;">
                        <th style="padding: 10px;">ตัวชี้วัด</th>
                        <th style="padding: 10px; text-align: right;"><span style="color: #fff;">A</span> · <?= e($pa['label']) ?></th>
                        <th style="padding: 10px; text-align: right;"><span style="color: #fff;">B</span> · <?= e($pb['label']) ?></th>
                        <th style="padding: 10px; text-align: right;">A เทียบกับ B</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid #222;">
                        <td style="padding: 10px; color: #ccc;">ชนะ / แพ้</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold;"><?= $sa['wins'] ?>W - <?= $sa['losses'] ?>L</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold;"><?= $sb['wins'] ?>W - <?= $sb['losses'] ?>L</td>
                        <td></td>
                    </tr>
                    <?php foreach ($metrics as [$k, $name, $type, $better]): ?>
                        <tr style="border-bottom: 1px solid #222;">
                            <td style="padding: 10px; color: #ccc;"><?= $name ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: bold;"><?= fmt($sa[$k], $type) ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: bold;"><?= fmt($sb[$k], $type) ?></td>
                            <td style="padding: 10px; text-align: right;"><?= delta($sa[$k], $sb[$k], $type, $better) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!$sa['matches'] || !$sb['matches']): ?>
            <p style="color: #777; font-size: 0.85rem; margin: 12px 0 0 0;">* ช่วงที่ไม่มีแมตช์จะแสดงเป็น "-" และไม่นำมาเทียบ</p>
        <?php endif; ?>
    </div>

    <!-- ตารางเทียบรายผู้เล่น -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #00e5ff;"><i class="fa-solid fa-users"></i> เปรียบเทียบรายผู้เล่น (A / B)</h4>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem; min-width: 820px;">
                <thead>
                    <tr style="border-bottom: 1px solid #333; color: #aaa; text-align: left;">
                        <th style="padding: 10px;">ผู้เล่น</th>
                        <th style="padding: 10px; text-align: center;">เกม</th>
                        <th style="padding: 10px; text-align: center;">Win Rate</th>
                        <th style="padding: 10px; text-align: center;">KDA</th>
                        <th style="padding: 10px; text-align: center;">Gold / เกม</th>
                        <th style="padding: 10px; text-align: center;">MVP เฉลี่ย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <?php
                        $a = $pla[(int)$m['member_id']] ?? null;
                        $b = $plb[(int)$m['member_id']] ?? null;
                        $cell = function ($field, $type, $better) use ($a, $b) {
                            $va = $a ? ($a[$field] !== null ? (float)$a[$field] : null) : null;
                            $vb = $b ? ($b[$field] !== null ? (float)$b[$field] : null) : null;
                            return '<div style="font-weight:bold;">' . fmt($va, $type) . ' <span style="color:#555;">/</span> <span style="color:#aaa; font-weight:normal;">' . fmt($vb, $type) . '</span></div>'
                                . '<div style="font-size:0.78rem;">' . delta($va, $vb, $type, $better) . '</div>';
                        };
                        ?>
                        <tr style="border-bottom: 1px solid #222;">
                            <td style="padding: 10px;">
                                <a href="member-stats.php?member_id=<?= (int)$m['member_id'] ?>" style="color: #fff; font-weight: bold; text-decoration: none;"><?= e($m['in_game_name']) ?></a>
                                <div style="color: #777; font-size: 0.78rem;"><?= e($m['main_role']) ?></div>
                            </td>
                            <td style="padding: 10px; text-align: center;"><b><?= (int)($a['games'] ?? 0) ?></b> <span style="color:#555;">/</span> <span style="color:#aaa;"><?= (int)($b['games'] ?? 0) ?></span></td>
                            <td style="padding: 10px; text-align: center;"><?= $cell('win_rate', 'pct', 'higher') ?></td>
                            <td style="padding: 10px; text-align: center;"><?= $cell('kda', 'dec2', 'higher') ?></td>
                            <td style="padding: 10px; text-align: center;"><?= $cell('gold', 'int', 'higher') ?></td>
                            <td style="padding: 10px; text-align: center;"><?= $cell('mvp', 'dec', 'higher') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$members): ?>
                        <tr><td colspan="6" style="padding: 20px; text-align: center; color: #777;">ยังไม่มีสมาชิกในทีม</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- กราฟแนวโน้ม: จำนวนแมตช์ชนะ/แพ้ต่อช่วงเวลา (Win Rate อยู่ใน tooltip และตาราง) -->
    <div style="<?= $card ?>">
        <h4 style="margin-top: 0; color: #00e5ff;"><i class="fa-solid fa-chart-column"></i> แนวโน้มผลการซ้อม <?= $mode === 'week' ? '8 สัปดาห์ล่าสุด' : '6 เดือนล่าสุด' ?></h4>
        <div style="height: 300px;"><canvas id="trendChart" aria-label="กราฟจำนวนแมตช์ชนะและแพ้ต่อช่วงเวลา" role="img"></canvas></div>
        <details style="margin-top: 12px;">
            <summary style="cursor: pointer; color: #aaa; font-size: 0.85rem;">ดูข้อมูลเป็นตาราง</summary>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 8px;">
                <tr style="color: #aaa; text-align: left;"><th style="padding: 6px;">ช่วงเวลา</th><th style="padding: 6px;">ชนะ</th><th style="padding: 6px;">แพ้</th><th style="padding: 6px;">Win Rate</th></tr>
                <?php foreach ($trend as $t): ?>
                    <tr style="border-top: 1px solid #222;"><td style="padding: 6px;"><?= e($t['label']) ?></td><td style="padding: 6px;"><?= $t['wins'] ?></td><td style="padding: 6px;"><?= $t['losses'] ?></td><td style="padding: 6px;"><?= fmt($t['win_rate'], 'pct') ?></td></tr>
                <?php endforeach; ?>
            </table>
        </details>
    </div>

    <script>
    (function () {
        const trend = <?= json_encode($trend, JSON_UNESCAPED_UNICODE) ?>;
        new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels: trend.map(t => t.label),
                datasets: [
                    { label: 'ชนะ', data: trend.map(t => t.wins), backgroundColor: '#0ca5bd', borderColor: '#12141d', borderWidth: { top: 2 }, borderSkipped: 'bottom', maxBarThickness: 36 },
                    { label: 'แพ้', data: trend.map(t => t.losses), backgroundColor: '#f03e3e', borderColor: '#12141d', borderWidth: { top: 2 }, borderRadius: { topLeft: 4, topRight: 4 }, borderSkipped: 'bottom', maxBarThickness: 36 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { stacked: true, ticks: { color: '#aaa' }, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { color: '#aaa', precision: 0 }, grid: { color: '#222' }, title: { display: true, text: 'จำนวนแมตช์', color: '#aaa' } }
                },
                plugins: {
                    legend: { labels: { color: '#ddd' } },
                    tooltip: {
                        callbacks: {
                            footer: items => {
                                const t = trend[items[0].dataIndex];
                                return t.win_rate === null ? 'ไม่มีแมตช์' : 'Win Rate ' + t.win_rate.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    })();
    </script>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
