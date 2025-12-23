<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Review của tôi - Thong Dong";

// 2. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 3. Lấy danh sách Review của user này
$sql = "SELECT r.*, p.name as product_name 
        FROM Reviews r 
        LEFT JOIN Products p ON r.product_id = p.product_id
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

$success = !empty($_GET['success']);

// Helper hiển thị sao
function starsHtml($rating){
    $rating = (int)$rating;
    $html = '<div class="stars" aria-label="Rating '.$rating.'/5" style="color:#f1c40f; letter-spacing:1px;">';
    for ($i=1; $i<=5; $i++){
        $html .= ($i <= $rating) ? '★' : '☆';
    }
    $html .= '</div>';
    return $html;
}

// Map trạng thái
function statusLabel($s) {
    if ($s === 'approved') return '<span class="badge" style="background:#e6f4ea; color:#1e7e34;">Đã duyệt</span>';
    if ($s === 'hidden') return '<span class="badge" style="background:#fce8e6; color:#c0392b;">Đã ẩn</span>';
    return '<span class="badge" style="background:#fff3cd; color:#856404;">Chờ duyệt</span>';
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card review-card">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap;">
      <div>
        <h1 class="page-title" style="margin:0 0 6px;">Review của tôi</h1>
        <p class="muted" style="margin:0;">Tổng cộng: <b><?php echo $reviews_result->num_rows; ?></b> review</p>
      </div>
      <div class="review-actions">
        <a class="btn outline" href="account.php">Về tài khoản</a>
        <a class="btn" href="reviews.php">Xem review cộng đồng</a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="auth-alert" style="margin-top:12px; background:#e6f4ea; color:#1e7e34; border:1px solid #c3e6cb;">
        <div><b>Đã gửi review thành công!</b> Cảm ơn bạn nhiều nha.</div>
      </div>
    <?php endif; ?>

    <div class="review-list" style="margin-top:20px;">
      <?php if ($reviews_result->num_rows === 0): ?>
        <div class="card" style="padding:16px; text-align:center;">
          <b>Bạn chưa viết review nào.</b>
          <div class="muted" style="margin-top:6px;">Vào trang sản phẩm mua rồi bấm “Viết đánh giá” để chia sẻ cảm nhận nha.</div>
          <a class="btn outline small" href="shop.php" style="margin-top:10px;">Đi mua sắm</a>
        </div>
      <?php else: ?>
        <?php while ($r = $reviews_result->fetch_assoc()): ?>
          <div class="review-item" style="border-bottom:1px solid #eee; padding:15px 0; display:flex; gap:15px; flex-wrap:wrap;">
            
            <div class="review-left" style="flex:1;">
              <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:5px;">
                <span class="badge" style="background:#eee;"><?php echo htmlspecialchars($r['product_name']); ?></span>
                <?php echo starsHtml($r['rating']); ?>
                <?php echo statusLabel($r['status']); ?>
              </div>

              <?php if(!empty($r['title'])): ?>
                <h3 class="review-title" style="margin:5px 0; font-size:16px;"><?php echo htmlspecialchars($r['title']); ?></h3>
              <?php endif; ?>

              <div style="margin-bottom:5px; color:#555; line-height:1.5;">
                  <?php echo nl2br(htmlspecialchars($r['comment'])); ?>
              </div>
              
              <div class="review-meta" style="font-size:12px; color:#999;">
                  <?php echo date('H:i d/m/Y', strtotime($r['created_at'])); ?>
              </div>
            </div>

            <div class="review-right" style="display:flex; flex-direction:column; gap:5px; justify-content:center;">
              <a class="btn small outline" href="product-detail.php?id=<?php echo $r['product_id']; ?>">Xem SP</a>
            </div>

          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>