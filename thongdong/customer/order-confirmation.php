<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Xác nhận đơn hàng - Thong Dong";

// 2. Lấy ID đơn hàng từ URL
$order_id = (int)($_GET['id'] ?? 0);

// 3. Kiểm tra đăng nhập (bảo mật: chỉ xem được đơn của chính mình)
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 4. Lấy thông tin đơn hàng từ DB
$order = null;
if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM Orders WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

// 5. Lấy thông tin Ngân hàng (nếu cần hiển thị)
$settings = [];
if ($order && $order['payment_method_id'] == 2) {
    $res = $conn->query("SELECT * FROM Settings");
    while ($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
}

// Helper format tiền
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:40px 0 70px;">
  <section class="card" style="text-align:center; max-width:600px; margin:0 auto;">
    
    <?php if (!$order): ?>
      <h1 style="color:#c0392b;">Không tìm thấy đơn hàng</h1>
      <p class="muted">Đơn hàng không tồn tại hoặc bạn không có quyền xem.</p>
      <a class="btn" href="shop.php" style="margin-top:15px;">Về cửa hàng</a>
    
    <?php else: ?>
      
      <div style="font-size:48px; margin-bottom:10px;">🎉</div>
      <h1 style="margin:0 0 10px;">Đặt hàng thành công!</h1>
      <p class="muted" style="margin-bottom:20px;">
        Cảm ơn bạn đã chọn Thong Dong. Đơn hàng <b>#<?php echo $order['order_id']; ?></b> đang được xử lý.
      </p>

      <div style="background:#f9f9f9; padding:20px; border-radius:8px; text-align:left; margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
            <span class="muted">Mã đơn hàng:</span>
            <b>#<?php echo $order['order_id']; ?></b>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
            <span class="muted">Người nhận:</span>
            <b><?php echo htmlspecialchars($order['shipping_address']); ?></b> 
            </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
            <span class="muted">Số điện thoại:</span>
            <b><?php echo htmlspecialchars($order['phone']); ?></b>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
            <span class="muted">Tổng tiền:</span>
            <b style="color:#c0392b; font-size:18px;"><?php echo money_vnd($order['total_price']); ?></b>
        </div>
        <div style="display:flex; justify-content:space-between;">
            <span class="muted">Hình thức thanh toán:</span>
            <b><?php echo ($order['payment_method_id'] == 2) ? 'Chuyển khoản' : 'COD (Tiền mặt)'; ?></b>
        </div>
      </div>

      <?php if ($order['payment_method_id'] == 2): ?>
        <div class="card" style="background:#e6f4ea; border:1px solid #c3e6cb; padding:20px; margin-bottom:20px;">
            <h3 style="margin:0 0 10px; color:#155724;">Thông tin chuyển khoản</h3>
            <p class="muted" style="margin-bottom:10px;">Vui lòng chuyển khoản theo thông tin dưới đây để đơn hàng được xác nhận nhanh nhất:</p>
            
            <div style="font-size:15px; line-height:1.6; color:#333;">
                Ngân hàng: <b><?php echo htmlspecialchars($settings['bank_name'] ?? 'Vietcombank'); ?></b><br>
                Số tài khoản: <b style="font-size:18px; color:#c0392b;"><?php echo htmlspecialchars($settings['bank_number'] ?? ''); ?></b><br>
                Chủ tài khoản: <b><?php echo htmlspecialchars($settings['bank_owner'] ?? 'THONG DONG'); ?></b><br>
                Nội dung CK: <b style="color:#155724;"><?php echo htmlspecialchars($settings['bank_note'] ?? 'TD') . ' ' . $order['phone']; ?></b>
            </div>
        </div>
      <?php endif; ?>

      <div style="display:flex; gap:10px; justify-content:center;">
        <a class="btn" href="shop.php">Tiếp tục mua sắm</a>
        <a class="btn outline" href="order-detail.php?id=<?php echo $order['order_id']; ?>">Xem chi tiết đơn</a>
      </div>

    <?php endif; ?>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>