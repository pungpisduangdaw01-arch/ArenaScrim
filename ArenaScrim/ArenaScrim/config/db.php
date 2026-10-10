<?php
$host = 'localhost';
$dbname = 'arenascrim_db';
$username = 'root'; // ปรับตามเครื่องของคุณ
$password = '';     // ปรับตามเครื่องของคุณ (ถ้า XAMPP ปกติจะว่างไว้)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // เก็บรายละเอียดไว้ใน error log ของเซิร์ฟเวอร์ ไม่แสดงให้ผู้ใช้เห็น
    error_log("ArenaScrim DB connection failed: " . $e->getMessage());
    http_response_code(503);
    die("ระบบฐานข้อมูลไม่พร้อมใช้งานในขณะนี้ กรุณาลองใหม่อีกครั้งภายหลัง");
}

require_once __DIR__ . '/functions.php';
