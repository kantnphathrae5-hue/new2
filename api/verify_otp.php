<?php
header('Content-Type: application/json');
require_once '../Include/database.php';
require_once '../databases/Registrations.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp']) && isset($_POST['event_id'])) {
    $input_otp = $_POST['otp'];
    $event_id = intval($_POST['event_id']);

    $json_file = '../databases/otp_data.json';
    if (!file_exists($json_file)) {
         echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลระบบ OTP']);
         exit;
    }

    $otp_data = json_decode(file_get_contents($json_file), true);
    $matched_email = null;

    foreach ($otp_data as $email => $data) {
        if ($data['code'] == $input_otp) {
            if (time() > $data['expires_at']) {
                unset($otp_data[$email]);
                file_put_contents($json_file, json_encode($otp_data, JSON_PRETTY_PRINT));
                echo json_encode(['status' => 'error', 'message' => 'รหัสหมดอายุแล้ว กรุณาให้ผู้เข้าร่วมกดขอใหม่']);
                exit;
            }
            $matched_email = $email;
            break;
        }
    }

    if (!$matched_email) {
        echo json_encode(['status' => 'error', 'message' => 'รหัส OTP ไม่ถูกต้อง หรือไม่มีในระบบ']);
        exit;
    }

    global $conn;
    $sql = "SELECT r.registration_id, u.name 
            FROM registrations r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE u.email = ? AND r.event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $matched_email, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $registration_id = $row['registration_id'];
        // ดักกรณีไม่มีชื่อ ให้แสดงข้อความแทน
        $user_name = !empty($row['name']) ? $row['name'] : 'ไม่ระบุชื่อ'; 

        updateCheckInStatus($registration_id, 1);

        unset($otp_data[$matched_email]);
        file_put_contents($json_file, json_encode($otp_data, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'เช็คอินสำเร็จ!',
            'user_name' => $user_name,
            'reg_id' => $registration_id // ส่ง ID กลับไปอัปเดตตาราง
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้ใช้นี้ในกิจกรรมนี้']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
}
?>