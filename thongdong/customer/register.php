<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Đăng ký - Thong Dong";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($password !== $password2) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } else {
        // 2. Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email này đã được đăng ký.';
        } else {
            // 3. Tạo tài khoản mới
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'customer';
            $status = 'active';

            $stmt_insert = $conn->prepare("INSERT INTO Users (full_name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt_insert->bind_param("sssss", $name, $email, $hashed_password, $role, $status);

            if ($stmt_insert->execute()) {
                $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
            } else {
                $error = 'Có lỗi xảy ra: ' . $conn->error;
            }
        }
    }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
  <section class="card auth-card">
    <h1 style="margin:0 0 10px;">Tạo tài khoản</h1>
    <p class="muted" style="margin:0 0 16px;">Đăng ký để lưu thông tin và theo dõi đơn hàng nha.</p>

    <?php if ($error): ?>
      <div class="auth-alert" style="background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb; padding:10px; margin-bottom:15px; border-radius:8px;">
        ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="auth-success" style="background:#e6f4ea; color:#1e7e34; border:1px solid #c3e6cb; padding:15px; margin-bottom:15px; border-radius:8px;">
        ✅ <?php echo htmlspecialchars($success); ?>
        <div style="margin-top:10px;">
            <a href="login.php" class="btn small">Đăng nhập ngay</a>
        </div>
      </div>
    <?php endif; ?>

    <form method="post" class="auth-form">
      <div class="form-group">
        <label>Họ và tên</label>
        <input class="input" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input class="input" name="email" type="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Mật khẩu</label>
        <input class="input" name="password" type="password" required>
      </div>

      <div class="form-group">
        <label>Nhập lại mật khẩu</label>
        <input class="input" name="password2" type="password" required>
      </div>

      <button class="btn" type="submit" style="width:100%;">Đăng ký</button>

      <div class="auth-links">
        <a class="link" href="login.php">← Quay lại đăng nhập</a>
      </div>
    </form>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>