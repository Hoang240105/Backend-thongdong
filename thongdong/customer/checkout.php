<?php
session_start();
require_once '../includes/db.php'; // Kết nối Database

$pageTitle = "Thanh toán - Thong Dong";

// 1. Kiểm tra giỏ hàng
if (empty($_SESSION['cart'])) {
    header('Location: shop.php');
    exit;
}

// 2. Yêu cầu đăng nhập để đặt hàng
if (empty($_SESSION['user_id']) && empty($_SESSION['customer'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? $_SESSION['customer']['id'];

// 3. Lấy Cài đặt từ DB
$settings = [];
$res = $conn->query("SELECT * FROM Settings");
while ($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

$ship_fee_config = (int)($settings['ship_fee'] ?? 25000);
$free_ship_limit = (int)($settings['free_ship'] ?? 500000);
$bank_enable     = ($settings['bank_enable'] ?? '1') === '1';

// 4. Tính toán tổng tiền
$cartItems = $_SESSION['cart'];
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$shipping_fee = ($subtotal >= $free_ship_limit) ? 0 : $ship_fee_config;
$total_price = $subtotal + $shipping_fee;

if (!function_exists('money_vnd')) {
    function money_vnd($n){ return number_format((int)$n, 0, ',', '.') . 'đ'; }
}

$errors = [];

// 5. XỬ LÝ ĐẶT HÀNG (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $note    = trim($_POST['note'] ?? '');
    $payment = $_POST['payment'] ?? 'cod';

    if ($name === '' || $phone === '' || $address === '') {
        $errors[] = 'Vui lòng điền đầy đủ thông tin nhận hàng.';
    }

    if (empty($errors)) {
        $payment_id = ($payment === 'bank') ? 2 : 1; 

        // --- PHẦN A: LƯU VÀO BẢNG ORDERS ---
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, payment_method_id, address, phone, note, created_at) VALUES (?, ?, 'pending', ?, ?, ?, ?, NOW())");
        $stmt->bind_param("idiiss", $user_id, $total_price, $payment_id, $address, $phone, $note);
        
        if ($stmt->execute()) {
            $order_id = $stmt->insert_id; 

            // --- PHẦN B: LƯU CHI TIẾT & TRỪ TỒN KHO ---
            $stmt_item = $conn->prepare("INSERT INTO orderitems (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            // Lệnh trừ kho: Kiểm tra số lượng hiện tại phải lớn hơn hoặc bằng số lượng mua
            $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");

            foreach ($cartItems as $item) {
                $p_id = $item['id'];
                $p_qty = (int)$item['qty'];
                $p_price = $item['price'];

                // B1: Lưu chi tiết đơn hàng
                $stmt_item->bind_param("iiid", $order_id, $p_id, $p_qty, $p_price);
                $stmt_item->execute();

                // B2: Cập nhật tồn kho thực tế
                $stmt_update_stock->bind_param("iii", $p_qty, $p_id, $p_qty);
                $stmt_update_stock->execute();
            }

            // --- PHẦN C: HOÀN TẤT ---
            unset($_SESSION['cart']); 
            header("Location: order-confirmation.php?id=$order_id");
            exit;

        } else {
            $errors[] = "Lỗi lưu đơn hàng: " . $conn->error;
        }
    }
}

include '../includes/customer-layout-top.php';
?>

<main class="container" style="padding:34px 0 70px;">
    <section class="card" style="padding: 20px;">
        <h1 style="margin:0 0 14px;">Thanh toán</h1>

        <?php if (!empty($errors)): ?>
            <div class="auth-alert" style="margin:0 0 14px; background:#fce8e6; color:#c0392b; border:1px solid #f5c6cb; padding:10px; border-radius: 8px;">
                <?php foreach ($errors as $e): ?>
                    <div>• <?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="checkout-grid" style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px;">
            <div class="checkout-left">
                <h3>Thông tin nhận hàng</h3>
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Họ và tên *</label>
                    <input class="input" name="name" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;" value="<?php echo htmlspecialchars($_SESSION['customer']['name'] ?? ''); ?>">
                </div>

                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Số điện thoại *</label>
                    <input class="input" name="phone" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;" value="<?php echo htmlspecialchars($_SESSION['customer']['phone'] ?? ''); ?>">
                </div>

                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Địa chỉ giao hàng *</label>
                    <textarea class="input" name="address" rows="3" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;" placeholder="Số nhà, đường, phường/xã..."></textarea>
                </div>

                <div class="form-group" style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Hình thức thanh toán</label>
                    <div class="pay-box" style="border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                        <label class="pay-item" style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px; cursor: pointer;">
                            <input type="radio" name="payment" value="cod" checked>
                            <div>
                                <b>Thanh toán khi nhận hàng (COD)</b>
                                <div class="muted" style="font-size: 12px; color: #777;">Thanh toán cho shipper khi nhận được hàng.</div>
                            </div>
                        </label>

                        <?php if ($bank_enable): ?>
                        <label class="pay-item" style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer;">
                            <input type="radio" name="payment" value="bank">
                            <div>
                                <b>Chuyển khoản ngân hàng</b>
                                <div class="muted" style="font-size: 12px; color: #777;">Chuyển khoản qua QR/STK trước khi giao hàng.</div>
                            </div>
                        </label>
                        <?php endif; ?>
                    </div>

                    <div id="bankInfo" style="display:none; margin-top:15px; padding:15px; background:#f9f9f9; border-radius:8px; border: 1px dashed #ccc;">
                        <b>Thông tin chuyển khoản:</b>
                        <div class="muted" style="margin-top:6px; line-height:1.6; font-size: 14px;">
                            Ngân hàng: <b><?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?></b><br>
                            Số tài khoản: <b><?php echo htmlspecialchars($settings['bank_number'] ?? ''); ?></b><br>
                            Chủ tài khoản: <b><?php echo htmlspecialchars($settings['bank_owner'] ?? ''); ?></b>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:block; margin-bottom:5px;">Ghi chú đơn hàng (tuỳ chọn)</label>
                    <textarea class="input" name="note" rows="2" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;" placeholder="Ví dụ: Giao giờ hành chính..."></textarea>
                </div>
            </div>

            <div class="checkout-right">
                <div class="card" style="padding:20px; background:#fafafa; border: 1px solid #eee; border-radius: 8px;">
                    <h3 style="margin-top:0;">Đơn hàng của bạn</h3>
                    <div class="order-summary">
                        <?php foreach ($cartItems as $item): ?>
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;">
                                <div><b><?php echo htmlspecialchars($item['name']); ?></b> <small>x<?php echo $item['qty']; ?></small></div>
                                <div><?php echo money_vnd($item['price'] * $item['qty']); ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr style="border:0; border-top:1px solid #ddd; margin:15px 0;">

                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                            <span class="muted">Tạm tính:</span>
                            <span><?php echo money_vnd($subtotal); ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                            <span class="muted">Phí vận chuyển:</span>
                            <span><?php echo ($shipping_fee == 0) ? 'Miễn phí' : money_vnd($shipping_fee); ?></span>
                        </div>

                        <div style="display:flex; justify-content:space-between; font-size:18px; font-weight:bold; color:#c0392b;">
                            <span>Tổng cộng:</span>
                            <span><?php echo money_vnd($total_price); ?></span>
                        </div>
                    </div>

                    <button class="btn primary" type="submit" style="width:100%; margin-top:20px; padding:15px; background: #333; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                        XÁC NHẬN ĐẶT HÀNG
                    </button>
                </div>
            </div>
        </form>
    </section>
</main>

<script>
    const bankBox = document.getElementById('bankInfo');
    const radios = document.querySelectorAll('input[name="payment"]');
    function toggleBank(){
        const checked = document.querySelector('input[name="payment"]:checked');
        if (!checked) return;
        bankBox.style.display = checked.value === 'bank' ? 'block' : 'none';
    }
    radios.forEach(r => r.addEventListener('change', toggleBank));
    toggleBank();
</script>

<?php include '../includes/customer-layout-bottom.php'; ?>