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
    if ($action == 'verify_checkin_otp') {
        $reg_id = intval($_POST['registration_id']);
        $attendee_email = trim($_POST['attendee_email']);
        $event_id = intval($_POST['event_id']);
        $input_otp = trim($_POST['otp']);

        $json_file = __DIR__ . '/../databases/otp_data.json';
        
        if (file_exists($json_file)) {
            $otp_data = json_decode(file_get_contents($json_file), true);

            // ค้นหารหัสจากอีเมลของผู้เข้าร่วม
            if (isset($otp_data[$attendee_email])) {
                if (time() > $otp_data[$attendee_email]['expires_at']) {
                    echo "<script>alert('❌ รหัส OTP หมดอายุแล้ว โปรดให้ผู้เข้าร่วมกดขอใหม่'); window.history.back();</script>";
                } elseif ($otp_data[$attendee_email]['code'] == $input_otp) {
                    
                    // ทำการอัปเดตสถานะเช็คอินลงฐานข้อมูล
                    $conn = getConnection();
                    $stmt = $conn->prepare("UPDATE registrations SET is_checked_in = 1 WHERE registration_id = ?");
                    $stmt->bind_param("i", $reg_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    // ลบรหัสทิ้งหลังใช้เสร็จ ป้องกันการนำมาใช้ซ้ำ
                    unset($otp_data[$attendee_email]);
                    file_put_contents($json_file, json_encode($otp_data, JSON_PRETTY_PRINT));
                    
                    echo "<script>alert('✅ เช็คอินสำเร็จ!'); window.location.href='/entrypj/templates/event_checkin.php?event_id=" . $event_id . "';</script>";
                } else {
                    echo "<script>alert('❌ รหัส OTP ไม่ถูกต้อง!'); window.history.back();</script>";
                }
            } else {
                 echo "<script>alert('❌ ไม่พบรหัส OTP (ผู้เข้าร่วมอาจยังไม่ได้กดขอรหัส หรือรหัสหมดอายุแล้ว)'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('❌ ไม่พบไฟล์ฐานข้อมูล OTP'); window.history.back();</script>";
        }
        exit();
    }
}
?>