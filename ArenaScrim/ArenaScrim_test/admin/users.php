<?php
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ เข้าได้เฉพาะ Role 'admin' เท่านั้น
require_admin();

$page_title = "จัดการสมาชิก - ArenaScrim";
$page_css = "admin";

// ค้นหาจาก username / ชื่อแสดงผล / อีเมล และกรองตามสิทธิ์
$search = trim($_GET['q'] ?? '');
$role_filter = in_array($_GET['role'] ?? '', ['user', 'admin'], true) ? $_GET['role'] : '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(u.username LIKE ? OR u.display_name LIKE ? OR u.email LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
if ($role_filter) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
}
$stmt = $pdo->prepare("SELECT u.*, t.team_name
    FROM users u LEFT JOIN teams t ON t.leader_id = u.user_id"
    . ($where ? " WHERE " . implode(' AND ', $where) : '') . "
    ORDER BY u.created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$input = "padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;";

// แบ่งหน้าตาราง
[$users_page, $page, $pages] = paginate_rows($users);

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <h2 style="color: var(--neon-red); margin-bottom: 20px;"><i class="fa-solid fa-users-gear"></i> จัดการสมาชิกระบบ</h2>

    <?php show_flash(); ?>

    <div class="arena-card">
        <form method="GET" style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="ค้นหา Username / ชื่อแสดงผล / อีเมล..." style="flex: 1; min-width: 220px; <?= $input ?>">
            <select name="role" style="<?= $input ?>">
                <option value="">ทุกสิทธิ์</option>
                <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" class="btn-cyber" style="padding: 10px 18px;">ค้นหา</button>
            <?php if ($search !== '' || $role_filter): ?>
                <a href="users.php" style="color: var(--text-muted); align-self: center;">ล้างตัวกรอง</a>
            <?php endif; ?>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>ชื่อแสดงผล</th>
                    <th>อีเมล</th>
                    <th>ทีมที่เป็นโค้ช</th>
                    <th>สิทธิ์</th>
                    <th>วันที่สมัคร</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$users): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">ไม่พบสมาชิกตามเงื่อนไข</td></tr>
                <?php endif; ?>
                <?php foreach ($users_page as $u): ?>
                    <tr>
                        <td><?= (int)$u['user_id'] ?></td>
                        <td style="color: var(--neon-cyan); font-weight: 600;">@<?= e($u['username']) ?></td>
                        <td><?= e($u['display_name']) ?></td>
                        <td style="color: var(--text-muted);"><?= e($u['email']) ?></td>
                        <td><?= $u['team_name'] ? e($u['team_name']) : '<span style="color: var(--text-muted);">-</span>' ?></td>
                        <td><span class="badge-role <?= e($u['role']) ?>"><?= strtoupper(e($u['role'])) ?></span></td>
                        <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td style="white-space: nowrap;">
                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <?= post_button('../actions/admin-process.php?action=toggle_role', ['id' => (int)$u['user_id']],
                                    '<i class="fa-solid fa-user-gear"></i> ' . ($u['role'] === 'admin' ? 'ลดเป็น User' : 'ตั้งเป็น Admin'),
                                    'color: var(--neon-gold); margin-right: 10px;', 'ยืนยันเปลี่ยนสิทธิ์ของผู้ใช้นี้?') ?>
                                <?= post_button('../actions/admin-process.php?action=reset_password', ['id' => (int)$u['user_id']],
                                    '<i class="fa-solid fa-key"></i> ตั้งรหัสใหม่', 'color: var(--neon-cyan); margin-right: 10px;',
                                    "ตั้งรหัสผ่านใหม่ให้ @{$u['username']}?\nรหัสเดิมจะใช้ไม่ได้ ระบบจะแสดงรหัสใหม่ให้คุณแจ้งผู้ใช้") ?>
                                <?= post_button('../actions/admin-process.php?action=delete_user', ['id' => (int)$u['user_id']],
                                    '<i class="fa-solid fa-trash"></i> ลบ', 'color: var(--neon-red);',
                                    "ยืนยันลบสมาชิกคนนี้?\nถ้าเป็นโค้ช ทีมและผลการซ้อมจะยังอยู่ (ไม่มีโค้ช) และประกาศหาทีมซ้อมที่เปิดอยู่จะถูกปิด") ?>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">(ตัวคุณ)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($page, $pages) ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
