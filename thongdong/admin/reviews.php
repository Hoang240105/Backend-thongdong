<?php
session_start();
require_once '../includes/db.php';
require __DIR__ . '/includes/admin-guard.php';

$pageTitle = "Review - Admin Thong Dong";

// ---- XỬ LÝ DUYỆT / ẨN / XÓA ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)$_POST['id'];

    if ($id > 0) {
        if ($action === 'approve') {
            $conn->query("UPDATE Reviews SET status = 'approved' WHERE review_id = $id");
        }
        if ($action === 'hide') {
            $conn->query("UPDATE Reviews SET status = 'hidden' WHERE review_id = $id");
        }
        if ($action === 'delete') {
            $conn->query("DELETE FROM Reviews WHERE review_id = $id");
        }
    }
    // Reload để cập nhật giao diện
    header('Location: reviews.php');
    exit;
}

// ---- LẤY DỮ LIỆU & LỌC ----
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';

// SQL: Join Reviews -> Users (lấy tên khách) -> Products (lấy tên SP)
$sql = "SELECT r.*, u.full_name, u.email, p.name as product_name 
        FROM Reviews r
        JOIN Users u ON r.user_id = u.user_id
        JOIN Products p ON r.product_id = p.product_id
        WHERE 1=1";

if ($status !== 'all') {
    $sql .= " AND r.status = '$status'";
}

if ($q) {
    $safe_q = $conn->real_escape_string($q);
    $sql .= " AND (u.full_name LIKE '%$safe_q%' OR p.name LIKE '%$safe_q%' OR r.comment LIKE '%$safe_q%')";
}

$sql .= " ORDER BY r.created_at DESC";
$reviews = $conn->query($sql);

function statusBadge($st) {
    if ($st === 'approved') return '<span class="badge ok" style="background:#e6f4ea; color:#1e7e34;">Đã duyệt</span>';
    if ($st === 'hidden')   return '<span class="badge warn" style="background:#fce8e6; color:#d93025;">Đã ẩn</span>';
    return '<span class="badge" style="background:#fff3cd; color:#856404;">Chờ duyệt</span>';
}

function stars($n) {
    $n = (int)$n;
    $s = '';
    for ($i=1;$i<=5;$i++) $s .= ($i <= $n) ? '★' : '☆';
    return $s;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
  <style>
      .stars { color: #f1c40f; letter-spacing: 2px; }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/admin-header.php'; ?>

<main class="container admin-page" style="padding:28px 0 70px;">
  <section class="card" style="padding:18px;">
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:end; margin-bottom:15px;">
      <div>
        <h1 class="page-title" style="margin:0 0 8px;">Đánh giá (Review)</h1>
        <p class="muted" style="margin:0;">Duyệt, ẩn hoặc xoá review khách hàng.</p>
      </div>

      <div class="page-actions">
        <a class="btn outline" href="reviews.php">Làm mới</a>
      </div>
    </div>

    <form method="get" class="admin-filters" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap; margin-bottom:20px;">
      <div class="control">
        <label class="control-label" style="display:block; margin-bottom:5px; font-weight:bold;">Tìm kiếm</label>
        <input class="input" name="q" placeholder="Tên khách, sản phẩm..." value="<?php echo htmlspecialchars($q); ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
      </div>

      <div class="control">
        <label class="control-label" style="display:block; margin-bottom:5px; font-weight:bold;">Trạng thái</label>
        <select class="input" name="status" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
          <option value="all">Tất cả</option>
          <option value="pending" <?php if($status=='pending') echo 'selected'; ?>>Chờ duyệt</option>
          <option value="approved" <?php if($status=='approved') echo 'selected'; ?>>Đã duyệt</option>
          <option value="hidden" <?php if($status=='hidden') echo 'selected'; ?>>Đã ẩn</option>
        </select>
      </div>

      <div class="control">
        <button class="btn small" type="submit" style="padding:9px 15px; background:#333; color:#fff; border:none; border-radius:4px; cursor:pointer;">Lọc</button>
      </div>
    </form>

    <div class="table-wrap" style="overflow-x:auto;">
      <table class="table" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:2px solid #eee; text-align:left;">
            <th style="padding:10px;">Mã</th>
            <th style="padding:10px;">Thời gian</th>
            <th style="padding:10px;">Khách hàng</th>
            <th style="padding:10px;">Sản phẩm</th>
            <th style="padding:10px;">Đánh giá</th>
            <th style="padding:10px;">Nội dung</th>
            <th style="padding:10px;">Trạng thái</th>
            <th style="padding:10px; text-align:right;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($reviews->num_rows === 0): ?>
            <tr>
              <td colspan="8" class="muted" style="padding:20px; text-align:center; color:#999;">Không có review nào phù hợp.</td>
            </tr>
          <?php else: ?>

          <?php while ($rv = $reviews->fetch_assoc()): ?>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:10px;"><b>#<?php echo $rv['review_id']; ?></b></td>
              <td style="padding:10px; font-size:13px;"><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></td>
              <td style="padding:10px;">
                <b><?php echo htmlspecialchars($rv['full_name']); ?></b>
                <div class="muted" style="font-size:12px;"><?php echo htmlspecialchars($rv['email']); ?></div>
              </td>
              <td style="padding:10px;"><?php echo htmlspecialchars($rv['product_name']); ?></td>
              <td class="stars" style="padding:10px;"><?php echo stars($rv['rating']); ?></td>
              <td style="padding:10px; max-width:300px;">
                <?php if(!empty($rv['title'])): ?>
                    <b style="display:block; margin-bottom:4px;"><?php echo htmlspecialchars($rv['title']); ?></b>
                <?php endif; ?>
                <div class="muted" style="font-size:13px; line-height:1.4;">
                  <?php echo nl2br(htmlspecialchars($rv['comment'])); ?>
                </div>
              </td>
              <td style="padding:10px;"><?php echo statusBadge($rv['status']); ?></td>
              <td style="padding:10px; text-align:right;">
                <div class="btn-row" style="display:flex; gap:5px; justify-content:flex-end;">
                  <?php if($rv['status'] !== 'approved'): ?>
                      <form method="post">
                        <input type="hidden" name="id" value="<?php echo $rv['review_id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn small" type="submit" style="background:#1e7e34; color:#fff; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Duyệt</button>
                      </form>
                  <?php endif; ?>

                  <?php if($rv['status'] !== 'hidden'): ?>
                      <form method="post">
                        <input type="hidden" name="id" value="<?php echo $rv['review_id']; ?>">
                        <input type="hidden" name="action" value="hide">
                        <button class="btn small outline" type="submit" style="background:#eee; color:#333; border:1px solid #ccc; padding:4px 8px; border-radius:4px; cursor:pointer;">Ẩn</button>
                      </form>
                  <?php endif; ?>

                  <form method="post" onsubmit="return confirm('Xoá review này vĩnh viễn?');">
                    <input type="hidden" name="id" value="<?php echo $rv['review_id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="btn small outline danger" type="submit" style="background:#c0392b; color:#fff; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">Xoá</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </section>
</main>

</body>
</html>