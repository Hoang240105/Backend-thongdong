<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<header class="admin-topbar">
  <div class="container admin-topbar-inner">
    <a class="admin-logo" href="/thongdong/admin/dashboard.php">
      <img src="/thongdong/assets/img/patterns/thong-donglogo.png" alt="Thong Dong">
    </a>

    <nav class="admin-nav">
      <a href="/thongdong/admin/dashboard.php">Dashboard</a>
      <a href="/thongdong/admin/products.php">Sản phẩm</a>
      <a href="/thongdong/admin/orders.php">Đơn hàng</a>
      <a href="/thongdong/admin/customers.php">Khách hàng</a>
      <a href="/thongdong/admin/blog.php">Nhật ký</a>
      <a href="/thongdong/admin/returns.php">Đổi trả</a>
      <a href="/thongdong/admin/settings.php">Cài đặt</a>
      <a href="/thongdong/admin/reviews.php">Review</a>

    </nav>

    <div class="admin-right">
      <?php if (!empty($_SESSION['admin'])): ?>
        <div class="admin-pill">
  Admin: <?php echo htmlspecialchars($_SESSION['admin']['name']); ?>
        </div>
        <a class="btn outline" href="/thongdong/admin/logout.php">Đăng xuất</a>
      <?php else: ?>
        <div class="admin-pill">Khu vực quản trị</div>
      <?php endif; ?>
    </div>
  </div>
</header>
