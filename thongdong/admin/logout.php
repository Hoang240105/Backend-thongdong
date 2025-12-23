<?php
session_start();
unset($_SESSION['admin']);
header('Location: /thongdong/admin/login.php');
exit;
