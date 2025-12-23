<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Quên mật khẩu - Thong Dong";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Vui lòng nhập email.";
    } else {
        // 2. Kiểm tra email trong Database
        $stmt = $conn->prepare("SELECT user_id, full_name FROM Users WHERE email = ? AND role = 'customer'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Email tồn tại -> Giả lập gửi mail thành công
            // (Thực tế đoạn này sẽ gửi mail chứa link reset password)
            $success = "Đã tìm thấy tài khoản của <b>" . htmlspecialchars($user['full_name']) . "</b>.<br>Hệ thống đã gửi hướng dẫn đặt lại mật khẩu vào email này (Mô phỏng).";
        } else {
            // Email không tồn tại
            $error = "Email này chưa được đăng ký tại Thong Dong.";
        }
    }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
  <section class="card auth-card">
    <h1 style="margin:0 0 10px;">Quên mật khẩu</h1>
    <p class="muted" style="margin:0 0 16px;">Nhập email để nhận hướng dẫn đặt lại mật khẩu nha.</p>

    <?php if ($success): ?>
      <div class="auth-success" style="background:#e6f4ea; color:#1e7e34; border:1px solid #c3e6cb; padding:15px; border-radius:8px; margin-bottom:15px;">
          ✅ <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="auth-alert" style="background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb; padding:15px; border-radius:8px; margin-bottom:15px;">
          ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <div class="form-group">
        <label>Email đã đăng ký</label>
        <input class="input" name="email" type="email" required placeholder="vd: demo@thongdong.vn" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <button class="btn" type="submit" style="width:100%;">Gửi yêu cầu</button>

      <div class="auth-links">
        <a class="link" href="login.php">← Quay lại đăng nhập</a>
      </div>
    </form>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>