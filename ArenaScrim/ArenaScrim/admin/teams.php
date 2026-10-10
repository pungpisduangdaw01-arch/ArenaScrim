<?php
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ เข้าได้เฉพาะ Role 'admin' เท่านั้น
require_admin();

$page_title = "จัดการทีม - ArenaScrim";
$page_css = "admin";

// ค้นหาจากชื่อทีม / ตัวย่อ / หัวหน้าทีม
$search = trim($_GET['q'] ?? '');
$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE t.team_name LIKE ? OR t.team_tag LIKE ? OR u.username LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// รายชื่อทีม พร้อมหัวหน้าทีม จำนวนสมาชิก แมตช์ และผลชนะ
$stmt = $pdo->prepare("
    SELECT t.*, u.username AS leader_name,
           (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.team_id) AS member_count,
           (SELECT COUNT(*) FROM scrim_matches m WHERE m.team_id = t.team_id) AS match_count,
           (SELECT COUNT(*) FROM scrim_matches m WHERE m.team_id = t.team_id AND m.match_result = 'WIN') AS win_count,
           (SELECT MAX(m.match_date) FROM scrim_matches m WHERE m.team_id = t.team_id) AS last_match
    FROM teams t
    LEFT JOIN users u ON t.leader_id = u.user_id
    $where
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$teams = $stmt->fetchAll();

$input = "padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;";

// แบ่งหน้าตาราง
[$teams_page, $page, $pages] = paginate_rows($teams);

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <h2 style="color: var(--neon-red); margin-bottom: 20px;"><i class="fa-solid fa-shield-halved"></i> จัดการทีม</h2>

    <?php show_flash(); ?>

    <div class="arena-card">
        <form method="GET" style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="ค้นหาชื่อทีม / ตัวย่อ / โค้ช..." style="flex: 1; min-width: 220px; <?= $input ?>">
            <button type="submit" class="btn-cyber" style="padding: 10px 18px;">ค้นหา</button>
            <?php if ($search !== ''): ?>
                <a href="teams.php" style="color: var(--text-muted); align-self: center;">ล้างตัวกรอง</a>
            <?php endif; ?>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อทีม</th>
                    <th>โค้ช</th>
                    <th>สมาชิก</th>
                    <th>แมตช์ (ชนะ)</th>
                    <th>ซ้อมล่าสุด</th>
                    <th>วันที่สร้าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teams)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">ไม่พบทีมตามเงื่อนไข</td></tr>
                <?php endif; ?>
                <?php foreach ($teams_page as $t): ?>
                    <tr>
                        <td><?= (int)$t['team_id'] ?></td>
                        <td style="font-weight: 600;"><a href="team-detail.php?id=<?= (int)$t['team_id'] ?>" style="color: var(--text-main); text-decoration: none;"><?= e($t['team_name']) ?> <span style="color: var(--neon-cyan);">[<?= e($t['team_tag']) ?>]</span> <i class="fa-solid fa-magnifying-glass" style="font-size: 0.75rem; color: var(--text-muted);"></i></a></td>
                        <td style="color: var(--neon-cyan);">@<?= e($t['leader_name']) ?></td>
                        <td><?= (int)$t['member_count'] ?></td>
                        <td><?= (int)$t['match_count'] ?> <span style="color: var(--text-muted);">(<?= (int)$t['win_count'] ?>)</span></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= $t['last_match'] ? date('d/m/Y', strtotime($t['last_match'])) : '-' ?></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
                        <td>
                            <?= post_button('../actions/admin-process.php?action=delete_team', ['id' => (int)$t['team_id']],
                                '<i class="fa-solid fa-trash"></i> ลบ', 'color: var(--neon-red);',
                                "ยืนยันลบทีมนี้?\nสมาชิก ผลการซ้อม และประกาศ Scrim ของทีมจะถูกลบทั้งหมด") ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($page, $pages) ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
