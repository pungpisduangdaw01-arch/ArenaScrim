<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "โพสต์หาทีมซ้อม - ArenaScrim";
$page_css = "scrim";

// เช็กว่าผู้ใช้เป็นหัวหน้าทีมหรือไม่
$my_team = get_led_team($pdo, $user_id);

include '../includes/header.php';
?>

<div style="max-width: 600px; margin: 30px auto;">
    <?php show_flash(); ?>
    <?php if (!$my_team): ?>
        <div class="arena-card" style="text-align: center;">
            <h3 style="color: var(--neon-red);"><i class="fa-solid fa-triangle-exclamation"></i> ไม่สามารถลงประกาศได้</h3>
            <p style="color: var(--text-muted);">คุณต้องสร้างทีมและเป็นโค้ชก่อน จึงจะสามารถโพสต์หาทีมซ้อมได้ครับ</p>
            <a href="../user/team-manage.php" class="btn-cyber">ไปที่หน้าจัดการทีม</a>
        </div>
    <?php else: ?>
        <div class="arena-card">
            <h2 style="color: var(--neon-cyan); margin-top: 0;"><i class="fa-solid fa-bullhorn"></i> ประกาศหาทีมซ้อม (Scrim)</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                ทีม: <strong style="color: #fff;"><?= htmlspecialchars($my_team['team_name']) ?> [<?= htmlspecialchars($my_team['team_tag']) ?>]</strong>
            </p>

            <form action="../actions/scrim-process.php?action=create" method="POST"> <?= csrf_field() ?>

                <div style="margin-bottom: 15px;">
                    <label style="color: var(--text-muted); font-size: 0.85rem;">Rank เฉลี่ยของทีม</label>
                    <select name="avg_rank" required style="width: 100%; padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;">
                        <?php foreach (RANKS as $rank): ?>
                            <option value="<?= e($rank) ?>"><?= e($rank) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="color: var(--text-muted); font-size: 0.85rem;">รูปแบบการซ้อม</label>
                        <select name="match_format" required style="width: 100%; padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;">
                            <option value="BO1">BO1 (1 แมตช์)</option>
                            <option value="BO3" selected>BO3 (ชนะ 2 ใน 3)</option>
                            <option value="BO5">BO5 (ชนะ 3 ใน 5)</option>
                        </select>
                    </div>

                    <div>
                        <label style="color: var(--text-muted); font-size: 0.85rem;">วัน-เวลาที่สะดวกซ้อม</label>
                        <input type="datetime-local" name="scrim_time" required min="<?= date('Y-m-d\TH:i') ?>" style="width: 100%; padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="color: var(--text-muted); font-size: 0.85rem;">หมายเหตุเพิ่มเติม</label>
                    <textarea name="note" rows="3" maxlength="1000" placeholder="เช่น ขอทีมดราฟต์จริงจัง, ลองแผนใหม่" style="width: 100%; padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;"></textarea>
                </div>

                <button type="submit" class="btn-cyber" style="width: 100%;">โพสต์หาทีมซ้อมทันที</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>