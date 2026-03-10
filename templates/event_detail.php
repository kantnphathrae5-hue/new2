<?php
session_start();

require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Events.php';

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'] ?? null; 

$event = getEventById($event_id);
$images = getAllEventImages($event_id);

if (!$event) {
    echo "<script>alert('ไม่พบข้อมูลกิจกรรมนี้'); window.location.href='/';</script>";
    exit();
}

global $conn;

// 1. เช็คสถานะผู้ใช้งาน
$registration_status = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT status FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $registration_status = strtolower($row['status']); 
    }
    $stmt->close();
}

// 2. นับจำนวนคนเข้าร่วมที่ได้รับการอนุมัติ
$current_joined = 0;
$stmt_count = $conn->prepare("SELECT COUNT(*) AS total_joined FROM registrations WHERE event_id = ? AND (status = 'approved' OR status = 'Approved')");
$stmt_count->bind_param("i", $event_id);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
if ($row_count = $res_count->fetch_assoc()) {
    $current_joined = $row_count['total_joined'];
}
$stmt_count->close();

// 3. กำหนดเงื่อนไขคนเต็มและเวลาจบกิจกรรม
$is_full = ($event['max_participants'] > 0 && $current_joined >= $event['max_participants']);
$is_ended = (time() > strtotime($event['end_date'])); 
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($event['event_name']); ?> - รายละเอียด</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #7f8c8d; font-weight: bold; transition: 0.2s; }
        .btn-back:hover { color: #3498db; }
        h1 { color: #2c3e50; margin-top: 0; border-bottom: 2px solid #3498db; padding-bottom: 15px; }
        .detail-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; line-height: 1.6; }
        .detail-item { margin-bottom: 10px; }
        .detail-label { font-weight: bold; color: #34495e; width: 120px; display: inline-block; }
        .action-box { text-align: center; margin: 30px 0; padding: 25px; background: #f0f8ff; border-radius: 8px; border: 2px dashed #bcdcff; }
        .btn-join { background-color: #3498db; color: white; padding: 12px 25px; border: none; border-radius: 5px; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-join:hover { background-color: #2980b9; transform: translateY(-2px); }
        .status-badge { display: inline-block; padding: 12px 25px; border-radius: 5px; font-size: 1.1em; font-weight: bold; }
        .status-approved { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-rejected { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-ended { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .status-full { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .gallery-title { color: #2c3e50; margin-bottom: 15px; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .gallery-grid img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; }
        .no-image { color: #95a5a6; font-style: italic; background: #f1f2f6; padding: 20px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    
    <div class="container">
        <a href="/entrypj/templates/home.php" class="btn-back">⬅ กลับหน้ารายการกิจกรรม</a>
        <h1>📌 <?php echo htmlspecialchars($event['event_name']); ?></h1>
        
        <div class="detail-box">
            <div class="detail-item"><span class="detail-label">รายละเอียด:</span> <?php echo nl2br(htmlspecialchars($event['description'])); ?></div>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">
            <div class="detail-item"><span class="detail-label">วันเวลาที่จัด:</span> <?php echo date('d M Y, H:i', strtotime($event['start_date'])); ?> ถึง <?php echo date('d M Y, H:i', strtotime($event['end_date'])); ?></div>
            <div class="detail-item"><span class="detail-label">สถานที่:</span> <?php echo htmlspecialchars($event['location']); ?></div>
            <div class="detail-item">
                <span class="detail-label">รับสมัครจำนวน:</span> 
                <span style="<?php echo $is_full ? 'color: red; font-weight: bold;' : 'color: green; font-weight: bold;'; ?>">
                    <?php echo $current_joined; ?> / <?php echo htmlspecialchars($event['max_participants']); ?> คน
                </span>
            </div>
        </div>

        <div class="action-box">
            <?php if (!$user_id): ?>
                <p style="color: #7f8c8d; margin-bottom: 15px;">กรุณาเข้าสู่ระบบเพื่อขอเข้าร่วมกิจกรรมนี้</p>
                <a href="/entrypj/templates/sign_in.php" class="btn-join" style="text-decoration: none;">🔒 เข้าสู่ระบบ</a>
                
            <?php else: ?>
                <?php if ($registration_status == 'approved'): ?>
                    <div class="status-badge status-approved">✅ คุณได้รับอนุมัติให้เข้าร่วมกิจกรรมนี้แล้ว</div>
                <?php elseif ($registration_status == 'pending'): ?>
                    <div class="status-badge status-pending">⏳ อยู่ระหว่างรอผู้จัดงานอนุมัติคำขอของคุณ</div>
                <?php elseif ($registration_status == 'rejected'): ?>
                    <div class="status-badge status-rejected">❌ ขออภัย คำขอเข้าร่วมของคุณถูกปฏิเสธ</div>
                
                <?php elseif ($is_ended): ?>
                    <div class="status-badge status-ended">⛔ กิจกรรมนี้จบลงแล้ว ไม่สามารถเข้าร่วมได้</div>
                <?php elseif ($is_full): ?>
                    <div class="status-badge status-full">🚫 ผู้เข้าร่วมเต็มแล้ว (<?php echo $current_joined; ?>/<?php echo $event['max_participants']; ?> คน)</div>
                
                <?php else: ?>
                    <form action="/entrypj/routes/Registration.php" method="POST">
                        <input type="hidden" name="action" value="request_join">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <button type="submit" class="btn-join" onclick="return confirm('ยืนยันการขอเข้าร่วมกิจกรรมนี้?');">➕ ขอเข้าร่วมกิจกรรม</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <h3 class="gallery-title">📸 แกลลอรี่รูปภาพ (<?php echo count($images); ?> รูป)</h3>
        <?php if (count($images) > 0): ?>
            <div class="gallery-grid">
                <?php foreach ($images as $img_path): ?>
                    <?php 
                        $rawPath = $img_path ?? '';
                        $cleanPath = str_replace('/entrypj', '', $rawPath);
                        $displayPath = !empty($cleanPath) ? '/entrypj' . $cleanPath : ''; 
                    ?>
                    <img src="<?php echo htmlspecialchars($displayPath); ?>" alt="รูปภาพกิจกรรม">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-image">กิจกรรมนี้ยังไม่มีรูปภาพเพิ่มเติม</div>
        <?php endif; ?>
    </div>
</body>
</html>