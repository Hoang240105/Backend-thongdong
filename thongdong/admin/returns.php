<?php
session_start();
require_once '../includes/db.php';
require __DIR__ . '/includes/admin-guard.php';

$pageTitle = "Đổi trả & Hoàn tiền - Admin | Thong Dong";

// ---- XỬ LÝ UPDATE STATUS ----
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $rid = (int)$_POST['rid'];
    $newStatus = $_POST['status'] ?? '';

    if ($rid > 0 && $newStatus) {
        $stmt = $conn->prepare("UPDATE Returns SET status = ? WHERE return_id = ?");
        $stmt->bind_param("si", $newStatus, $rid);
        if ($stmt->execute()) {
            $flash = "Đã cập nhật trạng thái yêu cầu #$rid.";
        }
    }
}

// ---- helpers ----
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function badgeClass($status){
    // Map status DB sang class CSS
    if ($status === 'completed') return 'badge success'; // Hoàn tất
    if ($status === 'approved') return 'badge info';     // Đã duyệt/Đã nhận
    if ($status === 'received') return 'badge info';     // Đã nhận hàng
    if ($status === 'rejected') return 'badge danger';   // Từ chối
    return 'badge warn'; // pending
}

function statusLabel($s) {
    $map = [
        'pending'   => 'Chờ xử lý',
        'approved'  => 'Đang xử lý',
        'received'  => 'Đã nhận hàng',
        'completed' => 'Hoàn tất',
        'rejected'  => 'Từ chối'
    ];
    return $map[$s] ?? $s;
}

function typeLabel($t){
    return $t === 'refund' ? 'Hoàn tiền' : 'Đổi hàng';
}

// ---- LỌC & TÌM KIẾM (GET) ----
$q = trim($_GET['q'] ?? '');
$fType = $_GET['type'] ?? 'all';
$fStatus = $_GET['status'] ?? 'all';

// SQL Query: Join Returns -> Orders -> Users
$sql = "SELECT r.*, o.order_id, u.full_name, u.phone, u.email 
        FROM Returns r
        LEFT JOIN Orders o ON r.order_id = o.order_id
        LEFT JOIN Users u ON o.user_id = u.user_id
        WHERE 1=1";

if ($q) {
    $safe_q = $conn->real_escape_string($q);
    $sql .= " AND (r.return_id LIKE '%$safe_q%' OR o.order_id LIKE '%$safe_q%' OR u.full_name LIKE '%$safe_q%' OR u.phone LIKE '%$safe_q%')";
}

if ($fType !== 'all') {
    $sql .= " AND r.type = '$fType'";
}

if ($fStatus !== 'all') {
    $sql .= " AND r.status = '$fStatus'";
}

$sql .= " ORDER BY r.created_at DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo h($pageTitle); ?></title>
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
</head>
<body>

<?php include __DIR__ . '/includes/admin-header.php'; ?>

<main class="container" style="padding:28px 0 70px;">
  <section class="card" style="padding:18px;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <div>
        <h1 style="margin:0 0 6px;">Đổi trả & Hoàn tiền</h1>
        <p class="muted" style="margin:0;">Quản lý yêu cầu đổi hàng và refund từ khách.</p>
      </div>
      <a class="btn outline small" href="returns.php">Làm mới</a>
    </div>

    <?php if ($flash): ?>
      <div class="auth-alert" style="margin-top:14px; background:#e6f4ea; color:#1e7e34; border:1px solid #c3e6cb;">
        ✅ <?php echo h($flash); ?>
      </div>
    <?php endif; ?>

    <form method="get" class="card" style="padding:14px; margin-top:14px;">
      <div class="filters" style="display:grid; grid-template-columns: 1.2fr 0.8fr 0.8fr 0.4fr; gap:12px;">
        <div class="form-group" style="margin:0;">
          <label>Tìm kiếm</label>
          <input class="input" name="q" value="<?php echo h($q); ?>" placeholder="Mã yêu cầu, mã đơn, tên khách...">
        </div>
        <div class="form-group" style="margin:0;">
          <label>Loại</label>
          <select class="input" name="type">
            <option value="all">Tất cả</option>
            <option value="exchange" <?php if($fType=='exchange') echo 'selected'; ?>>Đổi hàng</option>
            <option value="refund" <?php if($fType=='refund') echo 'selected'; ?>>Hoàn tiền</option>
          </select>
        </div>
        <div class="form-group" style="margin:0;">
          <label>Trạng thái</label>
          <select class="input" name="status">
            <option value="all">Tất cả</option>
            <option value="pending" <?php if($fStatus=='pending') echo 'selected'; ?>>Chờ xử lý</option>
            <option value="approved" <?php if($fStatus=='approved') echo 'selected'; ?>>Đang xử lý</option>
            <option value="received" <?php if($fStatus=='received') echo 'selected'; ?>>Đã nhận hàng</option>
            <option value="completed" <?php if($fStatus=='completed') echo 'selected'; ?>>Hoàn tất</option>
            <option value="rejected" <?php if($fStatus=='rejected') echo 'selected'; ?>>Từ chối</option>
          </select>
        </div>
        <div class="form-group" style="margin:0; display:flex; align-items:flex-end;">
          <button class="btn" type="submit" style="width:100%;">Lọc</button>
        </div>
      </div>
    </form>

    <div style="margin-top:14px; overflow:auto;">
      <table class="table" style="width:100%; min-width:980px;">
        <thead>
          <tr>
            <th>Mã</th>
            <th>Thời gian</th>
            <th>Mã đơn</th>
            <th>Khách hàng</th>
            <th>Loại</th>
            <th>Lý do</th>
            <th>Trạng thái</th>
            <th style="text-align:right;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($r = $result->fetch_assoc()): 
                // Xử lý thông tin ngân hàng (nếu có)
                $bankInfo = $r['bank_info'] ?? ''; 
            ?>
              <tr>
                <td><b>#<?php echo $r['return_id']; ?></b></td>
                <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                <td><a href="orders.php?view=<?php echo $r['order_id']; ?>">#<?php echo $r['order_id']; ?></a></td>
                <td>
                    <b><?php echo h($r['full_name']); ?></b><br>
                    <span class="muted" style="font-size:12px;"><?php echo h($r['phone']); ?></span>
                </td>
                <td>
                    <?php if($r['type']=='refund'): ?>
                        <span style="color:#c0392b;">Hoàn tiền</span><br>
                        <small><?php echo number_format($r['refund_amount']); ?>đ</small>
                    <?php else: ?>
                        <span>Đổi hàng</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?php echo h($r['reason']); ?>
                </td>
                <td>
                    <span class="<?php echo badgeClass($r['status']); ?>">
                        <?php echo statusLabel($r['status']); ?>
                    </span>
                </td>
                <td style="text-align:right;">
                  <button class="btn outline small" type="button" 
                          onclick="openDetail(<?php echo h(json_encode([
                              'rid' => $r['return_id'],
                              'oid' => $r['order_id'],
                              'created' => date('H:i d/m/Y', strtotime($r['created_at'])),
                              'type' => $r['type'],
                              'reason' => $r['reason'],
                              'status' => statusLabel($r['status']),
                              'name' => $r['full_name'],
                              'phone' => $r['phone'],
                              'email' => $r['email'],
                              'bank' => $bankInfo,
                              'amount' => number_format($r['refund_amount']).'đ'
                          ])); ?>)">
                    Xem
                  </button>

                  <button class="btn small" type="button" 
                          onclick="openUpdate(<?php echo $r['return_id']; ?>, '<?php echo $r['status']; ?>')">
                    Xử lý
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="muted" style="padding:20px; text-align:center;">
                Chưa có yêu cầu nào.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<div id="detailModal" class="modal" style="display:none;">
  <div class="modal-backdrop" onclick="closeModal('detailModal')"></div>
  <div class="modal-card card" style="max-width:600px; width:calc(100% - 24px); padding:20px;">
    <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
        <h3 style="margin:0;">Chi tiết yêu cầu</h3>
        <button class="btn outline small" onclick="closeModal('detailModal')">Đóng</button>
    </div>
    
    <div id="detailContent"></div>
  </div>
</div>

<div id="updateModal" class="modal" style="display:none;">
  <div class="modal-backdrop" onclick="closeModal('updateModal')"></div>
  <div class="modal-card card" style="max-width:400px; width:calc(100% - 24px); padding:20px;">
    <h3 style="margin-top:0;">Cập nhật trạng thái</h3>
    
    <form method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="rid" id="uRid" value="">

      <div class="form-group">
        <label>Chọn trạng thái mới</label>
        <select class="input" name="status" id="uStatus" style="width:100%;">
          <option value="pending">Chờ xử lý</option>
          <option value="approved">Đang xử lý (Đã duyệt)</option>
          <option value="received">Đã nhận lại hàng</option>
          <option value="completed">Hoàn tất (Đã đổi/Hoàn tiền)</option>
          <option value="rejected">Từ chối</option>
        </select>
      </div>

      <button class="btn primary" type="submit" style="width:100%;">Lưu thay đổi</button>
    </form>
  </div>
</div>

<script>
function closeModal(id){ document.getElementById(id).style.display = 'none'; }
function openModal(id){ document.getElementById(id).style.display = 'block'; }

function openDetail(data){
  let html = `
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
        <div>
            <div class="muted">Yêu cầu</div>
            <div><b>#${data.rid}</b> (${data.type === 'refund' ? 'Hoàn tiền' : 'Đổi hàng'})</div>
            <div>Ngày: ${data.created}</div>
            <div>Status: <b>${data.status}</b></div>
        </div>
        <div>
            <div class="muted">Khách hàng</div>
            <div>${data.name}</div>
            <div>${data.phone}</div>
            <div>Đơn hàng: #${data.oid}</div>
        </div>
    </div>
    
    <div class="card" style="padding:10px; background:#f9f9f9; margin-bottom:15px;">
        <div class="muted">Lý do:</div>
        <p style="margin:5px 0 0;">${data.reason}</p>
    </div>
  `;

  if(data.type === 'refund'){
      html += `
        <div class="card" style="padding:10px; border:1px solid #c3e6cb;">
            <div class="muted" style="color:#155724;">Thông tin hoàn tiền (${data.amount}):</div>
            <p style="margin:5px 0 0;">${data.bank || 'Không có thông tin ngân hàng'}</p>
        </div>
      `;
  }

  document.getElementById('detailContent').innerHTML = html;
  openModal('detailModal');
}

function openUpdate(rid, currentStatus){
  document.getElementById('uRid').value = rid;
  document.getElementById('uStatus').value = currentStatus;
  openModal('updateModal');
}
</script>

<?php include __DIR__ . '/includes/admin-layout-bottom.php'; ?>
</body>
</html>