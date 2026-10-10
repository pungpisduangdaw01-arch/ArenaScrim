<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "แก้ไขข้อมูลทีม - ArenaScrim";
$page_css = "team";

// ดึงข้อมูลทีมของตนเอง ถ้าไม่ใช่หัวหน้าทีม ให้ส่งกลับหน้าจัดการทีม
$team = get_led_team($pdo, $user_id);
if (!$team) {
    header("Location: team-manage.php");
    exit;
}

$input_style = "width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px; box-sizing: border-box;";

include '../includes/header.php';
?>

<div class="card-create-team" style="background: #12141d; padding: 30px; border-radius: 12px; border: 1px solid #222; max-width: 600px; margin: 40px auto;">
    <h2 style="color: #00e5ff; margin-bottom: 20px; text-align: center;">
        <i class="fa-solid fa-pen-to-square"></i> แก้ไขข้อมูลทีม
    </h2>

    <?php show_flash(); ?>

    <form action="../actions/team-process.php?action=edit" method="POST" style="display: flex; flex-direction: column; gap: 15px;"> <?= csrf_field() ?>
        <div>
            <label style="color:#fff;">ชื่อทีม (Team Name)</label>
            <input type="text" name="team_name" required maxlength="100" value="<?= e($team['team_name']) ?>" style="<?= $input_style ?>">
        </div>

        <div>
            <label style="color:#fff;">ตัวย่อทีม (Tag)</label>
            <input type="text" name="team_tag" required maxlength="10" value="<?= e($team['team_tag']) ?>" style="<?= $input_style ?>">
        </div>

        <h4 style="color: #00e5ff; margin: 10px 0 0 0;"><i class="fa-solid fa-address-book"></i> ช่องทางติดต่อ (แสดงในประกาศหาทีมซ้อม)</h4>

        <div>
            <label style="color:#fff;"><i class="fa-brands fa-discord"></i> ลิงก์ Discord</label>
            <input type="url" name="discord_link" value="<?= e($team['discord_link']) ?>" placeholder="https://discord.gg/xxxx" style="<?= $input_style ?>">
        </div>

        <div>
            <label style="color:#fff;"><i class="fa-brands fa-facebook"></i> ลิงก์ Facebook / Messenger</label>
            <input type="url" name="facebook_link" value="<?= e($team['facebook_link']) ?>" placeholder="https://m.me/yourpage" style="<?= $input_style ?>">
        </div>

        <div>
            <label style="color:#fff;"><i class="fa-brands fa-line"></i> Line ID</label>
            <input type="text" name="line_id" maxlength="100" value="<?= e($team['line_id']) ?>" placeholder="@yourteam" style="<?= $input_style ?>">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button type="submit" class="btn-cyber" style="flex: 1; padding: 12px; cursor: pointer;">บันทึกการแก้ไข</button>
            <a href="team-manage.php" style="flex: 1; padding: 12px; text-align: center; background: #333; color: #fff; text-decoration: none; border-radius: 6px;">ยกเลิก</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
