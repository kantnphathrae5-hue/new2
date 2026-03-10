<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
// เติม __DIR__ เข้าไปเพื่อบอกตำแหน่งที่ชัดเจน เซิร์ฟเวอร์จะได้ไม่บล็อก
require_once __DIR__ . '/../Include/database.php';
require_once __DIR__ . '/../databases/Events.php';

// ปุ่ม ลบ
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;

    if ($action == 'delete' && $id > 0) {
        if (deleteEvent($id)) {
            // ✅ เปลี่ยนให้เด้งไป manage_event แทน home
            echo "<script>alert('ลบกิจกรรมเรียบร้อยแล้ว'); window.location.href='/entrypj/templates/manage_event.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการลบ'); window.history.back();</script>";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    //เช็คการเข้าสู่ระบบก่อน
    if (empty($_SESSION['user_id'])) {
        echo "<script>alert('กรุณาเข้าสู่ระบบก่อนทำรายการ!'); window.location.href='/templates/sign_in.php';</script>";
        exit();
    }


    $data = [
        'organizer_id'     => $_SESSION['user_id'],
        'event_name'       => $_POST['event_name'] ?? '',
        'description'      => $_POST['description'] ?? '',
        'start_date'       => $_POST['start_date'] ?? '',
        'end_date'         => $_POST['end_date'] ?? '',
        'max_participants' => !empty($_POST['max_participants']) ? $_POST['max_participants'] : null,
        'location'         => $_POST['location'] ?? ''
    ];

    // แก้ไขกิจกรรม
    if ($action == 'update') {
        $id = $_POST['event_id'];
        
        if (updateEvent($id, $data)) {
            
            // เช็คว่ามีการเลือกรูปใหม่เข้ามาหรือไม่
            if (isset($_FILES['event_images']) && !empty($_FILES['event_images']['name'][0])) {
                
                // ✅ เพิ่มคำสั่งลบประวัติรูปเก่าทิ้งก่อน (ต้องไปเพิ่มฟังก์ชันนี้ใน databases/Events.php ด้วยนะครับ)
                deleteAllEventImages($id);
                
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $fileCount = count($_FILES['event_images']['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['event_images']['error'][$i] === UPLOAD_ERR_OK) {
                        
                        $file_name = time() . '_' . uniqid() . '_' . basename($_FILES['event_images']['name'][$i]);
                        $target_file = $upload_dir . $file_name;

                        if (move_uploaded_file($_FILES['event_images']['tmp_name'][$i], $target_file)) {
                            $image_path = '/entrypj/uploads/' . $file_name;
                            
                            addEventImage($id, $image_path); 
                        }
                    }
                }
            }

            echo "<script>alert('แก้ไขข้อมูลกิจกรรมสำเร็จ!'); window.location.href='/entrypj/templates/manage_event.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการแก้ไขข้อมูล'); window.history.back();</script>";
        }
        exit();
    }

    //สร้างกิจกรรมใหม่ 
    elseif ($action == 'create') {

        $new_event_id = createEvent($data);

        if ($new_event_id) {

            $upload_dir = __DIR__ . '/../uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (isset($_FILES['event_images']) && !empty($_FILES['event_images']['name'][0])) {

                $fileCount = count($_FILES['event_images']['name']);

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['event_images']['error'][$i] === UPLOAD_ERR_OK) {

                        $file_name = time() . '_' . uniqid() . '_' . basename($_FILES['event_images']['name'][$i]);
                        $target_file = $upload_dir . $file_name;

                        if (move_uploaded_file($_FILES['event_images']['tmp_name'][$i], $target_file)) {

                            $image_path = '/entrypj/uploads/' . $file_name;
                            addEventImage($new_event_id, $image_path);
                        }
                    }
                }
            }

            echo "<script>
                    alert('บันทึกกิจกรรมและอัปโหลดรูปภาพเรียบร้อยแล้ว!'); 
                    window.location.href='/entrypj/templates/manage_event.php';
                  </script>";
            exit();
        } else {
            echo "<script>
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูลกิจกรรมลงฐานข้อมูล'); 
                    window.history.back();
                  </script>";
            exit();
        }
    }
}
?>