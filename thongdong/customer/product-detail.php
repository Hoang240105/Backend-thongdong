<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Chi tiết sản phẩm - Thong Dong";

// 2. Lấy ID từ URL
$id = (int)($_GET['id'] ?? 0);
$product = null;
$related = [];

if ($id > 0) {
    // 3. Lấy thông tin sản phẩm
    $sql = "SELECT p.*, c.name as category_name 
            FROM Products p 
            LEFT JOIN Categories c ON p.category_id = c.category_id 
            WHERE p.product_id = ? AND p.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $pageTitle = htmlspecialchars($product['name']) . " - Thong Dong";

        // 4. Lấy sản phẩm liên quan (cùng danh mục)
        $cat_id = $product['category_id'];
        $sql_rel = "SELECT * FROM Products 
                    WHERE category_id = ? AND product_id != ? AND status = 'active' 
                    LIMIT 4";
        $stmt_rel = $conn->prepare($sql_rel);
        $stmt_rel->bind_param("ii", $cat_id, $id);
        $stmt_rel->execute();
        $related = $stmt_rel->get_result();
    }
}

// 5. Xử lý Thêm vào giỏ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    if ($product && $pid === $product['product_id']) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        
        // Nếu đã có -> cộng dồn
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] += $qty;
        } else {
            // Nếu chưa -> thêm mới (đúng chuẩn cấu trúc mới)
            $_SESSION['cart'][$pid] = [
                'id'    => $product['product_id'],
                'name'  => $product['name'],
                'price' => (int)$product['price'],
                'qty'   => $qty,
                'image' => $product['image_url'] ?? '',
            ];
        }

        header('Location: cart.php');
        exit;
    }
}

// Helper format tiền
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
  <?php if (!$product): ?>
    <section class="card" style="padding:30px; text-align:center;">
      <h1 style="margin:0 0 8px;">Không tìm thấy sản phẩm</h1>
      <p class="muted">Sản phẩm này không tồn tại hoặc đã ngừng kinh doanh.</p>
      <a class="btn" href="shop.php" style="margin-top:15px;">Quay lại cửa hàng</a>
    </section>
  <?php else: ?>

    <?php
        // Xử lý ảnh chính
        $img = $product['image_url'];
        if(empty($img)) $img = '/thongdong/assets/img/products/placeholder.jpg';
        if(strpos($img, 'http') === false && strpos($img, '/thongdong') === false) {
             $img = '/thongdong/' . ltrim($img, '/');
        }
    ?>

    <nav class="muted" style="margin-bottom:12px; font-size:14px;">
      <a href="shop.php" style="text-decoration:none; color:#666;">Cửa hàng</a>
      <span> / </span>
      <span style="color:#333;"><?php echo htmlspecialchars($product['name']); ?></span>
    </nav>

    <section class="product-detail card">
      <div class="pd-left">
        <div class="pd-img">
          <img
            src="<?php echo htmlspecialchars($img); ?>"
            alt="<?php echo htmlspecialchars($product['name']); ?>"
            loading="lazy"
            onerror="this.src='/thongdong/assets/img/products/placeholder.jpg'"
          >
        </div>
      </div>

      <div class="pd-right">
        <h1 class="pd-title"><?php echo htmlspecialchars($product['name']); ?></h1>

        <div class="pd-meta">
          <span class="price big" style="color:#c0392b;"><?php echo money_vnd($product['price']); ?></span>
          <?php if($product['category_name']): ?>
            <span class="tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
          <?php endif; ?>
        </div>

<div class="pd-desc" style="line-height:1.6; margin-top:15px; color:#555;">
    <?php 
        // 1. Giải mã các ký tự thực thể như &ocirc; thành chữ ô
        $desc = html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'); 
        // 2. Cho phép các thẻ HTML cơ bản (như <p>, <br>, <b>) hoạt động
        echo $desc; 
    ?>
</div>

        <div class="pd-spec">
          <div class="spec-item">
            <div class="muted">Tình trạng</div>
            <div style="color:green;"><b><?php echo ($product['stock_quantity'] > 0) ? 'Còn hàng' : 'Hết hàng'; ?></b></div>
          </div>
          <div class="spec-item">
            <div class="muted">Đã bán</div>
            <div><b><?php echo rand(10, 100); ?>+</b></div>
          </div>
        </div>

        <form method="post" class="pd-actions">
          <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

          <div class="qty">
            <button type="button" class="qty-btn" onclick="qtyDown()">-</button>
            <input id="qtyInput" class="qty-input" type="number" name="qty" value="1" min="1">
            <button type="button" class="qty-btn" onclick="qtyUp()">+</button>
          </div>

          <?php if ($product['stock_quantity'] > 0): ?>
            <button class="btn" type="submit" name="add_to_cart">Thêm vào giỏ</button>
          <?php else: ?>
            <button class="btn disabled" type="button" disabled>Hết hàng</button>
          <?php endif; ?>
          
          <a class="btn outline" href="cart.php">Xem giỏ hàng</a>
        </form>

        <div class="pd-actions" style="margin-top:10px;">
          <a class="btn outline" href="review-create.php?id=<?php echo $product['product_id']; ?>" style="width:100%; text-align:center;">
             ⭐ Viết đánh giá
          </a>
        </div>
      </div>
    </section>

    <section class="card" style="margin-top:16px;">
      <h3 style="margin:0 0 10px;">Có thể bạn cũng thích</h3>

      <div class="rel-grid">
        <?php while ($p = $related->fetch_assoc()): 
            $p_img = $p['image_url'];
            if(empty($p_img)) $p_img = '/thongdong/assets/img/products/placeholder.jpg';
            if(strpos($p_img, 'http') === false && strpos($p_img, '/thongdong') === false) {
                $p_img = '/thongdong/' . ltrim($p_img, '/');
            }
        ?>
          <a class="rel-card" href="product-detail.php?id=<?php echo $p['product_id']; ?>">
            <div class="rel-img">
              <img
                src="<?php echo htmlspecialchars($p_img); ?>"
                alt="<?php echo htmlspecialchars($p['name']); ?>"
                loading="lazy"
                onerror="this.src='/thongdong/assets/img/products/placeholder.jpg'"
              >
            </div>

            <div class="rel-body">
              <div class="rel-name"><?php echo htmlspecialchars($p['name']); ?></div>
              <div class="price" style="color:#c0392b;"><?php echo money_vnd($p['price']); ?></div>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    </section>

    <script>
      function qtyDown(){
        const el = document.getElementById('qtyInput');
        const v = parseInt(el.value || '1', 10);
        el.value = Math.max(1, v - 1);
      }
      function qtyUp(){
        const el = document.getElementById('qtyInput');
        const v = parseInt(el.value || '1', 10);
        el.value = Math.max(1, v + 1);
      }
    </script>

  <?php endif; ?>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>