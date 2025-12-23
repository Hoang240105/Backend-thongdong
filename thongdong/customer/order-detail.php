<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Chi tiết đơn hàng - Thong Dong";

// 2. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

$order_id = (int)($_GET['id'] ?? 0);
$order = null;
$items = [];

// 3. Lấy thông tin đơn hàng
if ($order_id > 0) {
    // Chỉ lấy đơn của chính user này
    $stmt = $conn->prepare("SELECT * FROM Orders WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        // 4. Lấy chi tiết sản phẩm trong đơn
        $sql_items = "SELECT oi.*, p.name, p.image_url 
                      FROM OrderItems oi 
                      LEFT JOIN Products p ON oi.product_id = p.product_id 
                      WHERE oi.order_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $items = $stmt_items->get_result();
    }
}

// Helper
function safe($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

// Map trạng thái đơn hàng
function map_status($st) {
    $map = [
        'pending'   => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipped'   => 'Đang giao',
        'delivered' => 'Hoàn tất',
        'cancelled' => 'Đã hủy'
    ];
    return $map[$st] ?? $st;
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card" style="padding:18px;">
    
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap;">
      <div>
        <h1 style="margin:0 0 6px;">Chi tiết đơn hàng #<?php echo $order_id; ?></h1>
        <p class="muted" style="margin:0;">Xem thông tin đơn và sản phẩm đã đặt.</p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn outline" href="order-history.php">← Quay lại danh sách</a>
        <a class="btn outline" href="returns.php">Chính sách đổi trả</a>
      </div>
    </div>

    <div style="height:14px;"></div>

    <?php if (!$order): ?>
      <div class="card" style="padding:30px; text-align:center; background:#f9f9f9;">
        <b style="font-size:18px;">Không tìm thấy đơn hàng.</b>
        <div class="muted" style="margin-top:6px;">Đơn hàng không tồn tại hoặc bạn không có quyền xem.</div>
        <div style="margin-top:12px;">
          <a class="btn" href="order-history.php">Về danh sách đơn</a>
        </div>
      </div>
    <?php else: ?>

      <div class="checkout-grid">
        <div class="checkout-left">
          <div class="card" style="padding:14px;">
            <h3 style="margin:0 0 10px;">Thông tin đơn</h3>

            <div class="muted">Mã đơn</div>
            <div style="margin:2px 0 10px; font-size:16px;"><b>#<?php echo $order['order_id']; ?></b></div>

            <div class="muted">Thời gian đặt</div>
            <div style="margin:2px 0 10px;"><b><?php echo date('H:i d/m/Y', strtotime($order['created_at'])); ?></b></div>

            <div class="muted">Thanh toán</div>
            <div style="margin:2px 0 10px;">
                <b><?php echo ($order['payment_method_id'] == 2) ? 'Chuyển khoản' : 'Tiền mặt (COD)'; ?></b>
            </div>

            <div class="muted">Trạng thái</div>
            <div style="margin:2px 0 10px;">
                <span class="badge" style="font-size:14px; background:#eee;">
                    <?php echo map_status($order['status']); ?>
                </span>
            </div>
          </div>

          <div style="height:12px;"></div>

          <div class="card" style="padding:14px;">
            <h3 style="margin:0 0 10px;">Thông tin nhận hàng</h3>

            <div class="muted">Địa chỉ & SĐT</div>
            <div style="margin:2px 0 10px; line-height:1.5;">
                <b><?php echo nl2br(safe($order['shipping_address'])); ?></b><br>
                SĐT: <b><?php echo safe($order['phone']); ?></b>
            </div>

            <?php if (!empty($order['note'])): ?>
              <div class="muted">Ghi chú</div>
              <div style="margin:2px 0 0;"><i>"<?php echo nl2br(safe($order['note'])); ?>"</i></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="checkout-right">
          <div class="card" style="padding:14px;">
            <h3 style="margin:0 0 10px;">Sản phẩm</h3>

            <div class="checkout-lines">
              <?php 
                $subtotal = 0;
                while ($it = $items->fetch_assoc()): 
                  $line_total = $it['price'] * $it['quantity'];
                  $subtotal += $line_total;
              ?>
                <div class="line" style="display:flex; justify-content:space-between; margin-bottom:10px; border-bottom:1px dashed #eee; padding-bottom:10px;">
                  <div>
                    <b><?php echo safe($it['name']); ?></b>
                    <div class="muted">
                        <?php echo money_vnd($it['price']); ?> x <?php echo $it['quantity']; ?>
                    </div>
                  </div>
                  <div style="font-weight:bold;"><?php echo money_vnd($line_total); ?></div>
                </div>
              <?php endwhile; ?>

              <div class="line total" style="display:flex; justify-content:space-between; margin-top:15px; font-size:18px;">
                <div><b>Tổng cộng</b></div>
                <div style="color:#c0392b;"><b><?php echo money_vnd($order['total_price']); ?></b></div>
              </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:10px; flex-wrap:wrap;">
              <?php if($order['status'] === 'delivered'): ?>
                  <a class="btn" href="return-request.php?order_id=<?php echo $order['order_id']; ?>">
                    Yêu cầu Đổi/Trả
                  </a>
                  <a class="btn outline" href="reviews.php?order_id=<?php echo $order['order_id']; ?>">Viết Đánh Giá</a>
              <?php endif; ?>
              
              <a class="btn outline" href="shop.php" style="flex:1; text-align:center;">Mua thêm</a>
            </div>

            <?php if($order['status'] === 'pending'): ?>
                <div class="muted" style="margin-top:10px; font-size:13px;">
                  * Đơn hàng đang chờ xử lý. Nếu muốn hủy, vui lòng liên hệ Hotline.
                </div>
            <?php endif; ?>

          </div>
        </div>
      </div>

    <?php endif; ?>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>