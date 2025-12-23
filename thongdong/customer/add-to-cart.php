<?php
session_start();
require_once '../includes/db.php'; // 1. Kết nối Database

$pid = (int)($_GET['id'] ?? 0); // Lấy ID sản phẩm (ép kiểu số)

if ($pid <= 0) {
    header('Location: shop.php');
    exit;
}

// 2. Tìm sản phẩm trong Database
$stmt = $conn->prepare("SELECT * FROM Products WHERE product_id = ? AND status = 'active'");
$stmt->bind_param("i", $pid);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    // Không tìm thấy hoặc sản phẩm ngừng bán
    header('Location: shop.php');
    exit;
}

// 3. Thêm vào giỏ hàng (Session)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = &$_SESSION['cart'];

if (isset($cart[$pid])) {
    // Nếu đã có -> Tăng số lượng
    $cart[$pid]['qty'] += 1;
} else {
    // Nếu chưa có -> Thêm mới
    $cart[$pid] = [
        'id'    => $product['product_id'],
        'name'  => $product['name'],
        'price' => (int)$product['price'],
        'qty'   => 1,
        'image' => $product['image_url'] ?? '', // Map cột image_url
    ];
}

// 4. Quay lại trang trước
$back = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header('Location: ' . $back);
exit;
?>