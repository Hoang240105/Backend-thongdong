<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "Quản trị - Thong Dong"; ?></title>

    <link rel="stylesheet" href="../assets/css/base.css">
    <style>
        :root { --admin-primary: #8e44ad; --admin-bg: #f5f7fa; --sidebar-width: 260px; }
        
        body { background-color: var(--admin-bg); display: flex; min-height: 100vh; margin: 0; font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar bên trái */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid #e1e4e8;
            position: fixed; top: 0; left: 0; bottom: 0;
            padding: 20px; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .admin-brand { font-size: 24px; font-weight: bold; color: var(--admin-primary); text-decoration: none; margin-bottom: 40px; text-align: center; display: block; }
        .nav-menu { list-style: none; padding: 0; margin: 0; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: block; padding: 12px 16px; color: #555; text-decoration: none; border-radius: 8px; font-weight: 500; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #f3e5f5; color: var(--admin-primary); }
        
        /* Nội dung chính bên phải */
        .admin-main { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        .admin-header { background: #fff; padding: 15px 30px; border-bottom: 1px solid #e1e4e8; display: flex; justify-content: flex-end; align-items: center; }
        .admin-content { padding: 30px; flex: 1; }
        
        /* Các nút và bảng */
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none; cursor: pointer; border: none; font-size: 14px; font-weight: 500; }
        .btn.primary { background: var(--admin-primary); color: #fff; }
        .btn.outline { border: 1px solid #ddd; background: #fff; color: #555; }
        .btn.danger { background: #e74c3c; color: #fff; }
        .card { background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); margin-bottom: 20px; }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <a href="/thongdong/index.php" class="admin-brand">Thong Dong Admin</a>
    <ul class="nav-menu">
        <li class="nav-item"><a href="dashboard.php" class="nav-link">Tổng quan</a></li>
        <li class="nav-item"><a href="products.php" class="nav-link">Sản phẩm</a></li>
        <li class="nav-item"><a href="orders.php" class="nav-link">Đơn hàng</a></li>
        <li class="nav-item"><a href="blog.php" class="nav-link active">Nhật ký (Blog)</a></li>
        <li class="nav-item"><a href="users.php" class="nav-link">Người dùng</a></li>
    </ul>
</aside>

<div class="admin-main">
    <header class="admin-header">
        <div style="font-size: 14px;">
            Xin chào, <b><?php echo htmlspecialchars($_SESSION['customer']['name'] ?? 'Admin'); ?></b>
            <span style="margin: 0 10px; color: #ddd;">|</span>
            <a href="../customer/logout.php" style="color: #e74c3c; text-decoration: none;">Đăng xuất</a>
        </div>
    </header>

    <div class="admin-content">