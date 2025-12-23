<?php
// BẮT BUỘC: header dùng session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>

<header class="site-header">
  <div class="container header-inner">

    <!-- LOGO -->
    <a href="/thongdong/customer/index.php" class="logo">
      <img
        src="/thongdong/assets/img/patterns/thong-donglogo.png"
        alt="Thong Dong">
    </a>

    <!-- MENU -->
    <nav class="main-nav">
      <a href="/thongdong/customer/index.php">Trang chủ</a>
      <a href="/thongdong/customer/shop.php">Cửa hàng</a>
      <a href="/thongdong/customer/blog.php">Nhật ký</a>
      <a href="/thongdong/customer/about.php">Giới thiệu</a>
      <a href="/thongdong/customer/cart.php">Giỏ hàng</a>
      <a href="/thongdong/customer/returns.php">Chính sách đổi trả</a>
      <a href="/thongdong/customer/reviews.php">Review</a>



      <?php if (!empty($_SESSION['customer'])): ?>
        <!-- ĐÃ ĐĂNG NHẬP -->
        <a class="btn outline" href="/thongdong/customer/account.php">
          <?php echo htmlspecialchars($_SESSION['customer']['name']); ?>
        </a>
        <a href="/thongdong/customer/logout.php" class="btn outline">
          Đăng xuất
        </a>
      <?php else: ?>
        <!-- CHƯA ĐĂNG NHẬP -->
        <a class="btn outline" href="/thongdong/customer/login.php">
          Đăng nhập
        </a>
      <?php endif; ?>
    </nav>

  </div>
</header>
