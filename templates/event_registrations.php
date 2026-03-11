<?php
session_start();
require_once '../Include/database.php';
require_once '../databases/Events.php';
require_once '../databases/Registrations.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /entrypj/templates/sign_in.php");
    exit();
}

$event_id = $_GET['event_id'] ?? 0;
if ($event_id == 0) {
    die("ไม่พบรหัสกิจกรรม");
}

$event = getEventById($event_id);

// ป้องกันคนอื่นแอบเข้า
if ($event['organizer_id'] != $_SESSION['user_id']) {
    echo "<script>alert('คุณไม่มีสิทธิ์จัดการกิจกรรมนี้!'); window.location.href='/entrypj/templates/home.php';</script>";
    exit();
}

$registrations = getRegistrationsByEvent($event_id);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ลงทะเบียน</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-top: 0;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #7f8c8d;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-back:hover {
            color: #3498db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: bold;
        }

        td:nth-child(2) {
            text-align: left;
            font-weight: bold;
            color: #34495e;
        }

        /* จัดชื่อชิดซ้าย */
        tr:hover {
            background-color: #f1f5f9;
        }

        /* สีตัวอักษรสถานะ */
        .text-pending {
            color: #f39c12;
            font-weight: bold;
        }

        .text-approved {
            color: #27ae60;
            font-weight: bold;
        }

        .text-rejected {
            color: #e74c3c;
            font-weight: bold;
        }

        /* สไตล์ปุ่มกด */
        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            transition: 0.2s;
            font-size: 0.9em;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-approve {
            background-color: #2ecc71;
        }

        .btn-approve:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .btn-reject {
            background-color: #e74c3c;
        }

        .btn-reject:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <a href="/entrypj/templates/manage_event.php" class="btn-back">⬅ กลับหน้าจัดการกิจกรรม</a>
        <a href="/entrypj/templates/statistics.php?event_id=<?php echo $event_id; ?>" style="display: inline-block; margin-bottom: 20px; margin-left: 15px; text-decoration: none; background: #9b59b6; color: white; padding: 5px 15px; border-radius: 5px; font-weight: bold;">
            📊 ดูสถิติกิจกรรมนี้
        </a>
        <h2>👥 ผู้ลงทะเบียน: <?php echo htmlspecialchars($event['event_name']); ?></h2>

        <table>
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>เพศ</th>
                    <th>จังหวัด</th>
                    <th>สถานะปัจจุบัน</th>
                    <th>จัดการอนุมัติ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registrations)): ?>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>#<?php echo $reg['registration_id']; ?></td>
                            <td><?php echo htmlspecialchars($reg['name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['gender']); ?></td>
                            <td><?php echo htmlspecialchars($reg['province']); ?></td>

                            <?php
                            $status = empty($reg['status']) ? 'Pending' : $reg['status'];
                            $class_name = "text-" . strtolower($status);
                            ?>
                            <td class="<?php echo $class_name; ?>"><?php echo $status; ?></td>

                            <td>
                                <?php if ($status != 'approved' && $status != 'Approved'): ?>
                                    <form action="/entrypj/routes/Registration.php" method="POST" style="display:inline-block; margin: 0;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
                                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn-action btn-approve" onclick="return confirm('ยืนยันการอนุมัติ?');">✅ อนุมัติ</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($status != 'rejected' && $status != 'Rejected'): ?>
                                    <form action="/entrypj/routes/Registration.php" method="POST" style="display:inline-block; margin: 0;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
                                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn-action btn-reject" onclick="return confirm('ยืนยันการปฏิเสธ?');">❌ ปฏิเสธ</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">ยังไม่มีผู้ลงทะเบียนในกิจกรรมนี้</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>

</html>