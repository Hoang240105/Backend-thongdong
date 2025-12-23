<?php
session_start();
require_once '../includes/db.php'; 

// 1. Lấy SLUG từ đường dẫn (Thay vì ID)
$slug = $_GET['slug'] ?? '';
$pageTitle = "Bài viết - Thong Dong";
$post = null;

if (!empty($slug)) {
    // 2. Tìm bài viết theo SLUG
    $stmt = $conn->prepare("SELECT * FROM BlogPosts WHERE slug = ? AND status = 'published'");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
}

if ($post) {
    $pageTitle = htmlspecialchars($post['title']) . " - Thong Dong";
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
  <?php if (!$post): ?>
    <section class="card" style="padding:30px; text-align:center;">
      <h1 style="margin:0 0 8px;">Không tìm thấy bài viết</h1>
      <p class="muted">Bài viết này không tồn tại hoặc đã bị ẩn.</p>
      <a class="btn" href="blog.php" style="margin-top:15px;">Quay lại Nhật ký</a>
    </section>
  <?php else: ?>
    
    <article class="card blog-detail">

      <div class="blog-top">
        <span class="tag">Blog</span> 
        <span class="muted"><?php echo date('d/m/Y', strtotime($post['published_at'])); ?></span>
      </div>

      <h1 class="blog-detail-title"><?php echo htmlspecialchars($post['title']); ?></h1>

      <?php 
        // 3. Xử lý ảnh (Dùng đúng cột thumbnail_url)
        $heroImage = $post['thumbnail_url'] ?? '';
        
        // Logic xử lý đường dẫn ảnh cho đúng thư mục
        if (!empty($heroImage) && strpos($heroImage, 'assets/') === 0) {
            $heroImage = '../' . $heroImage;
        }
        // Nếu ảnh rỗng thì dùng ảnh mẫu
        if (empty($heroImage)) {
            $heroImage = '../assets/img/blog/placeholder.jpg';
        }
      ?>

      <div class="blog-hero-img" style="margin: 20px 0; border-radius: 8px; overflow: hidden;">
        <img src="<?php echo htmlspecialchars($heroImage); ?>" 
             alt="<?php echo htmlspecialchars($post['title']); ?>" 
             style="width: 100%; height: auto; display: block;"
             onerror="this.src='../assets/img/blog/placeholder.jpg'">
      </div>

      <div class="blog-content" style="line-height: 1.8; color: #333; font-size: 16px;">
          <?php echo $post['content']; ?>
      </div>

      <div style="margin-top:40px; border-top: 1px solid #eee; padding-top: 20px; display: flex; gap: 10px;">
        <a class="btn outline" href="blog.php">← Quay lại Nhật ký</a>
        <a class="btn" href="shop.php">Xem cửa hàng</a>
      </div>

    </article>
  <?php endif; ?>
</main>

<style>
    /* CSS bổ sung cho đẹp */
    .blog-detail { max-width: 800px; margin: 0 auto; padding: 40px; }
    .blog-detail-title { margin: 15px 0; font-size: 28px; line-height: 1.3; color: #2c3e50; }
    .blog-top { display: flex; gap: 15px; align-items: center; font-size: 14px; }
    .tag { background: #f0f0f0; padding: 4px 10px; border-radius: 20px; font-weight: 500; color: #555; }
    
    /* CSS cho ảnh trong bài viết (nếu có) */
    .blog-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .blog-content h2 { margin-top: 30px; margin-bottom: 15px; font-size: 22px; color: #333; }
    .blog-content p { margin-bottom: 20px; }
</style>

<?php include '../includes/customer-layout-bottom.php'; ?>