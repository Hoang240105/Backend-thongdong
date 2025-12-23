<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (empty($_SESSION['admin'])) {
  header('Location: /thongdong/admin/login.php');
  exit;
}
