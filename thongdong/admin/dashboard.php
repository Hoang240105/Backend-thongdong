<?php
session_start();
require_once '../includes/db.php';
// Đảm bảo đường dẫn này đúng với cấu trúc thư mục của bạn
include __DIR__ . '/includes/admin-guard.php'; 

// --- TÍNH TOÁN SỐ LIỆU ---

// 1. Tổng sản phẩm (Sửa tên bảng thành viết thường 'products' nếu cần)
$sql_prod = "SELECT COUNT(*) FROM products WHERE status != 'inactive'"; 
$count_products = $conn->query($sql_prod)->fetch_row()[0] ?? 0;

// 2. Đơn hàng mới (Pending)
$sql_new_orders = "SELECT COUNT(*) FROM orders WHERE status = 'pending'";
$count_orders_new = $conn->query($sql_new_orders)->fetch_row()[0] ?? 0;

// 3. Doanh thu ước tính (Tổng tiền các đơn KHÔNG bị hủy)
$sql_revenue = "SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'";
$revenue = $conn->query($sql_revenue)->fetch_row()[0] ?? 0;

// 4. Tổng khách hàng
$sql_users = "SELECT COUNT(*) FROM users WHERE role = 'customer'";
$count_customers = $conn->query($sql_users)->fetch_row()[0] ?? 0;

// 5. Lấy 5 đơn hàng mới nhất
// ĐÃ SỬA: order_date -> created_at để khớp với Database của bạn
$sql_recent = "SELECT o.*, u.full_name 
               FROM orders o 
               JOIN users u ON o.user_id = u.user_id 
               ORDER BY o.created_at DESC LIMIT 5";
$recent_orders = $conn->query($sql_recent);

// Helper format tiền
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

// Map trạng thái đơn hàng (tiếng Việt)
$status_map = [
    'pending'   => 'Chờ xử lý',
    'confirmed' => 'Đã xác nhận',
    'shipped'   => 'Đang giao',
    'delivered' => 'Hoàn tất',
    'cancelled' => 'Đã hủy'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Thong Dong</title>
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
  <style>
    /* Thêm một chút style bổ trợ nếu file admin.css chưa có */
    :root { --line: #eee; }
    .admin-page { padding: 30px 0; }
    .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/admin-header.php'; ?>

<main class="container admin-page">
  <section class="card" style="padding: 20px;">
    <h1>Dashboard</h1>
    <p class="muted">Tổng quan tình hình kinh doanh hôm nay.</p>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:16px;">
      <div class="card" style="padding:16px; border: 1px solid #eee;">
        <div class="muted">Sản phẩm</div>
        <div style="font-size:28px; margin-top:6px; font-weight:bold;">
            <?php echo $count_products; ?>
        </div>
      </div>
      <div class="card" style="padding:16px; border: 1px solid #eee;">
        <div class="muted">Đơn mới</div>
        <div style="font-size:28px; margin-top:6px; font-weight:bold; color:#d35400;">
            <?php echo $count_orders_new; ?>
        </div>
      </div>
      <div class="card" style="padding:16px; border: 1px solid #eee;">
        <div class="muted">Doanh thu (tạm tính)</div>
        <div style="font-size:28px; margin-top:6px; font-weight:bold; color:#27ae60;">
            <?php echo money_vnd($revenue); ?>
        </div>
      </div>
      <div class="card" style="padding:16px; border: 1px solid #eee;">
        <div class="muted">Khách hàng</div>
        <div style="font-size:28px; margin-top:6px; font-weight:bold;">
            <?php echo $count_customers; ?>
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 1.4fr 0.6fr; gap:20px; margin-top:20px;">
      <div class="card" style="padding:16px; border: 1px solid #eee;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom: 15px;">
          <h2 style="margin:0; font-size:18px;">Đơn hàng gần đây</h2>
          <a class="btn outline small" href="orders.php">Xem tất cả</a>
        </div>

        <div style="overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr style="text-align:left; background: #fafafa;">
                <th style="padding:12px; border-bottom:1px solid var(--line);">Mã</th>
                <th style="padding:12px; border-bottom:1px solid var(--line);">Khách hàng</th>
                <th style="padding:12px; border-bottom:1px solid var(--line);">Tổng tiền</th>
                <th style="padding:12px; border-bottom:1px solid var(--line);">Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                  <?php while ($o = $recent_orders->fetch_assoc()): 
                      $st_key = $o['status'];
                      $st_label = $status_map[$st_key] ?? $st_key;
                      
                      $color = '#333';
                      if($st_key == 'pending') $color = '#d35400'; 
                      if($st_key == 'delivered') $color = '#27ae60'; 
                      if($st_key == 'cancelled') $color = '#c0392b'; 
                  ?>
                  <tr>
                    <td style="padding:12px; border-bottom:1px solid var(--line);">
                        <b>#<?php echo $o['order_id']; ?></b>
                    </td>
                    <td style="padding:12px; border-bottom:1px solid var(--line);">
                        <?php echo htmlspecialchars($o['full_name']); ?>
                    </td>
                    <td style="padding:12px; border-bottom:1px solid var(--line); font-weight:bold;">
                        <?php echo money_vnd($o['total_price']); ?>
                    </td>
                    <td style="padding:12px; border-bottom:1px solid var(--line);">
                        <span style="color: <?php echo $color; ?>; font-weight:bold;">
                            <?php echo $st_label; ?>
                        </span>
                    </td>
                  </tr>
                  <?php endwhile; ?>
              <?php else: ?>
                  <tr>
                      <td colspan="4" style="padding:20px; text-align:center; color:#999;">Chưa có đơn hàng nào.</td>
                  </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" style="padding:16px; border: 1px solid #eee;">
        <h2 style="margin:0 0 15px; font-size:18px;">Lối tắt nhanh</h2>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <a class="btn primary" href="products.php" style="text-align:center;">Quản lý sản phẩm</a>
          <a class="btn outline" href="orders.php" style="text-align:center;">Quản lý đơn hàng</a>
          <a class="btn outline" href="blog.php" style="text-align:center;">Viết nhật ký (Blog)</a>
          <a class="btn outline" href="customers.php" style="text-align:center;">Danh sách khách</a>
          <a class="btn outline" href="reviews.php" style="text-align:center;">Duyệt đánh giá</a>
        </div>

        <div style="margin-top:20px; padding-top:15px; border-top: 1px dashed #ccc; font-size: 13px;">
          <span class="muted">Đang đăng nhập:</span><br>
          <b><?php echo htmlspecialchars($_SESSION['admin']['email'] ?? 'Quản trị viên'); ?></b>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
</body>
</html>