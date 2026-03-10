<?php
session_start();

require_once __DIR__ . '/../Include/database.php';

// ตรวจสอบการล็อกอิน
if (empty($_SESSION['user_id'])) {
    header("Location: /entrypj/templates/sign_in.php");
    exit();
}

$conn = getConnection();

// ---------------------------------------------------------
// 1. ดึงและคำนวณข้อมูลสถิติ: เพศ
// ---------------------------------------------------------
$genders = ['ชาย' => 0, 'หญิง' => 0, 'อื่นๆ/ไม่ระบุ' => 0];
$res_gender = $conn->query("SELECT gender, COUNT(*) as cnt FROM users GROUP BY gender");
while ($row = $res_gender->fetch_assoc()) {
    $g = $row['gender'];
    $cnt = $row['cnt'];
    if ($g == 'Male') $genders['ชาย'] += $cnt;
    elseif ($g == 'Female') $genders['หญิง'] += $cnt;
    else $genders['อื่นๆ/ไม่ระบุ'] += $cnt;
}
// หาค่ามากสุดเพื่อนำไปคำนวณความยาวของหลอดกราฟ CSS (ป้องกันหาร 0)
$max_gender = max($genders) > 0 ? max($genders) : 1;

// ---------------------------------------------------------
// 2. ดึงและคำนวณข้อมูลสถิติ: จังหวัด (Top 10)
// ---------------------------------------------------------
$provinces = [];
$max_prov = 0;
$res_prov = $conn->query("SELECT province, COUNT(*) as cnt FROM users WHERE province != '' AND province IS NOT NULL GROUP BY province ORDER BY cnt DESC LIMIT 10");
while ($row = $res_prov->fetch_assoc()) {
    $provinces[$row['province']] = $row['cnt'];
    if ($row['cnt'] > $max_prov) {
        $max_prov = $row['cnt'];
    }
}
if ($max_prov == 0) $max_prov = 1;

// ---------------------------------------------------------
// 3. ดึงและคำนวณข้อมูลสถิติ: ช่วงอายุ
// ---------------------------------------------------------
$age_ranges = [
    'ต่ำกว่า 18 ปี' => 0,
    '18-24 ปี' => 0,
    '25-34 ปี' => 0,
    '35-44 ปี' => 0,
    '45-54 ปี' => 0,
    '55 ปีขึ้นไป' => 0,
    'ไม่ระบุ' => 0
];
$res_age = $conn->query("SELECT birthdate FROM users");
while ($row = $res_age->fetch_assoc()) {
    if (empty($row['birthdate'])) {
        $age_ranges['ไม่ระบุ']++;
        continue;
    }
    
    // คำนวณอายุจากวันเกิดด้วย PHP
    $birthDate = new DateTime($row['birthdate']);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;

    if ($age < 18) $age_ranges['ต่ำกว่า 18 ปี']++;
    elseif ($age >= 18 && $age <= 24) $age_ranges['18-24 ปี']++;
    elseif ($age >= 25 && $age <= 34) $age_ranges['25-34 ปี']++;
    elseif ($age >= 35 && $age <= 44) $age_ranges['35-44 ปี']++;
    elseif ($age >= 45 && $age <= 54) $age_ranges['45-54 ปี']++;
    else $age_ranges['55 ปีขึ้นไป']++;
}
$max_age = max($age_ranges) > 0 ? max($age_ranges) : 1;

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สถิติผู้ใช้งานระบบ</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 0; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #7f8c8d; font-weight: bold; transition: 0.2s; }
        .btn-back:hover { color: #3498db; }
        
        .card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card h3 { color: #34495e; margin-top: 0; margin-bottom: 20px; }
        
        /* สไตล์ของกราฟแท่งที่สร้างด้วย CSS */
        .stat-row { margin-bottom: 15px; }
        .stat-label { display: flex; justify-content: space-between; font-weight: bold; font-size: 0.95em; margin-bottom: 5px; color: #555; }
        .bar-bg { width: 100%; background-color: #ecf0f1; border-radius: 5px; height: 24px; overflow: hidden; position: relative; }
        
        /* แอนิเมชันให้แท่งกราฟค่อยๆ วิ่งตอนเปิดหน้า */
        .bar-fill { height: 100%; border-radius: 5px; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; color: white; font-size: 0.85em; font-weight: bold;
                    animation: fillBar 1s ease-out forwards; width: 0; }
        
        @keyframes fillBar { from { width: 0; } }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?> 

    <div class="container">
        <a href="/entrypj/templates/home.php" class="btn-back">⬅ กลับหน้าหลัก</a>
        <h2>📊 สถิติภาพรวมผู้ใช้งานระบบ</h2>

        <div class="card">
            <h3>🚻 สัดส่วนเพศผู้ใช้งาน</h3>
            <?php foreach($genders as $label => $count): ?>
                <?php 
                    $percent = ($count / $max_gender) * 100; 
                    // แยกสีตามเพศ
                    $color = ($label == 'ชาย') ? '#3498db' : (($label == 'หญิง') ? '#e74c3c' : '#95a5a6');
                ?>
                <div class="stat-row">
                    <div class="stat-label"><span><?php echo $label; ?></span> <span><?php echo $count; ?> คน</span></div>
                    <div class="bar-bg">
                        <div class="bar-fill" style="background-color: <?php echo $color; ?>; width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>🎂 ช่วงอายุผู้ใช้งาน</h3>
            <?php foreach($age_ranges as $label => $count): ?>
                <?php if ($count == 0) continue; // ข้ามช่วงอายุที่ไม่มีคน ?>
                <?php $percent = ($count / $max_age) * 100; ?>
                <div class="stat-row">
                    <div class="stat-label"><span><?php echo $label; ?></span> <span><?php echo $count; ?> คน</span></div>
                    <div class="bar-bg">
                        <div class="bar-fill" style="background-color: #2ecc71; width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <h3>📍 10 อันดับจังหวัดที่มีผู้ใช้งานมากที่สุด</h3>
            <?php if (empty($provinces)): ?>
                <p style="text-align: center; color: #95a5a6;">ยังไม่มีข้อมูลจังหวัด</p>
            <?php else: ?>
                <?php foreach($provinces as $label => $count): ?>
                    <?php $percent = ($count / $max_prov) * 100; ?>
                    <div class="stat-row">
                        <div class="stat-label"><span><?php echo htmlspecialchars($label); ?></span> <span><?php echo $count; ?> คน</span></div>
                        <div class="bar-bg">
                            <div class="bar-fill" style="background-color: #f39c12; width: <?php echo $percent; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>