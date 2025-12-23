<?php
session_start();
require_once '../includes/db.php';
require __DIR__ . '/includes/admin-guard.php';

$pageTitle = "Cài đặt - Thong Dong";
$success = '';
$errors = [];

// Danh sách các key cấu hình mặc định
$defaults = [
    'store_name'  => 'Thong Dong',
    'slogan'      => 'Nến thơm thuần Việt dành cho người Việt.',
    'hotline'     => '0900 000 000',
    'email'       => 'hello@thongdong.vn',
    'address'     => 'Đà Nẵng, Việt Nam',
    'ship_fee'    => '25000',
    'free_ship'   => '500000',
    'bank_enable' => '1',
    'bank_name'   => 'Vietcombank',
    'bank_number' => '0123456789',
    'bank_owner'  => 'THONG DONG',
    'bank_note'   => 'TD + SĐT',
];

// --- XỬ LÝ LƯU CÀI ĐẶT (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Chuẩn bị dữ liệu từ form
    $new_settings = [];
    foreach ($defaults as $key => $default_val) {
        if ($key === 'bank_enable') {
            $val = isset($_POST[$key]) ? '1' : '0';
        } else {
            $val = trim($_POST[$key] ?? '');
        }
        $new_settings[$key] = $val;
    }

    // 2. Validate cơ bản
    if ($new_settings['store_name'] === '') $errors[] = 'Vui lòng nhập tên cửa hàng.';
    if ($new_settings['hotline'] === '') $errors[] = 'Vui lòng nhập hotline.';

    // 3. Lưu vào Database (Dùng REPLACE để Insert hoặc Update)
    if (empty($errors)) {
        $stmt = $conn->prepare("REPLACE INTO Settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($new_settings as $key => $val) {
            $stmt->bind_param("ss", $key, $val);
            $stmt->execute();
        }
        $success = 'Đã lưu cài đặt thành công!';
    }
}

// --- TẢI CÀI ĐẶT TỪ DB (GET) ---
$db_settings = [];
$result = $conn->query("SELECT * FROM Settings");
while ($row = $result->fetch_assoc()) {
    $db_settings[$row['setting_key']] = $row['setting_value'];
}

// Gộp: Mặc định < Database < Form vừa submit (nếu lỗi)
$values = array_merge($defaults, $db_settings);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    // Nếu lỗi thì giữ lại dữ liệu vừa nhập
    $values = array_merge($values, $new_settings); 
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
</head>

<body class="admin-page admin-settings">

<?php include __DIR__ . '/includes/admin-header.php'; ?>

<main class="container" style="padding: 28px 0 80px;">
  <section class="card" style="padding:18px;">
    <h1 style="margin:0 0 6px;">Cài đặt hệ thống</h1>
    <p class="muted" style="margin:0 0 14px;">Cấu hình thông tin cửa hàng, phí vận chuyển và thanh toán.</p>

    <?php if ($success): ?>
      <div class="auth-alert" style="border-color:#c3e6cb; background:#e6f4ea; color:#155724;">
        ✅ <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="auth-alert" style="border-color:#f5c6cb; background:#f8d7da; color:#721c24;">
        <?php foreach ($errors as $e): ?>
          <div>• <?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="settings-grid">
      <div class="card" style="padding:16px;">
        <h2 style="margin:0 0 12px; font-size:20px;">Thông tin cửa hàng</h2>

        <div class="form-grid">
          <div class="form-group">
            <label>Tên cửa hàng *</label>
            <input class="input" name="store_name" value="<?php echo htmlspecialchars($values['store_name']); ?>">
          </div>

          <div class="form-group">
            <label>Hotline *</label>
            <input class="input" name="hotline" value="<?php echo htmlspecialchars($values['hotline']); ?>">
          </div>

          <div class="form-group full">
            <label>Slogan</label>
            <input class="input" name="slogan" value="<?php echo htmlspecialchars($values['slogan']); ?>">
          </div>

          <div class="form-group full">
            <label>Email liên hệ *</label>
            <input class="input" name="email" value="<?php echo htmlspecialchars($values['email']); ?>">
          </div>

          <div class="form-group full">
            <label>Địa chỉ *</label>
            <textarea class="input" name="address" rows="3"><?php echo htmlspecialchars($values['address']); ?></textarea>
          </div>
        </div>

        <hr style="margin:16px 0; border:none; border-top:1px solid var(--line);">

        <h2 style="margin:0 0 12px; font-size:20px;">Vận chuyển</h2>
        <div class="form-grid">
          <div class="form-group">
            <label>Phí ship cố định (VNĐ)</label>
            <input class="input" type="number" name="ship_fee" value="<?php echo htmlspecialchars($values['ship_fee']); ?>">
          </div>

          <div class="form-group">
            <label>Freeship đơn từ (VNĐ)</label>
            <input class="input" type="number" name="free_ship" value="<?php echo htmlspecialchars($values['free_ship']); ?>">
          </div>
        </div>
        <div class="muted" style="margin-top:10px; font-size:13px;">Gợi ý: Đặt 0 để tắt tính năng freeship.</div>
      </div>

      <div class="card" style="padding:16px;">
        <h2 style="margin:0 0 12px; font-size:20px;">Cấu hình Chuyển khoản</h2>

        <label style="display:flex; gap:10px; align-items:flex-start; padding:12px; border:1px solid var(--line); border-radius:12px; cursor:pointer;">
          <input type="checkbox" name="bank_enable" <?php echo ($values['bank_enable'] === '1') ? 'checked' : ''; ?> style="margin-top:4px;">
          <div>
            <b>Bật thanh toán chuyển khoản</b>
            <div class="muted">Hiển thị tùy chọn này ở trang Checkout.</div>
          </div>
        </label>

        <div style="height:15px;"></div>

        <div class="form-grid">
          <div class="form-group">
            <label>Tên Ngân hàng</label>
            <input class="input" name="bank_name" placeholder="VD: Vietcombank" value="<?php echo htmlspecialchars($values['bank_name']); ?>">
          </div>
          <div class="form-group">
            <label>Số tài khoản</label>
            <input class="input" name="bank_number" value="<?php echo htmlspecialchars($values['bank_number']); ?>">
          </div>

          <div class="form-group">
            <label>Chủ tài khoản</label>
            <input class="input" name="bank_owner" style="text-transform:uppercase;" value="<?php echo htmlspecialchars($values['bank_owner']); ?>">
          </div>
          <div class="form-group">
            <label>Nội dung CK mặc định</label>
            <input class="input" name="bank_note" value="<?php echo htmlspecialchars($values['bank_note']); ?>">
          </div>
        </div>

        <button class="btn primary" type="submit" style="width:100%; margin-top:20px; padding:12px;">Lưu Thay Đổi</button>
      </div>
    </form>
  </section>
</main>

<?php include __DIR__ . '/includes/admin-layout-bottom.php'; ?>

</body>
</html>