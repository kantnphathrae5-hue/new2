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
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
        }

        .search-container {
            background-color: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .search-container input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn-search {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 9px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-clear {
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            padding: 9px 15px;
            border-radius: 4px;
        }

        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .event-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .event-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #eee;
        }

        .event-info {
            padding: 15px;
            flex-grow: 1;
            /* ดันปุ่มลงไปล่างสุดเสมอ */
        }

        .event-title {
            font-size: 1.25em;
            font-weight: bold;
            color: #2c3e50;
            margin: 0 0 10px 0;
        }

        .event-detail {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .event-detail strong {
            width: 80px;
            display: inline-block;
            color: #333;
        }

        .event-actions {
            padding: 15px;
            border-top: 1px solid #eee;
            background-color: #fafafa;
        }

        .btn-join {
            width: 100%;
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px;
            font-size: 1em;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: bold;
        }

        .btn-join:hover {
            background-color: #219653;
        }

        .no-events {
            text-align: center;
            padding: 50px;
            background: #fff;
            border-radius: 8px;
            color: #7f8c8d;
            border: 1px solid #ddd;
        }
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
                // ดึงรูปหน้าปกของกิจกรรมนี้
                $cover_image = getEventCoverImage($event['event_id']);
                ?>
                <div class="event-card">
                    <img src="<?php echo htmlspecialchars($cover_image); ?>" alt="รูปกิจกรรม" class="event-img">

                    <div class="event-info">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['event_name']); ?></h3>

                        <div class="event-detail">
                            <strong>ผู้จัดงาน:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?>
                        </div>
                        <div class="event-detail">
                            <strong>วันที่:</strong> <?php echo date('d/m/Y H:i', strtotime($event['start_date'])); ?>
                        </div>
                        <div class="event-detail">
                            <strong>สถานที่:</strong> <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                        <div class="event-detail">
                            <strong>รับสมัคร:</strong> <?php echo $event['max_participants']; ?> คน
                        </div>
                    </div>
                    <a href="/entrypj/templates/event_detail.php?id=<?php echo $event['event_id']; ?>" style="display: inline-block; padding: 8px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; font-size: 0.9em; margin-top: 10px;">
                        🔍 ดูรายละเอียด
                    </a>
                    <div class="event-actions">
                        <form action="/entrypj/routes/Registration.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="request_join">
                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                            <button type="submit" class="btn-join" onclick="return confirm('ต้องการขอเข้าร่วมกิจกรรมนี้ใช่หรือไม่?');">
                                ขอเข้าร่วมกิจกรรม
                            </button>
                        </form>
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