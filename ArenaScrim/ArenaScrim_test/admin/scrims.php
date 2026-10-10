<?php
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ เข้าได้เฉพาะ Role 'admin' เท่านั้น
require_admin();

$page_title = "จัดการประกาศ Scrim - ArenaScrim";
$page_css = "admin";

// ปรับประกาศที่เลยเวลาซ้อมแล้วให้เป็น "หมดเวลา" (เหมือนหน้าตลาดหาทีมซ้อม)
$pdo->exec("UPDATE scrim_posts SET status = 'expired' WHERE status = 'open' AND scrim_time < NOW()");

$STATUS = ['open' => 'เปิดรับ', 'matched' => 'ได้ทีมแล้ว', 'expired' => 'หมดเวลา'];
$status_filter = array_key_exists($_GET['status'] ?? '', $STATUS) ? $_GET['status'] : '';

$stmt = $pdo->prepare("
    SELECT sp.*, t.team_name, t.team_tag, u.username AS leader_name
    FROM scrim_posts sp
    JOIN teams t ON sp.team_id = t.team_id
    LEFT JOIN users u ON t.leader_id = u.user_id
    " . ($status_filter ? "WHERE sp.status = ?" : '') . "
    ORDER BY sp.created_at DESC
");
$stmt->execute($status_filter ? [$status_filter] : []);
$scrims = $stmt->fetchAll();

// จำนวนประกาศในแต่ละสถานะ
$counts = array_fill_keys(array_keys($STATUS), 0);
foreach ($pdo->query("SELECT status, COUNT(*) AS n FROM scrim_posts GROUP BY status") as $r) {
    $counts[$r['status']] = (int)$r['n'];
}
$chip = function ($active) {
    return "padding: 6px 14px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; border: 1px solid "
        . ($active ? "var(--neon-cyan); background: rgba(0,229,255,0.12); color: var(--neon-cyan);" : "var(--border-color); color: var(--text-muted);");
};

// แบ่งหน้าตาราง
[$scrims_page, $page, $pages] = paginate_rows($scrims);

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <h2 style="color: var(--neon-red); margin-bottom: 20px;"><i class="fa-solid fa-list-check"></i> จัดการประกาศหาทีมซ้อม (Scrim)</h2>

    <?php show_flash(); ?>

    <div class="arena-card">
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
            <a href="scrims.php" style="<?= $chip(!$status_filter) ?>">ทั้งหมด (<?= array_sum($counts) ?>)</a>
            <?php foreach ($STATUS as $k => $label): ?>
                <a href="?status=<?= $k ?>" style="<?= $chip($status_filter === $k) ?>"><?= $label ?> (<?= $counts[$k] ?>)</a>
            <?php endforeach; ?>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ทีม</th>
                    <th>ผู้โพสต์</th>
                    <th>Rank</th>
                    <th>รูปแบบ</th>
                    <th>สถานะ</th>
                    <th>เวลาซ้อม</th>
                    <th>หมายเหตุ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$scrims): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted);">ไม่มีประกาศหาทีมซ้อม</td></tr>
                <?php endif; ?>
                <?php foreach ($scrims_page as $s): ?>
                    <tr>
                        <td><?= (int)$s['post_id'] ?></td>
                        <td style="font-weight: 600; color: var(--text-main);"><?= e($s['team_name']) ?> <span style="color: var(--neon-cyan);">[<?= e($s['team_tag']) ?>]</span></td>
                        <td style="color: var(--neon-cyan);">@<?= e($s['leader_name']) ?></td>
                        <td style="color: var(--neon-gold);"><?= e($s['avg_rank']) ?></td>
                        <td><?= e($s['match_format']) ?></td>
                        <td>
                            <span style="font-weight: 600; color: <?= $s['status'] === 'open' ? 'var(--neon-cyan)' : 'var(--text-muted)' ?>;">
                                <?= $STATUS[$s['status']] ?? e($s['status']) ?>
                            </span>
                        </td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('d/m/Y H:i', strtotime($s['scrim_time'])) ?></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem; max-width: 220px;"><?= $s['note'] ? e(mb_strimwidth($s['note'], 0, 60, '...')) : '-' ?></td>
                        <td>
                            <?= post_button('../actions/admin-process.php?action=delete_scrim', ['id' => (int)$s['post_id']],
                                '<i class="fa-solid fa-trash"></i> ลบ', 'color: var(--neon-red);', 'ยืนยันลบประกาศนี้?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($page, $pages) ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
