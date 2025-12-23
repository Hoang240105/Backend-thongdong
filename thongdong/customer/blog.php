<?php
session_start();
require_once '../includes/db.php'; // Kết nối DB

$pageTitle = "Nhật ký - Thong Dong";

// Lấy danh sách bài viết đã xuất bản
$sql = "SELECT * FROM BlogPosts WHERE status = 'published' ORDER BY published_at DESC";
$result = $conn->query($sql);

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:32px 0 70px;">
  <section class="card page-head">
    <h1 class="page-title">Nhật ký</h1>
    <p class="muted">Những mẩu chuyện nhỏ - chậm lại một chút giữa đời thường.</p>
  </section>

  <div class="blog-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:20px; margin-top:20px;">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): 
          // 1. Lấy đường dẫn ảnh từ DB
          $img = $row['thumbnail_url'];
          
          // 2. Chuyển sang đường dẫn tương đối (../) để an toàn hơn
          // Nếu DB lưu "assets/..." -> Code sẽ hiểu là "../assets/..." (lùi ra thư mục cha để tìm)
          if (!empty($img) && strpos($img, 'assets/') === 0) {
              $img = '../' . $img;
          }
          
          $link = 'blog-detail.php?slug=' . $row['slug'];
      ?>
        <article class="card blog-card" style="display:flex; flex-direction:column; overflow:hidden;">
            <a href="<?php echo $link; ?>" style="display:block; height:180px; overflow:hidden; background:#f5f5f5;">
                <img src="<?php echo htmlspecialchars($img); ?>" 
                     alt="<?php echo htmlspecialchars($row['title']); ?>" 
                     style="width:100%; height:100%; object-fit:cover; transition:transform 0.3s;">
            </a>

            <div class="blog-body" style="padding:18px; flex:1; display:flex; flex-direction:column;">
                <div class="muted" style="font-size:13px; margin-bottom:8px;">
                    <?php echo date('d/m/Y', strtotime($row['published_at'])); ?>
                </div>

                <h3 class="blog-title" style="margin:0 0 10px; font-size:18px; line-height:1.4;">
                    <a href="<?php echo $link; ?>" style="text-decoration:none; color:inherit;">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </a>
                </h3>

                <p class="blog-excerpt" style="color:#555; font-size:14px; line-height:1.6; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; margin-bottom:15px;">
                    <?php echo htmlspecialchars($row['excerpt']); ?>
                </p>

                <div style="margin-top:auto;">
                    <a class="btn small outline" href="<?php echo $link; ?>">Đọc thêm</a>
                </div>
            </div>
        </article>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="card" style="grid-column:1/-1; padding:30px; text-align:center;">
        <p>Chưa có bài viết nào.</p>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include '../includes/customer-layout-bottom.php'; ?>