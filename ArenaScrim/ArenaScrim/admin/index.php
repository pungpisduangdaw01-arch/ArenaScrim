<?php
require_once '../config/db.php';

// ตรวจสอบสิทธิ์ เข้าได้เฉพาะ Role 'admin' เท่านั้น
require_admin();

$page_title = "ผู้ดูแลระบบ - ArenaScrim";
$page_css = "admin";

// ดึงสถิติต่างๆ
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_teams = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
$total_scrims = $pdo->query("SELECT COUNT(*) FROM scrim_posts")->fetchColumn();
$total_matches = $pdo->query("SELECT COUNT(*) FROM scrim_matches")->fetchColumn();
$total_heroes = $pdo->query("SELECT COUNT(*) FROM heroes")->fetchColumn();

// รายการล่าสุด (แสดงสั้นๆ 5 รายการ ดูทั้งหมดได้ที่หน้าจัดการของแต่ละเมนู)
$recent_users = $pdo->query("SELECT username, display_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_matches = $pdo->query("SELECT m.match_date, m.opponent_name, m.match_result, t.team_name
    FROM scrim_matches m JOIN teams t ON m.team_id = t.team_id ORDER BY m.match_date DESC LIMIT 5")->fetchAll();

// การ์ดสถิติ: [จำนวน, ชื่อ, ไอคอน, สี, หน้าที่ลิงก์ไป]
$cards = [
    [$total_users, 'สมาชิกทั้งหมด', 'fa-users', 'var(--neon-cyan)', 'users.php'],
    [$total_teams, 'ทีมในระบบ', 'fa-shield-halved', 'var(--neon-gold)', 'teams.php'],
    [$total_scrims, 'ประกาศ Scrim ทั้งหมด', 'fa-gamepad', 'var(--neon-red)', 'scrims.php'],
    [$total_matches, 'ผลการซ้อมที่บันทึก', 'fa-chart-line', 'var(--neon-cyan)', 'teams.php'],
    [$total_heroes, 'ฮีโร่ในระบบ', 'fa-dragon', 'var(--neon-gold)', 'heroes.php'],
];

include '../includes/header.php';
?>

<div style="margin-top: 20px;">
    <h2 style="color: var(--neon-red); margin-bottom: 20px;"><i class="fa-solid fa-user-shield"></i> ระบบควบคุมผู้ดูแลระบบ (Admin Panel)</h2>

    <?php show_flash(); ?>

    <!-- ส่วนสรุปสถิติ: กดการ์ดเพื่อไปหน้าจัดการ -->
    <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
        <?php foreach ($cards as [$n, $label, $icon, $color, $href]): ?>
            <a href="<?= $href ?>" class="stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon" style="color: <?= $color ?>; background: rgba(255,255,255,0.04);"><i class="fa-solid <?= $icon ?>"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($n) ?></h3>
                    <p><?= $label ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 25px;">
        <!-- สมาชิกใหม่ล่าสุด -->
        <div class="arena-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: var(--text-main); margin: 0;"><i class="fa-solid fa-user-plus"></i> สมาชิกใหม่ล่าสุด</h3>
                <a href="users.php" style="color: var(--neon-cyan); text-decoration: none; font-size: 0.85rem;">ดูทั้งหมด <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <table class="admin-table" style="margin-top: 12px;">
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td style="color: var(--neon-cyan); font-weight: 600;">@<?= e($u['username']) ?></td>
                            <td><?= e($u['display_name']) ?></td>
                            <td><span class="badge-role <?= e($u['role']) ?>"><?= strtoupper(e($u['role'])) ?></span></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ผลการซ้อมล่าสุดของทุกทีม -->
        <div class="arena-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color: var(--text-main); margin: 0;"><i class="fa-solid fa-clock-rotate-left"></i> ผลการซ้อมล่าสุด</h3>
                <a href="teams.php" style="color: var(--neon-cyan); text-decoration: none; font-size: 0.85rem;">ดูทีมทั้งหมด <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <table class="admin-table" style="margin-top: 12px;">
                <tbody>
                    <?php if (!$recent_matches): ?>
                        <tr><td style="text-align: center; color: var(--text-muted);">ยังไม่มีการบันทึกผลการซ้อม</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent_matches as $m): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= e($m['team_name']) ?> <span style="color: var(--text-muted); font-weight: normal;">vs</span> <?= e($m['opponent_name']) ?></td>
                            <td style="font-weight: 600; color: <?= $m['match_result'] === 'WIN' ? '#0ca5bd' : '#f03e3e' ?>;"><?= e($m['match_result']) ?></td>
                            <td style="color: var(--text-muted); font-size: 0.85rem; white-space: nowrap;"><?= date('d/m/Y', strtotime($m['match_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
