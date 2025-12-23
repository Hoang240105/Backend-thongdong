<?php
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP mặc định pass là rỗng
$dbname = "thongdong_db";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập font chữ tiếng Việt
$conn->set_charset("utf8mb4");
?>