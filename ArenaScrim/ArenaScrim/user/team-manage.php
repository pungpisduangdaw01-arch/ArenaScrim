<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "จัดการทีม - ArenaScrim";
$page_css = "team";

// 1. ดึงข้อมูลทีมของหัวหน้า
$team = get_led_team($pdo, $user_id);
$team_id = $team['team_id'] ?? 0;
$members = [];

if ($team) {
    // 2. ดึงรายชื่อสมาชิกทั้งหมดตาม team_id
    $stmt_members = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY member_id");
    $stmt_members->execute([$team_id]);
    $members = $stmt_members->fetchAll();
}

include '../includes/header.php';
?>

<?php show_flash(); ?>

<?php if (!$team): ?>

    <div class="card-create-team" style="background: #12141d; padding: 30px; border-radius: 12px; border: 1px solid #222; max-width: 600px; margin: 40px auto; text-align: center;">
        <h2 style="color: #00e5ff; margin-bottom: 20px;"><i class="fa-solid fa-users"></i> สร้างทีมของคุณ</h2>
        <p style="color: #aaa; margin-bottom: 20px;">ตั้งทีมของคุณเพื่อเปิดใช้ระบบวิเคราะห์สถิติและหาทีมซ้อม Scrim</p>
        
        <form action="../actions/team-process.php?action=create" method="POST" style="display: flex; flex-direction: column; gap: 15px; text-align: left;"> <?= csrf_field() ?>
            <div>
                <label style="color:#fff;">ชื่อทีม (Team Name)</label>
                <input type="text" name="team_name" required maxlength="100" placeholder="เช่น Bacon Time" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>
            <div>
                <label style="color:#fff;">ตัวย่อทีม (Tag)</label>
                <input type="text" name="team_tag" required maxlength="10" placeholder="เช่น BAC" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>
            <div>
                <label style="color:#fff;"><i class="fa-brands fa-discord"></i> ลิงก์ Discord (ไม่บังคับ)</label>
                <input type="url" name="discord_link" placeholder="https://discord.gg/xxxx" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>
            <div>
                <label style="color:#fff;"><i class="fa-brands fa-facebook"></i> ลิงก์ Facebook / Messenger (ไม่บังคับ)</label>
                <input type="url" name="facebook_link" placeholder="https://m.me/yourpage" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>
            <div>
                <label style="color:#fff;"><i class="fa-brands fa-line"></i> Line ID (ไม่บังคับ)</label>
                <input type="text" name="line_id" maxlength="100" placeholder="@yourteam" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>
            <button type="submit" class="btn-cyber" style="margin-top: 15px; width: 100%; padding: 12px; cursor: pointer;">สร้างทีมทันที</button>
        </form>
    </div>

<?php else: ?>

    <div class="team-layout">
        
<div class="team-info-card">
    <h3>
        <?php echo htmlspecialchars($team['team_name'] ?? ''); ?> 
        <span class="team-badge">[<?php echo htmlspecialchars($team['team_tag'] ?? ''); ?>]</span>
    </h3>
    
    <div class="team-actions" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
        <a href="team-edit.php" class="btn-cyber" style="font-size: 0.85rem; padding: 8px 14px; text-decoration: none; text-align: center;">
            <i class="fa-solid fa-pen-to-square"></i> แก้ไขข้อมูลทีม
        </a>

        <!-- ปุ่มเพิ่มใหม่สำหรับไปหน้าบันทึกผลซ้อม -->
        <a href="match-add.php" class="btn-cyber" style="font-size: 0.85rem; padding: 8px 14px; text-decoration: none; text-align: center; background: #00e5ff; color: #000;">
            <i class="fa-solid fa-plus-circle"></i> + บันทึกผลการซ้อม
        </a>
        
        <a href="match-history.php" class="btn-cyber" style="font-size: 0.85rem; padding: 8px 14px; text-decoration: none; text-align: center;">
            <i class="fa-solid fa-clock-rotate-left"></i> ประวัติการซ้อม
        </a>

        <a href="member-stats.php" class="btn-cyber" style="font-size: 0.85rem; padding: 8px 14px; text-decoration: none; text-align: center;">
            <i class="fa-solid fa-users-viewfinder"></i> สถิติผู้เล่นรายบุคคล
        </a>

        <!-- ปุ่มไปหน้ากรอก/แก้ไขข้อมูลส่วนตัวผู้เล่น -->
        <a href="member-info.php" class="btn-cyber" style="font-size: 0.85rem; padding: 8px 14px; text-decoration: none; text-align: center; background: #222; color: #00e5ff; border: 1px solid #00e5ff; margin-top: 5px;">
            <i class="fa-solid fa-id-card"></i> ข้อมูลส่วนตัวผู้เล่น (OpenID / ID)
        </a>

        <?= post_button('../actions/team-process.php?action=delete', [],
            '<i class="fa-solid fa-trash"></i> ยุบทีม', 'color: #ff4d4d; font-size: 0.85rem; padding: 8px; width: 100%; text-align: center;',
            "คุณต้องการยุบทีมนี้ใช่หรือไม่?\nสมาชิก ผลการซ้อม และประกาศ Scrim ทั้งหมดของทีมจะถูกลบถาวร") ?>
    </div>
</div>
        <!-- ฝั่งขวา: รายชื่อสมาชิก -->
        <div class="team-info-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0; color: #fff;">สมาชิกภายในทีม</h4>
                <a href="member-add.php" class="btn-cyber" style="font-size: 0.85rem; padding: 6px 12px; text-decoration: none;">
                    <i class="fa-solid fa-user-plus"></i> เพิ่มสมาชิก
                </a>
            </div>

            <table class="member-table">
                <thead>
                    <tr>
                        <th>ชื่อในเกม (IGN)</th>
                        <th>ตำแหน่ง (Role)</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- แสดงหัวหน้าทีม -->
                    <tr>
        <td><?php echo htmlspecialchars($_SESSION['display_name'] ?? 'โค้ช'); ?></td>
        <td><span class="role-badge" style="background: #00e5ff22; color: #00e5ff; border: 1px solid #00e5ff; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem;">โค้ช</span></td>
        <td><span style="color: #666; font-size: 0.85rem;">(โค้ช)</span></td>
    </tr>

                    <!-- วนลูปดึงสมาชิก -->
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><a href="member-info.php?member_id=<?= (int)$m['member_id'] ?>" title="ดูข้อมูลส่วนตัว" style="color: #fff; text-decoration: none;"><?php echo htmlspecialchars($m['in_game_name'] ?? ''); ?> <i class="fa-solid fa-id-card" style="color: #00e5ff; font-size: 0.8rem;"></i></a></td>
                            <td>
                                <span class="role-badge" style="background: #222; color: #fff; border: 1px solid #444; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars(!empty($m['main_role']) ? $m['main_role'] : 'ไม่ระบุ'); ?>
                                </span>
                            </td>
                            <td>
                                <?= post_button('../actions/team-process.php?action=delete_member', ['id' => (int)($m['member_id'] ?? 0)],
                                    '<i class="fa-solid fa-user-minus"></i> ลบ', 'color: #ff4d4d; font-size: 0.85rem;',
                                    "ต้องการลบสมาชิกคนนี้ใช่หรือไม่?\nสถิติการซ้อมของสมาชิกคนนี้จะถูกลบไปด้วย") ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>