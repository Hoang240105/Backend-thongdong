<?php
$pageTitle = "Đổi trả & Hoàn tiền - Thong Dong";
include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card return-card" style="padding:22px;">
    <h1 class="page-title" style="margin:0 0 8px;">Đổi trả & Hoàn tiền</h1>
    <p class="muted" style="margin:0 0 18px;">
      Thong Dong hỗ trợ đổi/trả/hoàn tiền trong trường hợp sản phẩm lỗi, giao nhầm, hoặc chưa ưng mùi.
    </p>

    <div class="card return-box" style="padding:22px; margin:0 0 18px; border:1px solid #eee;">
      <h3 style="margin:0 0 10px;">Điều kiện áp dụng</h3>
      <ul style="margin:0; padding-left:18px; line-height:1.9; color:#555;">
        <li>Gửi yêu cầu trong <b>7 ngày</b> kể từ khi nhận hàng.</li>
        <li>Sản phẩm còn nguyên vẹn, còn hộp/tem (nếu có).</li>
        <li>Chuẩn bị mã đơn hàng + mô tả tình trạng/lý do.</li>
      </ul>
    </div>

    <div class="card return-box" style="padding:22px; margin:0 0 22px; border:1px solid #eee;">
      <h3 style="margin:0 0 10px;">Hình thức xử lý</h3>
      <ul style="margin:0; padding-left:18px; line-height:1.9; color:#555;">
        <li><b>Đổi sản phẩm</b> tương đương.</li>
        <li><b>Hoàn tiền</b> (refund) qua chuyển khoản ngân hàng.</li>
        <li><b>Voucher</b> mua hàng (trong một số trường hợp đặc biệt).</li>
      </ul>
    </div>

    <div class="return-actions" style="margin-top:6px; display:flex; gap:12px; flex-wrap:wrap;">
      <a class="btn" href="return-request.php">Tạo yêu cầu đổi/trả</a>
      <a class="btn outline" href="my-returns.php">Lịch sử yêu cầu</a>
      <a class="btn outline" href="account.php">Về tài khoản</a>
    </div>
  </section>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>