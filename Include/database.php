<?php
    mysqli_report(MYSQLI_REPORT_OFF);
$hostname = 'gonggang.net';
$dbName = 'u910454988_entrypj';
$username = 'u910454988_entrypj';
$password = '[Z8H>|Kz:[9D@mR7';
$conn = new mysqli($hostname, $username, $password, $dbName);

if (!$conn->connect_error) {
    $conn->set_charset("utf8mb4");
}


if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}
if ($conn) {
    $conn->set_charset("utf8mb4");
}

function getConnection(): mysqli
{
    global $conn;
    if (!$conn || $conn->connect_error) {
        // ถ้ารหัสผ่านผิด หรือต่อไม่ติด จะโชว์หน้านี้แทน Error 500
        die("<div style='padding: 20px; border: 2px solid red; background: #fee; text-align: center; font-family: sans-serif;'>
                <h2>❌ เชื่อมต่อฐานข้อมูลไม่สำเร็จ</h2>
                <p><b>สาเหตุ:</b> " . ($conn ? $conn->connect_error : mysqli_connect_error()) . "</p>
                <p>กรุณาตรวจสอบ Hostname, Username, Password และชื่อ Database ในไฟล์ <b>Include/database.php</b> ว่าตรงกับของ InfinityFree หรือไม่</p>
             </div>");
    }
    return $conn;
}

/// ฟังก์ชันสร้างรหัส OTP ตามเวลาสดๆ (ไม่ลง DB)
function getDynamicOTP($user_id, $event_id, $time_offset = 0) {
    $secret_key = "MyEventSecret2026"; 
    $time_window = floor(time() / 1800) + $time_offset; 
    $hash = md5($secret_key . $user_id . $event_id . $time_window);
    $numbers = preg_replace("/[^0-9]/", "", $hash);
    return str_pad(substr($numbers, 0, 6), 6, '0', STR_PAD_RIGHT);
}

// ฟังก์ชันตรวจสอบความถูกต้อง
function verifyDynamicOTP($user_id, $event_id, $input_otp) {
    return ($input_otp === getDynamicOTP($user_id, $event_id, 0) || 
            $input_otp === getDynamicOTP($user_id, $event_id, -1));
}
?>

