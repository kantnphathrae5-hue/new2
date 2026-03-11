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

// 1. รับค่าการค้นหา และ ค่าตัวกรอง Tab (ค่าเริ่มต้นคือ available)
$search_name = $_GET['search_name'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$current_filter = $_GET['filter'] ?? 'available'; 

// 2. ดึงกิจกรรมทั้งหมดตามการค้นหามาก่อน
$all_events = searchEventsForHome($_SESSION['user_id'], $search_name, $start_date, $end_date);

// 3. เตรียม Array สำหรับเก็บกิจกรรมที่ถูกคัดกรองแล้ว
$filtered_events = [];
global $conn;

if (!empty($all_events)) {
    foreach ($all_events as $event) {
        $current_event_id = $event['event_id'];
        
        // เช็คสถานะผู้ใช้งาน
        $registration_status = null;
        $stmt = $conn->prepare("SELECT status FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $current_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $registration_status = strtolower($row['status']);
        }
        $stmt->close();

        // นับจำนวนคนที่เข้าร่วม (เฉพาะ Approved)
        $current_joined = 0;
        $stmt_count = $conn->prepare("SELECT COUNT(*) AS total_joined FROM registrations WHERE event_id = ? AND (status = 'approved' OR status = 'Approved')");
        $stmt_count->bind_param("i", $current_event_id);
        $stmt_count->execute();
        $res_count = $stmt_count->get_result();
        if ($row_count = $res_count->fetch_assoc()) {
            $current_joined = $row_count['total_joined'];
        }
        $stmt_count->close();

        // เช็คเงื่อนไขคนเต็ม และ เวลาจบกิจกรรม
        $is_full = ($event['max_participants'] > 0 && $current_joined >= $event['max_participants']);
        $is_ended = (time() > strtotime($event['end_date']));

        // --- ระบบจัดหมวดหมู่ (หัวใจสำคัญของการแบ่งหน้า) ---
        $event_category = 'available'; // ค่าเริ่มต้นคือ เข้าร่วมได้

        if ($registration_status == 'approved' || $registration_status == 'pending') {
            // ถ้ารออนุมัติ หรือ อนุมัติแล้ว ให้อยู่ในหมวด "เข้าร่วมแล้ว" (แม้กิจกรรมจะจบก็ยังแสดงให้ดูประวัติ)
            $event_category = 'joined';
        } elseif ($registration_status == 'rejected' || $is_ended || $is_full) {
            // ถ้าถูกปฏิเสธ หรือ กิจกรรมจบแล้ว หรือ เต็มแล้ว ให้อยู่ในหมวด "ไม่สามารถเข้าร่วมได้"
            $event_category = 'unavailable';
        }

        // คัดเฉพาะกิจกรรมที่ตรงกับ Tab ที่กำลังกดดูอยู่เท่านั้น ไปแสดงผล
        if ($current_filter == $event_category) {
            $event['registration_status'] = $registration_status;
            $event['current_joined'] = $current_joined;
            $event['is_full'] = $is_full;
            $event['is_ended'] = $is_ended;
            $event['cover_image'] = getEventCoverImage($current_event_id); // โหลดรูปปกมาเผื่อไว้เลย
            
            $filtered_events[] = $event;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการกิจกรรมทั้งหมด</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .search-container { background-color: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        .search-container input { padding: 8px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-search { background-color: #3498db; color: white; border: none; padding: 9px 15px; border-radius: 4px; cursor: pointer; }
        .btn-clear { background-color: #95a5a6; color: white; text-decoration: none; padding: 9px 15px; border-radius: 4px; }
        
        /* สไตล์สำหรับปุ่ม Tabs (แบ่งหน้า) */
        .tabs-container { display: flex; justify-content: center; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .btn-tab { padding: 12px 25px; background-color: #fff; color: #7f8c8d; text-decoration: none; border-radius: 8px; font-weight: bold; border: 2px solid #ecf0f1; transition: 0.3s; }
        .btn-tab:hover { border-color: #bdc3c7; color: #2c3e50; }
        .btn-tab.active { background-color: #3498db; color: white; border-color: #3498db; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); }

        .event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .event-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); }
        .event-img { width: 100%; height: 200px; object-fit: cover; background-color: #eee; }
        .event-info { padding: 15px; flex-grow: 1; }
        .event-title { font-size: 1.25em; font-weight: bold; color: #2c3e50; margin: 0 0 10px 0; }
        .event-detail { font-size: 0.9em; color: #555; margin-bottom: 8px; display: flex; align-items: center; }
        .event-detail strong { width: 80px; display: inline-block; color: #333; }
        .event-actions { padding: 15px; border-top: 1px solid #eee; background-color: #fafafa; }
        .btn-join { width: 100%; background-color: #27ae60; color: white; border: none; padding: 12px; font-size: 1em; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-join:hover { background-color: #219653; }
        .no-events { text-align: center; padding: 50px; background: #fff; border-radius: 8px; color: #7f8c8d; border: 1px dashed #bdc3c7; font-size: 1.2em;}
    </style>
</head>
<body>

    <?php include 'header.php' ?>

    <h2 style="text-align: center; color: #2c3e50; margin-bottom: 25px;">📅 รายการกิจกรรมที่น่าสนใจ</h2>

    <div class="tabs-container">
        <a href="?filter=available&search_name=<?php echo urlencode($search_name); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
           class="btn-tab <?php echo $current_filter == 'available' ? 'active' : ''; ?>">🎯 กิจกรรมที่เปิดรับสมัคร</a>
        
        <a href="?filter=joined&search_name=<?php echo urlencode($search_name); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
           class="btn-tab <?php echo $current_filter == 'joined' ? 'active' : ''; ?>">✅ เข้าร่วมแล้ว / รออนุมัติ</a>
        
        <a href="?filter=unavailable&search_name=<?php echo urlencode($search_name); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
           class="btn-tab <?php echo $current_filter == 'unavailable' ? 'active' : ''; ?>">⛔ ปฏิเสธ / จบแล้ว / เต็ม</a>
    </div>

    <div class="search-container">
        <form method="GET" action="" style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 10px;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($current_filter); ?>">

            <label>ชื่อกิจกรรม:</label>
            <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="ค้นหาชื่อกิจกรรม...">
            <label>ตั้งแต่วันที่:</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            <label>ถึงวันที่:</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            <button type="submit" class="btn-search">🔍 ค้นหา</button>
            <a href="/entrypj/templates/home.php?filter=<?php echo $current_filter; ?>" class="btn-clear">❌ ล้างค่า</a>
        </form>
    </div>

    <?php if (!empty($filtered_events)): ?>
        <div class="event-grid">
            <?php foreach ($filtered_events as $event): ?>
                <div class="event-card">
                    <img src="<?php echo htmlspecialchars($event['cover_image']); ?>" alt="รูปกิจกรรม" class="event-img">

                    <div class="event-info">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                        <div class="event-detail"><strong>ผู้จัดงาน:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?></div>
                        <div class="event-detail"><strong>วันที่:</strong> <?php echo date('d/m/Y H:i', strtotime($event['start_date'])); ?></div>
                        <div class="event-detail"><strong>สถานที่:</strong> <?php echo htmlspecialchars($event['location']); ?></div>
                        <div class="event-detail">
                            <strong>รับสมัคร:</strong> 
                            <span style="<?php echo $event['is_full'] ? 'color: red; font-weight: bold;' : 'color: green; font-weight: bold;'; ?>">
                                <?php echo $event['current_joined']; ?>/<?php echo $event['max_participants']; ?> คน
                            </span>
                        </div>
                    </div>
                    
                    <a href="/entrypj/templates/event_detail.php?id=<?php echo $event['event_id']; ?>" style="display: block; text-align: center; padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 0.9em; margin: 0 15px 15px 15px;">
                        🔍 ดูรายละเอียด
                    </a>
                    
                    <div class="event-actions">
                        <?php if ($event['registration_status'] == 'approved'): ?>
                            <div style="text-align: center; background-color: #d4edda; color: #155724; padding: 12px; border: 1px solid #c3e6cb; border-radius: 6px; font-weight: bold;">✅ เข้าร่วมแล้ว</div>
                        <?php elseif ($event['registration_status'] == 'pending'): ?>
                            <div style="text-align: center; background-color: #fff3cd; color: #856404; padding: 12px; border: 1px solid #ffeeba; border-radius: 6px; font-weight: bold;">⏳ รออนุมัติ</div>
                        <?php elseif ($event['registration_status'] == 'rejected'): ?>
                            <div style="text-align: center; background-color: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 6px; font-weight: bold;">❌ ถูกปฏิเสธ</div>
                        <?php elseif ($event['is_ended']): ?>
                            <div style="text-align: center; background-color: #e2e3e5; color: #383d41; padding: 12px; border: 1px solid #d6d8db; border-radius: 6px; font-weight: bold;">⛔ กิจกรรมจบลงแล้ว</div>
                        <?php elseif ($event['is_full']): ?>
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
            📭 ยังไม่มีกิจกรรมในหมวดหมู่นี้ หรือไม่พบกิจกรรมที่ค้นหา
        </div>
    <?php endif; ?>

</body>
</html>