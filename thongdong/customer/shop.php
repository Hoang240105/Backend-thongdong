<?php
session_start();
require_once '../includes/data.php';

$pageTitle = "Cửa hàng - Thong Dong";
include '../includes/customer-layout-top.php';

function safe($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<main class="container shop-page">
  <section class="page-head card">
    <div>
      <h1 class="page-title">Cửa hàng</h1>
      <p class="muted">Chọn một mùi hương hợp mood hôm nay.</p>
    </div>

    <div class="shop-controls">
      <div class="control">
        <label class="control-label">Tìm kiếm</label>
        <input id="shopSearch" class="input" type="text" placeholder="Nhập tên sản phẩm..." />
      </div>

      <div class="control">
        <label class="control-label">Bộ sưu tập</label>
        <select id="shopFilter" class="input">
          <option value="all">Tất cả</option>
          <option value="NenThom">Nến Thơm</option>
          <option value="TinhDau">Tinh dầu</option>
          <option value="Gift">Quà tặng</option>
          <option value="LeHoi">Bộ sưu tập mùa Lễ Hội</option>
        </select>
      </div>

      <div class="control">
        <label class="control-label">Giá</label>
        <select id="priceFilter" class="input">
          <option value="all">Tất cả</option>
          <option value="under200">Dưới 200.000đ</option>
          <option value="200to300">200.000đ – 300.000đ</option>
          <option value="over300">Trên 300.000đ</option>
        </select>
      </div>
    </div>
  </section>

  <section class="shop-grid" id="shopGrid">
    <?php foreach ($PRODUCTS as $p): 
      $id    = (int)($p['id'] ?? 0);
      $name  = $p['name'] ?? 'Sản phẩm';
      $price = (int)($p['price'] ?? 0);
      $tag   = $p['tag'] ?? ($p['category'] ?? 'Nến Thơm');
      $cat   = $p['category'] ?? 'NenThom';
      $img   = $p['image'] ?? '';
      if (!$img) $img = '/thongdong/assets/img/products/placeholder.jpg'; // nếu chưa có ảnh
    ?>
      <article
        class="card product-card"
        data-name="<?php echo safe(mb_strtolower($name)); ?>"
        data-cat="<?php echo safe($cat); ?>"
        data-price="<?php echo (int)$price; ?>"
      >
        <div class="product-img">
          <img src="<?php echo safe($img); ?>" alt="<?php echo safe($name); ?>" loading="lazy">
        </div>

        <div class="product-body">
          <h3 class="product-title"><?php echo safe($name); ?></h3>

          <div class="product-meta">
            <span class="price"><?php echo formatVND($price); ?></span>
            <span class="tag"><?php echo safe($tag); ?></span>
          </div>

          <div class="product-actions">
            <a class="btn small" href="/thongdong/customer/product-detail.php?id=<?php echo $id; ?>">Xem chi tiết</a>
            <a class="btn small outline" href="/thongdong/customer/add-to-cart.php?id=<?php echo $id; ?>">Thêm giỏ</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="shop-note card">
    <h3>Gợi ý chọn nến</h3>
    <ul>
      <li><b>Nhẹ nhàng</b>: sen, bưởi, trà.</li>
      <li><b>Ấm áp</b>: quế, gừng, gỗ.</li>
      <li><b>Quà tặng</b>: set 2–3 hũ, kèm thiệp.</li>
    </ul>
  </section>
</main>

<script>
(function(){
  const grid = document.getElementById('shopGrid');
  if (!grid) return;

  const searchEl = document.getElementById('shopSearch');
  const filterEl = document.getElementById('shopFilter');
  const priceEl  = document.getElementById('priceFilter');

  const cards = Array.from(grid.querySelectorAll('.product-card'));

  function matchPriceRange(price, range) {
    if (range === 'all') return true;
    if (range === 'under200') return price < 200000;
    if (range === '200to300') return price >= 200000 && price <= 300000;
    if (range === 'over300') return price > 300000;
    return true;
  }

  function render(){
    const q = (searchEl?.value || '').trim().toLowerCase();
    const cat = filterEl?.value || 'all';
    const range = priceEl?.value || 'all';

    let visible = 0;

    cards.forEach(card => {
      const name = card.dataset.name || '';
      const ccat = card.dataset.cat || '';
      const price = parseInt(card.dataset.price || '0', 10);

      const okQ = !q || name.includes(q);
      const okCat = (cat === 'all') || (ccat === cat);
      const okPrice = matchPriceRange(price, range);

      const show = okQ && okCat && okPrice;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    // nếu không có sp nào phù hợp thì show 1 box
    let empty = grid.querySelector('.shop-empty');
    if (!empty) {
      empty = document.createElement('div');
      empty.className = 'card shop-empty';
      empty.style.gridColumn = '1 / -1';
      empty.style.padding = '18px';
      empty.innerHTML = '<b>Không tìm thấy sản phẩm phù hợp.</b><div class="muted" style="margin-top:6px;">Thử đổi bộ lọc hoặc từ khoá tìm kiếm nhé.</div>';
      grid.appendChild(empty);
    }
    empty.style.display = visible === 0 ? '' : 'none';
  }

  searchEl?.addEventListener('input', render);
  filterEl?.addEventListener('change', render);
  priceEl?.addEventListener('change', render);

  render();
})();
</script>

<?php include '../includes/customer-layout-bottom.php'; ?>
