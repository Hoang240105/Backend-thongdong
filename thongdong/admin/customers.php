<?php
session_start();
require_once '../includes/db.php';
require __DIR__ . '/includes/admin-guard.php';

$pageTitle = "Khách hàng - Admin Thong Dong";

// --- XỬ LÝ POST (KHOÁ / MỞ KHOÁ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $uid = (int)$_POST['id'];
    
    if ($uid > 0) {
        if ($action === 'lock') {
            $conn->query("UPDATE Users SET status = 'locked' WHERE user_id = $uid");
            header("Location: customers.php?msg=locked"); exit;
        }
        if ($action === 'unlock') {
            $conn->query("UPDATE Users SET status = 'active' WHERE user_id = $uid");
            header("Location: customers.php?msg=unlocked"); exit;
        }
    }
}

// Thông báo
$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'locked') $msg = "Đã khoá tài khoản khách hàng.";
    if ($_GET['msg'] == 'unlocked') $msg = "Đã mở khoá tài khoản.";
}

// --- LẤY DANH SÁCH KHÁCH HÀNG (GET) ---
$q = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

// SQL: Lấy thông tin user + đếm đơn hàng + tổng chi tiêu
$sql = "SELECT u.*, 
               COUNT(o.order_id) as order_count, 
               COALESCE(SUM(o.total_price), 0) as total_spent
        FROM Users u
        LEFT JOIN Orders o ON u.user_id = o.user_id AND o.status != 'cancelled'
        WHERE u.role = 'customer'";

// Lọc theo từ khóa (Tìm tên, email, sđt)
if ($q) {
    $safe_q = $conn->real_escape_string($q);
    $sql .= " AND (u.full_name LIKE '%$safe_q%' OR u.email LIKE '%$safe_q%' OR u.phone LIKE '%$safe_q%')";
}

// Lọc theo trạng thái
if ($status_filter !== 'all') {
    $db_status = ($status_filter === 'Hoạt động') ? 'active' : 'locked';
    $sql .= " AND u.status = '$db_status'";
}

$sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC";
$result = $conn->query($sql);

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
        <h1 class="admin-page-title">Khách hàng</h1>
        <p class="admin-page-sub muted">Quản lý danh sách thành viên.</p>
      </div>
      <div class="admin-actions">
        <a class="btn outline" href="customers.php">Làm mới</a>
      </div>
    </div>

    <form class="admin-filters" method="get" action="customers.php">
      <div class="control">
        <label for="q">Tìm kiếm</label>
        <input id="q" class="input" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tên, email, SĐT...">
      </div>

      <div class="control">
        <label for="status">Trạng thái</label>
        <select id="status" class="input" name="status">
          <option value="all">Tất cả</option>
          <option value="Hoạt động" <?php if($status_filter=='Hoạt động') echo 'selected'; ?>>Hoạt động</option>
          <option value="Tạm khoá" <?php if($status_filter=='Tạm khoá') echo 'selected'; ?>>Tạm khoá</option>
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
          <th>ID</th>
          <th>Khách hàng</th>
          <th>Email</th>
          <th>SĐT</th>
          <th style="text-align:right;">Số đơn</th>
          <th style="text-align:right;">Chi tiêu</th>
          <th>Trạng thái</th>
          <th>Tham gia</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>

      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($c = $result->fetch_assoc()): 
              $st_label = ($c['status'] === 'active') ? 'Hoạt động' : 'Tạm khoá';
              $st_class = ($c['status'] === 'active') ? 'status-success' : 'status-danger';
          ?>
            <tr>
              <td><b>#<?php echo $c['user_id']; ?></b></td>
              <td><?php echo htmlspecialchars($c['full_name']); ?></td>
              <td><?php echo htmlspecialchars($c['email']); ?></td>
              <td><?php echo htmlspecialchars($c['phone']); ?></td>
              <td style="text-align:right;"><b><?php echo $c['order_count']; ?></b></td>
              <td class="money" style="text-align:right; color:#c0392b; font-weight:bold;"><?php echo money_vnd($c['total_spent']); ?></td>
              <td>
                  <span class="status" style="<?php echo ($c['status']=='active')?'color:#27ae60':'color:#c0392b'; ?> font-weight:bold;">
                    <?php echo $st_label; ?>
                  </span>
              </td>
              <td><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></td>
              <td style="text-align:right;">
                <div class="admin-td-actions">
                  <a class="btn outline" href="customers.php?view=<?php echo $c['user_id']; ?>">Xem</a>
                  <?php if ($c['status'] === 'active'): ?>
                    <a class="btn" href="customers.php?lock=<?php echo $c['user_id']; ?>">Khoá</a>
                  <?php else: ?>
                    <a class="btn outline" style="color:#27ae60; border-color:#27ae60;" href="customers.php?unlock=<?php echo $c['user_id']; ?>">Mở</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="9" class="muted" style="padding:30px; text-align:center;">Không tìm thấy khách hàng nào.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <?php if (!empty($_GET['lock'])): $uid = (int)$_GET['lock']; ?>
    <section class="admin-card" style="padding:20px; margin-top:14px; max-width:500px; margin-left:auto; margin-right:auto; text-align:center;">
      <h3 style="margin:0 0 10px; color:#c0392b;">Khoá tài khoản khách hàng?</h3>
      <p>Khách hàng ID <b>#<?php echo $uid; ?></b> sẽ không thể đăng nhập và mua hàng nữa.</p>
      <form method="post" style="margin-top:20px; display:flex; justify-content:center; gap:10px;">
        <input type="hidden" name="action" value="lock">
        <input type="hidden" name="id" value="<?php echo $uid; ?>">
        <button class="btn" type="submit" style="background:#c0392b; color:white; border:none;">Xác nhận Khoá</button>
        <a class="btn outline" href="customers.php">Huỷ</a>
      </form>
    </section>
  <?php endif; ?>

  <?php if (!empty($_GET['unlock'])): $uid = (int)$_GET['unlock']; ?>
    <section class="admin-card" style="padding:20px; margin-top:14px; max-width:500px; margin-left:auto; margin-right:auto; text-align:center;">
      <h3 style="margin:0 0 10px; color:#27ae60;">Mở khoá tài khoản?</h3>
      <p>Khách hàng ID <b>#<?php echo $uid; ?></b> sẽ hoạt động trở lại bình thường.</p>
      <form method="post" style="margin-top:20px; display:flex; justify-content:center; gap:10px;">
        <input type="hidden" name="action" value="unlock">
        <input type="hidden" name="id" value="<?php echo $uid; ?>">
        <button class="btn" type="submit" style="background:#27ae60; color:white; border:none;">Xác nhận Mở</button>
        <a class="btn outline" href="customers.php">Huỷ</a>
      </form>
    </section>
  <?php endif; ?>

  <?php if (!empty($_GET['view'])): 
      $uid = (int)$_GET['view'];
      // 1. Lấy thông tin khách
      $user_info = $conn->query("SELECT * FROM Users WHERE user_id=$uid")->fetch_assoc();
      
      // 2. Lấy địa chỉ mặc định
      $addr_info = $conn->query("SELECT * FROM Addresses WHERE user_id=$uid AND is_default=1 LIMIT 1")->fetch_assoc();
      
      // 3. Lấy 5 đơn gần nhất
      $recent_orders = $conn->query("SELECT * FROM Orders WHERE user_id=$uid ORDER BY order_date DESC LIMIT 5");
  ?>
    <section class="admin-card" style="padding:20px; margin-top:14px;">
      <h3 style="margin:0 0 15px; border-bottom:1px solid #eee; padding-bottom:10px;">Hồ sơ khách hàng: <?php echo htmlspecialchars($user_info['full_name']); ?></h3>
      
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
          <div>
              <div class="muted">Thông tin liên hệ</div>
              <div style="font-size:16px; margin-top:5px;">
                  📧 <?php echo htmlspecialchars($user_info['email']); ?><br>
                  📞 <?php echo htmlspecialchars($user_info['phone'] ?? 'Chưa cập nhật'); ?>
              </div>
          </div>
          <div>
              <div class="muted">Địa chỉ giao hàng mặc định</div>
              <div style="font-size:16px; margin-top:5px;">
                  <?php if($addr_info): ?>
                      📍 <?php echo htmlspecialchars($addr_info['address_detail']); ?><br>
                      (Người nhận: <?php echo htmlspecialchars($addr_info['recipient_name']); ?> - <?php echo htmlspecialchars($addr_info['recipient_phone']); ?>)
                  <?php else: ?>
                      <i>Chưa có địa chỉ lưu trữ.</i>
                  <?php endif; ?>
              </div>
          </div>
      </div>

      <h4 style="margin:20px 0 10px;">Đơn hàng gần đây</h4>
      <table style="width:100%; border-collapse:collapse;">
          <tr style="background:#f9f9f9; text-align:left;">
              <th style="padding:8px;">Mã đơn</th>
              <th style="padding:8px;">Ngày đặt</th>
              <th style="padding:8px; text-align:right;">Tổng tiền</th>
              <th style="padding:8px;">Trạng thái</th>
          </tr>
          <?php if($recent_orders && $recent_orders->num_rows > 0): ?>
              <?php while($ro = $recent_orders->fetch_assoc()): 
                  $st_vn = $ro['status'];
                  if($st_vn=='pending') $st_vn = 'Chờ xử lý';
                  if($st_vn=='delivered') $st_vn = 'Hoàn tất';
                  if($st_vn=='cancelled') $st_vn = 'Đã hủy';
              ?>
              <tr>
                  <td style="padding:8px; border-bottom:1px solid #eee;">
                      <a href="orders.php?view=<?php echo $ro['order_id']; ?>">#<?php echo $ro['order_id']; ?></a>
                  </td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">
                      <?php echo date('d/m/Y', strtotime($ro['order_date'])); ?>
                  </td>
                  <td style="padding:8px; border-bottom:1px solid #eee; text-align:right; font-weight:bold;">
                      <?php echo money_vnd($ro['total_price']); ?>
                  </td>
                  <td style="padding:8px; border-bottom:1px solid #eee;">
                      <?php echo $st_vn; ?>
                  </td>
              </tr>
              <?php endwhile; ?>
          <?php else: ?>
              <tr><td colspan="4" style="padding:15px; text-align:center; color:#999;">Khách chưa có đơn hàng nào.</td></tr>
          <?php endif; ?>
      </table>

      <div style="margin-top:20px; text-align:right;">
        <a class="btn outline" href="customers.php">Đóng hồ sơ</a>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/admin-layout-bottom.php'; ?>