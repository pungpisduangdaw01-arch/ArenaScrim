<?php
require_once __DIR__ . '/../config/functions.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'ArenaScrim - RoV Team Management & Scrim Finder') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
    
    <!-- Chart.js สำหรับวาดกราฟสถิติ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ดึง CSS เฉพาะหน้าผ่านตัวแปร $page_css เพียงบรรทัดเดียว -->
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= htmlspecialchars($page_css) ?>.css">
    <?php endif; ?>
</head>
<body>

<nav class="navbar">
    <div class="container navbar-container">
        <a href="<?= BASE_URL ?>/index.php" class="brand-logo" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
    <img src="<?= BASE_URL ?>/assets/images/as1.png" alt="ArenaScrim Logo" style="height: 46px; width: auto;"> 
    Arena Scrim
</a>
        
        <?php
        // เมนูแยกตามสิทธิ์: [ลิงก์, ไอคอน, ข้อความ] และไฮไลต์เมนูของหน้าที่เปิดอยู่
        if (($_SESSION['role'] ?? '') === 'admin') {
            $menu = [
                ['admin/index.php', 'fa-gauge', 'ภาพรวม'],
                ['admin/users.php', 'fa-users-gear', 'จัดการสมาชิก'],
                ['admin/teams.php', 'fa-shield-halved', 'จัดการทีม'],
                ['admin/scrims.php', 'fa-list-check', 'จัดการประกาศ Scrim'],
                ['admin/heroes.php', 'fa-dragon', 'จัดการฮีโร่'],
            ];
        } elseif (isset($_SESSION['user_id'])) {
            $menu = [
                ['user/profile.php', '', 'โปรไฟล์ & สถิติ'],
                ['user/team-manage.php', '', 'จัดการทีม'],
                ['user/match-history.php', '', 'ประวัติการซ้อม'],
                ['user/period-stats.php', '', 'สถิติรายสัปดาห์/เดือน'],
                ['user/opponents.php', '', 'วิเคราะห์คู่แข่ง'],
                ['scrim/index.php', '', 'หาทีมซ้อม (Scrim)'],
            ];
        } else {
            $menu = [];
        }
        $current = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        ?>
        <ul class="nav-menu">
            <?php foreach ($menu as [$href, $icon, $label]): ?>
                <?php $active = substr($current, -strlen('/' . $href)) === '/' . $href; ?>
                <li><a href="<?= BASE_URL ?>/<?= $href ?>" class="nav-link<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>><?php if ($icon): ?><i class="fa-solid <?= $icon ?>"></i> <?php endif; ?><?= $label ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="user-controls">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-name"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($_SESSION['display_name'] ?? '') ?><?php if (($_SESSION['role'] ?? '') === 'admin'): ?> <span style="color: var(--neon-gold); font-size: 0.8rem;">(Admin)</span><?php endif; ?></span>
                <a href="<?= BASE_URL ?>/actions/auth-process.php?action=logout" class="btn-logout">ออกจากระบบ</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn-cyber" style="padding: 6px 16px; font-size: 0.9rem;">เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">