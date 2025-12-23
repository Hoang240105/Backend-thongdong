<?php
// 1. Cấu hình Session chuẩn chỉ (Phải đặt trước session_start)
session_set_cookie_params(0, '/'); 
session_start();

require_once '../includes/db.php'; 

$pageTitle = "Đăng nhập - Thong Dong";

// Nếu đã đăng nhập thì kiểm tra
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        header('Location: ../admin/blog.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, status, role FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Tài khoản đang bị khóa.';
                } else {
                    // --- BÀN TAY SẮT: XÓA SẠCH SẼ ---
                    // Tạo ID phiên mới để xóa dấu vết cũ
                    session_regenerate_id(true); 
                    // Xóa trắng dữ liệu đang có (để thổi bay ông "Khách Demo")
                    $_SESSION = []; 

                    // --- GHI DỮ LIỆU CHUẨN ---
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['customer'] = [
                        'id'    => $user['user_id'],
                        'name'  => $user['full_name'],
                        'email' => $user['email']
                    ];

                    // Lưu bắt buộc ngay lập tức
                    session_write_close(); 

                    // --- CHUYỂN HƯỚNG ---
                    if ($user['role'] === 'admin') {
                        // Dùng đường dẫn tuyệt đối cho chắc ăn
                        header('Location: /thongdong/admin/blog.php');
                    } else {
                        header('Location: /thongdong/customer/index.php');
                    }
                    exit;
                }
            } else {
                $error = 'Mật khẩu không đúng.';
            }
        } else {
            $error = 'Email này chưa được đăng ký.';
        }
    }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
  <section class="card auth-card">
    <h1 style="margin:0 0 10px;">Đăng nhập</h1>
    <p class="muted" style="margin:0 0 16px;">Chào bạn, đăng nhập để quản lý hoặc mua hàng.</p>
    <?php if ($error): ?>
      <div class="auth-alert" style="background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb; padding:10px; margin-bottom:15px; border-radius:8px;">
        ⚠️ <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    <form method="post" class="auth-form">
      <div class="form-group">
        <label>Email</label>
        <input class="input" name="email" type="email" required placeholder="vd: admin@thongdong.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input class="input" name="password" type="password" required placeholder="Nhập mật khẩu...">
      </div>
      <button class="btn" type="submit" style="width:100%;">Đăng nhập</button>
      <div class="auth-links">
        <a class="link" href="forgot-password.php">Quên mật khẩu?</a>
        <span class="muted">•</span>
        <a class="link" href="register.php">Tạo tài khoản mới</a>
      </div>
    </form>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>