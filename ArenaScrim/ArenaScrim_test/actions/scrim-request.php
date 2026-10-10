<?php
session_start();
require_once '../config/db.php';

$user_id = require_login();
$action = $_GET['action'] ?? '';
$back = "../scrim/index.php";

// ทุกคำสั่งในไฟล์นี้แก้ไขข้อมูล จึงต้องเป็น POST ที่มี CSRF token ถูกต้องเท่านั้น
verify_csrf($back);

// ต้องเป็นโค้ชของทีมจึงจะขอซ้อม / ยกเลิก / รับคำขอได้
$team = get_led_team($pdo, $user_id);
if (!$team) {
    redirect_with($back, 'error', "เฉพาะโค้ชของทีมเท่านั้นที่ขอซ้อมหรือรับคำขอได้");
}
$team_id = (int)$team['team_id'];

// --- 1. ขอซ้อมจากประกาศของทีมอื่น ---
if ($action === 'request') {
    $post_id = (int)($_POST['post_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if (mb_strlen($message) > 255) {
        redirect_with($back, 'error', "ข้อความถึงทีมเจ้าของประกาศยาวเกิน 255 ตัวอักษร");
    }
    $stmt = $pdo->prepare("SELECT team_id FROM scrim_posts WHERE post_id = ? AND status = 'open' AND scrim_time > NOW()");
    $stmt->execute([$post_id]);
    $owner = $stmt->fetchColumn();
    if ($owner === false) {
        redirect_with($back, 'error', "ประกาศนี้ปิดรับแล้ว หรือไม่พบประกาศ");
    }
    if ((int)$owner === $team_id) {
        redirect_with($back, 'error', "ขอซ้อมกับประกาศของทีมตัวเองไม่ได้");
    }
    $stmt = $pdo->prepare("SELECT request_id, status FROM scrim_requests WHERE post_id = ? AND team_id = ?");
    $stmt->execute([$post_id, $team_id]);
    $old = $stmt->fetch();
    if ($old && $old['status'] !== 'cancelled') {
        redirect_with($back, 'error', $old['status'] === 'pending' ? "ทีมของคุณส่งคำขอนี้ไปแล้ว" : "ประกาศนี้ตอบคำขอของทีมคุณไปแล้ว");
    }
    if ($old) {
        // เคยยกเลิกไปแล้ว ขอใหม่ได้
        $pdo->prepare("UPDATE scrim_requests SET status = 'pending', message = ?, created_at = NOW(), responded_at = NULL WHERE request_id = ?")
            ->execute([$message ?: null, $old['request_id']]);
    } else {
        $pdo->prepare("INSERT INTO scrim_requests (post_id, team_id, message) VALUES (?, ?, ?)")->execute([$post_id, $team_id, $message ?: null]);
    }
    redirect_with($back, 'success', "ส่งคำขอซ้อมแล้ว รอทีมเจ้าของประกาศตอบรับ");
}

// --- 2. ยกเลิกคำขอของทีมตัวเอง (ก่อนถูกรับ) ---
if ($action === 'cancel') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE scrim_requests SET status = 'cancelled', responded_at = NOW() WHERE request_id = ? AND team_id = ? AND status = 'pending'");
    $stmt->execute([$request_id, $team_id]);
    if ($stmt->rowCount() > 0) {
        redirect_with($back, 'success', "ยกเลิกคำขอซ้อมแล้ว");
    }
    redirect_with($back, 'error', "ไม่พบคำขอนี้ หรือคำขอถูกตอบไปแล้ว");
}

// --- 3. เจ้าของประกาศรับคำขอ 1 ทีม: คำขออื่นถูกปฏิเสธ และสร้างซีรีส์รอบันทึกผลให้ทั้งสองทีม ---
if ($action === 'accept') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    try {
        $pdo->beginTransaction();
        // ล็อกประกาศไว้ กันการรับคำขอซ้อนกันสองครั้ง
        $stmt = $pdo->prepare("SELECT r.request_id, r.team_id AS req_team, p.post_id, p.match_format, p.scrim_time,
                rt.team_name AS req_team_name
            FROM scrim_requests r
            JOIN scrim_posts p ON p.post_id = r.post_id
            JOIN teams rt ON rt.team_id = r.team_id
            WHERE r.request_id = ? AND r.status = 'pending' AND p.team_id = ? AND p.status = 'open'
            FOR UPDATE");
        $stmt->execute([$request_id, $team_id]);
        $req = $stmt->fetch();
        if (!$req) {
            $pdo->rollBack();
            redirect_with($back, 'error', "ไม่พบคำขอนี้ หรือประกาศไม่ได้เปิดรับแล้ว");
        }
        $pdo->prepare("UPDATE scrim_requests SET status = 'accepted', responded_at = NOW() WHERE request_id = ?")->execute([$request_id]);
        $pdo->prepare("UPDATE scrim_requests SET status = 'rejected', responded_at = NOW() WHERE post_id = ? AND status = 'pending'")
            ->execute([$req['post_id']]);
        $pdo->prepare("UPDATE scrim_posts SET status = 'matched' WHERE post_id = ?")->execute([$req['post_id']]);

        // ซีรีส์ของแต่ละทีมแยกกัน (แต่ละทีมบันทึกผลและเห็นเฉพาะของตัวเอง)
        $best_of = (int)substr($req['match_format'], 2);
        $ins = $pdo->prepare("INSERT INTO scrim_series (team_id, opponent_name, best_of, series_date, request_id) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$team_id, $req['req_team_name'], $best_of, $req['scrim_time'], $request_id]);
        $ins->execute([(int)$req['req_team'], $team['team_name'], $best_of, $req['scrim_time'], $request_id]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("scrim-request accept: " . $e->getMessage());
        redirect_with($back, 'error', "เกิดข้อผิดพลาดในการรับคำขอ กรุณาลองใหม่อีกครั้ง");
    }
    redirect_with($back, 'success', "รับคำขอซ้อมของ {$req['req_team_name']} แล้ว · ระบบสร้างซีรีส์รอบันทึกผลให้ทั้งสองทีม");
}

header("Location: $back");
exit;
