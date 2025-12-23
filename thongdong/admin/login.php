<?php
session_start();
// 1. Kết nối Database
require_once '../includes/db.php';

// Nếu đã đăng nhập thì chuyển luôn vào Dashboard
if (!empty($_SESSION['admin'])) {
    header('Location: dashboard.php'); // Sửa đường dẫn cho gọn
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đủ thông tin.';
    } else {
        // 2. Kiểm tra trong Database (Chỉ lấy user có role='admin')
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role FROM Users WHERE email = ? AND role = 'admin' AND status = 'active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 3. Kiểm tra mật khẩu (đã mã hóa hash)
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công -> Lưu Session
                $_SESSION['admin'] = [
                    'id'    => $user['user_id'],
                    'email' => $user['email'],
                    'name'  => $user['full_name'],
                    'role'  => $user['role'],
                ];
                
                // Chuyển hướng
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Mật khẩu không đúng.';
            }
        } else {
            $error = 'Email không tồn tại hoặc không có quyền Admin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Thong Dong</title>
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
</head>
<body>

<?php include __DIR__ . '/includes/admin-header.php'; ?>

<main class="admin-auth">
  <section class="admin-card" style="width:min(460px, 92%);">
    <h1 class="auth-title">Admin Login</h1>
    <div class="muted">Đăng nhập hệ thống quản trị.</div>

    <?php if ($error): ?>
      <div class="alert" style="background:#fff5f5; color:#c0392b; border:1px solid #fabeb9; padding:10px; margin:15px 0;">
          ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="field">
        <label>Email admin</label>
        <input class="input" type="email" name="email" required placeholder="admin@thongdong.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="field">
        <label>Mật khẩu</label>
        <input class="input" type="password" name="password" required placeholder="Nhập mật khẩu...">
      </div>

      <button class="btn primary" type="submit" style="width:100%; margin-top:10px;">
        Đăng nhập
      </button>
    </form>
    
    <div class="muted" style="margin-top:15px; font-size:13px;">
        <a href="/thongdong/index.php">← Về trang chủ</a>
    </div>
  </section>
</main>

</body>
</html>