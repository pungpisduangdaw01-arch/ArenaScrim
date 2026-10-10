<?php
require_once '../config/db.php';
$user_id = require_login();

// 1. ดึงข้อมูลทีม (ต้องเป็นหัวหน้าทีม)
$team = get_led_team($pdo, $user_id);
if (!$team) {
    redirect_with("team-manage.php", 'error', "คุณต้องสร้างทีมและเป็นโค้ชก่อน จึงจะบันทึกผลการซ้อมได้");
}
$team_id = (int)$team['team_id'];

// 2. โหมดแก้ไข: ?id=match_id (แก้ได้เฉพาะแมตช์ของทีมตัวเอง)
$edit_id = (int)($_GET['id'] ?? 0);
$match = [];
$player_stats = [];
if ($edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM scrim_matches WHERE match_id = ? AND team_id = ?");
    $stmt->execute([$edit_id, $team_id]);
    $match = $stmt->fetch();
    if (!$match) {
        redirect_with("match-history.php", 'error', "ไม่พบแมตช์ที่ต้องการแก้ไข");
    }
    $stmt = $pdo->prepare("SELECT * FROM match_player_stats WHERE match_id = ?");
    $stmt->execute([$edit_id]);
    foreach ($stmt->fetchAll() as $row) {
        $player_stats[(int)$row['member_id']] = $row;
    }
}
$is_edit = !empty($match);

// ซีรีส์ของทีมให้เลือกบันทึกเกมเข้าไป (ใหม่ -> เก่า) · ?series_id= ใช้เลือกไว้ล่วงหน้า เช่น มาจากคำขอซ้อมที่รับแล้ว
$series_list = series_scores($pdo, $team_id);
uasort($series_list, function ($a, $b) { return strcmp((string)$b['series_date'], (string)$a['series_date']); });
$series_selected = $is_edit ? (int)$match['series_id'] : (int)($_GET['series_id'] ?? 0);
if (!isset($series_list[$series_selected])) {
    $series_selected = 0;
}
$page_title = ($is_edit ? "แก้ไขผลการซ้อม" : "บันทึกผลการซ้อม") . " - ArenaScrim";

// 3. ดึงสมาชิกในทีม
$stmt_mem = $pdo->prepare("SELECT member_id, in_game_name, main_role FROM team_members WHERE team_id = ? ORDER BY member_id");
$stmt_mem->execute([$team_id]);
$members = $stmt_mem->fetchAll();

// 4. ดึงรายชื่อฮีโร่จาก SQL จัดกลุ่มตามตำแหน่ง
$heroes_data = $pdo->query("SELECT hero_name, hero_role FROM heroes ORDER BY hero_role ASC, hero_name ASC")->fetchAll();
$rov_heroes = [];
foreach ($heroes_data as $h) {
    $rov_heroes[$h['hero_role']][] = $h['hero_name'];
}

// ค่าเดิมของฟิลด์ (โหมดแก้ไข) หรือค่าเริ่มต้น (โหมดเพิ่ม)
$val = function ($field, $default = '') use ($match) {
    return $match[$field] ?? $default;
};

// สร้าง <select> เลือกฮีโร่ พร้อมเลือกค่าเดิมไว้
function hero_select($name, $selected, $rov_heroes, $placeholder, $style)
{
    $html = '<select name="' . e($name) . '" style="' . $style . '"><option value="">' . e($placeholder) . '</option>';
    foreach ($rov_heroes as $category => $heroes) {
        $html .= '<optgroup label="' . e($category) . '" style="background: #1a1d28; color: #00e5ff;">';
        foreach ($heroes as $hero) {
            $sel = ($hero === $selected) ? ' selected' : '';
            $html .= '<option value="' . e($hero) . '" style="background: #090a0f; color: #fff;"' . $sel . '>' . e($hero) . '</option>';
        }
        $html .= '</optgroup>';
    }
    // ฮีโร่ที่บันทึกไว้เดิมแต่ถูกลบออกจากรายการแล้ว: คงไว้เป็นตัวเลือก เพื่อไม่ให้สถิติเดิมหายเมื่อกดบันทึกการแก้ไข
    if ($selected !== '' && !in_array($selected, array_merge(...array_values($rov_heroes)), true)) {
        $html .= '<option value="' . e($selected) . '" selected>' . e($selected) . ' (ถูกลบจากรายการ)</option>';
    }
    return $html . '</select>';
}

$match_date = $is_edit ? date('Y-m-d\TH:i', strtotime($match['match_date'])) : date('Y-m-d\TH:i');

// สไตล์ช่องกรอกที่ใช้ซ้ำ
$input = "width: 100%; padding: 8px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 4px; box-sizing: border-box;";
$label = "display:block; font-size: 0.85rem; color: #aaa; margin-bottom: 4px;";
$cell  = "padding: 5px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 4px; text-align: center;";

include '../includes/header.php';
?>

<div style="max-width: 1200px; margin: 30px auto; background: #12141d; padding: 25px; border-radius: 12px; color: #fff; border: 1px solid #222;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="color: #00e5ff; margin: 0;">
            <i class="fa-solid fa-chart-line"></i>
            <?= $is_edit ? 'แก้ไขผลการซ้อม #' . (int)$match['match_id'] : 'บันทึกผลการซ้อม (Match Record)' ?>
        </h3>
        <a href="<?= $is_edit ? 'match-detail.php?id=' . (int)$match['match_id'] : 'match-history.php' ?>" style="color: #aaa; text-decoration: none; font-size: 0.9rem;">
            <i class="fa-solid fa-arrow-left"></i> ย้อนกลับ
        </a>
    </div>

    <?php show_flash(); ?>

    <form action="../actions/match-process.php" method="POST" id="matchForm"> <?= csrf_field() ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="match_id" value="<?= (int)$match['match_id'] ?>">
        <?php endif; ?>

        <!-- SECTION 0: ซีรีส์ -->
        <div style="background: #1a1d28; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <label style="<?= $label ?>"><i class="fa-solid fa-layer-group"></i> ซีรีส์ (เกมที่ซ้อมกับคู่แข่งเดียวกันในนัดเดียว)</label>
            <select name="series_id" id="seriesSelect" style="<?= $input ?>">
                <option value="new">-- สร้างซีรีส์ใหม่ (ใช้คู่แข่งและรูปแบบด้านล่าง) --</option>
                <?php foreach ($series_list as $sid => $s): ?>
                    <option value="<?= (int)$sid ?>" data-opponent="<?= e($s['opponent_name']) ?>" data-type="Bo<?= (int)$s['best_of'] ?>"
                            data-next="<?= min(7, (int)$s['games'] + 1) ?>" <?= $sid === $series_selected ? 'selected' : '' ?>>
                        vs <?= e($s['opponent_name']) ?> · Bo<?= (int)$s['best_of'] ?> · <?= $s['series_date'] ? date('d/m/Y', strtotime($s['series_date'])) : '-' ?>
                        · <?= (int)$s['games'] ?> เกม (<?= (int)$s['wins'] ?>–<?= (int)$s['losses'] ?>)<?= $s['request_id'] ? ' · จากคำขอซ้อม' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #888;">ถ้าเลือกซีรีส์เดิม ระบบจะใช้คู่แข่งและรูปแบบ Bo ของซีรีส์นั้น · ย้ายเกมไปซีรีส์อื่นได้ด้วยการแก้ไขเกม</small>
        </div>

        <!-- SECTION 1: ข้อมูลแมตช์หลัก & กติกา Bo -->
        <div style="background: #1a1d28; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h5 style="color: #00e5ff; margin-top: 0; margin-bottom: 12px;">1. ข้อมูลการแข่งขัน</h5>
            <div style="display: grid; grid-template-columns: 2fr 1.6fr 1fr 1fr 1fr 1fr; gap: 12px;">
                <div>
                    <label style="<?= $label ?>">คู่แข่ง (Opponent)</label>
                    <input type="text" name="opponent_name" required maxlength="100" value="<?= e($val('opponent_name')) ?>" placeholder="ชื่อทีมคู่แข่ง" style="<?= $input ?>">
                </div>
                <div>
                    <label style="<?= $label ?>">วัน-เวลาที่ซ้อม</label>
                    <input type="datetime-local" name="match_date" required value="<?= e($match_date) ?>" max="<?= date('Y-m-d\TH:i') ?>" style="<?= $input ?>">
                </div>
                <div>
                    <label style="<?= $label ?>">รูปแบบแข่ง</label>
                    <select name="match_type" style="<?= $input ?>">
                        <?php foreach (MATCH_TYPES as $t): ?>
                            <option value="<?= $t ?>" <?= $val('match_type', 'Bo3') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="<?= $label ?>">เกมที่เท่าไหร่</label>
                    <input type="number" name="game_number" value="<?= (int)$val('game_number', 1) ?>" min="1" max="7" style="<?= $input ?> text-align: center;">
                </div>
                <div>
                    <label style="<?= $label ?>">ผลแข่งขัน</label>
                    <select name="match_result" required style="<?= $input ?>">
                        <option value="WIN" <?= $val('match_result') === 'WIN' ? 'selected' : '' ?>>ชนะ (WIN)</option>
                        <option value="LOSS" <?= $val('match_result') === 'LOSS' ? 'selected' : '' ?>>แพ้ (LOSS)</option>
                    </select>
                </div>
                <div>
                    <label style="<?= $label ?>">เวลาจบเกม</label>
                    <input type="text" name="match_duration" value="<?= e($val('match_duration')) ?>" maxlength="10" pattern="\d{1,3}(:\d{2})?" title="เช่น 15:30" placeholder="เช่น 15:30" style="<?= $input ?> text-align: center;">
                </div>
            </div>
        </div>

        <!-- SECTION 2: สถิติ Object และเป้าหมายสำคัญในเกม -->
        <div style="background: #1a1d28; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h5 style="color: #ffd700; margin-top: 0; margin-bottom: 12px;"><i class="fa-solid fa-dragon"></i> 2. สถิติการเก็บ Object (Objectives Control)</h5>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr) repeat(2, 1.2fr); gap: 12px; align-items: center;">
                <div>
                    <label style="<?= $label ?>">มังกร (Abyssal Dragon)</label>
                    <input type="number" name="team_dragons" value="<?= (int)$val('team_dragons', 0) ?>" min="0" style="<?= $input ?> text-align: center;">
                </div>
                <div>
                    <label style="<?= $label ?>">สเลเยอร์ (Dark Slayer)</label>
                    <input type="number" name="team_slayers" value="<?= (int)$val('team_slayers', 0) ?>" min="0" style="<?= $input ?> text-align: center;">
                </div>
                <div>
                    <label style="<?= $label ?> color: #ff4d4d; font-weight: bold;">God Slayer</label>
                    <input type="number" name="team_god_slayers" value="<?= (int)$val('team_god_slayers', 0) ?>" min="0" style="<?= $input ?> text-align: center; border-color: #ff4d4d;">
                </div>
                <div>
                    <label style="<?= $label ?>">ป้อมที่พังได้ (Towers)</label>
                    <input type="number" name="team_towers" value="<?= (int)$val('team_towers', 0) ?>" min="0" max="9" style="<?= $input ?> text-align: center;">
                </div>
                <div style="background: #090a0f; padding: 10px; border-radius: 4px; border: 1px solid #333; text-align: center;">
                    <label style="font-size: 0.85rem; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <input type="checkbox" name="first_dragon" value="1" <?= $val('first_dragon') ? 'checked' : '' ?> style="transform: scale(1.2); cursor: pointer;">
                        <span>ได้มังกรตัวแรก (First Dragon)</span>
                    </label>
                </div>
                <div style="background: #090a0f; padding: 10px; border-radius: 4px; border: 1px solid #333; text-align: center;">
                    <label style="font-size: 0.85rem; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <input type="checkbox" name="first_tower" value="1" <?= $val('first_tower') ? 'checked' : '' ?> style="transform: scale(1.2); cursor: pointer;">
                        <span>ได้ป้อมแรก (First Tower)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- SECTION 3: ฮีโร่ฝั่งตรงข้าม 5 ตัว (Draft Analysis) -->
        <div style="background: #1a1d28; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h5 style="color: #ff4d4d; margin-top: 0; margin-bottom: 12px;">3. ฮีโร่ฝั่งตรงข้าม (Enemy Lineup)</h5>
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div><?= hero_select("enemy_hero_$i", $val("enemy_hero_$i"), $rov_heroes, "-- ฮีโร่ $i --", $input . ' font-size: 0.85rem;') ?></div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- SECTION 4: สถิติผู้เล่นฝั่งเรา -->
        <h5 style="color: #00e5ff; margin-bottom: 6px;">4. สถิติผู้เล่นฝั่งเรา</h5>
        <p style="color: #888; font-size: 0.8rem; margin: 0 0 10px 0;">
            ผู้เล่นที่ไม่ได้ลงเล่น (เช่น ตัวสำรอง) ให้เว้นช่องฮีโร่ว่างไว้ ระบบจะไม่บันทึกสถิติของคนนั้น ·
            ถ้ากรอก Gold / ดาเมจ เป็นตัวเลขจริง ระบบจะคำนวณ % ให้อัตโนมัติ (% ร่วมไฟต์ คำนวณจาก (K+A) ÷ Kill รวมของทีม)
        </p>
        <?php if (empty($members)): ?>
            <p style="color: #ff4d4d;">ยังไม่มีสมาชิกในทีม กรุณา <a href="member-add.php" style="color: #00e5ff;">เพิ่มสมาชิก</a> ก่อน (ยังบันทึกผลแมตช์ได้ แต่จะไม่มีสถิติรายบุคคล)</p>
        <?php else: ?>
            <div style="overflow-x: auto; margin-bottom: 20px;">
                <table style="width: 100%; min-width: 1250px; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #1a1d28; color: #00e5ff;">
                            <th style="padding: 10px; min-width: 130px;">ผู้เล่น / ตำแหน่ง</th>
                            <th style="padding: 10px; min-width: 130px;">ฮีโร่</th>
                            <th style="padding: 10px; text-align: center;">K / D / A</th>
                            <th style="padding: 10px; text-align: center;">MVP</th>
                            <th style="padding: 10px; text-align: center; color: #ffd700;">Gold</th>
                            <th style="padding: 10px; text-align: center; color: #ffd700;">ดาเมจที่ทำ</th>
                            <th style="padding: 10px; text-align: center; color: #ffd700;">ดาเมจที่รับ</th>
                            <th style="padding: 10px; text-align: center;">% เงิน</th>
                            <th style="padding: 10px; text-align: center;">% ดาเมจ</th>
                            <th style="padding: 10px; text-align: center;">% รับดาเมจ</th>
                            <th style="padding: 10px; text-align: center;">% ร่วมไฟต์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $index => $m): ?>
                            <?php
                            $ps = $player_stats[(int)$m['member_id']] ?? [];
                            $num = function ($f, $dec = 0) use ($ps) {
                                return isset($ps[$f]) ? number_format((float)$ps[$f], $dec, '.', '') : ($dec ? '0.0' : '0');
                            };
                            $n = "stats[$index]";
                            ?>
                            <tr class="player-row" style="border-bottom: 1px solid #222;">
                                <td style="padding: 8px;">
                                    <input type="hidden" name="<?= $n ?>[member_id]" value="<?= (int)$m['member_id'] ?>">
                                    <strong style="color: #fff; display: block;"><?= e($m['in_game_name']) ?></strong>
                                    <small style="color: #888;"><?= e($m['main_role']) ?></small>
                                </td>
                                <td style="padding: 8px;"><?= hero_select("{$n}[hero]", $ps['hero_name'] ?? '', $rov_heroes, '-- ไม่ได้ลงเล่น --', 'width: 100%; padding: 6px; background: #090a0f; border: 1px solid #333; color: #fff; border-radius: 4px;') ?></td>
                                <td style="padding: 8px; text-align: center;">
                                    <div style="display: flex; gap: 2px; justify-content: center; align-items: center;">
                                        <input type="number" name="<?= $n ?>[kills]" class="calc-kills" value="<?= $num('kills') ?>" min="0" style="width: 40px; <?= $cell ?>"> /
                                        <input type="number" name="<?= $n ?>[deaths]" value="<?= $num('deaths') ?>" min="0" style="width: 40px; <?= $cell ?>"> /
                                        <input type="number" name="<?= $n ?>[assists]" class="calc-assists" value="<?= $num('assists') ?>" min="0" style="width: 40px; <?= $cell ?>">
                                    </div>
                                </td>
                                <td style="padding: 8px; text-align: center;"><input type="number" step="0.1" min="0" max="16" name="<?= $n ?>[mvp_score]" value="<?= $num('mvp_score', 1) ?>" style="width: 55px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" min="0" name="<?= $n ?>[gold]" class="calc-gold" value="<?= $num('gold') ?>" style="width: 75px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" min="0" name="<?= $n ?>[damage_dealt]" class="calc-damage" value="<?= $num('damage_dealt') ?>" style="width: 85px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" min="0" name="<?= $n ?>[damage_taken]" class="calc-taken" value="<?= $num('damage_taken') ?>" style="width: 85px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" step="0.1" min="0" max="100" name="<?= $n ?>[gold_share]" class="share-gold" value="<?= $num('gold_share', 1) ?>" style="width: 58px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" step="0.1" min="0" max="100" name="<?= $n ?>[damage_share]" class="share-damage" value="<?= $num('damage_share', 1) ?>" style="width: 58px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" step="0.1" min="0" max="100" name="<?= $n ?>[taken_share]" class="share-taken" value="<?= $num('taken_share', 1) ?>" style="width: 58px; <?= $cell ?>"></td>
                                <td style="padding: 8px; text-align: center;"><input type="number" step="0.1" min="0" max="100" name="<?= $n ?>[participation_rate]" class="share-fight" value="<?= $num('participation_rate', 1) ?>" style="width: 58px; <?= $cell ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- SECTION 5: ลิงก์สื่อแนบ & สรุปโน้ตข้อผิดพลาด -->
        <div style="background: #1a1d28; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h5 style="color: #00e5ff; margin-top: 0; margin-bottom: 12px;">5. การวิเคราะห์เกม & สื่อแนบ</h5>
            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="<?= $label ?>">ลิงก์รูปสกรีนช็อต (Imgur / Drive / Discord)</label>
                    <input type="url" name="match_image_url" value="<?= e($val('match_image_url')) ?>" placeholder="https://i.imgur.com/example.png" style="<?= $input ?>">
                </div>
                <div style="flex: 1;">
                    <label style="<?= $label ?>">ลิงก์รีเพลย์ย้อนหลัง (YouTube / Twitch / Drive)</label>
                    <input type="url" name="replay_url" value="<?= e($val('replay_url')) ?>" placeholder="https://www.youtube.com/watch?v=..." style="<?= $input ?>">
                </div>
            </div>
            <div>
                <label style="<?= $label ?>">โน๊ตวิเคราะห์เกม / ข้อผิดพลาดที่ต้องแก้ (Mistake Log)</label>
                <textarea name="match_note" rows="4" maxlength="5000" placeholder="เช่น ดราฟต์หลุดช่วงต้นเกม / ออฟเลนโดนเติม 3 คนบ่อย / ไฟต์มังกรตัวแรกสื่อสารพลาด..." style="<?= $input ?> resize: vertical;"><?= e($val('match_note')) ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn-cyber" style="width: 100%; padding: 12px; cursor: pointer; background: #00e5ff; color: #000; font-weight: bold; border: none; border-radius: 6px; font-size: 1rem;">
            <?= $is_edit ? 'บันทึกการแก้ไข' : 'บันทึกผลการซ้อม' ?>
        </button>
    </form>
</div>

<script src="../assets/js/match-calc.js"></script>

<?php include '../includes/footer.php'; ?>
