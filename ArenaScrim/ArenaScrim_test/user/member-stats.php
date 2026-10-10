<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "สถิติส่วนตัวผู้เล่นรายบุคคล - ArenaScrim";
$page_css = "profile";

// 1. ดึงข้อมูลทีมของผู้ใช้ปัจจุบัน
$team = get_user_team($pdo, $user_id);

$team_id = $team['team_id'] ?? 0;

// 2. ดึงรายชื่อสมาชิกทั้งหมดในทีม เพื่อทำปุ่มสลับดูข้อมูลรายคน (เปลี่ยนมาใช้ in_game_name)
$team_members_list = [];
$target_member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

if ($team_id > 0) {
    $stmt_members = $pdo->prepare("SELECT member_id, user_id, in_game_name AS display_name 
        FROM team_members 
        WHERE team_id = ?");
    $stmt_members->execute([$team_id]);
    $team_members_list = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

    // ดูได้เฉพาะสมาชิกในทีมตัวเอง ถ้าไม่ระบุหรือไม่ใช่สมาชิกในทีม ให้เลือกคนแรก
    $member_ids = array_map('intval', array_column($team_members_list, 'member_id'));
    if (!in_array($target_member_id, $member_ids, true)) {
        $target_member_id = $member_ids[0] ?? 0;
    }
} else {
    $target_member_id = 0;
}

// ตัวแปรเก็บสถิติทั้งหมด
$target_display_name = "ไม่พบข้อมูลสมาชิก";
$wins = 0;
$losses = 0;
$total_matches = 0;
$win_rate = 0;
$avg_kda = ['kills' => 0, 'deaths' => 0, 'assists' => 0];
$total_kda = ['kills' => 0, 'deaths' => 0, 'assists' => 0];
$avg_shares = ['gold' => 0, 'damage' => 0, 'taken' => 0];
$avg_mvp_score = 0;
$top_heroes = [];
$match_history = [];

try {
    if ($target_member_id > 0) {
        foreach ($team_members_list as $m) {
            if ($m['member_id'] == $target_member_id) {
                $target_display_name = $m['display_name'];
                break;
            }
        }

        // ดึงสถิติภาพรวม รวมถึง % เงิน, % ดาเมจ และ MVP Score เฉลี่ย
        $sql_stats = "SELECT 
                    SUM(CASE WHEN m.match_result = 'WIN' THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN m.match_result = 'LOSS' THEN 1 ELSE 0 END) as losses,
                    AVG(s.kills) as avg_k, 
                    AVG(s.deaths) as avg_d, 
                    AVG(s.assists) as avg_a,
                    SUM(s.kills) as sum_k,
                    SUM(s.deaths) as sum_d,
                    SUM(s.assists) as sum_a,
                    AVG(NULLIF(s.gold, 0)) as avg_gold,
                    AVG(s.damage_share) as avg_damage,
                    AVG(s.taken_share) as avg_taken,
                    AVG(s.mvp_score) as avg_mvp
                  FROM match_player_stats s
                  JOIN scrim_matches m ON s.match_id = m.match_id
                  WHERE s.member_id = ?";
                  
        $stmt_stats = $pdo->prepare($sql_stats);
        $stmt_stats->execute([$target_member_id]);
        $p_data = $stmt_stats->fetch(PDO::FETCH_ASSOC);

        if ($p_data && ($p_data['wins'] !== null || $p_data['losses'] !== null)) {
            $wins = (int)($p_data['wins'] ?? 0);
            $losses = (int)($p_data['losses'] ?? 0);
            $avg_kda['kills'] = round($p_data['avg_k'] ?? 0, 1);
            $avg_kda['deaths'] = round($p_data['avg_d'] ?? 0, 1);
            $avg_kda['assists'] = round($p_data['avg_a'] ?? 0, 1);

            $total_kda['kills'] = (int)($p_data['sum_k'] ?? 0);
            $total_kda['deaths'] = (int)($p_data['sum_d'] ?? 0);
            $total_kda['assists'] = (int)($p_data['sum_a'] ?? 0);

            $avg_shares['gold'] = (int)round($p_data['avg_gold'] ?? 0);
            $avg_shares['damage'] = round($p_data['avg_damage'] ?? 0, 1);
            $avg_shares['taken'] = round($p_data['avg_taken'] ?? 0, 1);
            $avg_mvp_score = round($p_data['avg_mvp'] ?? 0, 1);
        }

        $total_matches = $wins + $losses;
        $win_rate = $total_matches > 0 ? round(($wins / $total_matches) * 100, 1) : 0;

        // ดึงฮีโร่ที่ใช้งานบ่อย
        $sql_hero = "SELECT s.hero_name,
                            COUNT(*) as play_count,
                            SUM(s.kills) as total_kills,
                            SUM(s.deaths) as total_deaths,
                            SUM(s.assists) as total_assists
                     FROM match_player_stats s
                     WHERE s.member_id = ? AND s.hero_name <> ''
                     GROUP BY s.hero_name 
                     ORDER BY play_count DESC 
                     LIMIT 5";
                     
        $stmt_hero = $pdo->prepare($sql_hero);
        $stmt_hero->execute([$target_member_id]);
        $top_heroes = $stmt_hero->fetchAll(PDO::FETCH_ASSOC);

        // ดึงประวัติการเล่นย้อนหลังรายบุคคล (เพิ่ม mvp_score)
        $sql_history = "SELECT m.match_id, m.match_date, m.match_result, s.kills, s.deaths, s.assists, 
                               s.gold_share, s.damage_share, s.taken_share, s.mvp_score,
                               s.hero_name
                        FROM match_player_stats s
                        JOIN scrim_matches m ON s.match_id = m.match_id
                        WHERE s.member_id = ?
                        ORDER BY m.match_id DESC";
        $stmt_history = $pdo->prepare($sql_history);
        $stmt_history->execute([$target_member_id]);
        $match_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("member-stats: " . $e->getMessage());
    $db_error = true;
}

include '../includes/header.php';
?>

<div style="max-width: 1000px; margin: 30px auto; color: #fff;">
    
    <!-- ปุ่มย้อนกลับ -->
    <div style="margin-bottom: 20px;">
        <a href="profile.php" style="color: #00e5ff; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; width: fit-content;">
            <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าสถิติภาพรวมทีม
        </a>
    </div>

    <?php if (!$team): ?>
        <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px; color: #aaa;">
            คุณยังไม่มีทีม <a href="team-manage.php" style="color: #00e5ff;">สร้างทีม</a> และเพิ่มสมาชิกก่อนเพื่อดูสถิติรายบุคคล
        </div>
    <?php elseif (count($team_members_list) === 0): ?>
        <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px; color: #aaa;">
            ทีมของคุณยังไม่มีสมาชิก <a href="member-add.php" style="color: #00e5ff;">เพิ่มสมาชิก</a> ก่อนเพื่อดูสถิติรายบุคคล
        </div>
    <?php endif; ?>

    <?php if (isset($db_error)): ?>
        <div style="color: #ff4d4d; margin-bottom: 20px;">เกิดข้อผิดพลาดในการดึงข้อมูล กรุณาลองใหม่อีกครั้ง</div>
    <?php endif; ?>

    <!-- แถบเลือกรายชื่อสมาชิกในทีม -->
    <?php if (count($team_members_list) > 0): ?>
    <div style="background: #12141d; padding: 15px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px;">
        <div style="color: #aaa; font-size: 0.85rem; margin-bottom: 10px;"><i class="fa-solid fa-users"></i> เลือกดูสถิติรายบุคคล:</div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php foreach ($team_members_list as $m): ?>
                <?php $isActive = ($m['member_id'] == $target_member_id); ?>
                <a href="member-stats.php?member_id=<?= $m['member_id'] ?>" 
                   style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: bold; transition: 0.2s;
                   background: <?= $isActive ? '#00e5ff' : '#1a1d29' ?>; 
                   color: <?= $isActive ? '#000' : '#fff' ?>; 
                   border: 1px solid <?= $isActive ? '#00e5ff' : '#333' ?>;">
                   <?= htmlspecialchars($m['display_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ส่วนหัวโปรไฟล์สมาชิก -->
    <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #00e5ff;"><i class="fa-solid fa-user-shield"></i> สถิติส่วนตัว: <?= htmlspecialchars($target_display_name) ?></h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">สังกัดทีม: <?= htmlspecialchars($team['team_name'] ?? 'ไม่มีทีม') ?></p>
        </div>
    </div>

    <!-- สถิติภาพรวม Cards (เพิ่ม MVP Score เฉลี่ย) -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px;">
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">แข่งทั้งหมด</div>
            <div style="font-size: 1.6rem; font-weight: bold; color: #fff;"><?= $total_matches ?></div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">Win Rate</div>
            <div style="font-size: 1.6rem; font-weight: bold; color: #00e5ff;"><?= $win_rate ?>%</div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">MVP Score เฉลี่ย</div>
            <div style="font-size: 1.6rem; font-weight: bold; color: #ffeb3b;"><?= $avg_mvp_score ?></div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">KDA เฉลี่ย</div>
            <div style="font-size: 1.1rem; font-weight: bold; color: #ffeb3b; margin-top: 5px;">
                <?= $avg_kda['kills'] ?> / <?= $avg_kda['deaths'] ?> / <?= $avg_kda['assists'] ?>
            </div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">ชนะ / แพ้</div>
            <div style="font-size: 1.5rem; font-weight: bold;"><span style="color: #4caf50;"><?= $wins ?>W</span> - <span style="color: #f44336;"><?= $losses ?>L</span></div>
        </div>
    </div>

    <!-- เปอร์เซ็นต์ส่วนแบ่งทีม (Gold %, Damage Dealt %, Damage Taken %) -->
    <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 25px;">
        <h4 style="margin-top: 0; color: #fff; margin-bottom: 15px;"><i class="fa-solid fa-chart-line"></i> ค่าเฉลี่ย( Gold / Damage Dealt / Damage Taken)</h4>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center;">
            <div style="background: #1a1d29; padding: 15px; border-radius: 8px; border: 1px solid #333;">
                <div style="color: #aaa; font-size: 0.85rem;"><i class="fa-solid fa-coins" style="color: #ffeb3b;"></i> เงินเฉลี่ยต่อเกม (Avg Gold)</div>
                <div style="font-size: 1.6rem; font-weight: bold; color: #ffeb3b; margin-top: 8px;"><?= number_format($avg_shares['gold']) ?> <span style="font-size: 0.9rem; color: #aaa;">/ เกม</span></div>
            </div>
            <div style="background: #1a1d29; padding: 15px; border-radius: 8px; border: 1px solid #333;">
                <div style="color: #aaa; font-size: 0.85rem;"><i class="fa-solid fa-fire" style="color: #00e5ff;"></i> % ดาเมจที่ทำเฉลี่ย (Damage Share)</div>
                <div style="font-size: 1.6rem; font-weight: bold; color: #00e5ff; margin-top: 8px;"><?= $avg_shares['damage'] ?>%</div>
            </div>
            <div style="background: #1a1d29; padding: 15px; border-radius: 8px; border: 1px solid #333;">
                <div style="color: #aaa; font-size: 0.85rem;"><i class="fa-solid fa-shield-heart" style="color: #ff4d4d;"></i> % ดาเมจที่รับเฉลี่ย (Taken Share)</div>
                <div style="font-size: 1.6rem; font-weight: bold; color: #ff4d4d; margin-top: 8px;"><?= $avg_shares['taken'] ?>%</div>
            </div>
        </div>
    </div>

    <!-- สถิติภาพรวม Cards (ปรับเปลี่ยนส่วนยอดสะสมให้เป็นค่าเฉลี่ย) -->
    <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 25px;">
        <h4 style="margin-top: 0; color: #fff; margin-bottom: 15px;"><i class="fa-solid fa-chart-pie"></i> ค่าเฉลี่ยผลงานต่อเกม (Average Stats Per Match)</h4>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center;">
            <div style="background: #1a1d29; padding: 12px; border-radius: 8px; border: 1px solid #333;">
                <div style="color: #aaa; font-size: 0.85rem;">Kills เฉลี่ยต่อเกม</div>
                <div style="font-size: 1.4rem; font-weight: bold; color: #00e5ff; margin-top: 5px;"><?= $avg_kda['kills'] ?> ครั้ง</div>
            </div>
            <div style="background: #1a1d29; padding: 12px; border-radius: 8px; border: 1px solid #333;">
                <div style="color: #aaa; font-size: 0.85rem;">Deaths เฉลี่ยต่อเกม</div>
                <div style="font-size: 1.4rem; font-weight: bold; color: #ff4d4d; margin-top: 5px;"><?= $avg_kda['deaths'] ?> ครั้ง</div>
            </div>
            <div style="background: #1a1d29; padding: 12px; border-radius: 8px; border: 1px solid #333;">
                <div style="color: #aaa; font-size: 0.85rem;">Assists เฉลี่ยต่อเกม</div>
                <div style="font-size: 1.4rem; font-weight: bold; color: #ffeb3b; margin-top: 5px;"><?= $avg_kda['assists'] ?> ครั้ง</div>
            </div>
        </div>
    </div>

    <!-- ส่วนแสดงกราฟ -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
        <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; text-align: center;">
            <h4 style="margin-top: 0; color: #fff;">อัตราการแพ้ / ชนะ (Win / Loss Ratio)</h4>
            <div style="width: 250px; margin: 0 auto;">
                <canvas id="winRateChart"></canvas>
            </div>
        </div>
        <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; text-align: center;">
            <h4 style="margin-top: 0; color: #fff;">สถิติฮีโร่ที่ใช้งานบ่อยที่สุด</h4>
            <div>
                <canvas id="heroChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ตารางประวัติการเล่นย้อนหลังรายบุคคล (เพิ่มคอลัมน์ MVP Score) -->
    <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: #fff; margin-bottom: 15px;"><i class="fa-solid fa-history"></i> ประวัติการเล่นย้อนหลังรายบุคคลของ <?= htmlspecialchars($target_display_name) ?></h4>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #333; color: #aaa;">
                        <th style="padding: 10px;">แมตช์ ID</th>
                        <th style="padding: 10px;">ผลการแข่งขัน</th>
                        <th style="padding: 10px;">ฮีโร่ที่ใช้</th>
                        <th style="padding: 10px;">K / D / A</th>
                        <th style="padding: 10px;">MVP Score</th>
                        <th style="padding: 10px;">% เงิน</th>
                        <th style="padding: 10px;">% ดาเมจทำ</th>
                        <th style="padding: 10px;">% ดาเมจรับ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($match_history) > 0): ?>
                        <?php foreach ($match_history as $row): ?>
                            <tr style="border-bottom: 1px solid #222;">
                                <td style="padding: 10px; color: #00e5ff;">#<?= $row['match_id'] ?></td>
                                <td style="padding: 10px;">
                                    <?php if ($row['match_result'] === 'WIN'): ?>
                                        <span style="color: #4caf50; font-weight: bold;">WIN</span>
                                    <?php else: ?>
                                        <span style="color: #f44336; font-weight: bold;">LOSS</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;"><?= htmlspecialchars($row['hero_name'] ?? '-') ?></td>
                                <td style="padding: 10px; color: #ffeb3b; font-weight: bold;"><?= $row['kills'] ?> / <?= $row['deaths'] ?> / <?= $row['assists'] ?></td>
                                <td style="padding: 10px; color: #ffeb3b; font-weight: bold;"><?= number_format($row['mvp_score'] ?? 0, 1) ?></td>
                                <td style="padding: 10px; color: #ffeb3b;"><?= number_format($row['gold_share'] ?? 0, 1) ?>%</td>
                                <td style="padding: 10px; color: #00e5ff;"><?= number_format($row['damage_share'] ?? 0, 1) ?>%</td>
                                <td style="padding: 10px; color: #ff4d4d;"><?= number_format($row['taken_share'] ?? 0, 1) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="padding: 20px; text-align: center; color: #aaa;">ยังไม่มีประวัติการแข่งขันสำหรับสมาชิกคนนี้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script วาดกราฟ Chart.js -->
<script>
const ctxWin = document.getElementById('winRateChart').getContext('2d');
new Chart(ctxWin, {
    type: 'doughnut',
    data: {
        labels: ['ชนะ (WIN)', 'แพ้ (LOSS)'],
        datasets: [{
            data: [<?= $wins ?>, <?= $losses ?>],
            backgroundColor: ['#00e5ff', '#ff4d4d'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: '#fff' } }
        }
    }
});

const heroNames = <?= json_encode(array_column($top_heroes, 'hero_name')) ?>;
const heroKills = <?= json_encode(array_map('intval', array_column($top_heroes, 'total_kills'))) ?>;
const heroDeaths = <?= json_encode(array_map('intval', array_column($top_heroes, 'total_deaths'))) ?>;

const ctxHero = document.getElementById('heroChart').getContext('2d');
new Chart(ctxHero, {
    type: 'bar',
    data: {
        labels: heroNames.length > 0 ? heroNames : ['ไม่มีข้อมูล'],
        datasets: [
            {
                label: 'ยอด Kills รวม',
                data: heroKills.length > 0 ? heroKills : [0],
                backgroundColor: '#00e5ff',
                borderColor: '#00e5ff',
                borderWidth: 1
            },
            {
                label: 'ยอด Deaths รวม',
                data: heroDeaths.length > 0 ? heroDeaths : [0],
                backgroundColor: '#ff4d4d',
                borderColor: '#ff4d4d',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0, color: '#aaa' } },
            x: { ticks: { color: '#aaa' } }
        },
        plugins: {
            legend: { labels: { color: '#fff' } }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>