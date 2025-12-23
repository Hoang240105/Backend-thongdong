<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo $pageTitle ?? "Thong Dong"; ?></title>

  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/customer.css">
  <link rel="stylesheet" href="/thongdong/assets/css/responsive.css">

</head>
<body class="customer-bg">

<?php include __DIR__ . '/customer-header.php'; ?>
