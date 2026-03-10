<?php

function getRegistrationsByEvent($event_id)
{
    global $conn;
    // เปลี่ยน Registrations และ Users เป็นตัวพิมพ์เล็ก
    $sql = "SELECT r.*, u.name, u.gender, u.province, u.email 
            FROM registrations r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.event_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $registrations = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $registrations[] = $row;
        }
    }
    return $registrations;
}

function updateRegistrationStatus($registration_id, $status) {
    global $conn;
    // อัปเดตฟิลด์ status ในตาราง registrations
    $stmt = $conn->prepare("UPDATE registrations SET status = ? WHERE registration_id = ?");
    $stmt->bind_param("si", $status, $registration_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function updateCheckInStatus($registration_id, $is_checked_in)
{
    global $conn;
    $sql = "UPDATE registrations SET is_checked_in = ? WHERE registration_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $is_checked_in, $registration_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function createRegistration($user_id, $event_id)
{
    global $conn;

    $check_sql = "SELECT registration_id FROM registrations WHERE user_id = ? AND event_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $event_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        $check_stmt->close();
        return false; 
    }
    $check_stmt->close();

    $sql = "INSERT INTO registrations (user_id, event_id, status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $event_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

function getUserHistory($user_id) {
    global $conn;
    
    // เปลี่ยน Registrations และ Events เป็นตัวพิมพ์เล็ก
    $sql = "SELECT r.*, e.event_name, e.start_date, e.location 
            FROM registrations r 
            JOIN events e ON r.event_id = e.event_id 
            WHERE r.user_id = ? 
            ORDER BY r.registration_id DESC"; 
            
    $stmt = $conn->prepare($sql);
    
    // ดักจับ Error กรณีที่ยังมีชื่อตารางผิดพลาด
    if (!$stmt) {
        die("Error ในฟังก์ชัน getUserHistory: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
    }
    return $history;
}
?>