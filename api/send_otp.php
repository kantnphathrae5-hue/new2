<?php
header('Content-Type: application/json');

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $userEmail = $_POST['email'];
    $otp = rand(100000, 999999);
    
    // ตั้งเวลาหมดอายุ 5 นาที (300 วินาที)
    $expires_at = time() + 1800; 

    // --- ส่วนบันทึกข้อมูลลงไฟล์ JSON แทน Database ---
    $json_file = '../databases/otp_data.json';
    $otp_data = [];
    
    // ถ้ามีไฟล์อยู่แล้วให้อ่านข้อมูลเก่าออกมาก่อน
    if (file_exists($json_file)) {
        $json_content = file_get_contents($json_file);
        if (!empty($json_content)) {
            $otp_data = json_decode($json_content, true);
        }
    }
    
    // บันทึก OTP ของอีเมลนี้ลงไปใหม่ (เขียนทับของเก่า)
    $otp_data[$userEmail] = [
        'code' => $otp,
        'expires_at' => $expires_at
    ];
    
    // เขียนข้อมูลกลับลงไฟล์
    file_put_contents($json_file, json_encode($otp_data, JSON_PRETTY_PRINT));
    // ----------------------------------------------

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'webproject.ajm.noreply@gmail.com'; // อีเมลของคุณ
        $mail->Password   = 'oyrwoartyhssjjww';    // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;

        $mail->setFrom('webproject.ajm.noreply@gmail.com', 'Event Check-in System');
        $mail->addAddress($userEmail);
        $mail->isHTML(true);
        $mail->Subject = 'รหัส OTP สำหรับเช็คอินกิจกรรม';
        $mail->Body    = "กรุณาแจ้งรหัสนี้กับผู้จัดงาน: <b style='font-size:24px; color:blue;'>{$otp}</b><br>รหัสนี้จะหมดอายุภายใน 5 นาที";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'ส่ง OTP ไปที่อีเมลแล้ว']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถส่งอีเมลได้']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
}
?>