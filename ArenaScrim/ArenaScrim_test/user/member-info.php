<?php
require_once '../config/db.php';
$user_id = require_login();

$page_title = "ข้อมูลส่วนตัวผู้เล่น - ArenaScrim";
$page_css = "team";

// ดูได้ทุกคนในทีม แต่แก้ไขได้เฉพาะหัวหน้าทีม
$team = get_user_team($pdo, $user_id);
if (!$team) {
    redirect_with("team-manage.php", 'error', "คุณยังไม่มีทีม กรุณาสร้างทีมก่อน");
}
$team_id = (int)$team['team_id'];
$is_leader = (int)$team['leader_id'] === $user_id;

$ROLES = MEMBER_ROLES;
$RANKS = RANKS;

// รายชื่อสมาชิกทั้งหมดในทีม
$stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY member_id");
$stmt->execute([$team_id]);
$members = $stmt->fetchAll();

// ผู้เล่นที่ถูกเลือก (ต้องอยู่ในทีมนี้เท่านั้น) ถ้าไม่ได้เลือกให้ใช้คนแรก
$selected = null;
$member_id = (int)($_GET['member_id'] ?? $_POST['member_id'] ?? 0);
foreach ($members as $m) {
    if ((int)$m['member_id'] === $member_id) $selected = $m;
}
if (!$selected && $members) {
    $selected = $members[0];
}
$edit_mode = $selected && $is_leader && isset($_GET['edit']);

// ---------- บันทึกข้อมูลส่วนตัวของผู้เล่น ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected) {
    $back = "member-info.php?member_id=" . (int)$selected['member_id'];
    verify_csrf($back);
    if (!$is_leader) {
        redirect_with($back, 'error', "เฉพาะโค้ชเท่านั้นที่แก้ไขข้อมูลผู้เล่นได้");
    }

    $f = [];
    foreach (['in_game_name' => 100, 'nickname' => 100, 'fullname' => 255, 'open_id' => 100, 'full_id' => 100, 'current_rank' => 50, 'contact' => 255] as $field => $max) {
        $f[$field] = trim($_POST[$field] ?? '');
        if (mb_strlen($f[$field]) > $max) {
            redirect_with("$back&edit=1", 'error', "ข้อมูลบางช่องยาวเกินกำหนด ($max ตัวอักษร)");
        }
    }
    $f['main_role'] = array_key_exists($_POST['main_role'] ?? '', $ROLES) ? $_POST['main_role'] : $selected['main_role'];
    $f['member_note'] = trim($_POST['member_note'] ?? '');
    if (mb_strlen($f['member_note']) > 2000) {
        redirect_with("$back&edit=1", 'error', "หมายเหตุยาวเกิน 2,000 ตัวอักษร");
    }

    if ($f['in_game_name'] === '') {
        redirect_with("$back&edit=1", 'error', "กรุณากรอกชื่อในเกม (IGN)");
    }
    $dup = $pdo->prepare("SELECT member_id FROM team_members WHERE team_id = ? AND in_game_name = ? AND member_id <> ?");
    $dup->execute([$team_id, $f['in_game_name'], $selected['member_id']]);
    if ($dup->fetch()) {
        redirect_with("$back&edit=1", 'error', "มีสมาชิกชื่อ \"{$f['in_game_name']}\" อยู่ในทีมแล้ว");
    }

    $stmt = $pdo->prepare("UPDATE team_members SET in_game_name = ?, main_role = ?, nickname = ?, fullname = ?, open_id = ?, full_id = ?,
            current_rank = ?, contact = ?, member_note = ?, info_updated_at = NOW()
        WHERE member_id = ? AND team_id = ?");
    $stmt->execute([
        $f['in_game_name'], $f['main_role'], $f['nickname'] ?: null, $f['fullname'] ?: null, $f['open_id'] ?: null, $f['full_id'] ?: null,
        $f['current_rank'] ?: null, $f['contact'] ?: null, $f['member_note'] ?: null, $selected['member_id'], $team_id,
    ]);
    redirect_with($back, 'success', "บันทึกข้อมูลของ {$f['in_game_name']} เรียบร้อยแล้ว");
}

// สรุปผลงานสั้นๆ ของผู้เล่นที่เลือก
$summary = null;
if ($selected) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS games, SUM(m.match_result = 'WIN') AS wins,
            SUM(s.kills) AS k, SUM(s.deaths) AS d, SUM(s.assists) AS a, AVG(s.mvp_score) AS mvp
        FROM match_player_stats s JOIN scrim_matches m ON s.match_id = m.match_id WHERE s.member_id = ?");
    $stmt->execute([$selected['member_id']]);
    $summary = $stmt->fetch();
}

// นับว่ากรอกข้อมูลส่วนตัวครบกี่ช่อง (ใช้แสดงสถานะในรายชื่อ)
$INFO_FIELDS = ['nickname', 'fullname', 'open_id', 'full_id', 'current_rank', 'contact'];
$filled = function ($m) use ($INFO_FIELDS) {
    return count(array_filter($INFO_FIELDS, function ($f) use ($m) { return !empty($m[$f]); }));
};

$card = "background: #12141d; padding: 22px; border-radius: 12px; border: 1px solid #222;";
$input = "width: 100%; padding: 10px; margin-top: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 6px; box-sizing: border-box;";
$label = "color: #aaa; font-size: 0.85rem;";

include '../includes/header.php';
?>

<div style="max-width: 1100px; margin: 30px auto; color: #fff;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h2 style="color: #00e5ff; margin: 0;"><i class="fa-solid fa-id-card"></i> ข้อมูลส่วนตัวผู้เล่น: <?= e($team['team_name']) ?></h2>
        <div style="display: flex; gap: 10px;">
            <a href="team-manage.php" style="color: #aaa; text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-arrow-left"></i> กลับหน้าจัดการทีม</a>
            <a href="profile-edit.php" style="color: #00e5ff; text-decoration: none; font-size: 0.9rem;"><i class="fa-solid fa-user-gear"></i> บัญชีของฉัน</a>
        </div>
    </div>

    <?php show_flash(); ?>

    <?php if (!$members): ?>
        <div style="<?= $card ?> color: #aaa;">
            ยังไม่มีสมาชิกในทีม <?php if ($is_leader): ?><a href="member-add.php" style="color: #00e5ff;">เพิ่มสมาชิก</a> ก่อน<?php endif; ?>
        </div>
    <?php else: ?>

    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start;">

        <!-- รายชื่อผู้เล่นในทีม: กดเพื่อเลือก -->
        <div style="<?= $card ?> padding: 14px;">
            <div style="<?= $label ?> margin: 0 0 10px 6px;"><i class="fa-solid fa-users"></i> เลือกผู้เล่นในทีม (<?= count($members) ?> คน)</div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <?php foreach ($members as $m): ?>
                    <?php $active = $selected && (int)$m['member_id'] === (int)$selected['member_id']; $n = $filled($m); ?>
                    <a href="member-info.php?member_id=<?= (int)$m['member_id'] ?>"
                       style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 8px; text-decoration: none;
                              background: <?= $active ? 'rgba(0,229,255,0.12)' : '#1a1d29' ?>; border: 1px solid <?= $active ? '#00e5ff' : '#2a2d3a' ?>;">
                        <span>
                            <strong style="color: #fff; display: block;"><?= e($m['in_game_name']) ?></strong>
                            <small style="color: #888;"><?= e($ROLES[$m['main_role']] ?? $m['main_role'] ?: 'ไม่ระบุตำแหน่ง') ?></small>
                        </span>
                        <span title="กรอกข้อมูลแล้ว <?= $n ?>/<?= count($INFO_FIELDS) ?> ช่อง"
                              style="font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; white-space: nowrap;
                                     color: <?= $n === count($INFO_FIELDS) ? '#0ca5bd' : ($n ? '#ffd700' : '#888') ?>; border: 1px solid currentColor;">
                            <?= $n === count($INFO_FIELDS) ? '✔ ครบ' : "$n/" . count($INFO_FIELDS) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ข้อมูลของผู้เล่นที่เลือก -->
        <div style="<?= $card ?>">
            <?php if ($edit_mode): ?>
                <!-- โหมดแก้ไข -->
                <h3 style="margin: 0 0 15px 0; color: #ffd700;"><i class="fa-solid fa-pen"></i> แก้ไขข้อมูล: <?= e($selected['in_game_name']) ?></h3>
                <form method="POST" action="member-info.php" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;"> <?= csrf_field() ?>
                    <input type="hidden" name="member_id" value="<?= (int)$selected['member_id'] ?>">
                    <div>
                        <label style="<?= $label ?>">ชื่อในเกม (IGN) *</label>
                        <input type="text" name="in_game_name" required maxlength="100" value="<?= e($selected['in_game_name']) ?>" style="<?= $input ?>">
                    </div>
                    <div>
                        <label style="<?= $label ?>">ตำแหน่งการเล่น</label>
                        <select name="main_role" style="<?= $input ?>">
                            <?php foreach ($ROLES as $v => $text): ?>
                                <option value="<?= e($v) ?>" <?= $selected['main_role'] === $v ? 'selected' : '' ?>><?= e($text) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="<?= $label ?>">ชื่อเล่น</label>
                        <input type="text" name="nickname" maxlength="100" value="<?= e($selected['nickname']) ?>" placeholder="เช่น บอย" style="<?= $input ?>">
                    </div>
                    <div>
                        <label style="<?= $label ?>">ชื่อ - นามสกุลจริง</label>
                        <input type="text" name="fullname" maxlength="255" value="<?= e($selected['fullname']) ?>" placeholder="เช่น สมชาย ใจดี" style="<?= $input ?>">
                    </div>
                    <div>
                        <label style="<?= $label ?>">OpenID (รหัส OpenID ในเกม)</label>
                        <input type="text" name="open_id" maxlength="100" value="<?= e($selected['open_id']) ?>" placeholder="เช่น 1234567890abcdef" style="<?= $input ?>">
                    </div>
                    <div>
                        <label style="<?= $label ?>">Full ID (ID ตัวละครเต็ม)</label>
                        <input type="text" name="full_id" maxlength="100" value="<?= e($selected['full_id']) ?>" placeholder="เช่น 9876543210" style="<?= $input ?>">
                    </div>
                    <div>
                        <label style="<?= $label ?>">แรงก์ปัจจุบัน</label>
                        <input type="text" name="current_rank" maxlength="50" list="rank-list" value="<?= e($selected['current_rank']) ?>" placeholder="เลือกหรือพิมพ์แรงก์" style="<?= $input ?>">
                        <datalist id="rank-list">
                            <?php foreach ($RANKS as $r): ?><option value="<?= e($r) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label style="<?= $label ?>">ช่องทางติดต่อ (Line / Discord / เบอร์โทร)</label>
                        <input type="text" name="contact" maxlength="255" value="<?= e($selected['contact']) ?>" placeholder="เช่น Line: boy123" style="<?= $input ?>">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="<?= $label ?>">หมายเหตุ (ฮีโร่ถนัด เวลาที่สะดวกซ้อม ฯลฯ)</label>
                        <textarea name="member_note" rows="3" maxlength="2000" style="<?= $input ?> resize: vertical; font-family: inherit;"><?= e($selected['member_note']) ?></textarea>
                    </div>
                    <div style="grid-column: 1 / -1; display: flex; gap: 10px;">
                        <button type="submit" class="btn-cyber" style="flex: 1; padding: 12px; cursor: pointer;">บันทึกข้อมูล</button>
                        <a href="member-info.php?member_id=<?= (int)$selected['member_id'] ?>" style="padding: 12px 20px; background: #222; color: #aaa; text-decoration: none; border-radius: 6px; border: 1px solid #333;">ยกเลิก</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- โหมดดูข้อมูล -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 18px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.5rem;"><?= e($selected['in_game_name']) ?>
                            <?php if ($selected['nickname']): ?><span style="color: #aaa; font-size: 1rem; font-weight: normal;">(<?= e($selected['nickname']) ?>)</span><?php endif; ?>
                        </h3>
                        <div style="margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap;">
                            <span style="padding: 3px 10px; border-radius: 6px; background: #222; border: 1px solid #444; font-size: 0.85rem;"><?= e($ROLES[$selected['main_role']] ?? $selected['main_role'] ?: 'ไม่ระบุตำแหน่ง') ?></span>
                            <?php if ($selected['current_rank']): ?>
                                <span style="padding: 3px 10px; border-radius: 6px; border: 1px solid #ffd700; color: #ffd700; font-size: 0.85rem;"><i class="fa-solid fa-crown"></i> <?= e($selected['current_rank']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($is_leader): ?>
                        <a href="member-info.php?member_id=<?= (int)$selected['member_id'] ?>&edit=1" class="btn-cyber" style="text-decoration: none; font-size: 0.9rem; white-space: nowrap;"><i class="fa-solid fa-pen"></i> แก้ไขข้อมูล</a>
                    <?php endif; ?>
                </div>

                <?php
                $rows = [
                    ['fa-user', 'ชื่อ - นามสกุลจริง', $selected['fullname']],
                    ['fa-face-smile', 'ชื่อเล่น', $selected['nickname']],
                    ['fa-fingerprint', 'OpenID', $selected['open_id']],
                    ['fa-hashtag', 'Full ID', $selected['full_id']],
                    ['fa-address-book', 'ช่องทางติดต่อ', $selected['contact']],
                    ['fa-calendar-plus', 'เข้าทีมเมื่อ', date('d/m/Y', strtotime($selected['joined_at']))],
                ];
                ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <?php foreach ($rows as [$icon, $name, $value]): ?>
                        <div style="background: #1a1d29; border: 1px solid #2a2d3a; border-radius: 8px; padding: 12px;">
                            <div style="<?= $label ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $name ?></div>
                            <div style="margin-top: 4px; font-weight: 600; word-break: break-all; <?= $value ? '' : 'color: #666; font-weight: normal;' ?>">
                                <?= $value ? e($value) : 'ยังไม่ได้กรอก' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background: #1a1d29; border: 1px solid #2a2d3a; border-radius: 8px; padding: 12px; margin-top: 10px;">
                    <div style="<?= $label ?>"><i class="fa-solid fa-note-sticky"></i> หมายเหตุ</div>
                    <div style="margin-top: 4px; line-height: 1.6; <?= $selected['member_note'] ? '' : 'color: #666;' ?>">
                        <?= $selected['member_note'] ? nl2br(e($selected['member_note'])) : 'ยังไม่ได้กรอก' ?>
                    </div>
                </div>

                <!-- สรุปผลงานในทีม -->
                <div style="margin-top: 18px; padding-top: 16px; border-top: 1px dashed #333; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; gap: 22px; flex-wrap: wrap;">
                        <?php $g = (int)($summary['games'] ?? 0); ?>
                        <div><div style="<?= $label ?>">ลงแข่ง</div><b style="font-size: 1.2rem;"><?= $g ?></b> เกม</div>
                        <div><div style="<?= $label ?>">Win Rate</div><b style="font-size: 1.2rem; color: #00e5ff;"><?= $g ? number_format($summary['wins'] / $g * 100, 1) : '0.0' ?>%</b></div>
                        <div><div style="<?= $label ?>">KDA</div><b style="font-size: 1.2rem; color: #ffeb3b;"><?= $g ? number_format(($summary['k'] + $summary['a']) / max(1, $summary['d']), 2) : '-' ?></b></div>
                        <div><div style="<?= $label ?>">MVP เฉลี่ย</div><b style="font-size: 1.2rem; color: #ffd700;"><?= $g ? number_format($summary['mvp'], 1) : '-' ?></b></div>
                    </div>
                    <a href="member-stats.php?member_id=<?= (int)$selected['member_id'] ?>" style="color: #00e5ff; text-decoration: none; font-size: 0.9rem;">ดูสถิติเต็ม <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <?php if ($selected['info_updated_at']): ?>
                    <div style="color: #666; font-size: 0.8rem; margin-top: 12px;">แก้ไขข้อมูลล่าสุด <?= date('d/m/Y H:i', strtotime($selected['info_updated_at'])) ?> น.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
