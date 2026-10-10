<?php
session_start();
require_once '../config/db.php';

$page_title = "ตลาดหาทีมซ้อม - ArenaScrim";
$page_css = "scrim";

// ปรับประกาศที่เลยเวลาซ้อมแล้วให้เป็น "หมดเวลา" อัตโนมัติ
$pdo->exec("UPDATE scrim_posts SET status = 'expired' WHERE status = 'open' AND scrim_time < NOW()");

// ตัวกรองสถานะ (ค่าเริ่มต้นแสดงเฉพาะที่เปิดรับ)
$filter = $_GET['filter'] ?? 'open';
$where = $filter === 'all' ? '' : "WHERE sp.status = 'open'";

// ดึงรายการโพสต์หาทีมซ้อม พร้อมดึงข้อมูลทีมและผู้นำทีม (leader_id)
$stmt = $pdo->query("
    SELECT sp.*, t.team_name, t.team_tag, t.discord_link, t.facebook_link, t.line_id, t.leader_id, u.display_name as poster_name
    FROM scrim_posts sp
    JOIN teams t ON sp.team_id = t.team_id
    LEFT JOIN users u ON t.leader_id = u.user_id
    $where
    ORDER BY sp.status = 'open' DESC, sp.scrim_time ASC
");
$scrims = $stmt->fetchAll();

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <div class="scrim-header">
        <div>
            <h2 style="margin: 0; color: var(--text-main);"><i class="fa-solid fa-gamepad" style="color: var(--neon-cyan);"></i> ตลาดหาทีมซ้อม (Scrim Lobby)</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">พบคู่ซ้อมฝีมือทัดเทียม นัดหมายแข่งด่วนได้ทันที</p>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="create.php" class="btn-cyber"><i class="fa-solid fa-plus"></i> โพสต์หาทีมซ้อม</a>
        <?php endif; ?>
    </div>

    <?php show_flash(); ?>

    <div style="margin-bottom: 15px; display: flex; gap: 10px; font-size: 0.9rem;">
        <a href="?filter=open" style="color: <?= $filter === 'all' ? 'var(--text-muted)' : 'var(--neon-cyan)' ?>;">เฉพาะที่เปิดรับ</a>
        <span style="color: var(--text-muted);">|</span>
        <a href="?filter=all" style="color: <?= $filter === 'all' ? 'var(--neon-cyan)' : 'var(--text-muted)' ?>;">ทั้งหมด</a>
    </div>

    <div class="scrim-grid">
        <?php if (empty($scrims)): ?>
            <div class="arena-card" style="grid-column: 1 / -1; text-align: center; color: var(--text-muted);">
                ขณะนี้ยังไม่มีประกาศหาทีมซ้อม คุณสามารถเป็นทีมแรกที่ประกาศได้!
            </div>
        <?php else: ?>
            <?php foreach ($scrims as $scrim): ?>
                <div class="scrim-card">
                    
                    <!-- ส่วนแสดงชื่อผู้โพสต์ -->
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px; border-bottom: 1px solid #222; padding-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa-solid fa-user"></i> โพสต์โดย: <strong style="color: var(--neon-cyan);"><?= htmlspecialchars($scrim['poster_name'] ?? 'Unknown') ?></strong></span>
                        <span style="font-size: 0.75rem; color: #777;"><?= date('d/m/Y H:i', strtotime($scrim['created_at'])) ?></span>
                    </div>

                    <div class="scrim-card-header">
                        <div>
                            <h3 class="team-name-title">
                                <?= htmlspecialchars($scrim['team_name']) ?>
                                <span style="font-size: 0.85rem; color: var(--neon-cyan);">[<?= htmlspecialchars($scrim['team_tag']) ?>]</span>
                            </h3>
                        </div>
                        <?php $status_labels = ['open' => 'กำลังหาทีม', 'matched' => 'ได้ทีมซ้อมแล้ว', 'expired' => 'หมดเวลา']; ?>
                        <span class="scrim-status-badge <?= e($scrim['status']) ?>">
                            <?= $status_labels[$scrim['status']] ?? e($scrim['status']) ?>
                        </span>
                    </div>

                    <div class="scrim-detail-row">
                        <span>Rank เฉลี่ย:</span>
                        <span class="scrim-detail-val" style="color: var(--neon-gold);"><?= htmlspecialchars($scrim['avg_rank']) ?></span>
                    </div>

                    <div class="scrim-detail-row">
                        <span>รูปแบบ:</span>
                        <span class="scrim-detail-val"><?= htmlspecialchars($scrim['match_format']) ?></span>
                    </div>

                    <div class="scrim-detail-row">
                        <span>เวลาซ้อม:</span>
                        <span class="scrim-detail-val"><?= date('d/m/Y H:i', strtotime($scrim['scrim_time'])) ?> น.</span>
                    </div>

                    <?php if ($scrim['note']): ?>
                        <div style="margin-top: 10px; font-size: 0.85rem; color: var(--text-muted); background: #090A0F; padding: 8px; border-radius: 6px;">
                            <i class="fa-regular fa-comment-dots"></i> <?= nl2br(htmlspecialchars($scrim['note'])) ?>
                        </div>
                    <?php endif; ?>

                    <!-- ปุ่ม Direct Links สำหรับติดต่อ -->
                    <div class="contact-btn-group">
                        <?php if ($scrim['discord_link']): ?>
                            <a href="<?= htmlspecialchars($scrim['discord_link']) ?>" target="_blank" class="btn-contact btn-discord">
                                <i class="fa-brands fa-discord"></i> Discord
                            </a>
                        <?php endif; ?>

                        <?php if ($scrim['facebook_link']): ?>
                            <a href="<?= htmlspecialchars($scrim['facebook_link']) ?>" target="_blank" class="btn-contact btn-facebook">
                                <i class="fa-brands fa-facebook"></i> Messenger
                            </a>
                        <?php endif; ?>

                        <?php if ($scrim['line_id']): ?>
                            <div class="btn-contact btn-line" title="Line ID: <?= htmlspecialchars($scrim['line_id']) ?>">
                                <i class="fa-brands fa-line"></i> <?= htmlspecialchars($scrim['line_id']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$scrim['discord_link'] && !$scrim['facebook_link'] && !$scrim['line_id']): ?>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">ทีมนี้ยังไม่ได้ระบุช่องทางติดต่อ</div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $scrim['leader_id']): ?>
    <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #333; padding-top: 8px;">
        <?php if ($scrim['status'] === 'open'): ?>
            <?= post_button('../actions/scrim-process.php?action=close', ['post_id' => (int)$scrim['post_id']], 'ปิดรับทีมซ้อมนี้',
                'font-size: 0.8rem; color: var(--text-muted); text-decoration: underline;',
                'ยืนยันว่าได้ทีมซ้อมแล้วและต้องการปิดประกาศนี้?') ?>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <!-- ปุ่มลบโพสต์ -->
        <?= post_button('../actions/scrim-delete.php', ['id' => (int)$scrim['post_id']], '<i class="fa-solid fa-trash"></i> ลบโพสต์',
            'font-size: 0.8rem; color: #ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(255, 77, 77, 0.3);',
            'คุณต้องการลบโพสต์หาทีมซ้อมนี้ใช่หรือไม่?') ?>
    </div>
<?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>