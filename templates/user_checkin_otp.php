<?php
session_start();

require_once __DIR__ . '/../Include/database.php';

// ตรวจสอบการล็อกอิน
if (empty($_SESSION['user_id'])) {
    header("Location: /entrypj/templates/sign_in.php");
    exit();
}

$event_id = $_GET['event_id'] ?? 0;
if ($event_id == 0) {
    echo "<script>alert('ไม่พบข้อมูลกิจกรรม'); window.history.back();</script>";
    exit();
}

// ดึงอีเมลของผู้ใช้จากฐานข้อมูลเพื่อเอาไปส่ง OTP
$user_id = $_SESSION['user_id'];
$conn = getConnection(); 
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$user_email = $userData ? $userData['email'] : '';
$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เช็คอินเข้างาน (OTP)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .otp-container { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 100%; }
        h2 { color: #2c3e50; margin-top: 0; }
        .btn-request { background-color: #f39c12; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 1em; font-weight: bold; margin-bottom: 20px; width: 100%; transition: 0.3s; }
        .btn-request:hover { background-color: #e67e22; }
        .btn-request:disabled { background-color: #bdc3c7; cursor: not-allowed; }
        
        .input-otp { width: 80%; padding: 12px; font-size: 1.2em; text-align: center; letter-spacing: 5px; border: 2px solid #bdc3c7; border-radius: 5px; margin-bottom: 15px; outline: none; transition: 0.3s;}
        .input-otp:focus { border-color: #3498db; }
        
        .btn-submit { background-color: #2ecc71; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 1em; font-weight: bold; width: 100%; transition: 0.3s; }
        .btn-submit:hover { background-color: #27ae60; }
        
        .message-box { margin-top: 15px; font-size: 0.9em; font-weight: bold; height: 20px;}
        .btn-back { display: block; margin-top: 20px; text-decoration: none; color: #7f8c8d; font-size: 0.9em; }
    </style>
</head>
<body>

    <div class="otp-container">
        <h2>📍 เช็คอินเข้าหน้างาน</h2>
        <p style="color: #7f8c8d; font-size: 0.9em; margin-bottom: 25px;">กรุณากดปุ่มเพื่อรับรหัส OTP ทางอีเมล <br><b>(<?php echo htmlspecialchars($user_email); ?>)</b></p>
        
        <button id="btnRequest" class="btn-request" onclick="requestOTP('<?php echo htmlspecialchars($user_email); ?>')">📨 กดเพื่อขอรหัส OTP</button>
        <div id="requestMsg" class="message-box"></div>

        <hr style="border: 0; height: 1px; background: #eee; margin: 25px 0;">

        <input type="text" id="otpInput" class="input-otp" placeholder="รหัส 6 หลัก" maxlength="6">
        <button id="btnSubmit" class="btn-submit" onclick="verifyOTP(<?php echo $event_id; ?>)">✅ ยืนยันการเช็คอิน</button>
        <div id="verifyMsg" class="message-box"></div>

        <a href="/entrypj/templates/history.php" class="btn-back">⬅ กลับหน้าประวัติกิจกรรม</a>
    </div>

    <script>
    function requestOTP(email) {
        if (!email) {
            alert('ไม่พบข้อมูลอีเมล กรุณาเข้าสู่ระบบใหม่'); return;
        }

        const btn = document.getElementById('btnRequest');
        const msg = document.getElementById('requestMsg');
        
        btn.disabled = true;
        btn.innerText = "กำลังส่งอีเมล...";
        msg.style.color = "orange";
        msg.innerText = "กรุณารอสักครู่...";

        const formData = new FormData();
        formData.append('email', email);

        fetch('/entrypj/api/send_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                msg.style.color = "green";
                msg.innerText = "ส่งรหัส OTP ไปที่อีเมลแล้ว! (รหัสมีอายุ 5 นาที)";
                btn.innerText = "ส่งสำเร็จแล้ว";
                
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerText = "📨 ขอรหัส OTP อีกครั้ง";
                    msg.innerText = "";
                }, 60000); // ให้กดขอใหม่ได้เมื่อผ่านไป 1 นาที
            } else {
                msg.style.color = "red";
                msg.innerText = data.message;
                btn.disabled = false;
                btn.innerText = "📨 กดเพื่อขอรหัส OTP";
            }
        })
        .catch(error => {
            msg.style.color = "red";
            msg.innerText = "เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์";
            btn.disabled = false;
            btn.innerText = "📨 กดเพื่อขอรหัส OTP";
        });
    }

    function verifyOTP(eventId) {
        const otpValue = document.getElementById('otpInput').value.trim();
        const msg = document.getElementById('verifyMsg');

        if(otpValue.length !== 6) {
            msg.style.color = "red";
            msg.innerText = "กรุณากรอกรหัส OTP ให้ครบ 6 หลัก";
            return;
        }

        msg.style.color = "orange";
        msg.innerText = "กำลังตรวจสอบ...";

        const formData = new FormData();
        formData.append('otp', otpValue);
        formData.append('event_id', eventId);

        fetch('/entrypj/api/verify_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                msg.style.color = "green";
                msg.innerText = "✅ เช็คอินสำเร็จแล้ว!";
                alert(data.message + " ยินดีต้อนรับคุณ " + data.user_name);
                window.location.href = "/entrypj/templates/history.php"; // เด้งกลับหน้าประวัติ
            } else {
                msg.style.color = "red";
                msg.innerText = "❌ " + data.message;
            }
        })
        .catch(error => {
            msg.style.color = "red";
            msg.innerText = "เกิดข้อผิดพลาดในการส่งข้อมูล";
        });
    }
    </script>
</body>
</html>