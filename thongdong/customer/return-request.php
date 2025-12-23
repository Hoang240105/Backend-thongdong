<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Tạo yêu cầu đổi/trả - Thong Dong";

// 2. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 3. Lấy danh sách đơn hàng của user (để hiện vào Dropdown)
$stmt = $conn->prepare("SELECT order_id, created_at FROM Orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders = [];
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
}

// Pre-fill từ URL (ví dụ bấm nút Đổi trả từ trang chi tiết đơn)
$pre_order_id = (int)($_GET['order_id'] ?? 0);

// ---- XỬ LÝ FORM (POST) ----
$errors = [];
$successId = '';
$success = false;

// Thông tin mặc định
$defaultName  = $_SESSION['customer']['name'] ?? '';
$defaultPhone = $_SESSION['customer']['phone'] ?? '';
$defaultEmail = $_SESSION['customer']['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $type    = $_POST['type'] ?? 'exchange'; // exchange | refund
    $reason  = trim($_POST['reason'] ?? '');
    $detail  = trim($_POST['detail'] ?? '');
    
    // Bank info (nếu là hoàn tiền)
    $bank_name  = trim($_POST['bank_name'] ?? '');
    $bank_acc   = trim($_POST['bank_acc'] ?? '');
    $bank_owner = trim($_POST['bank_owner'] ?? '');

    // Validate
    if ($orderId <= 0) $errors[] = 'Vui lòng chọn đơn hàng.';
    if ($reason === '') $errors[] = 'Vui lòng chọn lý do.';
    if ($detail === '') $errors[] = 'Vui lòng nhập mô tả chi tiết.';

    if ($type === 'refund') {
        if ($bank_name === '' || $bank_acc === '' || $bank_owner === '') {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin ngân hàng để hoàn tiền.';
        }
    }

    if (empty($errors)) {
        // Kiểm tra xem đơn hàng có phải của user này không (bảo mật)
        $check = $conn->prepare("SELECT order_id FROM Orders WHERE order_id = ? AND user_id = ?");
        $check->bind_param("ii", $orderId, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $errors[] = 'Đơn hàng không hợp lệ.';
        } else {
            // Xử lý thông tin ngân hàng thành chuỗi (để lưu vào DB)
            $bank_info = '';
            if ($type === 'refund') {
                $bank_info = "Ngân hàng: $bank_name\nSTK: $bank_acc\nChủ TK: $bank_owner";
            }

            // Lưu vào bảng Returns
            // Gộp detail và contact info vào cột reason hoặc detail (tùy cấu trúc DB)
            // Ở đây mình lưu detail vào cột reason (hoặc tạo cột detail riêng nếu DB có)
            // Theo cấu trúc mình đưa lúc trước: `reason TEXT`. Ta sẽ gộp vào đó cho tiện.
            
            $full_reason = "Lý do: $reason\nChi tiết: $detail";
            
            $stmt_ins = $conn->prepare("INSERT INTO Returns (order_id, type, reason, status, bank_info, created_at) VALUES (?, ?, ?, 'pending', ?, NOW())");
            $stmt_ins->bind_param("isss", $orderId, $type, $full_reason, $bank_info);

            if ($stmt_ins->execute()) {
                $success = true;
                $successId = $stmt_ins->insert_id;
            } else {
                $errors[] = "Lỗi hệ thống: " . $conn->error;
            }
        }
    }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card" style="padding:18px;">
    <h1 style="margin:0 0 6px;">Tạo yêu cầu đổi/trả</h1>
    <p class="muted" style="margin:0 0 14px;">Điền thông tin để Thong Dong hỗ trợ nhanh nhất.</p>

    <?php if ($success): ?>
      <div class="auth-alert" style="margin-bottom:14px; background:#e6f4ea; color:#1e7e34; border:1px solid #c3e6cb;">
        <b>Đã gửi yêu cầu thành công!</b> Mã yêu cầu là <b>#<?php echo $successId; ?></b>.
        <div class="muted" style="margin-top:8px;">
          <a class="btn small" href="my-returns.php" style="display:inline-flex;">Xem danh sách yêu cầu</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="auth-alert" style="margin-bottom:14px; background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb;">
        <?php foreach ($errors as $e): ?>
          <div>• <?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="checkout-grid" style="gap:16px;">
      <div class="checkout-left">
        <div class="form-group">
          <label>Chọn đơn hàng cần hỗ trợ *</label>
          <select class="input" name="order_id" required>
            <option value="">-- Chọn đơn hàng --</option>
            <?php foreach ($orders as $o): 
                $oid = $o['order_id'];
                $selected = ($oid == ($pre_order_id ?: ($_POST['order_id'] ?? 0))) ? 'selected' : '';
            ?>
              <option value="<?php echo $oid; ?>" <?php echo $selected; ?>>
                Đơn #<?php echo $oid; ?> — <?php echo date('d/m/Y', strtotime($o['created_at'])); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="muted" style="margin-top:6px;">
            Chỉ hiện các đơn hàng bạn đã đặt.
          </div>
        </div>

        <div class="form-group">
          <label>Loại yêu cầu *</label>
          <div class="pay-box">
            <?php $curType = $_POST['type'] ?? 'exchange'; ?>

            <label class="pay-item">
              <input type="radio" name="type" value="exchange" <?php echo ($curType === 'exchange') ? 'checked' : ''; ?>>
              <div>
                <b>Đổi hàng</b>
                <div class="muted">Đổi sản phẩm lỗi/hư hỏng</div>
              </div>
            </label>

            <label class="pay-item">
              <input type="radio" name="type" value="refund" <?php echo ($curType === 'refund') ? 'checked' : ''; ?>>
              <div>
                <b>Hoàn tiền</b>
                <div class="muted">Hoàn tiền vào tài khoản ngân hàng</div>
              </div>
            </label>
          </div>
        </div>

        <div id="refundBox" class="card" style="padding:12px; margin-top:8px; display:none; background:#f9f9f9;">
          <b>Thông tin nhận tiền hoàn (Bắt buộc)</b>
          <div class="muted" style="margin:6px 0 12px;">
            Nhập chính xác để chúng mình chuyển khoản lại nhé.
          </div>

          <div class="form-group">
            <label>Tên Ngân hàng</label>
            <input class="input" name="bank_name" value="<?php echo htmlspecialchars($_POST['bank_name'] ?? ''); ?>" placeholder="VD: Vietcombank">
          </div>

          <div class="form-group">
            <label>Số tài khoản</label>
            <input class="input" name="bank_acc" value="<?php echo htmlspecialchars($_POST['bank_acc'] ?? ''); ?>" placeholder="VD: 0123456789">
          </div>

          <div class="form-group">
            <label>Chủ tài khoản (Viết hoa không dấu)</label>
            <input class="input" name="bank_owner" value="<?php echo htmlspecialchars($_POST['bank_owner'] ?? ''); ?>" placeholder="VD: NGUYEN VAN A">
          </div>
        </div>

        <div class="form-group">
          <label>Lý do *</label>
          <?php $curReason = $_POST['reason'] ?? ''; ?>
          <select class="input" name="reason" required>
            <option value="">-- Chọn lý do --</option>
            <option value="Giao nhầm sản phẩm" <?php echo ($curReason==='Giao nhầm sản phẩm')?'selected':''; ?>>Giao nhầm sản phẩm</option>
            <option value="Sản phẩm lỗi / bể vỡ" <?php echo ($curReason==='Sản phẩm lỗi / bể vỡ')?'selected':''; ?>>Sản phẩm lỗi / bể vỡ</option>
            <option value="Chưa ưng mùi" <?php echo ($curReason==='Chưa ưng mùi')?'selected':''; ?>>Chưa ưng mùi (Đổi hàng)</option>
            <option value="Khác" <?php echo ($curReason==='Khác')?'selected':''; ?>>Khác</option>
          </select>
        </div>

        <div class="form-group">
          <label>Mô tả chi tiết & Link ảnh (nếu có) *</label>
          <textarea class="input" name="detail" rows="4" required placeholder="Mô tả tình trạng + Link ảnh minh chứng..."><?php echo htmlspecialchars($_POST['detail'] ?? ''); ?></textarea>
        </div>
      </div>

      <div class="checkout-right">
        <div class="card" style="padding:14px;">
          <h2 style="margin:0 0 10px; font-size:20px;">Thông tin liên hệ</h2>
          <div class="muted" style="margin-bottom:15px;">Chúng mình sẽ liên hệ qua thông tin này.</div>

          <div class="form-group">
            <label>Họ và tên</label>
            <input class="input" value="<?php echo htmlspecialchars($defaultName); ?>" disabled style="background:#eee;">
          </div>

          <div class="form-group">
            <label>Số điện thoại</label>
            <input class="input" value="<?php echo htmlspecialchars($defaultPhone); ?>" disabled style="background:#eee;">
          </div>

          <div class="form-group">
            <label>Email</label>
            <input class="input" value="<?php echo htmlspecialchars($defaultEmail); ?>" disabled style="background:#eee;">
          </div>

          <button class="btn" type="submit" style="width:100%; margin-top:10px;">
            Gửi yêu cầu
          </button>

          <div class="muted" style="margin-top:10px; text-align:center;">
            Thời gian xử lý dự kiến: 1–3 ngày làm việc.
          </div>

          <div style="display:flex; gap:10px; justify-content:center; margin-top:12px; flex-wrap:wrap;">
            <a class="btn outline small" href="returns.php">Xem chính sách</a>
            <a class="btn outline small" href="account.php">Về tài khoản</a>
          </div>
        </div>
      </div>
    </form>
  </section>
</main>

<script>
  const refundBox = document.getElementById('refundBox');
  const typeRadios = document.querySelectorAll('input[name="type"]');

  function toggleRefundBox(){
    const checked = document.querySelector('input[name="type"]:checked');
    refundBox.style.display = (checked && checked.value === 'refund') ? 'block' : 'none';
  }

  typeRadios.forEach(r => r.addEventListener('change', toggleRefundBox));
  toggleRefundBox();
</script>

<?php include '../includes/customer-layout-bottom.php'; ?>