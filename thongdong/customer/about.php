<?php
session_start();
require_once '../includes/db.php'; // Kết nối DB để lưu tin nhắn

$pageTitle = "Giới thiệu - Thong Dong";
$msg_success = '';
$msg_error = '';

// --- XỬ LÝ FORM LIÊN HỆ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $topic   = $_POST['topic'] ?? 'khac';
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $phone === '' || $message === '') {
        $msg_error = 'Vui lòng nhập Tên, SĐT và Lời nhắn.';
    } else {
        // Lưu vào DB
        $stmt = $conn->prepare("INSERT INTO ContactMessages (full_name, phone, email, topic, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $phone, $email, $topic, $message);
        
        if ($stmt->execute()) {
            $msg_success = 'Cảm ơn bạn! Thong Dong đã nhận được lời nhắn và sẽ phản hồi sớm.';
        } else {
            $msg_error = 'Có lỗi xảy ra, vui lòng thử lại sau.';
        }
    }
}
?>
<?php include '../includes/customer-layout-top.php'; ?>

<main class="container" style="padding:34px 0 70px;">

  <section class="card about-hero">
    <div class="about-hero-left">
      <h1 style="margin:0 0 10px;">Giới thiệu</h1>
      <p class="muted" style="margin:0 0 14px;">
        Thong Dong là một góc nhỏ dành cho những điều an yên - mùi hương thuần Việt cho người Việt.
      </p>
      <div class="about-cta">
        <a class="btn" href="shop.php">Xem cửa hàng</a>
        <a class="btn outline" href="blog.php">Đọc Nhật ký</a>
      </div>
    </div>

    <div class="about-media card">
      <img class="about-media-img logo"
           src="/thongdong/assets/img/brand/logo.png"
           alt="Thong Dong Logo"
           onerror="this.style.display='none'; this.parentElement.classList.add('no-img');">
    </div>
  </section>

  <section class="card about-section">
    <h2 class="about-title">Câu chuyện Thong Dong</h2>
    <p>
      Có những ngày mình chỉ muốn chậm lại một chút. Thong Dong ra đời từ những khoảnh khắc như vậy...
      khi căn phòng nhỏ cần một mùi hương ấm, một ngọn lửa dịu, để lòng mình “thở” nhẹ hơn.
    </p>
    <p>
      Tụi mình chọn những mùi hương gần gũi: <b>hoa hồng, nhài, quế, gừng, gỗ mộc</b>…
      để mỗi lần thắp nến là một lần nhắc mình: “Bình yên là do bạn lựa chọn.”
    </p>
  </section>

  <section class="about-grid">
    <article class="card about-card">
      <h3>Thuần Việt</h3>
      <p class="muted">Mùi hương gợi ký ức Việt: mộc mạc, ấm áp, dễ chịu.</p>
    </article>

    <article class="card about-card">
      <h3>Đủ tinh tế</h3>
      <p class="muted">Thiết kế tối giản, sang và ấm, mang lại vibe Tết, đúng vibe nhà.</p>
    </article>

    <article class="card about-card">
      <h3>Gói quà đẹp</h3>
      <p class="muted">Set quà nhỏ xinh, phù hợp tặng bạn bè, gia đình, đồng nghiệp.</p>
    </article>
  </section>

  <section class="card about-section">
    <h2 class="about-title">Cam kết nhỏ</h2>
    <ul class="about-list">
      <li><b>Thơm dễ chịu</b> - không gắt, hợp không gian sinh hoạt.</li>
      <li><b>Cháy đều</b> - gợi ý cách thắp đúng để nến bền hơn.</li>
      <li><b>Hỗ trợ nhanh</b> - cần tư vấn mùi hương, tụi mình trả lời liền.</li>
    </ul>
  </section>

  <section class="card about-section visit">
    <div class="visit-left">
      <h2 class="about-title">Đến thăm tụi mình tại (COMING SOON)</h2>
      <p class="muted" style="margin-top:0;">
        Thong Dong hiện chỉ hoạt động online, dự kiến sẽ ra mắt cửa hàng đầu tiên vào đầu năm 2026 tại Đà Nẵng.
      </p>
      <p class="muted" style="margin-top:0;">Đợi Thong Dong nhé!</p>

      <div class="visit-item">
        <div class="muted">Địa chỉ</div>
        <div><b>123 Đường Hoa Mai, Quận Hải Châu, Đà Nẵng</b></div>
      </div>

      <div class="visit-item">
        <div class="muted">Giờ mở cửa</div>
        <div><b>09:00 – 21:00</b> (Thứ 2 – Chủ nhật)</div>
      </div>

      <div class="visit-item">
        <div class="muted">Hotline</div>
        <div><b>0900 000 000</b></div>
      </div>

      <div class="visit-item">
        <div class="muted">Mạng xã hội</div>
        <div>
          <a href="#" class="link">Facebook</a>
          <span class="muted"> • </span>
          <a href="#" class="link">Instagram</a>
          <span class="muted"> • </span>
          <a href="#" class="link">TikTok</a>
        </div>
      </div>

      <div class="visit-actions">
        <a class="btn" href="shop.php">Mua online</a>
        <a class="btn outline" href="https://www.google.com/maps/search/Da+Nang" target="_blank">Mở Google Maps</a>
      </div>
    </div>

    <div class="about-media card">
      <img class="about-media-img"
           src="/thongdong/assets/img/brand/store.jpg"
           alt="Ảnh cửa hàng Thong Dong"
           onerror="this.style.display='none'; this.parentElement.classList.add('no-img');">
      <div class="about-media-fallback">Cửa hàng đầu tiên dự kiến ra mắt tại Đà Nẵng</div>
    </div>
  </section>

  <section class="card about-section contact-quick" id="contact">
    <h2 class="about-title">Liên hệ nhanh</h2>
    <p class="muted" style="margin-top:0;">
      Bạn để lại lời nhắn, tụi mình sẽ phản hồi sớm nha.
    </p>

    <?php if ($msg_success): ?>
        <div style="padding:15px; background:#e6f4ea; color:#1e7e34; border:1px solid #c3e6cb; border-radius:8px; margin-bottom:15px;">
            ✅ <?php echo htmlspecialchars($msg_success); ?>
        </div>
    <?php endif; ?>

    <?php if ($msg_error): ?>
        <div style="padding:15px; background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb; border-radius:8px; margin-bottom:15px;">
            ⚠️ <?php echo htmlspecialchars($msg_error); ?>
        </div>
    <?php endif; ?>

    <form class="contact-form" method="post" action="about.php#contact">
      <div class="form-row">
        <div class="form-group">
          <label>Họ và tên *</label>
          <input class="input" type="text" name="full_name" required placeholder="Ví dụ: Tiên">
        </div>
        <div class="form-group">
          <label>Số điện thoại *</label>
          <input class="input" type="text" name="phone" required placeholder="Ví dụ: 09xx xxx xxx">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Email (tuỳ chọn)</label>
          <input class="input" type="email" name="email" placeholder="vidu@gmail.com">
        </div>
        <div class="form-group">
          <label>Chủ đề</label>
          <select class="input" name="topic">
            <option value="tu-van">Tư vấn mùi hương</option>
            <option value="don-hang">Hỏi về đơn hàng</option>
            <option value="qua-tang">Gói quà / set quà</option>
            <option value="khac">Khác</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Lời nhắn *</label>
        <textarea class="input" name="message" rows="4" required placeholder="Bạn muốn tư vấn