<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Admin - Thong Dong'); ?></title>

  <!-- base chung (đang xài bên customer) -->
  <link rel="stylesheet" href="/thongdong/assets/css/base.css">

  <!-- admin css -->
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
  <link rel="stylesheet" href="/thongdong/assets/css/responsive.css">

</head>

<body class="admin-body">
<?php include __DIR__ . '/admin-header.php'; ?>
