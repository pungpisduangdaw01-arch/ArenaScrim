<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "จัดการข้อมูลส่วนตัว - ArenaScrim";
$page_css = "team";

// ดึงข้อมูลผู้ใช้ปัจจุบันจากฐานข้อมูล
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// จัดการเมื่อกดบันทึกฟอร์ม
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf("profile-edit.php");
    $nickname = trim($_POST['nickname'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $open_id  = trim($_POST['open_id'] ?? '');
    $full_id  = trim($_POST['full_id'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');

    if ($display_name === '' || mb_strlen($display_name) > 100) {
        $error_msg = "กรุณากรอกชื่อแสดงผล (ไม่เกิน 100 ตัวอักษร)";
    } elseif (mb_strlen($nickname) > 100) {
        $error_msg = "ชื่อเล่นยาวเกิน 100 ตัวอักษร";
    } elseif (mb_strlen($fullname) > 255 || mb_strlen($open_id) > 255 || mb_strlen($full_id) > 255) {
        $error_msg = "ชื่อ-นามสกุล / OpenID / Full ID ยาวเกิน 255 ตัวอักษร";
    } else {
        $update_stmt = $pdo->prepare("UPDATE users SET display_name = ?, nickname = ?, fullname = ?, open_id = ?, full_id = ? WHERE user_id = ?");
        $update_stmt->execute([$display_name, $nickname ?: null, $fullname ?: null, $open_id ?: null, $full_id ?: null, $user_id]);
        $success_msg = "บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว!";

        // อัปเดตชื่อที่แสดงบนแถบเมนูทันที
        $_SESSION['display_name'] = $display_name;

        // ดึงข้อมูลล่าสุดมาแสดงใหม่
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

include '../includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 40px auto;">
    <div style="background: #12141d; padding: 30px; border-radius: 12px; border: 1px solid #222;">
        <h2 style="color: #00e5ff; margin-bottom: 10px;"><i class="fa-solid fa-id-card"></i> ข้อมูลส่วนตัวผู้เล่น</h2>
        <p style="color: #aaa; margin-bottom: 20px; font-size: 0.9rem;">กรอก OpenID, Full ID และชื่อ-นามสกุลของคุณสำหรับใช้ในการแข่งขันและระบบ Scrim</p>

        <?php show_flash(); ?>

        <?php if (!empty($success_msg)): ?>
            <div style="background: rgba(0,229,255,0.1); border: 1px solid #00e5ff; color: #00e5ff; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem;">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: rgba(255,77,77,0.1); border: 1px solid #ff4d4d; color: #ff4d4d; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem;">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 15px;"> <?= csrf_field() ?>
            <div>
                <label style="color:#fff; font-size: 0.9rem;">ชื่อแสดงผลบนเว็บ (Display Name)</label>
                <input type="text" name="display_name" required maxlength="100" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>

            <div>
                <label style="color:#fff; font-size: 0.9rem;">ชื่อเล่น (Nickname)</label>
                <input type="text" name="nickname" maxlength="100" value="<?= htmlspecialchars($user['nickname'] ?? '') ?>" placeholder="เช่น บอย" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>

            <div>
                <label style="color:#fff; font-size: 0.9rem;">ชื่อ - นามสกุล (Full Name)</label>
                <input type="text" name="fullname" maxlength="255" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" placeholder="เช่น สมชาย ใจดี" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>

            <div>
                <label style="color:#fff; font-size: 0.9rem;">OpenID (รหัส OpenID ในเกม)</label>
                <input type="text" name="open_id" maxlength="255" value="<?= htmlspecialchars($user['open_id'] ?? '') ?>" placeholder="เช่น 1234567890abcdef" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>

            <div>
                <label style="color:#fff; font-size: 0.9rem;">Full ID (ID ตัวละครเต็ม)</label>
                <input type="text" name="full_id" maxlength="255" value="<?= htmlspecialchars($user['full_id'] ?? '') ?>" placeholder="เช่น 9876543210" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn-cyber" style="flex: 1; padding: 12px; cursor: pointer; text-align: center;">บันทึกข้อมูล</button>
                <a href="team-manage.php" style="background: #222; color: #aaa; padding: 12px 20px; border-radius: 6px; text-decoration: none; text-align: center; border: 1px solid #333; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">กลับหน้าจัดการทีม</a>
            </div>
        </form>
    </div>

    <!-- เปลี่ยนรหัสผ่าน -->
    <div style="background: #12141d; padding: 30px; border-radius: 12px; border: 1px solid #222; margin-top: 20px;">
        <h3 style="color: #00e5ff; margin: 0 0 15px 0;"><i class="fa-solid fa-key"></i> เปลี่ยนรหัสผ่าน</h3>
        <form action="../actions/auth-process.php?action=change_password" method="POST" style="display: flex; flex-direction: column; gap: 15px;"> <?= csrf_field() ?>
            <?php foreach (['current_password' => 'รหัสผ่านเดิม', 'new_password' => 'รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร)', 'confirm_password' => 'ยืนยันรหัสผ่านใหม่'] as $name => $text): ?>
                <div>
                    <label style="color:#fff; font-size: 0.9rem;"><?= $text ?></label>
                    <input type="password" name="<?= $name ?>" required <?= $name === 'current_password' ? '' : 'minlength="6"' ?> autocomplete="<?= $name === 'current_password' ? 'current-password' : 'new-password' ?>" style="width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px;">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn-cyber" style="padding: 12px; cursor: pointer;">เปลี่ยนรหัสผ่าน</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>