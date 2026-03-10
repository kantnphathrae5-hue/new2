<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Events.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /entrypj/templates/sign_in.php");
    exit();
}

$search_name = $_GET['search_name'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$events = searchEventsForHome($_SESSION['user_id'], $search_name, $start_date, $end_date);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการกิจกรรมทั้งหมด</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .search-container { background-color: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        .search-container input { padding: 8px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-search { background-color: #3498db; color: white; border: none; padding: 9px 15px; border-radius: 4px; cursor: pointer; }
        .btn-clear { background-color: #95a5a6; color: white; text-decoration: none; padding: 9px 15px; border-radius: 4px; }
        .event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .event-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); }
        .event-img { width: 100%; height: 200px; object-fit: cover; background-color: #eee; }
        .event-info { padding: 15px; flex-grow: 1; }
        .event-title { font-size: 1.25em; font-weight: bold; color: #2c3e50; margin: 0 0 10px 0; }
        .event-detail { font-size: 0.9em; color: #555; margin-bottom: 8px; display: flex; align-items: center; }
        .event-detail strong { width: 80px; display: inline-block; color: #333; }
        .event-actions { padding: 15px; border-top: 1px solid #eee; background-color: #fafafa; }
        .btn-join { width: 100%; background-color: #27ae60; color: white; border: none; padding: 12px; font-size: 1em; border-radius: 6px; cursor: pointer; transition: background 0.3s; font-weight: bold; }
        .btn-join:hover { background-color: #219653; }
        .no-events { text-align: center; padding: 50px; background: #fff; border-radius: 8px; color: #7f8c8d; border: 1px solid #ddd; }
    </style>
</head>
<body>

    <?php include 'header.php' ?>

    <h2>📅 รายการกิจกรรมที่น่าสนใจ</h2>

    <div class="search-container">
        <form method="GET" action="">
            <label>ชื่อกิจกรรม:</label>
            <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="ค้นหาชื่อกิจกรรม...">
            <label>ตั้งแต่วันที่:</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            <label>ถึงวันที่:</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            <button type="submit" class="btn-search">🔍 ค้นหา</button>
            <a href="/entrypj/templates/home.php" class="btn-clear">❌ ล้างค่า</a>
        </form>
    </div>

    <?php if (!empty($events)): ?>
        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <?php
                $cover_image = getEventCoverImage($event['event_id']);
                $current_event_id = $event['event_id'];
                
                global $conn; 
                
                // 1. เช็คสถานะผู้ใช้
                $registration_status = null;
                $stmt = $conn->prepare("SELECT status FROM registrations WHERE user_id = ? AND event_id = ?");
                $stmt->bind_param("ii", $_SESSION['user_id'], $current_event_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $registration_status = strtolower($row['status']);
                }
                $stmt->close();

                // 2. นับจำนวนคนที่เข้าร่วม (เฉพาะคนที่ Approved)
                $current_joined = 0;
                $stmt_count = $conn->prepare("SELECT COUNT(*) AS total_joined FROM registrations WHERE event_id = ? AND (status = 'approved' OR status = 'Approved')");
                $stmt_count->bind_param("i", $current_event_id);
                $stmt_count->execute();
                $res_count = $stmt_count->get_result();
                if ($row_count = $res_count->fetch_assoc()) {
                    $current_joined = $row_count['total_joined'];
                }
                $stmt_count->close();

                // 3. ตั้งค่าเงื่อนไข
                $is_full = ($event['max_participants'] > 0 && $current_joined >= $event['max_participants']);
                $is_ended = (time() > strtotime($event['end_date'])); // เช็คว่าเลยวันจบกิจกรรมหรือยัง
                ?>
                
                <div class="event-card">
                    <img src="<?php echo htmlspecialchars($cover_image); ?>" alt="รูปกิจกรรม" class="event-img">

                    <div class="event-info">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h3>

                        <div class="event-detail"><strong>ผู้จัดงาน:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?></div>
                        <div class="event-detail"><strong>วันที่:</strong> <?php echo date('d/m/Y H:i', strtotime($event['start_date'])); ?></div>
                        <div class="event-detail"><strong>สถานที่:</strong> <?php echo htmlspecialchars($event['location']); ?></div>
                        <div class="event-detail">
                            <strong>รับสมัคร:</strong> 
                            <span style="<?php echo $is_full ? 'color: red; font-weight: bold;' : 'color: green; font-weight: bold;'; ?>">
                                <?php echo $current_joined; ?>/<?php echo $event['max_participants']; ?> คน
                            </span>
                        </div>
                    </div>
                    
                    <a href="/entrypj/templates/event_detail.php?id=<?php echo $event['event_id']; ?>" style="display: block; text-align: center; padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 0.9em; margin: 0 15px 15px 15px;">
                        🔍 ดูรายละเอียด
                    </a>
                    
                    <div class="event-actions">
                        <?php if ($registration_status == 'approved'): ?>
                            <div style="text-align: center; background-color: #d4edda; color: #155724; padding: 12px; border: 1px solid #c3e6cb; border-radius: 6px; font-weight: bold;">✅ เข้าร่วมแล้ว</div>
                        <?php elseif ($registration_status == 'pending'): ?>
                            <div style="text-align: center; background-color: #fff3cd; color: #856404; padding: 12px; border: 1px solid #ffeeba; border-radius: 6px; font-weight: bold;">⏳ รออนุมัติ</div>
                        <?php elseif ($registration_status == 'rejected'): ?>
                            <div style="text-align: center; background-color: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 6px; font-weight: bold;">❌ ถูกปฏิเสธ</div>
                        
                        <?php elseif ($is_ended): ?>
                            <div style="text-align: center; background-color: #e2e3e5; color: #383d41; padding: 12px; border: 1px solid #d6d8db; border-radius: 6px; font-weight: bold;">⛔ กิจกรรมจบลงแล้ว</div>
                        <?php elseif ($is_full): ?>
                            <div style="text-align: center; background-color: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 6px; font-weight: bold;">🚫 ผู้เข้าร่วมเต็มแล้ว</div>
                        
                        <?php else: ?>
                            <form action="/entrypj/routes/Registration.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="request_join">
                                <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                <button type="submit" class="btn-join" onclick="return confirm('ต้องการขอเข้าร่วมกิจกรรมนี้ใช่หรือไม่?');">➕ ขอเข้าร่วมกิจกรรม</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-events">
            <h3>📭 ยังไม่มีกิจกรรมในระบบ หรือไม่พบกิจกรรมที่ค้นหา</h3>
        </div>
    <?php endif; ?>

</body>
</html>