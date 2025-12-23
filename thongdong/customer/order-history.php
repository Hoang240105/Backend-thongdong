<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Đơn hàng của tôi - Thong Dong";

// 2. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 3. Lấy danh sách đơn hàng của user này
$stmt = $conn->prepare("SELECT * FROM Orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

// Helper format tiền
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
        <h1 style="margin:0 0 6px;">Đơn hàng của bạn</h1>
        <p class="muted" style="margin:0;">Theo dõi đơn hàng đã đặt tại Thong Dong.</p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn outline" href="shop.php">Mua thêm</a>
        <a class="btn outline" href="account.php">Về tài khoản</a>
      </div>
    </div>

    <div style="height:14px;"></div>

    <?php if ($orders_result->num_rows === 0): ?>
      <div class="card" style="padding:30px; text-align:center;">
        <b>Chưa có đơn hàng nào.</b>
        <div class="muted" style="margin-top:6px;">Bạn ghé Cửa hàng chọn mùi hương hợp mood nhé.</div>
        <div style="margin-top:12px;">
          <a class="btn" href="shop.php">Đi tới Cửa hàng</a>
        </div>
      </div>
    <?php else: ?>

      <div class="card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; min-width:700px;">
            <thead>
              <tr style="text-align:left; background:#f9f9f9; font-size:14px;">
                <th style="padding:15px; border-bottom:1px solid #eee;">Mã đơn</th>
                <th style="padding:15px; border-bottom:1px solid #eee;">Thời gian</th>
                <th style="padding:15px; border-bottom:1px solid #eee;">Thanh toán</th>
                <th style="padding:15px; border-bottom:1px solid #eee;">Tổng tiền</th>
                <th style="padding:15px; border-bottom:1px solid #eee;">Trạng thái</th>
                <th style="padding:15px; border-bottom:1px solid #eee; text-align:right;">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($o = $orders_result->fetch_assoc()): 
                $statusLabel = map_status($o['status']);
                $paymentLabel = ($o['payment_method_id'] == 2) ? 'Chuyển khoản' : 'COD';
              ?>
                <tr style="border-bottom:1px solid #eee;">
                  <td style="padding:15px;">
                    <b>#<?php echo $o['order_id']; ?></b>
                  </td>
                  <td style="padding:15px; font-size:14px;">
                    <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?>
                  </td>
                  <td style="padding:15px;">
                    <?php echo $paymentLabel; ?>
                  </td>
                  <td style="padding:15px; font-weight:bold; color:#c0392b;">
                    <?php echo money_vnd($o['total_price']); ?>
                  </td>
                  <td style="padding:15px;">
                    <span class="badge" style="background:#eee;">
                        <?php echo $statusLabel; ?>
                    </span>
                  </td>
                  <td style="padding:15px; text-align:right;">
                    <a class="btn outline small" href="order-detail.php?id=<?php echo $o['order_id']; ?>">Xem chi tiết</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>