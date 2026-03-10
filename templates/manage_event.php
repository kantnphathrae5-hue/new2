<?php
session_start();
// ✅ เติม __DIR__ เพื่อป้องกันหน้าเว็บพัง (Error 500)
require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Events.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /entrypj/templates/sign_in.php");
    exit();
}

// ดึงเฉพาะกิจกรรมที่ "เราเป็นคนสร้าง"
$events = getEventsByOrganizer($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการกิจกรรมของฉัน</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* จัดหัวข้อและปุ่มสร้างให้อยู่บรรทัดเดียวกัน */
        .header-action { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px; }
        .header-action h2 { color: #2c3e50; margin: 0; }
        
        .btn-create { background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-create:hover { background-color: #2980b9; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; }
        tr:hover { background-color: #f1f5f9; }
        
        .event-name { font-weight: bold; color: #34495e; font-size: 1.05em; }

        /* สไตล์กลุ่มปุ่มจัดการ */
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-action { padding: 8px 12px; text-decoration: none; border-radius: 5px; font-size: 0.85em; font-weight: bold; color: white; transition: 0.2s; display: inline-block; text-align: center; }
        
        .btn-edit { background-color: #f39c12; }
        .btn-edit:hover { background-color: #d68910; transform: translateY(-2px); }
        
        .btn-delete { background-color: #e74c3c; }
        .btn-delete:hover { background-color: #c0392b; transform: translateY(-2px); }
        
        .btn-manage { background-color: #27ae60; }
        .btn-manage:hover { background-color: #219653; transform: translateY(-2px); }

        .empty-state { text-align: center; padding: 50px; color: #95a5a6; font-size: 1.1em; }
    </style>
</head>
<body>
    
    <?php include __DIR__ . '/header.php'; ?>

    <div class="container">
        <div class="header-action">
            <h2>⚙️ จัดการกิจกรรมของฉัน</h2>
            <a href="/entrypj/templates/create_event.php" class="btn-create">➕ สร้างกิจกรรมใหม่</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ชื่อกิจกรรม</th>
                    <th>วันที่เริ่ม</th>
                    <th>สถานที่</th>
                    <th>ผู้เข้าร่วม (สูงสุด)</th>
                    <th style="text-align: center;">จัดการกิจกรรม</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td class="event-name"><?php echo htmlspecialchars($event['event_name']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($event['start_date'])); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td><?php echo $event['max_participants']; ?> คน</td>
                        <td>
                            <div class="action-buttons" style="justify-content: center;">
                                <a href="/entrypj/templates/edit_event.php?id=<?php echo $event['event_id']; ?>" class="btn-action btn-edit">✏️ แก้ไข</a>
                                
                                <a href="/entrypj/templates/event_registrations.php?event_id=<?php echo $event['event_id']; ?>" class="btn-action btn-manage">👥 ดูผู้สมัคร</a>
                                
                                <a href="/entrypj/routes/event.php?action=delete&id=<?php echo $event['event_id']; ?>" class="btn-action btn-delete" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบกิจกรรมนี้? ข้อมูลผู้สมัครทั้งหมดจะถูกลบไปด้วย');">🗑️ ลบ</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                📭 คุณยังไม่ได้สร้างกิจกรรมใดๆ <br><br>
                                <a href="/entrypj/templates/create_event.php" style="color: #3498db; text-decoration: none; font-weight: bold;">คลิกที่นี่เพื่อสร้างกิจกรรมแรกของคุณ</a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>