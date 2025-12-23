<?php
session_start();

$pageTitle = "Giỏ hàng - Thong Dong";

// Khởi tạo giỏ nếu chưa có
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Helper format tiền
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

// XỬ LÝ UPDATE / REMOVE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cập nhật số lượng
    if (isset($_POST['update'])) {
        $qtys = $_POST['qty'] ?? [];
        foreach ($qtys as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;

            if (isset($_SESSION['cart'][$pid])) {
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$pid]); 
                } else {
                    $_SESSION['cart'][$pid]['qty'] = $qty; 
                }
            }
        }
    }

    // 2. Xoá 1 sản phẩm
    if (isset($_POST['remove'])) {
        $pid = (int)$_POST['remove'];
        if (isset($_SESSION['cart'][$pid])) {
            unset($_SESSION['cart'][$pid]);
        }
    }

    header('Location: cart.php');
    exit;
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
  <section class="card">
    <h1 style="margin:0 0 14px;">Giỏ hàng của bạn</h1>

    <?php if (empty($_SESSION['cart'])): ?>
      <div style="text-align:center; padding: 40px;">
          <p class="muted">Giỏ hàng đang trống trơn.</p>
          <a class="btn" href="shop.php" style="margin-top:10px;">Đi chọn nến thơm thôi</a>
      </div>

    <?php else: ?>
      <form method="post">
        <div style="overflow-x:auto;">
            <table class="cart-table" style="width:100%; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 1px solid #eee; text-align: left;">
                  <th style="width:80px; padding: 10px;">Ảnh</th>
                  <th>Sản phẩm</th>
                  <th>Giá</th>
                  <th style="width:100px;">Số lượng</th>
                  <th>Tạm tính</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $total = 0;
                  foreach ($_SESSION['cart'] as $pid => $item):
                    $price = (int)$item['price'];
                    $qty   = (int)$item['qty'];
                    $sub   = $price * $qty;
                    $total += $sub;

                    // --- XỬ LÝ ẢNH THÔNG MINH ---
                    $img = $item['image'] ?? '';
                    
                    // 1. Tự động đổi đuôi .jpg thành .png nếu cần
                    $img = str_replace('.jpg', '.png', $img);
                    
                    // 2. Xử lý đường dẫn chuẩn
                    if($img && strpos($img, 'http') === false) {
                        // Xóa dấu gạch chéo ở đầu nếu có
                        $img = ltrim($img, '/');
                        // Thêm đường dẫn tương đối để từ /customer/ lùi ra ngoài tìm thấy /assets/
                        $img = '../' . $img; 
                    }
                ?>
                  <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;">
                        <img src="<?php echo htmlspecialchars($img); ?>" 
                             style="width:60px; height:60px; object-fit:cover; border-radius:4px;"
                             onerror="this.src='../assets/img/products/placeholder.png'">
                    </td>
                    <td>
                      <b style="display:block;"><?php echo htmlspecialchars($item['name']); ?></b>
                    </td>
                    <td><?php echo money_vnd($price); ?></td>
                    <td>
                      <input class="qty-input" type="number" name="qty[<?php echo $pid; ?>]" value="<?php echo $qty; ?>" min="1" style="width:50px; padding:5px; border:1px solid #ddd; border-radius:4px;">
                    </td>
                    <td style="font-weight:bold;"><?php echo money_vnd($sub); ?></td>
                    <td style="text-align:right;">
                      <button class="btn small outline danger" name="remove" value="<?php echo $pid; ?>" style="padding:4px 8px; font-size:12px; color: #e74c3c; border-color: #e74c3c;">Xoá</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        </div>

        <div class="cart-actions" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
          <button type="submit" class="btn outline" name="update">Cập nhật giỏ hàng</button>
          
          <div class="cart-total" style="text-align:right;">
            <div class="muted">Tổng cộng</div>
            <div class="price big" style="font-size:24px; color:#c0392b; font-weight:bold;"><?php echo money_vnd($total); ?></div>
          </div>
        </div>

        <div style="margin-top:25px; text-align:right;">
          <a class="btn outline" href="shop.php" style="margin-right:10px;">Mua thêm</a>
          <a class="btn primary" href="checkout.php" style="padding:12px 30px; background: #8e44ad; color:#fff; border-radius: 8px;">Tiến hành thanh toán →</a>
        </div>
      </form>
    <?php endif; ?>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>