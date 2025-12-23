<?php
session_start();
require_once '../includes/db.php';
require __DIR__ . '/includes/admin-guard.php';

$pageTitle = "Đơn hàng - Admin Thong Dong";

// --- XỬ LÝ POST (CẬP NHẬT TRẠNG THÁI) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';

    if ($oid > 0 && $new_status) {
        // Cập nhật status
        $stmt = $conn->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $oid);
        $stmt->execute();
        
        header("Location: orders.php?msg=updated"); exit;
    }
}

// Thông báo
$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $msg = "Đã cập nhật trạng thái đơn hàng!";
}

// --- LẤY DỮ LIỆU ĐƠN HÀNG (GET) ---
$q = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

// SQL: Join Users (lấy tên), PaymentMethods (lấy tên PTTT), Addresses (lấy SĐT nhận hàng)
// Nếu bảng Addresses chưa có, bạn có thể lấy phone từ Users
$sql = "SELECT o.*, u.full_name, pm.name as payment_name 
        FROM Orders o
        LEFT JOIN Users u ON o.user_id = u.user_id
        LEFT JOIN PaymentMethods pm ON o.payment_method_id = pm.method_id
        WHERE 1=1";

// Lọc từ khóa (Mã đơn, Tên khách)
if ($q) {
    $safe_q = $conn->real_escape_string($q);
    $sql .= " AND (o.order_id LIKE '%$safe_q%' OR u.full_name LIKE '%$safe_q%')";
}

// Lọc trạng thái
if ($status_filter !== 'all') {
    $sql .= " AND o.status = '$status_filter'";
}

$sql .= " ORDER BY o.created_at DESC";
$result = $conn->query($sql);

// Map hiển thị trạng thái (Anh -> Việt)
$status_map = [
    'pending'   => 'Chờ xử lý',
    'confirmed' => 'Đã xác nhận',
    'shipped'   => 'Đang giao',
    'delivered' => 'Hoàn tất',
    'cancelled' => 'Đã hủy'
];

include __DIR__ . '/includes/admin-layout-top.php';

// Helper format tiền
if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}
?>

<main class="container admin-main">
  <section class="admin-card admin-head">
    <div class="admin-head-top">
      <div>
        <h1 class="admin-page-title">Đơn hàng</h1>
        <p class="admin-page-sub muted">Theo dõi và xử lý đơn hàng.</p>
      </div>

      <div class="admin-actions">
        <a class="btn outline" href="orders.php">Làm mới</a>
      </div>
    </div>

    <form class="admin-filters" method="get" action="orders.php">
      <div class="control">
        <label for="q">Tìm kiếm</label>
        <input id="q" class="input" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Mã đơn, tên khách...">
      </div>

      <div class="control">
        <label for="status">Trạng thái</label>
        <select id="status" class="input" name="status">
          <option value="all">Tất cả</option>
          <option value="pending" <?php if($status_filter=='pending') echo 'selected'; ?>>Chờ xử lý</option>
          <option value="confirmed" <?php if($status_filter=='confirmed') echo 'selected'; ?>>Đã xác nhận</option>
          <option value="shipped" <?php if($status_filter=='shipped') echo 'selected'; ?>>Đang giao</option>
          <option value="delivered" <?php if($status_filter=='delivered') echo 'selected'; ?>>Hoàn tất</option>
          <option value="cancelled" <?php if($status_filter=='cancelled') echo 'selected'; ?>>Đã huỷ</option>
        </select>
      </div>

      <button class="btn" type="submit">Lọc</button>
    </form>
  </section>

  <?php if ($msg): ?>
    <div class="admin-card" style="padding:15px; background:#e8f5e9; color:#2e7d32; margin-bottom:20px; border:1px solid #c8e6c9;">
        ✅ <?php echo $msg; ?>
    </div>
  <?php endif; ?>

  <section class="admin-card admin-table-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Mã đơn</th>
          <th>Thời gian</th>
          <th>Khách hàng</th>
          <th>Thanh toán</th>
          <th style="text-align:right;">Tổng tiền</th>
          <th>Trạng thái</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($o = $result->fetch_assoc()): 
              $st_key = $o['status'];
              $st_label = $status_map[$st_key] ?? $st_key;
              
              // Màu trạng thái
              $st_color = '#333';
              if($st_key == 'pending') $st_color = '#d35400';
              if($st_key == 'delivered') $st_color = '#27ae60';
              if($st_key == 'cancelled') $st_color = '#c0392b';
          ?>
            <tr>
              <td><b>#<?php echo $o['order_id']; ?></b></td>
              <td><?php echo date('H:i d/m/Y', strtotime($o['created_at'])); ?></td>
              <td>
                  <?php echo htmlspecialchars($o['full_name']); ?>
              </td>
              <td>
                  <span class="badge" style="background:#eee; font-weight:normal;">
                      <?php echo htmlspecialchars($o['payment_name'] ?? '---'); ?>
                  </span>
              </td>
              <td class="money" style="text-align:right; font-weight:bold;">
                  <?php echo money_vnd($o['total_price']); ?>
              </td>
              <td>
                  <span class="status" style="color: <?php echo $st_color; ?>; font-weight:bold;">
                      <?php echo $st_label; ?>
                  </span>
              </td>
              <td style="text-align:right;">
                <div class="admin-td-actions">
                  <a class="btn outline" href="orders.php?view=<?php echo $o['order_id']; ?>">Xem</a>
                  <a class="btn" href="orders.php?update=<?php echo $o['order_id']; ?>">Cập nhật</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="muted" style="padding:30px; text-align:center;">
              Không tìm thấy đơn hàng nào.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <?php if (!empty($_GET['view'])): 
      $vid = (int)$_GET['view'];
      // Lấy chi tiết đơn
 $sql_detail = "SELECT o.*, u.full_name, u.email 
               FROM orders o
               LEFT JOIN Users u ON o.user_id = u.user_id
               WHERE o.order_id = $vid";
      $order_detail = $conn->query($sql_detail)->fetch_assoc();
      
      // Lấy sản phẩm trong đơn
      $sql_items = "SELECT oi.*, p.name 
                    FROM OrderItems oi 
                    JOIN Products p ON oi.product_id = p.product_id 
                    WHERE oi.order_id = $vid";
      $items = $conn->query($sql_items);
  ?>
    <section class="admin-card" style="padding:20px; margin-top:14px;">
      <h3 style="margin:0 0 15px; border-bottom:1px solid #eee; padding-bottom:10px;">Chi tiết đơn #<?php echo $vid; ?></h3>
      
      <?php if($order_detail): ?>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
              <div>
                  <div class="muted">Người đặt:</div>
                  <b><?php echo htmlspecialchars($order_detail['full_name']); ?></b><br>
                  <?php echo htmlspecialchars($order_detail['phone']); ?><br>
                  <?php echo htmlspecialchars($order_detail['email']); ?>
              </div>
              <div>
    <div class="muted">Địa chỉ giao:</div>
    <b><?php echo htmlspecialchars($order_detail['address'] ?? 'Chưa cập nhật'); ?></b>
</div>
          </div>

          <table style="width:100%; border-collapse:collapse; margin-bottom:15px;">
              <tr style="background:#f9f9f9; text-align:left;">
                  <th style="padding:8px;">Sản phẩm</th>
                  <th style="padding:8px;">SL</th>
                  <th style="padding:8px; text-align:right;">Thành tiền</th>
              </tr>
              <?php while($it = $items->fetch_assoc()): ?>
              <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;">
                      <?php echo htmlspecialchars($it['name']); ?>
                  </td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">
                      x<?php echo $it['quantity']; ?>
                  </td>
                  <td style="padding:8px; border-bottom:1px solid #eee; text-align:right;">
                      <?php echo money_vnd($it['price'] * $it['quantity']); ?>
                  </td>
              </tr>
              <?php endwhile; ?>
              <tr>
                  <td colspan="2" style="padding:10px; text-align:right;"><b>Tổng cộng:</b></td>
                  <td style="padding:10px; text-align:right; color:#c0392b; font-weight:bold;">
                      <?php echo money_vnd($order_detail['total_price']); ?>
                  </td>
              </tr>
          </table>
      <?php endif; ?>

      <div style="margin-top:12px;">
        <a class="btn outline" href="orders.php">Đóng</a>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!empty($_GET['update'])): $uid = (int)$_GET['update']; ?>
    <section class="admin-card" style="padding:20px; margin-top:14px; max-width:500px;">
      <h3 style="margin:0 0 10px;">Cập nhật trạng thái #<?php echo $uid; ?></h3>

      <form method="post" action="orders.php" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
        <input type="hidden" name="order_id" value="<?php echo $uid; ?>">
        
        <div class="control" style="flex:1; display:flex; flex-direction:column; gap:6px;">
          <label class="muted">Trạng thái mới</label>
          <select class="input" name="new_status" style="width:100%;">
            <option value="pending">Chờ xử lý</option>
            <option value="confirmed">Đã xác nhận</option>
            <option value="shipped">Đang giao</option>
            <option value="delivered">Hoàn tất</option>
            <option value="cancelled">Đã huỷ</option>
          </select>
        </div>

        <button class="btn" type="submit">Lưu thay đổi</button>
        <a class="btn outline" href="orders.php">Huỷ</a>
      </form>
    </section>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/admin-layout-bottom.php'; ?>