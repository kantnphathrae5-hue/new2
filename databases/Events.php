<?php

function getAllEvents() {
    global $conn;
    
    // เปลี่ยน Events เป็น events และ Users เป็น users
    $sql = "SELECT e.*, u.name AS organizer_name 
            FROM events e 
            LEFT JOIN users u ON e.organizer_id = u.user_id 
            ORDER BY e.start_date DESC";
            
    $result = $conn->query($sql);
    
    $events = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    
    return $events;
}

function getEventById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// อัปเดตข้อมูลกิจกรรม
function updateEvent($id, $data) {
    global $conn;
    $stmt = $conn->prepare("UPDATE events SET event_name=?, description=?, start_date=?, end_date=?, max_participants=?, location=? WHERE event_id=?");
    
    $stmt->bind_param("ssssisi", 
        $data['event_name'], 
        $data['description'], 
        $data['start_date'], 
        $data['end_date'], 
        $data['max_participants'], 
        $data['location'],
        $id
    );

    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ลบข้อมูลกิจกรรม
function deleteEvent($id) {
    global $conn;
    
    // เปลี่ยน Event_Images และ Registrations เป็นตัวเล็ก
    $conn->query("DELETE FROM event_images WHERE event_id = " . intval($id));
    $conn->query("DELETE FROM registrations WHERE event_id = " . intval($id));
    
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function createEvent($data) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO events (organizer_id, event_name, description, start_date, end_date, max_participants, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issssis", 
        $data['organizer_id'], $data['event_name'], $data['description'], 
        $data['start_date'], $data['end_date'], $data['max_participants'], $data['location']
    );

    if ($stmt->execute()) {
        $last_id = $conn->insert_id; 
        $stmt->close();
        return $last_id; 
    }
    
    $stmt->close();
    return false;
}

function addEventImage($event_id, $image_path) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO event_images (event_id, image_path) VALUES (?, ?)");
    $stmt->bind_param("is", $event_id, $image_path);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function getEventsForHome($current_user_id) {
    global $conn;
    $sql = "SELECT e.*, u.name as organizer_name 
            FROM events e 
            JOIN users u ON e.organizer_id = u.user_id 
            WHERE e.organizer_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    return $events;
}

function getEventsByOrganizer($organizer_id) {
    global $conn;
    $sql = "SELECT e.*, u.name as organizer_name 
            FROM events e 
            JOIN users u ON e.organizer_id = u.user_id 
            WHERE e.organizer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    return $events;
}

function searchEventsForHome($current_user_id, $search_name = '', $start_date = '', $end_date = '') {
    global $conn;
    
    $sql = "SELECT e.*, u.name as organizer_name 
            FROM events e 
            JOIN users u ON e.organizer_id = u.user_id 
            WHERE e.organizer_id != ?";
            
    $types = "i";
    $params = [$current_user_id];
    
    if (!empty($search_name)) {
        $sql .= " AND e.event_name LIKE ?";
        $types .= "s";
        $params[] = "%" . $search_name . "%";
    }
    
    if (!empty($start_date)) {
        $sql .= " AND DATE(e.start_date) >= ?";
        $types .= "s";
        $params[] = $start_date;
    }
    
    if (!empty($end_date)) {
        $sql .= " AND DATE(e.start_date) <= ?"; 
        $types .= "s";
        $params[] = $end_date;
    }
    
    $sql .= " ORDER BY e.start_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("<div style='padding: 20px; border: 2px solid red; background: #fee; text-align: center; font-family: sans-serif; margin: 20px;'>
                <h2>❌ คำสั่ง SQL มีปัญหา!</h2>
                <p><b>ข้อผิดพลาดจากฐานข้อมูล MySQL:</b> <span style='color: red;'>" . $conn->error . "</span></p>
             </div>");
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }
    return $events;
}

// ดึงรูปภาพหน้าปกกิจกรรม
function getEventCoverImage($event_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT image_path FROM event_images WHERE event_id = ? LIMIT 1");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['image_path'];
    }
    return 'https://via.placeholder.com/300x200?text=No+Image'; 
}
// ดึงรูปภาพทั้งหมดของกิจกรรม
function getAllEventImages($event_id) {
    global $conn;
    // ใช้ตาราง event_images (ตัวพิมพ์เล็ก) ให้ตรงกับฐานข้อมูลออนไลน์
    $stmt = $conn->prepare("SELECT image_path FROM event_images WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row['image_path'];
        }
    }
    return $images;
}
// ลบรูปภาพเก่าทั้งหมดของกิจกรรม (เพื่อเตรียมอัปโหลดรูปใหม่ทับ)
function deleteAllEventImages($event_id) {
    global $conn;
    // ใช้ event_images (ตัวพิมพ์เล็ก) ให้ตรงกับฐานข้อมูลออนไลน์
    $stmt = $conn->prepare("DELETE FROM event_images WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>