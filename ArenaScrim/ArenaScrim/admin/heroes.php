<?php
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ เข้าได้เฉพาะ Role 'admin' เท่านั้น
require_admin();

$page_title = "จัดการฮีโร่ - ArenaScrim";
$page_css = "admin";

// ตัวกรอง: ค้นหาชื่อ และตำแหน่ง
$search = trim($_GET['q'] ?? '');
$role_filter = in_array($_GET['role'] ?? '', HERO_ROLES, true) ? $_GET['role'] : '';

// ฮีโร่ที่กำลังแก้ไข (?edit=hero_id)
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM heroes WHERE hero_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

// จำนวนครั้งที่ถูกใช้ในประวัติการซ้อม (ฝั่งเรา + ฝั่งตรงข้าม)
$usage = [];
foreach ($pdo->query("SELECT hero_name, COUNT(*) AS n FROM match_player_stats GROUP BY hero_name") as $r) {
    $usage[mb_strtolower($r['hero_name'])]['own'] = (int)$r['n'];
}
$enemy_sql = implode(' UNION ALL ', array_map(function ($i) {
    return "SELECT enemy_hero_$i AS h FROM scrim_matches WHERE enemy_hero_$i IS NOT NULL";
}, range(1, 5)));
foreach ($pdo->query("SELECT h, COUNT(*) AS n FROM ($enemy_sql) x GROUP BY h") as $r) {
    $usage[mb_strtolower($r['h'])]['enemy'] = (int)$r['n'];
}

// รายชื่อฮีโร่ตามตัวกรอง
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "hero_name LIKE ?";
    $params[] = "%$search%";
}
if ($role_filter) {
    $where[] = "hero_role = ?";
    $params[] = $role_filter;
}
$stmt = $pdo->prepare("SELECT * FROM heroes" . ($where ? " WHERE " . implode(' AND ', $where) : '') . " ORDER BY FIELD(hero_role, '" . implode("','", HERO_ROLES) . "'), hero_name");
$stmt->execute($params);
$heroes = $stmt->fetchAll();

// จำนวนฮีโร่ในแต่ละตำแหน่ง (ทั้งหมด ไม่ขึ้นกับตัวกรอง)
$role_counts = array_fill_keys(HERO_ROLES, 0);
foreach ($pdo->query("SELECT hero_role, COUNT(*) AS n FROM heroes GROUP BY hero_role") as $r) {
    $role_counts[$r['hero_role']] = (int)$r['n'];
}
$total = array_sum($role_counts);

$input = "padding: 10px; background: #090A0F; border: 1px solid var(--border-color); color: #fff; border-radius: 6px;";
$chip = function ($active) {
    return "padding: 6px 14px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; border: 1px solid "
        . ($active ? "var(--neon-cyan); background: rgba(0,229,255,0.12); color: var(--neon-cyan);" : "var(--border-color); color: var(--text-muted);");
};
$qs = function ($role) use ($search) {
    return '?' . http_build_query(array_filter(['q' => $search, 'role' => $role]));
};

// แบ่งหน้าตาราง
[$heroes_page, $page, $pages] = paginate_rows($heroes);

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <h2 style="color: var(--neon-red); margin-bottom: 6px;"><i class="fa-solid fa-dragon"></i> จัดการฮีโร่ (Hero Pool)</h2>
    <p style="color: var(--text-muted); margin: 0 0 20px 0; font-size: 0.9rem;">
        ฮีโร่ในรายการนี้คือตัวเลือกที่ผู้ใช้เห็นในฟอร์มบันทึกผลการซ้อม เมื่อเกมออกฮีโร่ใหม่ ให้เพิ่มที่นี่ ผู้ใช้จะเลือกใช้ได้ทันที
    </p>

    <?php show_flash(); ?>

    <!-- ฟอร์มเพิ่ม / แก้ไขฮีโร่ -->
    <div class="arena-card" style="margin-bottom: 20px; <?= $editing ? 'border-color: var(--neon-gold);' : '' ?>">
        <h3 style="margin-top: 0; color: <?= $editing ? 'var(--neon-gold)' : 'var(--neon-cyan)' ?>;">
            <?= $editing ? '<i class="fa-solid fa-pen"></i> แก้ไขฮีโร่: ' . e($editing['hero_name']) : '<i class="fa-solid fa-plus"></i> เพิ่มฮีโร่ใหม่' ?>
        </h3>
        <form method="POST" action="../actions/admin-process.php?action=<?= $editing ? 'edit_hero' : 'add_hero' ?>" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;"> <?= csrf_field() ?>
            <?php if ($editing): ?>
                <input type="hidden" name="hero_id" value="<?= (int)$editing['hero_id'] ?>">
            <?php endif; ?>
            <input type="text" name="hero_name" required maxlength="50" value="<?= e($editing['hero_name'] ?? '') ?>" placeholder="ชื่อฮีโร่ เช่น Bijan" style="flex: 2; min-width: 200px; <?= $input ?>" <?= $editing ? '' : 'autofocus' ?>>
            <select name="hero_role" required style="flex: 1; min-width: 180px; <?= $input ?>">
                <option value="">-- เลือกตำแหน่ง --</option>
                <?php foreach (HERO_ROLES as $r): ?>
                    <option value="<?= e($r) ?>" <?= ($editing['hero_role'] ?? $role_filter) === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-cyber" style="padding: 10px 22px;"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มฮีโร่' ?></button>
            <?php if ($editing): ?>
                <a href="heroes.php" style="color: var(--text-muted);">ยกเลิก</a>
            <?php endif; ?>
        </form>
        <?php if ($editing): ?>
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 10px 0 0 0;">* ถ้าเปลี่ยนชื่อ ระบบจะเปลี่ยนชื่อฮีโร่ในประวัติการซ้อมเดิมทั้งหมดให้ตรงกันด้วย (ใช้แก้ชื่อที่พิมพ์ผิด)</p>
        <?php endif; ?>
    </div>

    <!-- รายการฮีโร่ -->
    <div class="arena-card">
        <form method="GET" style="display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <input type="text" name="q" value="<?= e($search) ?>" placeholder="ค้นหาชื่อฮีโร่..." style="flex: 1; min-width: 200px; <?= $input ?>">
            <?php if ($role_filter): ?><input type="hidden" name="role" value="<?= e($role_filter) ?>"><?php endif; ?>
            <button type="submit" class="btn-cyber" style="padding: 10px 18px;">ค้นหา</button>
        </form>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
            <a href="<?= $qs('') ?>" style="<?= $chip(!$role_filter) ?>">ทั้งหมด (<?= $total ?>)</a>
            <?php foreach ($role_counts as $r => $n): ?>
                <a href="<?= $qs($r) ?>" style="<?= $chip($role_filter === $r) ?>"><?= e($r) ?> (<?= $n ?>)</a>
            <?php endforeach; ?>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อฮีโร่</th>
                    <th>ตำแหน่ง</th>
                    <th style="text-align: center;">ถูกใช้ (ฝั่งเรา / ฝั่งตรงข้าม)</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$heroes): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">ไม่พบฮีโร่ตามเงื่อนไข</td></tr>
                <?php endif; ?>
                <?php foreach ($heroes_page as $h): ?>
                    <?php $u = $usage[mb_strtolower($h['hero_name'])] ?? []; ?>
                    <tr style="<?= $editing && (int)$editing['hero_id'] === (int)$h['hero_id'] ? 'background: rgba(255,215,0,0.06);' : '' ?>">
                        <td><?= (int)$h['hero_id'] ?></td>
                        <td style="font-weight: 600; color: var(--text-main);"><?= e($h['hero_name']) ?></td>
                        <td style="color: var(--text-muted);"><?= e($h['hero_role']) ?></td>
                        <td style="text-align: center; color: var(--text-muted);"><?= (int)($u['own'] ?? 0) ?> / <?= (int)($u['enemy'] ?? 0) ?></td>
                        <td style="white-space: nowrap;">
                            <a href="heroes.php?edit=<?= (int)$h['hero_id'] ?>" style="color: var(--neon-gold); text-decoration: none; margin-right: 12px;"><i class="fa-solid fa-pen"></i> แก้ไข</a>
                            <?= post_button('../actions/admin-process.php?action=delete_hero', ['id' => (int)$h['hero_id']],
                                '<i class="fa-solid fa-trash"></i> ลบ', 'color: var(--neon-red);',
                                "ลบฮีโร่ {$h['hero_name']} ออกจากรายการ?\nประวัติการซ้อมเดิมที่ใช้ฮีโร่นี้จะยังอยู่ แต่ผู้ใช้จะเลือกฮีโร่นี้ไม่ได้อีก") ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= pagination_links($page, $pages) ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
