<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Viết review - Thong Dong";

// 2. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    $next = 'review-create.php?id=' . (int)($_GET['id'] ?? 0);
    // Lưu ý: customer/login.php phải hỗ trợ xử lý param ?redirect=... hoặc bạn sửa lại logic login sau
    $_SESSION['redirect_after_login'] = $next;
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 3. Lấy ID sản phẩm & kiểm tra tồn tại
$pid = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$product = null;

if ($pid > 0) {
    $stmt = $conn->prepare("SELECT product_id, name FROM Products WHERE product_id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

if (!$product) {
    include '../includes/customer-layout-top.php';
    echo '<main class="container" style="padding:32px 0 70px;">
            <section class="card" style="padding:30px; text-align:center;">
                <h1 style="margin:0 0 8px;">Không tìm thấy sản phẩm</h1>
                <p class="muted">Sản phẩm không tồn tại hoặc đường dẫn sai.</p>
                <a class="btn" href="shop.php" style="margin-top:15px;">Về cửa hàng</a>
            </section>
          </main>';
    include '../includes/customer-layout-bottom.php';
    exit;
}

$errors = [];

// 4. XỬ LÝ GỬI REVIEW (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)($_POST['rating'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($rating < 1 || $rating > 5) $errors[] = "Vui lòng chọn số sao (1–5).";
    if ($title === '') $errors[] = "Vui lòng nhập tiêu đề.";
    if ($content === '') $errors[] = "Vui lòng nhập nội dung đánh giá.";

    if (empty($errors)) {
        // Lưu vào DB (Trạng thái mặc định: pending - chờ duyệt)
        $stmt_ins = $conn->prepare("INSERT INTO Reviews (product_id, user_id, rating, title, comment, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt_ins->bind_param("iiiss", $pid, $user_id, $rating, $title, $content);

        if ($stmt_ins->execute()) {
            // Chuyển hướng về trang "Review của tôi"
            header('Location: my-reviews.php?success=1');
            exit;
        } else {
            $errors[] = "Lỗi hệ thống: " . $conn->error;
        }
    }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card review-card">
    <h1 class="page-title" style="margin:0 0 8px;">Viết đánh giá</h1>
    <p class="muted" style="margin:0 0 14px;">
      Sản phẩm: <b><?php echo htmlspecialchars($product['name']); ?></b>
    </p>

    <?php if ($errors): ?>
      <div class="auth-alert" style="background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb; padding:10px; margin-bottom:15px; border-radius:8px;">
        <?php foreach ($errors as $e): ?>
          <div>• <?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="review-form">
      <input type="hidden" name="product_id" value="<?php echo $pid; ?>">

      <div class="form-group">
        <label>Đánh giá (Sao) *</label>
        <div style="position:relative;">
            <select class="input" name="rating" required style="appearance:none; -webkit-appearance:none;">
            <option value="">-- Chọn số sao --</option>
            <option value="5" <?php echo ((int)($_POST['rating'] ?? 0) === 5) ? 'selected' : ''; ?>>★★★★★ (Tuyệt vời)</option>
            <option value="4" <?php echo ((int)($_POST['rating'] ?? 0) === 4) ? 'selected' : ''; ?>>★★★★☆ (Hài lòng)</option>
            <option value="3" <?php echo ((int)($_POST['rating'] ?? 0) === 3) ? 'selected' : ''; ?>>★★★☆☆ (Bình thường)</option>
            <option value="2" <?php echo ((int)($_POST['rating'] ?? 0) === 2) ? 'selected' : ''; ?>>★★☆☆☆ (Không thích)</option>
            <option value="1" <?php echo ((int)($_POST['rating'] ?? 0) === 1) ? 'selected' : ''; ?>>★☆☆☆☆ (Tệ)</option>
            </select>
            <div style="position:absolute; right:10px; top:50%; transform:translateY(-50%); pointer-events:none; color:#999;">▼</div>
        </div>
      </div>

      <div class="form-group">
        <label>Tiêu đề *</label>
        <input class="input" name="title" placeholder="Ví dụ: Mùi hương rất ấm, đóng gói đẹp..."
               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
      </div>

      <div class="form-group">
        <label>Nội dung chi tiết *</label>
        <textarea class="input" name="content" rows="5" placeholder="Chia sẻ cảm nhận chi tiết của bạn về sản phẩm nhé..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
      </div>

      <div class="review-actions">
        <button class="btn" type="submit">Gửi đánh giá</button>
        <a class="btn outline" href="product-detail.php?id=<?php echo $pid; ?>">Quay lại sản phẩm</a>
      </div>
      
      <div class="muted" style="margin-top:10px; font-size:13px; text-align:center;">
        Đánh giá của bạn sẽ được kiểm duyệt trước khi hiển thị công khai.
      </div>
    </form>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>