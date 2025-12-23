<?php
session_start();
// 1. Kết nối Database
require_once '../includes/db.php';

$pageTitle = "Trang chủ - Thong Dong";

// 2. Lấy 4 sản phẩm nổi bật (Mới nhất)
$sql_featured = "SELECT * FROM Products WHERE status = 'active' ORDER BY created_at DESC LIMIT 4";
$featured = $conn->query($sql_featured);

// 3. Tìm bài viết "Cách thắp nến" (hoặc bài hướng dẫn bất kỳ)
// Tìm bài có tiêu đề chứa chữ "thắp" hoặc "hướng dẫn"
$sql_howto = "SELECT * FROM BlogPosts WHERE status = 'published' AND (title LIKE '%thắp%' OR title LIKE '%hướng dẫn%') LIMIT 1";
$howto_res = $conn->query($sql_howto);
$howto = $howto_res->fetch_assoc();

// Helper format tiền
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

include '../includes/customer-layout-top.php';
?>

<main>

  <section class="hero">
    <div class="container hero-inner hero-card">
      <div class="hero-logo">
        <img src="/thongdong/assets/img/patterns/thong-donglogo.png" alt="Thong Dong" onerror="this.style.display='none'">
      </div>

      <p class="hero-text">Nến thơm thuần Việt dành cho người Việt.</p>

      <div class="hero-actions">
        <a href="shop.php" class="btn">Xem cửa hàng</a>
        <a href="blog.php" class="btn outline">Đọc Nhật ký</a>
      </div>
    </div>
  </section>
  
  <div class="hero-media">
    <img src="/thongdong/assets/img/banner/hero.png" alt="Thong Dong banner">
  </div>

  <section class="home-section">
    <div class="container">
      <div class="home-head">
        <div>
          <h2 class="home-title">Sản phẩm nổi bật</h2>
          <p class="muted" style="margin:0;">Chọn một mùi hương hợp mood hôm nay.</p>
        </div>
        <a class="btn outline" href="shop.php">Xem tất cả</a>
      </div>

      <div class="home-products">
        <?php if ($featured && $featured->num_rows > 0): ?>
            <?php while ($p = $featured->fetch_assoc()): 
                // Xử lý ảnh
                $img = $p['image_url'];
                if(empty($img)) $img = '/thongdong/assets/img/products/placeholder.jpg';
                if($img && strpos($img, 'http') === false && strpos($img, '/thongdong') === false) {
                    $img = '/thongdong/' . ltrim($img, '/');
                }
            ?>
            <article class="product-card">
                <a class="product-link" href="product-detail.php?id=<?php echo $p['product_id']; ?>">
                <div class="product-img">
                    <img
                    src="<?php echo htmlspecialchars($img); ?>"
                    alt="<?php echo htmlspecialchars($p['name']); ?>"
                    loading="lazy"
                    onerror="this.src='/thongdong/assets/img/products/placeholder.jpg'"
                    >
                </div>

                <div class="product-body">
                    <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <div class="price" style="color:#c0392b; font-weight:bold;"><?php echo money_vnd($p['price']); ?></div>
                </div>
                </a>

                <div class="product-actions">
                <a class="btn small" href="product-detail.php?id=<?php echo $p['product_id']; ?>">Xem</a>
                <a class="btn small outline" href="add-to-cart.php?id=<?php echo $p['product_id']; ?>">+ Giỏ</a>
                </div>
            </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="muted">Chưa có sản phẩm nào.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="home-howto">
    <div class="container">
      <div class="howto-head">
        <h2 class="home-title" style="margin:8px 0 6px;">Cách thắp nến thơm lâu</h2>
        <p class="muted" style="margin:0;">
          Một quy trình nhỏ để nến thơm đều, ít khói và dùng bền hơn.
        </p>
      </div>

      <div class="howto-steps">
        <div class="howto-step">
          <div class="step-ico">🕯️</div>
          <div class="step-title">Lần đầu thắp đủ lâu</div>
          <div class="muted step-text">Để mặt nến chảy đều 1-2 giờ để tránh lõm.</div>
        </div>

        <div class="howto-step">
          <div class="step-ico">✂️</div>
          <div class="step-title">Cắt tim nến</div>
          <div class="muted step-text">Giữ tim nến ~0.5cm trước mỗi lần thắp để giảm khói.</div>
        </div>

        <div class="howto-step">
          <div class="step-ico">🌬️</div>
          <div class="step-title">Tránh gió mạnh</div>
          <div class="muted step-text">Đặt nến nơi ít gió để cháy đều và thơm ổn định.</div>
        </div>

        <div class="howto-step">
          <div class="step-ico">🧯</div>
          <div class="step-title">Tắt an toàn</div>
          <div class="muted step-text">Dùng nắp/đồ dập nến, không thổi mạnh gây khói.</div>
        </div>
      </div>

      <div class="howto-actions">
        <?php if ($howto): ?>
            <a class="btn" href="blog-detail.php?id=<?php echo $howto['post_id']; ?>">
            Hiểu thêm về cách thắp
            </a>
        <?php else: ?>
            <a class="btn" href="blog.php">
            Xem Nhật ký
            </a>
        <?php endif; ?>
      </div>
    </div>
  </section>

</main>

<?php include '../includes/customer-layout-bottom.php'; ?>