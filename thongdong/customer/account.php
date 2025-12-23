<?php
session_start();
// 1. Kết nối Database
require_once '../includes/db.php';

// Kiểm tra đăng nhập
if (empty($_SESSION['customer']) && empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Tài khoản - Thong Dong";

// Lấy ID khách hàng từ Session
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 2. Lấy thông tin khách hàng mới nhất từ DB
$stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// 3. Lấy 3 đơn hàng gần nhất
$stmt_orders = $conn->prepare("SELECT o.*, pm.name as payment_name 
                               FROM Orders o 
                               LEFT JOIN PaymentMethods pm ON o.payment_method_id = pm.method_id
                               WHERE o.user_id = ? 
                               ORDER BY o.order_date DESC LIMIT 3");
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

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

<main class="container" style="padding:34px 0 70px;">

  <section class="card account-head">
    <div class="account-left">
      <h1 style="margin:0 0 8px;">Tài khoản</h1>
      <p class="muted" style="margin:0;">
        Chào <b><?php echo htmlspecialchars($customer['full_name']); ?></b> ✨
        <span class="muted"> (<?php echo htmlspecialchars($customer['email']); ?>)</span>
      </p>
    </div>

    <div class="account-actions">
      <a class="btn outline" href="cart.php">Giỏ hàng</a>
      <a class="btn" href="logout.php">Đăng xuất</a>
    </div>
  </section>

  <section class="account-grid">
    <article class="card account-card">
      <h2 class="account-title">Thông tin của bạn</h2>
      <div class="account-info">
        <div class="info-row">
          <div class="muted">Họ và tên</div>
          <div><b><?php echo htmlspecialchars($customer['full_name']); ?></b></div>
        </div>
        <div class="info-row">
          <div class="muted">Email</div>
          <div><b><?php echo htmlspecialchars($customer['email']); ?></b></div>
        </div>
        <div class="info-row">
          <div class="muted">Số điện thoại</div>
          <div><b><?php echo htmlspecialchars($customer['phone'] ?? 'Chưa cập nhật'); ?></b></div>
        </div>
        <div class="info-row">
          <div class="muted">Thành viên từ</div>
          <div><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></div>
        </div>
      </div>
    </article>

    <div class="card" style="padding:18px;">
      <h3 style="margin:0 0 10px;">Đơn hàng gần đây</h3>

      <?php if ($orders_result->num_rows === 0): ?>
        <div class="muted">Chưa có đơn nào. Bạn ghé cửa hàng chọn nến nha.</div>
        <a class="btn" style="margin-top:12px;" href="shop.php">Mua ngay</a>
      <?php else: ?>
        <div style="display:grid; gap:10px; margin-top:8px;">
          <?php while ($o = $orders_result->fetch_assoc()): ?>
            <div class="card" style="padding:12px;">
              <div style="display:flex; justify-content:space-between; gap:12px;">
                <div>
                  <a href="order-detail.php?id=<?php echo $o['order_id']; ?>" style="font-weight:bold; text-decoration:none; color:#333;">
                      #<?php echo $o['order_id']; ?>
                  </a>
                  <div class="muted" style="margin-top:4px; font-size:13px;">
                    <?php echo date('d/m/Y H:i', strtotime($o['order_date'])); ?> •
                    <?php echo htmlspecialchars($o['payment_name'] ?? 'COD'); ?>
                  </div>
                </div>
                <div style="text-align:right;">
                  <b style="color:#c0392b;">
                    <?php echo money_vnd($o['total_price']); ?>
                  </b>
                  <div class="muted" style="margin-top:4px; font-size:13px;">
                    <?php 
                        $st = map_status($o['status']);
                        // Tô màu trạng thái
                        $color = '#666';
                        if($o['status']=='pending') $color='#d35400';
                        if($o['status']=='delivered') $color='#27ae60';
                        echo "<span style='color:$color; font-weight:bold;'>$st</span>";
                    ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>

        <a class="btn outline" style="margin-top:12px;" href="order-history.php">
          Xem tất cả lịch sử
        </a>
      <?php endif; ?>
    </div>

    <article class="card account-card account-links">
      <h2 class="account-title">Nhanh tay</h2>
      <a class="quick-link" href="shop.php">
        <span>🛍️</span>
        <div>
          <b>Vào cửa hàng</b>
          <div class="muted">Chọn mùi hương hợp mood</div>
        </div>
      </a>

      <a class="quick-link" href="blog.php">
        <span>📓</span>
        <div>
          <b>Đọc Nhật ký</b>
          <div class="muted">Mẹo dùng nến & câu chuyện nhỏ</div>
        </div>
      </a>

      <a class="quick-link" href="about.php">
        <span>🏮</span>
        <div>
          <b>Giới thiệu Thong Dong</b>
          <div class="muted">Thuần Việt - Tinh tế - Giá cả phải chăng</div>
        </div>
      </a>
    </article>

  </section>

</main>

<?php include '../includes/customer-layout-bottom.php'; ?>