<?php
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ เข้าได้เฉพาะ Role 'admin' เท่านั้น (หน้านี้ดูได้อย่างเดียว)
require_admin();

$team_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, u.username AS coach_username, u.display_name AS coach_name
    FROM teams t LEFT JOIN users u ON t.leader_id = u.user_id WHERE t.team_id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();
if (!$team) {
    redirect_with("teams.php", 'error', "ไม่พบทีมนี้");
}

$page_title = "รายละเอียดทีม " . $team['team_name'] . " - ArenaScrim";
$page_css = "admin";

// รายชื่อผู้เล่น (แสดงเฉพาะข้อมูลในเกม ไม่แสดงข้อมูลส่วนบุคคล)
$stmt = $pdo->prepare("SELECT tm.member_id, tm.in_game_name, tm.main_role, tm.current_rank,
        COUNT(s.stat_id) AS games, SUM(s.kills) AS k, SUM(s.deaths) AS d, SUM(s.assists) AS a
    FROM team_members tm LEFT JOIN match_player_stats s ON s.member_id = tm.member_id
    WHERE tm.team_id = ? GROUP BY tm.member_id ORDER BY tm.member_id");
$stmt->execute([$team_id]);
$members = $stmt->fetchAll();

// ผลการซ้อมทั้งหมดของทีม (ใหม่ -> เก่า)
$stmt = $pdo->prepare("SELECT m.match_id, m.match_date, m.opponent_name, m.match_type, m.game_number, m.match_result,
        COALESCE(SUM(s.kills), 0) AS k, COALESCE(SUM(s.deaths), 0) AS d, COALESCE(SUM(s.assists), 0) AS a
    FROM scrim_matches m LEFT JOIN match_player_stats s ON s.match_id = m.match_id
    WHERE m.team_id = ? GROUP BY m.match_id ORDER BY m.match_date DESC, m.match_id DESC");
$stmt->execute([$team_id]);
$matches = $stmt->fetchAll();

$wins = count(array_filter($matches, function ($m) { return $m['match_result'] === 'WIN'; }));
$total = count($matches);
$win_rate = $total ? round($wins / $total * 100, 1) : 0;

// ประกาศหาทีมซ้อมของทีม
$stmt = $pdo->prepare("SELECT post_id, avg_rank, match_format, scrim_time, status FROM scrim_posts WHERE team_id = ? ORDER BY scrim_time DESC");
$stmt->execute([$team_id]);
$posts = $stmt->fetchAll();

[$matches_page, $page, $pages] = paginate_rows($matches);
$box = "background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; text-align: center;";

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <a href="teams.php" style="color: var(--neon-cyan); text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-arrow-left"></i> กลับหน้าจัดการทีม</a>
    <h2 style="color: var(--neon-red); margin: 10px 0 6px 0;"><i class="fa-solid fa-shield-halved"></i> <?= e($team['team_name']) ?> <span style="color: var(--neon-cyan);">[<?= e($team['team_tag']) ?>]</span></h2>
    <p style="color: var(--text-muted); margin: 0 0 20px 0; font-size: 0.9rem;">
        โค้ช: <?= $team['coach_username'] ? '@' . e($team['coach_username']) . ' (' . e($team['coach_name']) . ')' : '<span style="color: var(--neon-red);">ไม่มีโค้ช</span>' ?>
        · สร้างเมื่อ <?= date('d/m/Y', strtotime($team['created_at'])) ?>
        · <i class="fa-solid fa-eye"></i> หน้านี้ดูได้อย่างเดียว
    </p>

    <?php show_flash(); ?>

    <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom: 20px;">
        <div style="<?= $box ?>"><div style="color: var(--text-muted); font-size: 0.8rem;">ผู้เล่น</div><div style="font-size: 1.5rem; font-weight: bold;"><?= count($members) ?></div></div>
        <div style="<?= $box ?>"><div style="color: var(--text-muted); font-size: 0.8rem;">ผลการซ้อม (เกม)</div><div style="font-size: 1.5rem; font-weight: bold;"><?= $total ?></div></div>
        <div style="<?= $box ?>"><div style="color: var(--text-muted); font-size: 0.8rem;">ชนะ / แพ้</div><div style="font-size: 1.5rem; font-weight: bold;"><?= $wins ?>W - <?= $total - $wins ?>L</div></div>
        <div style="<?= $box ?>"><div style="color: var(--text-muted); font-size: 0.8rem;">Win Rate</div><div style="font-size: 1.5rem; font-weight: bold; color: var(--neon-cyan);"><?= $win_rate ?>%</div></div>
        <div style="<?= $box ?>"><div style="color: var(--text-muted); font-size: 0.8rem;">ประกาศหาทีมซ้อม</div><div style="font-size: 1.5rem; font-weight: bold;"><?= count($posts) ?></div></div>
    </div>

    <div class="arena-card" style="margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: var(--text-main);"><i class="fa-solid fa-users"></i> รายชื่อผู้เล่น</h3>
        <table class="admin-table">
            <thead><tr><th>ชื่อในเกม</th><th>ตำแหน่ง</th><th>แรงก์</th><th>ลงเล่น (เกม)</th><th>K / D / A รวม</th></tr></thead>
            <tbody>
                <?php if (!$members): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">ยังไม่มีผู้เล่น</td></tr>
                <?php endif; ?>
                <?php foreach ($members as $m): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--text-main);"><?= e($m['in_game_name']) ?></td>
                        <td><?= e(MEMBER_ROLES[$m['main_role']] ?? $m['main_role'] ?: '-') ?></td>
                        <td><?= e($m['current_rank'] ?: '-') ?></td>
                        <td><?= (int)$m['games'] ?></td>
                        <td><?= (int)$m['k'] ?> / <?= (int)$m['d'] ?> / <?= (int)$m['a'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="arena-card" style="margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: var(--text-main);"><i class="fa-solid fa-clock-rotate-left"></i> ผลการซ้อม</h3>
        <table class="admin-table">
            <thead><tr><th>วันที่</th><th>คู่แข่ง</th><th>รูปแบบ</th><th>ผล</th><th>K / D / A ทีม</th><th></th></tr></thead>
            <tbody>
                <?php if (!$matches): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">ยังไม่มีผลการซ้อม</td></tr>
                <?php endif; ?>
                <?php foreach ($matches_page as $m): ?>
                    <tr>
                        <td style="color: var(--text-muted); white-space: nowrap;"><?= date('d/m/Y H:i', strtotime($m['match_date'])) ?></td>
                        <td style="font-weight: 600;"><?= e($m['opponent_name']) ?></td>
                        <td><?= e($m['match_type']) ?> (เกม <?= (int)$m['game_number'] ?>)</td>
                        <td style="font-weight: 600; color: <?= $m['match_result'] === 'WIN' ? '#0ca5bd' : '#f03e3e' ?>;"><?= e($m['match_result']) ?></td>
                        <td><?= (int)$m['k'] ?> / <?= (int)$m['d'] ?> / <?= (int)$m['a'] ?></td>
                        <td><a href="../user/match-detail.php?id=<?= (int)$m['match_id'] ?>" style="color: var(--neon-cyan); text-decoration: none;"><i class="fa-solid fa-eye"></i> ดู</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($page, $pages) ?>
    </div>

    <div class="arena-card">
        <h3 style="margin-top: 0; color: var(--text-main);"><i class="fa-solid fa-bullhorn"></i> ประกาศหาทีมซ้อม</h3>
        <table class="admin-table">
            <thead><tr><th>เวลาซ้อม</th><th>Rank</th><th>รูปแบบ</th><th>สถานะ</th></tr></thead>
            <tbody>
                <?php if (!$posts): ?>
                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">ไม่มีประกาศ</td></tr>
                <?php endif; ?>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($p['scrim_time'])) ?></td>
                        <td><?= e($p['avg_rank']) ?></td>
                        <td><?= e($p['match_format']) ?></td>
                        <td><?= e(['open' => 'เปิดรับ', 'matched' => 'ได้ทีมแล้ว', 'expired' => 'หมดเวลา'][$p['status']] ?? $p['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
