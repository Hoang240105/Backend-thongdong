<?php
session_start();

// Xóa toàn bộ dữ liệu phiên làm việc (Bao gồm user_id, role, customer, cart...)
session_unset(); 
session_destroy(); 

// Chuyển hướng về trang Đăng nhập để bạn có thể đăng nhập lại ngay
header('Location: login.php');
exit;
?>