<?php
function getUser():mysqli_result|bool
{
    global $conn;
    $sql = 'select * from users';
    $result = $conn->query($sql);
    return $result;
}

function createUser($data) {
    global $conn;
    
    if (!isset($data['name'])) {
        die("Error: Name data is missing.");
    }

    if (getUserByEmail($data['email'])) {
        return false; 
    }

    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

    // ✅ แก้ไข: เปลี่ยน Users เป็น users (ตัวพิมพ์เล็ก) ให้ตรงกับฐานข้อมูล
    $sql = "INSERT INTO users (name, gender, birthdate, province, email, password) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssssss", 
        $data['name'], 
        $data['gender'], 
        $data['birthdate'], 
        $data['province'], 
        $data['email'], 
        $hashed_password
    );

    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}
?>