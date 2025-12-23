<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Review khách hàng - Thong Dong";

// 2. Lấy ID sản phẩm nếu có lọc
$pid = (int)($_GET['product'] ?? 0);

// 3. Truy vấn Review đã được DUYỆT (status = 'approved')
$sql = "SELECT r.*, u.full_name, p.name as product_name 
        FROM Reviews r 
        LEFT JOIN Users u ON r.user_id = u.user_id
        LEFT JOIN Products p ON r.product_id = p.product_id
        WHERE r.status = 'approved'";

if ($pid > 0) {
    $sql .= " AND r.product_id = $pid";
}

$sql .= " ORDER BY r.created_at DESC";
$result = $conn->query($sql);

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

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card review-card">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap;">
      <div>
        <h1 class="page-title" style="margin:0 0 6px;">Góc Review</h1>
        <p class="muted" style="margin:0;">Xem mọi người nói gì về nến Thong Dong nè.</p>
      </div>

      <div class="review-actions">
        <a class="btn outline" href="shop.php">Qua cửa hàng</a>
        <a class="btn" href="my-reviews.php">Review của tôi</a>
      </div>
    </div>

    <div class="review-list" style="margin-top:20px;">
      <?php if ($result->num_rows === 0): ?>
        <div class="card" style="padding:30px; text-align:center;">
          <b>Chưa có review nào được duyệt.</b>
          <div class="muted" style="margin-top:6px;">Bạn hãy là người đầu tiên chia sẻ cảm nhận nhé!</div>
          <div style="margin-top:15px;">
             <a class="btn small" href="order-history.php">Chọn sản phẩm từ đơn hàng</a>
             <a class="btn" href="review-create.php?id=<?php echo $product_id; ?>">Viết đánh giá cho sản phẩm này</a>
          </div>
        </div>
      <?php else: ?>
        <?php while ($r = $result->fetch_assoc()): ?>
          <div class="review-item" style="border-bottom:1px solid #eee; padding:20px 0; display:flex; gap:15px; flex-wrap:wrap;">
            
            <div class="review-left" style="flex:1;">
              <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px;">
                <span class="badge" style="background:#eee; font-weight:normal;"><?php echo htmlspecialchars($r['product_name']); ?></span>
                <?php echo starsHtml($r['rating']); ?>
              </div>

              <?php if(!empty($r['title'])): ?>
                <h3 class="review-title" style="margin:0 0 8px; font-size:18px; color:#333;"><?php echo htmlspecialchars($r['title']); ?></h3>
              <?php endif; ?>
              
              <div style="margin-bottom:10px; line-height:1.6; color:#555;">
                  <?php echo nl2br(htmlspecialchars($r['comment'])); ?>
              </div>
              
              <div class="review-meta" style="font-size:13px; color:#999;">
                <b><?php echo htmlspecialchars($r['full_name']); ?></b> • <?php echo date('d/m/Y', strtotime($r['created_at'])); ?>
              </div>
            </div>

            <div class="review-right" style="display:flex; flex-direction:column; justify-content:center;">
              <a class="btn small outline" href="product-detail.php?id=<?php echo $r['product_id']; ?>">Xem sản phẩm</a>
            </div>

          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>