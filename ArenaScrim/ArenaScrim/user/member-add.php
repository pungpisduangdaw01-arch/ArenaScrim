<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "เพิ่มสมาชิกในทีม - ArenaScrim";
$page_css = "team";

// ดึงข้อมูลทีมของผู้นำ ถ้ายังไม่มีทีมให้กลับไปสร้างทีมก่อน
$team = get_led_team($pdo, $user_id);
if (!$team) {
    header("Location: team-manage.php");
    exit;
}

include '../includes/header.php';
?>

<div style="max-width: 500px; margin: 40px auto; background: #12141d; padding: 30px; border-radius: 12px; border: 1px solid #222;">
    <h3 style="color: #00e5ff; margin-top: 0; margin-bottom: 20px;">
        <i class="fa-solid fa-user-plus"></i> เพิ่มสมาชิกเข้าทีม
    </h3>

    <?php show_flash(); ?>

    <form action="../actions/team-process.php?action=add_member" method="POST" style="display: flex; flex-direction: column; gap: 15px;"> <?= csrf_field() ?>

        <div>
            <label style="color: #fff; display: block; margin-bottom: 5px;">ชื่อในเกม (In-Game Name / IGN)</label>
            <input type="text" name="ign" required maxlength="100" placeholder="เช่น DarkSlayer01" style="width: 100%; padding: 10px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px; box-sizing: border-box;">
        </div>

       <div>
    <label style="color: #fff; display: block; margin-bottom: 5px;">ตำแหน่งการเล่น (Role)</label>
    <select name="role" required style="width: 100%; padding: 10px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px; box-sizing: border-box;">
        <?php foreach (MEMBER_ROLES as $value => $text): ?>
            <option value="<?= e($value) ?>"><?= e($text) ?></option>
        <?php endforeach; ?>
    </select>
</div>

        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-cyber" style="flex: 1; padding: 10px; cursor: pointer;">บันทึกสมาชิก</button>
            <a href="team-manage.php" style="padding: 10px 15px; background: #222; color: #aaa; text-decoration: none; border-radius: 6px; text-align: center;">ยกเลิก</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>