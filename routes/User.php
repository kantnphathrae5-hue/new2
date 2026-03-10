<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ✅ แก้ไข: ใช้ __DIR__ นำหน้าเพื่อป้องกันระบบมองหาไฟล์ผิดที่จนเกิด Error หน้าขาว
require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Users.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // สมัครสมาชิก
    if ($action == 'register') {
        $userData = [
            'name'      => $_POST['name']?? null,
            'gender'    => $_POST['gender']?? null,
            'birthdate' => $_POST['birthdate']?? null,
            'province'  => $_POST['province']?? null,
            'email'     => $_POST['email']?? null,
            'password'  => $_POST['password']?? null
        ];

        if (createUser($userData)) {
            echo "<script>
                    alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'); 
                    window.location.href='/entrypj/templates/sign_in.php';
                  </script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด! อีเมลนี้อาจมีผู้ใช้งานแล้ว'); window.history.back();</script>";
        }
        exit();
    
    // เข้าสู่ระบบ 
    } elseif ($action == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $user = getUserByEmail($email);

        // ✅ แก้ไข: เพิ่ม password_verify เพื่อให้ถอดรหัสผ่านตอนล็อกอินได้ (สำคัญมาก!)
        if ($user && password_verify($password, $user['password'])) {
           
            $_SESSION['user_id'] = $user['user_id']; 
            $_SESSION['name'] = $user['name'];
            
            $show_name = htmlspecialchars($user['name']);
            // แก้ให้เด้งกลับไปที่หน้าแรก
            echo "<script>
                    alert('เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ $show_name'); 
                    window.location.href='/entrypj/templates/home.php';
                  </script>";
        } else {
            echo "<script>alert('อีเมลหรือรหัสผ่านไม่ถูกต้อง'); window.history.back();</script>";
        }
        exit();
    }
}

// ออกจากระบบ 
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $get_action = $_GET['action'] ?? '';

    if ($get_action == 'logout') {
        session_unset(); 
        session_destroy(); 
        echo "<script>
                alert('ออกจากระบบเรียบร้อยแล้ว ไว้พบกันใหม่ครับ!');
                window.location.href='/entrypj/templates/sign_in.php';
              </script>";
        exit();
    }
}
?>