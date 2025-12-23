<?php
session_start();
echo "<h3>Thông tin phiên đăng nhập hiện tại:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>