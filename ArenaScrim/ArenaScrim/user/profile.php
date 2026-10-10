<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "โปรไฟล์ & สถิติ - ArenaScrim";
$page_css = "profile";
$display_name = $_SESSION['display_name'] ?? '';

// 1. ดึงข้อมูลทีมของผู้ใช้ปัจจุบัน
$team = get_user_team($pdo, $user_id);
$team_id = $team['team_id'] ?? 0;
$is_leader = $team && (int)$team['leader_id'] === $user_id;

$wins = 0;
$losses = 0;
$total_matches = 0;
$win_rate = 0;
$avg_kda = ['kills' => 0, 'deaths' => 0, 'assists' => 0];
$top_heroes = [];
$match_history = [];

if ($team_id > 0) {
    // ผลแพ้/ชนะนับจากแมตช์ของทีม (1 แมตช์ = 1 ครั้ง)
    $stmt = $pdo->prepare("SELECT
            SUM(match_result = 'WIN') AS wins,
            SUM(match_result = 'LOSS') AS losses
        FROM scrim_matches WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $r = $stmt->fetch();
    $wins = (int)($r['wins'] ?? 0);
    $losses = (int)($r['losses'] ?? 0);
    $total_matches = $wins + $losses;
    $win_rate = $total_matches > 0 ? round(($wins / $total_matches) * 100, 1) : 0;

    // KDA เฉลี่ยต่อผู้เล่นต่อเกม
    $stmt = $pdo->prepare("SELECT AVG(s.kills) AS avg_k, AVG(s.deaths) AS avg_d, AVG(s.assists) AS avg_a
        FROM match_player_stats s
        JOIN scrim_matches m ON s.match_id = m.match_id
        WHERE m.team_id = ?");
    $stmt->execute([$team_id]);
    $k = $stmt->fetch();
    $avg_kda['kills'] = round((float)($k['avg_k'] ?? 0), 1);
    $avg_kda['deaths'] = round((float)($k['avg_d'] ?? 0), 1);
    $avg_kda['assists'] = round((float)($k['avg_a'] ?? 0), 1);

    // ฮีโร่ที่ใช้งานบ่อยที่สุดของทีม
    $stmt = $pdo->prepare("SELECT s.hero_name, COUNT(*) AS play_count,
            SUM(s.kills) AS total_kills, SUM(s.deaths) AS total_deaths
        FROM match_player_stats s
        JOIN scrim_matches m ON s.match_id = m.match_id
        WHERE m.team_id = ? AND s.hero_name <> ''
        GROUP BY s.hero_name
        ORDER BY play_count DESC
        LIMIT 5");
    $stmt->execute([$team_id]);
    $top_heroes = $stmt->fetchAll();

    // ประวัติ 10 แมตช์ล่าสุดของทีม พร้อม K/D/A รวมของทีม
    $stmt = $pdo->prepare("SELECT m.match_id, m.match_date, m.opponent_name, m.match_result,
            COALESCE(SUM(s.kills), 0) AS kills, COALESCE(SUM(s.deaths), 0) AS deaths, COALESCE(SUM(s.assists), 0) AS assists
        FROM scrim_matches m
        LEFT JOIN match_player_stats s ON s.match_id = m.match_id
        WHERE m.team_id = ?
        GROUP BY m.match_id
        ORDER BY m.match_date DESC, m.match_id DESC
        LIMIT 10");
    $stmt->execute([$team_id]);
    $match_history = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<div style="max-width: 1000px; margin: 30px auto; color: #fff;">
    <?php show_flash(); ?>
    <!-- ส่วนหัวโปรไฟล์ทีม -->
    <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #00e5ff;"><i class="fa-solid fa-shield-halved"></i> สถิติภาพรวมทีม: <?= e($team['team_name'] ?? 'ยังไม่มีทีม') ?></h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">ผู้เล่นในระบบ: <?= htmlspecialchars($display_name) ?></p>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php if (!$team): ?>
                <a href="team-manage.php" class="btn-cyber" style="padding: 10px 18px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-users"></i> สร้างทีม
                </a>
            <?php endif; ?>
            <?php if ($team_id > 0): ?>
                <!-- ปุ่มไปหน้าดูสถิติผู้เล่นรายบุคคล -->
                <a href="member-stats.php" class="btn-cyber" style="padding: 10px 18px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-users-viewfinder"></i> สถิติผู้เล่น
                </a>
                <a href="period-stats.php" class="btn-cyber" style="padding: 10px 18px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-calendar-week"></i> เทียบสัปดาห์/เดือน
                </a>
                <?php if ($is_leader): ?>
                <a href="match-add.php" class="btn-cyber" style="padding: 10px 18px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-plus"></i> บันทึกผลการซ้อม
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- สถิติภาพรวม Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">แข่งทั้งหมด</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #fff;"><?= $total_matches ?></div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">อัตราการชนะ (Win Rate)</div>
            <div style="font-size: 1.8rem; font-weight: bold; color: #00e5ff;"><?= $win_rate ?>%</div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">KDA เฉลี่ยต่อผู้เล่น</div>
            <div style="font-size: 1.2rem; font-weight: bold; color: #ffeb3b; margin-top: 5px;">
                <?= $avg_kda['kills'] ?> / <?= $avg_kda['deaths'] ?> / <?= $avg_kda['assists'] ?>
            </div>
        </div>
        <div style="background: #12141d; padding: 15px; border-radius: 8px; border: 1px solid #222; text-align: center;">
            <div style="color: #aaa; font-size: 0.85rem;">ชนะ / แพ้</div>
            <div style="font-size: 1.8rem; font-weight: bold;"><span style="color: #4caf50;"><?= $wins ?>W</span> - <span style="color: #f44336;"><?= $losses ?>L</span></div>
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
            <h4 style="margin-top: 0; color: #fff;">ฮีโร่ที่ทีมใช้บ่อยที่สุด (Kills vs Deaths)</h4>
            <div>
                <canvas id="heroChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ตารางประวัติการเล่นย้อนหลัง -->
    <div style="background: #12141d; padding: 20px; border-radius: 12px; border: 1px solid #222; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h4 style="margin: 0; color: #fff;"><i class="fa-solid fa-history"></i> ประวัติการแข่งขันล่าสุดของทีม</h4>
            <a href="match-history.php" style="color: #00e5ff; font-size: 0.85rem; text-decoration: none;">ดูทั้งหมด <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #333; color: #aaa;">
                        <th style="padding: 10px;">แมตช์ ID</th>
                        <th style="padding: 10px;">วันที่</th>
                        <th style="padding: 10px;">คู่แข่ง</th>
                        <th style="padding: 10px;">ผลการแข่งขัน</th>
                        <th style="padding: 10px;">K / D / A รวมทีม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($match_history) > 0): ?>
                        <?php foreach ($match_history as $row): ?>
                            <tr style="border-bottom: 1px solid #222;">
                                <td style="padding: 10px; color: #00e5ff;">#<?= (int)$row['match_id'] ?></td>
                                <td style="padding: 10px; color: #aaa;"><?= date('d/m/Y H:i', strtotime($row['match_date'])) ?></td>
                                <td style="padding: 10px;"><a href="match-detail.php?id=<?= (int)$row['match_id'] ?>" style="color: #fff;"><?= e($row['opponent_name']) ?></a></td>
                                <td style="padding: 10px;">
                                    <?php if ($row['match_result'] === 'WIN'): ?>
                                        <span style="color: #4caf50; font-weight: bold;">WIN</span>
                                    <?php else: ?>
                                        <span style="color: #f44336; font-weight: bold;">LOSS</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px; color: #ffeb3b;"><?= (int)$row['kills'] ?> / <?= (int)$row['deaths'] ?> / <?= (int)$row['assists'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: #aaa;">ยังไม่มีประวัติการแข่งขันของทีมนี้</td>
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