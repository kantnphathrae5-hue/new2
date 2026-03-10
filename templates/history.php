<?php
// 1. ต้องเปิดใช้งาน Session เป็นบรรทัดแรกเสมอ
session_start();

require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Registrations.php'; 

// ตรวจสอบการล็อกอิน
if (empty($_SESSION['user_id'])) {
    header("Location: /templates/sign_in.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. ดึงอีเมลด้วยรูปแบบ MySQLi ให้ตรงกับไฟล์ database.php ของคุณ
$conn = getConnection(); 
if ($conn) {
    $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id); // "i" หมายถึง Integer (ตัวเลข)
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $user_email = $userData ? $userData['email'] : '';
    $stmt->close();
} else {
    $user_email = '';
}

// ดึงประวัติกิจกรรม
$history = getUserHistory($user_id);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการเข้าร่วมกิจกรรม</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 0; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #7f8c8d; font-weight: bold; transition: 0.2s; }
        .btn-back:hover { color: #3498db; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; }
        tr:hover { background-color: #f1f5f9; }
        
        .badge { padding: 8px 15px; border-radius: 20px; font-size: 0.9em; font-weight: bold; display: inline-block; text-align: center; min-width: 80px; }
        .badge-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-approved { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-rejected { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .empty-state { text-align: center; padding: 50px; color: #95a5a6; font-size: 1.1em; }
        
        /* สไตล์ปุ่ม OTP */
        .btn-otp { background-color: #3498db; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; font-size: 0.9em; transition: 0.3s; }
        .btn-otp:hover { background-color: #2980b9; }
        .btn-otp:disabled { background-color: #bdc3c7; cursor: not-allowed; }
    </style>
</head>
<body>
    
    <?php include __DIR__ . '/header.php'; ?> 

    <div class="container">
        <a href="/entrypj/templates/home.php" class="btn-back">⬅ กลับหน้ารายการกิจกรรม</a>
        <h2>📜 ประวัติการขอเข้าร่วมกิจกรรมของคุณ</h2>

        <table>
            <thead>
                <tr>
                    <th>ชื่อกิจกรรม</th>
                    <th>วันที่เริ่ม</th>
                    <th>สถานที่</th>
                    <th>สถานะ</th>
                    <th>เช็คอินหน้างาน</th> </tr>
            </thead>
            <tbody>
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $row): ?>
                    <tr>
                        <td style="font-weight: bold; color: #34495e;"><?php echo htmlspecialchars($row['event_name']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['start_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <?php 
                            $status = empty($row['status']) ? 'pending' : strtolower($row['status']); 
                        ?>
                        <td>
                            <span class="badge badge-<?php echo $status; ?>">
                                <?php 
                                    if ($status == 'approved') echo '✅ อนุมัติแล้ว';
                                    elseif ($status == 'rejected') echo '❌ ปฏิเสธ';
                                    else echo '⏳ รออนุมัติ';
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($status == 'approved'): ?>
                                <button class="btn-otp" onclick="requestOTP(this, '<?php echo htmlspecialchars($user_email); ?>')">📍 ขอรหัส OTP</button>
                                <div class="otp-message" style="font-size: 0.85em; margin-top: 5px; font-weight: bold;"></div>
                            <?php else: ?>
                                <span style="color: #95a5a6; font-size: 0.9em;">ยังไม่สามารถเช็คอินได้</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                📭 คุณยังไม่มีประวัติการลงทะเบียนกิจกรรม
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function requestOTP(btn, email) {
        if (!email) {
            alert('ไม่พบข้อมูลอีเมลของคุณ กรุณาล็อกอินใหม่');
            return;
        }

        const msgDiv = btn.nextElementSibling; // div สำหรับแสดงข้อความใต้ปุ่ม
        btn.disabled = true;
        btn.innerText = "กำลังส่ง...";
        msgDiv.style.color = "orange";
        msgDiv.innerText = "กรุณารอสักครู่...";

        const formData = new FormData();
        formData.append('email', email);

        fetch('/api/send_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                msgDiv.style.color = "green";
                msgDiv.innerText = "ส่งแล้ว! โปรดเช็คอีเมล (อายุ 5 นาที)";
                btn.innerText = "ส่งรหัสสำเร็จ";
                
                // ให้กดขอใหม่ได้เมื่อผ่านไป 1 นาที
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerText = "📍 ขอรหัส OTP ใหม่";
                    msgDiv.innerText = "";
                }, 60000);
            } else {
                msgDiv.style.color = "red";
                msgDiv.innerText = data.message;
                btn.disabled = false;
                btn.innerText = "📍 ขอรหัส OTP";
            }
        })
        .catch(error => {
            msgDiv.style.color = "red";
            msgDiv.innerText = "เกิดข้อผิดพลาดในการเชื่อมต่อ";
            btn.disabled = false;
            btn.innerText = "📍 ขอรหัส OTP";
        });
    }
    </script>
</body>
</html>