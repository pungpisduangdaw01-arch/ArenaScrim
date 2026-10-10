<?php
// ฟังก์ชันกลางที่ใช้ร่วมกันทุกหน้า
// ตั้งเวลาให้ตรงกับประเทศไทย (และตรงกับ MySQL) เพื่อให้เวลาซ้อม/วันที่แสดงผลถูกต้อง
date_default_timezone_set('Asia/Bangkok');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// URL ฐานของโปรเจกต์ (เช่น /ArenaScrim/ArenaScrim) คำนวณอัตโนมัติจากตำแหน่งโฟลเดอร์ใน htdocs
// ทำให้ลิงก์ CSS/เมนูทำงานได้ไม่ว่าจะย้ายโฟลเดอร์ไปไว้ที่ไหน
if (!defined('BASE_URL')) {
    $doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $app_root = str_replace('\\', '/', realpath(dirname(__DIR__)));
    $base = ($doc_root !== '' && strpos($app_root, $doc_root) === 0) ? substr($app_root, strlen($doc_root)) : '';
    define('BASE_URL', rtrim($base, '/'));
}

// Escape ข้อความก่อนแสดงผลใน HTML
function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// บังคับให้ต้องล็อกอินก่อน (ต้องเรียกก่อน include header.php เพื่อให้ redirect ทำงานได้)
// อ่านสิทธิ์ล่าสุดจากฐานข้อมูลทุกครั้ง เพื่อให้การเปลี่ยนสิทธิ์/ลบบัญชีโดย Admin มีผลทันที
function require_login()
{
    global $pdo;
    if (empty($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
        if ($role === false) {
            // บัญชีถูกลบไปแล้ว: ล้าง Session แล้วกลับไปหน้าล็อกอิน
            $_SESSION = [];
            session_destroy();
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }
        $_SESSION['role'] = $role;
    }
    return (int)$_SESSION['user_id'];
}

// ---------- ป้องกัน CSRF ----------
// Token ประจำ Session ใส่ในทุกฟอร์ม POST
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

// ตรวจว่าเป็น POST และ Token ถูกต้อง ไม่เช่นนั้นส่งกลับหน้า $back พร้อมข้อความแจ้งเตือน
function verify_csrf($back)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        redirect_with($back, 'error', "คำขอไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่อีกครั้ง");
    }
}

// ปุ่มส่งคำขอแบบ POST พร้อม CSRF token (ใช้แทนลิงก์ลบ/เปลี่ยนสถานะ)
function post_button($action, array $fields, $label_html, $style = '', $confirm = '')
{
    $html = '<form method="POST" action="' . e($action) . '" style="display: inline; margin: 0;"'
        . ($confirm !== '' ? ' onsubmit="return confirm(' . e(json_encode($confirm, JSON_UNESCAPED_UNICODE)) . ');"' : '')
        . '>' . csrf_field();
    foreach ($fields as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    return $html . '<button type="submit" style="cursor: pointer; font: inherit; background: none; border: none; padding: 0; ' . $style . '">'
        . $label_html . '</button></form>';
}

// บังคับให้เป็น Admin เท่านั้น
function require_admin()
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: " . BASE_URL . "/user/profile.php");
        exit;
    }
}

// ตั้งข้อความแจ้งเตือนแล้ว redirect
function redirect_with($url, $type, $message)
{
    $_SESSION[$type] = $message;
    header("Location: $url");
    exit;
}

// แสดงข้อความแจ้งเตือน success / error แล้วลบออกจาก Session
function show_flash()
{
    foreach (['success' => 'var(--neon-cyan)', 'error' => 'var(--neon-red)'] as $type => $color) {
        if (!empty($_SESSION[$type])) {
            echo '<div style="background: rgba(255,255,255,0.04); border: 1px solid ' . $color . '; color: ' . $color . '; padding: 12px; border-radius: 8px; margin: 15px 0;">'
                . e($_SESSION[$type]) . '</div>';
            unset($_SESSION[$type]);
        }
    }
}

// ดึงทีมที่ผู้ใช้เป็นหัวหน้า (1 คนมีได้ 1 ทีม)
function get_led_team(PDO $pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE leader_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

// ดึงทีมที่ผู้ใช้สังกัด (เป็นหัวหน้า หรืออยู่ในรายชื่อสมาชิก)
function get_user_team(PDO $pdo, $user_id)
{
    $team = get_led_team($pdo, $user_id);
    if ($team) {
        return $team;
    }
    $stmt = $pdo->prepare("SELECT t.* FROM teams t JOIN team_members tm ON t.team_id = tm.team_id WHERE tm.user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

// ตรวจสอบลิงก์ให้เป็น http/https เท่านั้น (กัน javascript: ฯลฯ)
function clean_url($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }
    return preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

// ---------- แบ่งหน้า (20 รายการต่อหน้า) ----------
const PER_PAGE = 20;

// ตัดรายการเฉพาะหน้าปัจจุบัน (?page=) คืนค่า [รายการในหน้านี้, หน้าปัจจุบัน, จำนวนหน้าทั้งหมด]
function paginate_rows(array $rows)
{
    $pages = max(1, (int)ceil(count($rows) / PER_PAGE));
    $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
    return [array_slice($rows, ($page - 1) * PER_PAGE, PER_PAGE), $page, $pages];
}

// ลิงก์เปลี่ยนหน้า (คงตัวกรองอื่นใน query string ไว้)
function pagination_links($page, $pages)
{
    if ($pages <= 1) {
        return '';
    }
    $link = function ($n, $label, $active = false) {
        $qs = http_build_query(array_merge($_GET, ['page' => $n]));
        $style = $active ? 'background: var(--neon-cyan, #00e5ff); color: #000; border-color: var(--neon-cyan, #00e5ff);' : 'color: #aaa;';
        return '<a href="?' . e($qs) . '" style="padding: 6px 12px; border: 1px solid #333; border-radius: 6px; text-decoration: none; ' . $style . '">' . $label . '</a>';
    };
    $html = '<div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; margin-top: 16px;">';
    if ($page > 1) {
        $html .= $link($page - 1, '&laquo; ก่อนหน้า');
    }
    for ($n = max(1, $page - 2); $n <= min($pages, $page + 2); $n++) {
        $html .= $link($n, $n, $n === $page);
    }
    if ($page < $pages) {
        $html .= $link($page + 1, 'ถัดไป &raquo;');
    }
    return $html . '<span style="align-self: center; color: #777; font-size: 0.85rem; margin-left: 6px;">หน้า ' . $page . ' / ' . $pages . '</span></div>';
}

// ตำแหน่งของฮีโร่ในเกม RoV (ใช้ในระบบจัดการฮีโร่และการจัดกลุ่มในฟอร์ม)
const HERO_ROLES = ['Dark Slayer', 'Jungle', 'Mid Lane', 'Dragon Lane', 'Support / Tank'];

// ตำแหน่งของสมาชิกในทีม [ค่าที่เก็บ => ข้อความที่แสดง]
const MEMBER_ROLES = [
    'Dark Slayer' => 'Dark Slayer Lane',
    'Jungle'      => 'Jungle',
    'Mid Lane'    => 'Mid Lane',
    'Dragon Lane' => 'Abyssal Dragon Lane',
    'Support'     => 'Support / Roaming',
    'Sub'         => 'Sub / สำรอง',
];

// รูปแบบการแข่ง Bo1–Bo7 (ใช้ทั้งผลการซ้อมและประกาศหาทีมซ้อม)
const MATCH_TYPES = ['Bo1', 'Bo2', 'Bo3', 'Bo4', 'Bo5', 'Bo6', 'Bo7'];

// ลบซีรีส์ที่ไม่มีเกมเหลืออยู่ของทีม (ยกเว้นซีรีส์ที่สร้างจากคำขอซ้อม ซึ่งรอโค้ชบันทึกผล)
function cleanup_empty_series(PDO $pdo, $team_id)
{
    $pdo->prepare("DELETE s FROM scrim_series s LEFT JOIN scrim_matches m ON m.series_id = s.series_id
        WHERE s.team_id = ? AND s.request_id IS NULL AND m.match_id IS NULL")->execute([$team_id]);
}

// ผลรวมของซีรีส์ เช่น "2–1" (นับจากเกมที่บันทึกแล้ว เล่นไม่ครบก็แสดงเท่าที่เล่น)
function series_scores(PDO $pdo, $team_id)
{
    $stmt = $pdo->prepare("SELECT s.series_id, s.opponent_name, s.best_of, s.series_date, s.request_id,
            COUNT(m.match_id) AS games, COALESCE(SUM(m.match_result = 'WIN'), 0) AS wins, COALESCE(SUM(m.match_result = 'LOSS'), 0) AS losses
        FROM scrim_series s LEFT JOIN scrim_matches m ON m.series_id = s.series_id
        WHERE s.team_id = ? GROUP BY s.series_id");
    $stmt->execute([$team_id]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) {
        $out[(int)$r['series_id']] = $r;
    }
    return $out;
}

// แรงก์ในเกม (ใช้ในประกาศหาทีมซ้อมและข้อมูลผู้เล่น)
const RANKS = ['Diamond', 'Commander', 'Conqueror', 'Supreme Conqueror', 'Glorious Ruler', 'Eternal Legend'];
