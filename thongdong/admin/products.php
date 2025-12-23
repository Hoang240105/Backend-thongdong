<?php
session_start();
// 1. Kết nối Database
require_once '../includes/db.php';
require __DIR__ . '/includes/admin-guard.php';

$errors = [];
$flash = '';

/* ===== XỬ LÝ UPLOAD ẢNH ===== */
function upload_image_if_any(string $field): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

    $tmp = $_FILES[$field]['tmp_name'];
    $name = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $allow = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allow, true)) return null;

    $dir = __DIR__ . '/../assets/img/products';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    // Đặt tên file ngẫu nhiên để tránh trùng
    $newName = 'p_' . date('Ymd_His') . '_' . rand(1000,9999) . '.' . $ext;
    $dest = $dir . '/' . $newName;

    if (!move_uploaded_file($tmp, $dest)) return null;

    return 'assets/img/products/' . $newName; // Lưu đường dẫn tương đối
}

/* ===== XỬ LÝ ACTIONS (THÊM / SỬA / XÓA) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Lấy dữ liệu từ form
    $name     = trim($_POST['name'] ?? '');
    $price    = (int)($_POST['price'] ?? 0);
    $cat_id   = (int)($_POST['category_id'] ?? 0);
    $stock    = (int)($_POST['stock'] ?? 0);
    $status   = $_POST['status'] ?? 'active'; // active | inactive
    $desc     = trim($_POST['desc'] ?? '');
    $img_link = trim($_POST['image'] ?? ''); // Link ảnh nhập tay
    
    // Xử lý ảnh: Ưu tiên ảnh upload > Link nhập tay
    $uploaded = upload_image_if_any('image_file');
    $final_img = $uploaded ? $uploaded : $img_link;

    // --- A. THÊM SẢN PHẨM ---
    if ($action === 'create') {
        if ($name === '') $errors[] = 'Thiếu tên sản phẩm.';
        if ($price <= 0) $errors[] = 'Giá phải lớn hơn 0.';

        if (!$errors) {
            $stmt = $conn->prepare("INSERT INTO Products (name, category_id, price, stock_quantity, status, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiisss", $name, $cat_id, $price, $stock, $status, $desc, $final_img);
            
            if ($stmt->execute()) {
                header('Location: products.php?ok=created'); exit;
            } else {
                $errors[] = "Lỗi Database: " . $conn->error;
            }
        }
    }

    // --- B. CẬP NHẬT SẢN PHẨM ---
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        
        // Nếu không upload ảnh mới và không nhập link mới thì giữ nguyên ảnh cũ (lấy từ hidden field hoặc query lại, ở đây ta query lại nếu cần, hoặc đơn giản là chỉ update nếu có dữ liệu mới)
        // Cách đơn giản: Nếu $final_img rỗng, ta KHÔNG update cột image_url
        
        $sql = "UPDATE Products SET name=?, category_id=?, price=?, stock_quantity=?, status=?, description=?";
        $params = [$name, $cat_id, $price, $stock, $status, $desc];
        $types = "siiiss";

        if ($final_img) {
            $sql .= ", image_url=?";
            $params[] = $final_img;
            $types .= "s";
        }
        
        $sql .= " WHERE product_id=?";
        $params[] = $id;
        $types .= "i";

        if ($name && $id > 0) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                header('Location: products.php?ok=updated'); exit;
            } else {
                $errors[] = "Lỗi Database: " . $conn->error;
            }
        }
    }

    // --- C. XÓA SẢN PHẨM ---
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            // Xóa ảnh cũ nếu cần (nâng cao), ở đây chỉ xóa DB
            $conn->query("DELETE FROM Products WHERE product_id=$id");
            header('Location: products.php?ok=deleted'); exit;
        }
    }
}

// Xử lý Flash Message
$ok = $_GET['ok'] ?? '';
if ($ok === 'created') $flash = 'Đã thêm sản phẩm thành công.';
if ($ok === 'updated') $flash = 'Đã cập nhật sản phẩm.';
if ($ok === 'deleted') $flash = 'Đã xoá sản phẩm.';

// --- LẤY DỮ LIỆU ĐỂ HIỂN THỊ ---

// 1. Lấy danh sách Danh mục (để hiện Dropdown)
$cats = [];
$res_cats = $conn->query("SELECT * FROM Categories");
while($c = $res_cats->fetch_assoc()) {
    $cats[] = $c;
}

// 2. Lọc & Tìm kiếm Sản phẩm
$q   = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$st  = trim($_GET['st'] ?? '');

$sql_prod = "SELECT p.*, c.name as category_name 
             FROM Products p 
             LEFT JOIN Categories c ON p.category_id = c.category_id 
             WHERE 1=1";

if ($q) {
    $safe_q = $conn->real_escape_string($q);
    $sql_prod .= " AND (p.name LIKE '%$safe_q%' OR p.product_id = '$safe_q')";
}
if ($cat > 0) {
    $sql_prod .= " AND p.category_id = $cat";
}
if ($st) {
    $sql_prod .= " AND p.status = '$st'";
}

$sql_prod .= " ORDER BY p.created_at DESC";
$result = $conn->query($sql_prod);

// 3. Lấy thông tin sản phẩm đang sửa (nếu có)
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $res_edit = $conn->query("SELECT * FROM Products WHERE product_id = $eid");
    $editing = $res_edit->fetch_assoc();
}

function fmt_vnd(int $n): string {
    return number_format($n, 0, ',', '.') . 'đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Sản phẩm - Admin Thong Dong</title>
  <link rel="stylesheet" href="/thongdong/assets/css/admin.css">
</head>
<body>

<?php include __DIR__ . '/includes/admin-header.php'; ?>

<main class="container admin-page">

  <section class="glass pad-16" style="margin-bottom:12px;">
    <h1 style="margin:0; font-size:28px;">Quản lý Sản phẩm</h1>

    <?php if ($flash): ?>
      <div class="glass pad-12" style="margin-top:12px; background:#e6f4ea; border-color: rgba(20,110,70,0.25); color:#1e7e34;">
        ✅ <?php echo htmlspecialchars($flash); ?>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="glass pad-12" style="margin-top:12px; background:#fce8e6; border-color: rgba(158,42,43,0.25); color:#c0392b;">
        <b>Lỗi:</b>
        <ul style="margin:5px 0 0; padding-left:18px;">
          <?php foreach($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </section>

  <section class="glass pad-16" style="margin-bottom:12px;">
    <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm theo ID hoặc Tên..." style="padding:10px 12px; border-radius:12px; border:1px solid var(--line); min-width:240px;">
      
      <select name="cat" style="padding:10px 12px; border-radius:12px; border:1px solid var(--line);">
        <option value="0">Tất cả danh mục</option>
        <?php foreach($cats as $c): ?>
          <option value="<?php echo $c['category_id']; ?>" <?php if($cat == $c['category_id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($c['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="st" style="padding:10px 12px; border-radius:12px; border:1px solid var(--line);">
        <option value="">Tất cả trạng thái</option>
        <option value="active" <?php if($st==='active') echo 'selected'; ?>>Đang bán</option>
        <option value="inactive" <?php if($st==='inactive') echo 'selected'; ?>>Ngừng bán</option>
      </select>

      <button class="btn primary" type="submit">Lọc</button>
      <a class="btn outline" href="products.php">Reset</a>
    </form>
  </section>

  <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:12px; align-items:start;">

    <section class="glass pad-16">
      <h2 style="margin:0 0 10px; font-size:18px;">Danh sách sản phẩm</h2>

      <div style="overflow:auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Ảnh</th>
              <th>Tên / Danh mục</th>
              <th>Giá</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($result->num_rows === 0): ?>
            <tr><td colspan="5" style="color:#4b4b4b; text-align:center; padding: 20px;">Không có sản phẩm nào.</td></tr>
          <?php endif; ?>

          <?php while($p = $result->fetch_assoc()): 
              // Fix link ảnh
              $img_url = !empty($p['image_url']) ? $p['image_url'] : 'assets/img/products/placeholder.jpg';
              // Nếu link ảnh không bắt đầu bằng http và không có ../ thì thêm ../ để hiển thị đúng trong admin
              if(strpos($img_url, 'http') === false && strpos($img_url, '../') === false) {
                  $display_img = '../' . $img_url;
              } else {
                  $display_img = $img_url;
              }
          ?>
            <tr>
              <td>
                <img src="<?php echo htmlspecialchars($display_img); ?>" alt="" style="width:50px; height:50px; border-radius:8px; object-fit:cover; border:1px solid #eee;">
              </td>
              <td>
                <div style="font-weight:600;"><?php echo htmlspecialchars($p['name']); ?></div>
                <div style="font-size:12px; color:#666;"><?php echo htmlspecialchars($p['category_name'] ?? 'Chưa phân loại'); ?></div>
                <div style="font-size:11px; color:#999;">ID: #<?php echo $p['product_id']; ?> | Kho: <?php echo $p['stock_quantity']; ?></div>
              </td>
              <td><?php echo fmt_vnd((int)$p['price']); ?></td>
              <td>
                <?php if($p['status'] === 'active'): ?>
                    <span style="font-size:12px; padding:2px 8px; border-radius:10px; background:#e6f4ea; color:#1e7e34;">Đang bán</span>
                <?php else: ?>
                    <span style="font-size:12px; padding:2px 8px; border-radius:10px; background:#fce8e6; color:#d93025;">Ngừng bán</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex; gap:5px;">
                  <a class="btn outline" style="padding:5px 10px; font-size:13px;" href="products.php?edit=<?php echo $p['product_id']; ?>">Sửa</a>
                  
                  <form method="post" onsubmit="return confirm('Xoá sản phẩm này?');" style="display:inline;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo $p['product_id']; ?>">
                      <button class="btn outline" style="padding:5px 10px; font-size:13px; color:red; border-color:red;">Xoá</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="glass pad-16">
      <h2 style="margin:0 0 10px; font-size:18px;"><?php echo $editing ? 'Sửa sản phẩm #'.$editing['product_id'] : 'Thêm sản phẩm mới'; ?></h2>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?php echo $editing['product_id']; ?>">
        <?php endif; ?>

        <div style="margin-bottom:10px;">
          <label style="font-size:13px; color:#666;">Tên sản phẩm</label>
          <input name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line);">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
          <div>
            <label style="font-size:13px; color:#666;">Giá (VND)</label>
            <input name="price" type="number" value="<?php echo (int)($editing['price'] ?? 0); ?>" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line);">
          </div>
          <div>
            <label style="font-size:13px; color:#666;">Danh mục</label>
            <select name="category_id" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line);">
              <?php foreach($cats as $c): ?>
                <option value="<?php echo $c['category_id']; ?>" <?php if(($editing['category_id']??0) == $c['category_id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
          <div>
            <label style="font-size:13px; color:#666;">Tồn kho</label>
            <input name="stock" type="number" value="<?php echo (int)($editing['stock_quantity'] ?? 10); ?>" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line);">
          </div>
          <div>
            <label style="font-size:13px; color:#666;">Trạng thái</label>
            <select name="status" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line);">
              <option value="active" <?php if(($editing['status']??'') === 'active') echo 'selected'; ?>>Đang bán</option>
              <option value="inactive" <?php if(($editing['status']??'') === 'inactive') echo 'selected'; ?>>Ngừng bán</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom:10px;">
          <label style="font-size:13px; color:#666;">Ảnh (Link hoặc Tải lên)</label>
          <input name="image" value="<?php echo htmlspecialchars($editing['image_url'] ?? ''); ?>" placeholder="Link ảnh..." style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line); margin-bottom:5px;">
          <input type="file" name="image_file" accept="image/*">
        </div>

        <div style="margin-bottom:10px;">
          <label style="font-size:13px; color:#666;">Mô tả</label>
          <textarea id="descEditor" name="desc" rows="5" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--line);"><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>
        </div>

        <button class="btn primary" type="submit" style="width:100%; padding:12px;"><?php echo $editing ? 'Cập nhật' : 'Thêm mới'; ?></button>
        <?php if ($editing): ?>
          <a class="btn outline" href="products.php" style="width:100%; margin-top:8px; display:block; text-align:center;">Huỷ chỉnh sửa</a>
        <?php endif; ?>
      </form>
    </section>

  </div>

</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#descEditor',
    height: 300,
    menubar: false,
    plugins: 'lists link',
    toolbar: 'undo redo | bold italic | bullist numlist | link',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
  });
</script>

</body>
</html>