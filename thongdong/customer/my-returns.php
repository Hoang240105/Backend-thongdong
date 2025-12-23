<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối DB

$pageTitle = "Yêu cầu đổi/trả - Thong Dong";

// 2. Kiểm tra đăng nhập
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

function safe($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 3. Lấy danh sách yêu cầu của user này (Join với bảng Orders để check user_id)
$sql_list = "SELECT r.* FROM Returns r 
             JOIN Orders o ON r.order_id = o.order_id 
             WHERE o.user_id = ? 
             ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql_list);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests_result = $stmt->get_result();

// 4. Lấy chi tiết yêu cầu (nếu có ID)
$selected = null;
$selectedId = (int)($_GET['id'] ?? 0);

if ($selectedId > 0) {
    $sql_detail = "SELECT r.*, o.user_id 
                   FROM Returns r 
                   JOIN Orders o ON r.order_id = o.order_id 
                   WHERE r.return_id = ? AND o.user_id = ?";
    $stmt_detail = $conn->prepare($sql_detail);
    $stmt_detail->bind_param("ii", $selectedId, $user_id);
    $stmt_detail->execute();
    $selected = $stmt_detail->get_result()->fetch_assoc();
}

// Map trạng thái sang tiếng Việt
function map_status($s) {
    $map = [
        'pending'   => 'Chờ xử lý',
        'approved'  => 'Đang xử lý',
        'received'  => 'Đã nhận hàng hoàn',
        'completed' => 'Hoàn tất',
        'rejected'  => 'Từ chối'
    ];
    return $map[$s] ?? $s;
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card" style="padding:18px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <div>
        <h1 class="page-title" style="margin:0 0 6px;">Yêu cầu đổi/trả</h1>
        <p class="muted" style="margin:0;">Danh sách yêu cầu bạn đã gửi cho Thong Dong.</p>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="return-request.php">Tạo yêu cầu mới</a>
        <a class="btn outline" href="account.php">Về tài khoản</a>
      </div>
    </div>

    <div style="height:14px;"></div>

    <?php if ($selected): ?>
      <div class="card" style="padding:14px; margin-bottom:14px; border:1px solid #ddd;">
        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div>
            <h2 style="margin:0 0 6px; font-size:20px;">Chi tiết yêu cầu</h2>
            <div class="muted">Mã yêu cầu: <b>#<?php echo safe($selected['return_id']); ?></b></div>
          </div>
          <a class="btn outline" href="my-returns.php">Đóng chi tiết</a>
        </div>

        <div style="height:10px;"></div>

        <?php
          $typeLabel = ($selected['type'] === 'refund') ? 'Hoàn tiền' : 'Đổi hàng';
          $statusLabel = map_status($selected['status']);
        ?>

        <div class="checkout-lines" style="margin-top:8px;">
          <div class="line">
            <div class="muted">Loại yêu cầu</div>
            <div><b><?php echo safe($typeLabel); ?></b></div>
          </div>
          <div class="line">
            <div class="muted">Trạng thái</div>
            <div style="color:#c0392b; font-weight:bold;"><?php echo safe($statusLabel); ?></div>
          </div>
          <div class="line">
            <div class="muted">Mã đơn hàng</div>
            <div><a href="order-detail.php?id=<?php echo $selected['order_id']; ?>">#<?php echo safe($selected['order_id']); ?></a></div>
          </div>
          <div class="line">
            <div class="muted">Thời gian gửi</div>
            <div><?php echo date('d/m/Y H:i', strtotime($selected['created_at'])); ?></div>
          </div>
        </div>

        <div style="height:12px;"></div>

        <div class="card" style="padding:12px; background:#f9f9f9;">
          <b>Lý do & Mô tả:</b>
          <p style="margin-top:6px; white-space:pre-wrap; line-height:1.5;"><?php echo safe($selected['reason']); ?></p>
        </div>

        <?php if($selected['type'] === 'refund' && !empty($selected['bank_info'])): ?>
            <div style="height:12px;"></div>
            <div class="card" style="padding:12px; background:#e6f4ea; border:1px solid #c3e6cb;">
            <b>Thông tin nhận tiền hoàn:</b>
            <p style="margin-top:6px;"><?php echo safe($selected['bank_info']); ?></p>
            </div>
        <?php endif; ?>

      </div>
    <?php endif; ?>

    <?php if ($requests_result->num_rows === 0): ?>
      <div class="card" style="padding:30px; text-align:center;">
        <b>Chưa có yêu cầu đổi/trả nào.</b>
        <div class="muted" style="margin-top:6px;">Nếu sản phẩm có vấn đề, bạn hãy tạo yêu cầu hỗ trợ nhé.</div>
        <div style="margin-top:12px;">
          <a class="btn" href="return-request.php">Tạo yêu cầu đổi/trả</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card" style="padding:0; overflow:hidden;">
        <div style="padding:14px; border-bottom:1px solid rgba(0,0,0,.06);">
          <b>Lịch sử yêu cầu</b>
        </div>

        <div style="padding:14px; display:grid; gap:12px;">
          <?php while ($r = $requests_result->fetch_assoc()): 
            $typeLabel = ($r['type'] === 'refund') ? 'Hoàn tiền' : 'Đổi hàng';
            $statusLabel = map_status($r['status']);
          ?>
            <div class="card" style="padding:12px; background:#fff; border:1px solid #eee;">
              <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
                <div>
                  <div style="font-size:16px;">
                    <b>#<?php echo $r['return_id']; ?></b>
                    <span class="muted"> • <?php echo $typeLabel; ?></span>
                  </div>
                  <div class="muted" style="margin-top:4px; font-size:13px;">
                    Đơn hàng: <b>#<?php echo $r['order_id']; ?></b> • <?php echo date('d/m/Y', strtotime($r['created_at'])); ?>
                  </div>
                </div>

                <div style="text-align:right;">
                  <span class="badge" style="display:inline-block; margin-bottom:5px; background:#eee;">
                    <?php echo $statusLabel; ?>
                  </span>
                  <div>
                    <a class="btn outline small" href="my-returns.php?id=<?php echo $r['return_id']; ?>">Xem chi tiết</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    <?php endif; ?>

  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>