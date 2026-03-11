<?php
session_start();
require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Events.php';

if (empty($_SESSION['user_id'])) {
    header("Location: /entrypj/templates/sign_in.php");
    exit();
}

// ต้องระบุ event_id ถึงจะดูสถิติได้
$event_id = $_GET['event_id'] ?? 0;
if ($event_id == 0) {
    echo "<script>alert('ไม่พบรหัสกิจกรรม'); window.history.back();</script>";
    exit();
}

$event = getEventById($event_id);

// ป้องกันคนอื่นแอบดูสถิติ (ต้องเป็นผู้จัดเท่านั้น)
if ($event['organizer_id'] != $_SESSION['user_id']) {
    echo "<script>alert('คุณไม่มีสิทธิ์ดูสถิติของกิจกรรมนี้'); window.location.href='/entrypj/templates/home.php';</script>";
    exit();
}

$conn = getConnection();

// 1. ดึงสถิติเพศ (เฉพาะที่ได้รับอนุมัติในกิจกรรมนี้)
$genders = ['ชาย' => 0, 'หญิง' => 0, 'อื่นๆ/ไม่ระบุ' => 0];
$stmt = $conn->prepare("SELECT u.gender, COUNT(*) as cnt FROM users u JOIN registrations r ON u.user_id = r.user_id WHERE r.event_id = ? AND (r.status = 'approved' OR r.status = 'Approved') GROUP BY u.gender");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $g = $row['gender'];
    if ($g == 'Male') $genders['ชาย'] += $row['cnt'];
    elseif ($g == 'Female') $genders['หญิง'] += $row['cnt'];
    else $genders['อื่นๆ/ไม่ระบุ'] += $row['cnt'];
}
$stmt->close();
$max_gender = max($genders) > 0 ? max($genders) : 1;

// 2. ดึงสถิติจังหวัด (เฉพาะที่ได้รับอนุมัติในกิจกรรมนี้)
$provinces = [];
$max_prov = 0;
$stmt2 = $conn->prepare("SELECT u.province, COUNT(*) as cnt FROM users u JOIN registrations r ON u.user_id = r.user_id WHERE r.event_id = ? AND (r.status = 'approved' OR r.status = 'Approved') AND u.province != '' GROUP BY u.province ORDER BY cnt DESC LIMIT 5");
$stmt2->bind_param("i", $event_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $provinces[$row['province']] = $row['cnt'];
    if ($row['cnt'] > $max_prov) $max_prov = $row['cnt'];
}
$stmt2->close();
if ($max_prov == 0) $max_prov = 1;

// 3. ช่วงอายุ (เฉพาะที่ได้รับอนุมัติในกิจกรรมนี้)
$age_ranges = ['ต่ำกว่า 18 ปี' => 0, '18-24 ปี' => 0, '25-34 ปี' => 0, '35-44 ปี' => 0, '45 ปีขึ้นไป' => 0];
$stmt3 = $conn->prepare("SELECT u.birthdate FROM users u JOIN registrations r ON u.user_id = r.user_id WHERE r.event_id = ? AND (r.status = 'approved' OR r.status = 'Approved')");
$stmt3->bind_param("i", $event_id);
$stmt3->execute();
$res3 = $stmt3->get_result();
while ($row = $res3->fetch_assoc()) {
    if (empty($row['birthdate'])) continue;
    $age = (new DateTime($row['birthdate']))->diff(new DateTime('today'))->y;
    if ($age < 18) $age_ranges['ต่ำกว่า 18 ปี']++;
    elseif ($age >= 18 && $age <= 24) $age_ranges['18-24 ปี']++;
    elseif ($age >= 25 && $age <= 34) $age_ranges['25-34 ปี']++;
    elseif ($age >= 35 && $age <= 44) $age_ranges['35-44 ปี']++;
    else $age_ranges['45 ปีขึ้นไป']++;
}
$stmt3->close();
$max_age = max($age_ranges) > 0 ? max($age_ranges) : 1;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สถิติกิจกรรม</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .stat-row { margin-bottom: 15px; }
        .stat-label { display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 5px; color: #555; }
        .bar-bg { width: 100%; background-color: #ecf0f1; border-radius: 5px; height: 24px; overflow: hidden; }
        .bar-fill { height: 100%; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; color: white; font-size: 0.85em; font-weight: bold; width: 0; animation: fillBar 1s ease-out forwards; }
        @keyframes fillBar { from { width: 0; } }
    </style>
</head>
<body>
    <?php include 'header.php'; ?> 
    <div class="container">
        <a href="/entrypj/templates/event_registrations.php?event_id=<?php echo $event_id; ?>" style="text-decoration:none; font-weight:bold; color:#7f8c8d;">⬅ กลับหน้าจัดการผู้เข้าร่วม</a>
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">📊 สถิติผู้เข้าร่วม: <?php echo htmlspecialchars($event['event_name']); ?></h2>

        <div class="card">
            <h3>🚻 สัดส่วนเพศผู้เข้าร่วม</h3>
            <?php foreach($genders as $label => $count): ?>
                <?php $color = ($label == 'ชาย') ? '#3498db' : (($label == 'หญิง') ? '#e74c3c' : '#95a5a6'); ?>
                <div class="stat-row">
                    <div class="stat-label"><span><?php echo $label; ?></span> <span><?php echo $count; ?> คน</span></div>
                    <div class="bar-bg"><div class="bar-fill" style="background-color: <?php echo $color; ?>; width: <?php echo ($count/$max_gender)*100; ?>%;"></div></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>🎂 ช่วงอายุผู้เข้าร่วม</h3>
            <?php foreach($age_ranges as $label => $count): ?>
                <?php if ($count == 0) continue; ?>
                <div class="stat-row">
                    <div class="stat-label"><span><?php echo $label; ?></span> <span><?php echo $count; ?> คน</span></div>
                    <div class="bar-bg"><div class="bar-fill" style="background-color: #2ecc71; width: <?php echo ($count/$max_age)*100; ?>%;"></div></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>📍 จังหวัดที่เข้าร่วม</h3>
            <?php if (empty($provinces)): ?> <p style="text-align:center; color:#95a5a6;">ยังไม่มีข้อมูล</p> <?php endif; ?>
            <?php foreach($provinces as $label => $count): ?>
                <div class="stat-row">
                    <div class="stat-label"><span><?php echo htmlspecialchars($label); ?></span> <span><?php echo $count; ?> คน</span></div>
                    <div class="bar-bg"><div class="bar-fill" style="background-color: #f39c12; width: <?php echo ($count/$max_prov)*100; ?>%;"></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>