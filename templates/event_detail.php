<?php

require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Events.php';

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$event = getEventById($event_id);
$images = getAllEventImages($event_id);


if (!$event) {
    echo "<script>alert('ไม่พบข้อมูลกิจกรรมนี้'); window.location.href='/';</script>";
    exit();
}
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
        
        /* สไตล์แกลลอรี่รูปภาพ */
        .gallery-title { color: #2c3e50; margin-bottom: 15px; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .gallery-grid img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease; cursor: pointer; }
        .gallery-grid img:hover { transform: scale(1.03); }
        .no-image { color: #95a5a6; font-style: italic; background: #f1f2f6; padding: 20px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    
    

    <div class="container">
        <a href="/entrypj/templates/home.php" class="btn-back">⬅ กลับหน้ารายการกิจกรรม</a>
        
        <h1>📌 <?php echo htmlspecialchars($event['event_name']); ?></h1>
        
        <div class="detail-box">
            <div class="detail-item">
                <span class="detail-label">รายละเอียด:</span> 
                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
            </div>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">
            <div class="detail-item">
                <span class="detail-label">วันเวลาที่จัด:</span> 
                <?php echo date('d M Y, H:i', strtotime($event['start_date'])); ?> 
                ถึง <?php echo date('d M Y, H:i', strtotime($event['end_date'])); ?>
            </div>
            <div class="detail-item">
                <span class="detail-label">สถานที่:</span> 
                <?php echo htmlspecialchars($event['location']); ?>
            </div>
            <div class="detail-item">
                <span class="detail-label">รับสมัครจำนวน:</span> 
                <?php echo htmlspecialchars($event['max_participants']); ?> คน
            </div>
        </div>

        <h3 class="gallery-title">📸 แกลลอรี่รูปภาพ (<?php echo count($images); ?> รูป)</h3>
        
        <?php if (count($images) > 0): ?>
            <div class="gallery-grid">
                <?php foreach ($images as $img_path): ?>
                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="รูปภาพกิจกรรม">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-image">กิจกรรมนี้ยังไม่มีรูปภาพเพิ่มเติม</div>
        <?php endif; ?>

    </div>
</body>
</html>