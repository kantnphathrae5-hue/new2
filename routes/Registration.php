<?php
session_start();

// ดึงไฟล์ฐานข้อมูลอย่างปลอดภัย
require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Registrations.php';

// เช็คว่าล็อกอินหรือยัง
if (empty($_SESSION['user_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนทำรายการ'); window.location.href='/templates/sign_in.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ระบบขอเข้าร่วมกิจกรรม (ของผู้ใช้ทั่วไป)
    if ($action == 'request_join') {
        $event_id = intval($_POST['event_id'] ?? 0);
        $user_id = $_SESSION['user_id'];

        if ($event_id > 0) {
            if (createRegistration($user_id, $event_id)) {
                echo "<script>alert('ส่งคำขอเข้าร่วมกิจกรรมสำเร็จ! กรุณารอผู้จัดอนุมัติ'); window.location.href='/entrypj/templates/home.php';</script>";
            } else {
                echo "<script>alert('คุณได้ส่งคำขอเข้าร่วมกิจกรรมนี้ไปแล้ว หรือเกิดข้อผิดพลาด!'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('ข้อมูลกิจกรรมไม่ถูกต้อง'); window.history.back();</script>";
        }
        exit();
    }

    // 2. ระบบจัดการสถานะ อนุมัติ/ปฏิเสธ (ของผู้จัดกิจกรรม)
    if ($action == 'update_status') {
        $registration_id = intval($_POST['registration_id'] ?? 0);
        $status = $_POST['status'] ?? ''; // ค่าที่ส่งมาต้องเป็น 'approved' หรือ 'rejected'

        if ($registration_id > 0 && !empty($status)) {
            // เรียกใช้ฟังก์ชันอัปเดตสถานะ
            if (updateRegistrationStatus($registration_id, $status)) {
                echo "<script>alert('อัปเดตสถานะสำเร็จ!'); window.history.back();</script>";
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตสถานะ'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('ข้อมูลไม่ครบถ้วน'); window.history.back();</script>";
        }
        exit();
    }
}
?>