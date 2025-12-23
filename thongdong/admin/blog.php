<?php
// Thiết lập cookie giống bên Login để 2 bên nhìn thấy nhau
session_set_cookie_params(0, '/');
session_start();
require_once '../includes/db.php';

// Kiểm tra quyền Admin
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../customer/login.php');
    exit;
}

// ... (Phần còn lại giữ nguyên không đổi) ...  
// Kiểm tra quyền Admin
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../customer/login.php');
    exit;
}

$pageTitle = "Quản lý Nhật ký (Blog) - Admin Thong Dong";

// Xử lý Lọc & Tìm kiếm
$where = [];
$params = [];
$types = "";

// Lọc theo từ khóa
$keyword = trim($_GET['keyword'] ?? '');
if ($keyword) {
    $where[] = "(title LIKE ? OR excerpt LIKE ?)";
    $keywordLike = "%$keyword%";
    $params[] = $keywordLike;
    $params[] = $keywordLike;
    $types .= "ss";
}

// Lọc theo trạng thái
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Xây dựng câu SQL
$sql = "SELECT * FROM BlogPosts";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

include '../includes/admin-layout-top.php';
?>

<div class="main-content">
  <div class="page-header">
    <div>
      <h1 class="page-title">Nhật ký (Blog)</h1>
      <p class="text-muted">Quản lý bài viết và chia sẻ.</p>
    </div>
    <div class="header-actions">
      <a href="blog.php" class="btn outline">Làm mới</a>
      <a href="blog-form.php" class="btn primary">+ Thêm bài</a>
    </div>
  </div>

  <div class="card mb-4">
    <form method="get" class="filter-bar">
      <div class="form-group" style="flex:1;">
        <label>Tìm kiếm</label>
        <input type="text" name="keyword" class="form-input" 
               value="<?php echo htmlspecialchars($keyword); ?>" 
               placeholder="Tiêu đề, nội dung ngắn...">
      </div>
      <div class="form-group" style="width:200px;">
        <label>Trạng thái</label>
        <select name="status" class="form-select">
          <option value="all">Tất cả</option>
          <option value="published" <?php echo ($statusFilter === 'published') ? 'selected' : ''; ?>>Đã đăng</option>
          <option value="draft" <?php echo ($statusFilter === 'draft') ? 'selected' : ''; ?>>Nháp</option>
        </select>
      </div>
      <div class="form-group" style="align-self:flex-end;">
        <button type="submit" class="btn primary">Lọc</button>
      </div>
    </form>
  </div>

  <div class="card">
    <?php if ($result->num_rows === 0): ?>
        <div style="padding:40px; text-align:center; color:#999;">
            Không tìm thấy bài viết nào.
        </div>
    <?php else: ?>
        <div class="list-group">
        <?php while ($row = $result->fetch_assoc()): 
            // Xử lý ảnh (Admin xem ảnh thì cần lùi ra thư mục cha ../)
            $img = $row['thumbnail_url'];
            if (!empty($img) && strpos($img, 'assets/') === 0) {
                $img = '../' . $img;
            }
            if (empty($img)) $img = '../assets/img/blog/placeholder.jpg';
            
            // Xử lý trạng thái
            $statusLabel = '';
            $statusClass = '';
            if ($row['status'] === 'published') {
                $statusLabel = 'Đã đăng';
                $statusClass = 'badge-success'; // Màu xanh
            } else {
                $statusLabel = 'Nháp';
                $statusClass = 'badge-secondary'; // Màu xám
            }
        ?>
            <div class="list-item" style="display:flex; gap:15px; padding:15px; border-bottom:1px solid #eee; align-items:start;">
                <div style="width:120px; height:80px; flex-shrink:0; background:#f0f0f0; border-radius:4px; overflow:hidden;">
                    <img src="<?php echo htmlspecialchars($img); ?>" 
                         alt="Thumb" 
                         style="width:100%; height:100%; object-fit:cover;"
                         onerror="this.src='../assets/img/blog/placeholder.jpg'">
                </div>

                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:5px;">
                        <h3 style="margin:0; font-size:16px;">
                            <a href="blog-form.php?id=<?php echo $row['post_id']; ?>" style="text-decoration:none; color:#333;">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                        </h3>
                        <div style="text-align:right;">
                            <span class="badge <?php echo $statusClass; ?>" style="padding:4px 8px; border-radius:4px; font-size:12px; font-weight:bold;">
                                <?php echo $statusLabel; ?>
                            </span>
                            <div style="font-size:12px; color:#999; margin-top:4px;">
                                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <p style="margin:0 0 10px; font-size:14px; color:#666; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                        <?php echo htmlspecialchars($row['excerpt']); ?>
                    </p>

                    <div class="action-buttons" style="display:flex; gap:10px;">
                        <a href="../customer/blog-detail.php?slug=<?php echo $row['slug']; ?>" target="_blank" class="btn small outline">Xem</a>
                        <a href="blog-form.php?id=<?php echo $row['post_id']; ?>" class="btn small primary">Sửa</a>
                        <a href="blog-delete.php?id=<?php echo $row['post_id']; ?>" class="btn small danger" onclick="return confirm('Bạn có chắc muốn xóa bài này?');">Xóa</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
  </div>
</div>

<style>
/* CSS Nhanh cho Badge nếu chưa có trong layout */
.badge-success { background: #e6f4ea; color: #1e7e34; }
.badge-secondary { background: #e2e3e5; color: #383d41; }
.form-input, .form-select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
.filter-bar { display: flex; gap: 15px; align-items: flex-end; padding: 15px; }
.mb-4 { margin-bottom: 1.5rem; }
</style>

<?php include '../includes/admin-layout-bottom.php'; ?>