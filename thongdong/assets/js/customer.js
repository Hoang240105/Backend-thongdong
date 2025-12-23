(function () {
  const grid = document.getElementById('shopGrid');
  if (!grid) return;

  const searchEl = document.getElementById('shopSearch');
  const filterEl = document.getElementById('shopFilter');
  const priceEl = document.getElementById('priceFilter');

  const products = [
  { id: 1,  name: 'Nến Thơm Đà Lạt (Pine & Wood)', price: 150000, cat: 'nen_thom', tag: 'Nến Thơm', image: '/thongdong/assets/img/products/nen-da-lat.jpg' },
  { id: 2,  name: 'Nến Thơm Good Morning',        price: 150000, cat: 'nen_thom', tag: 'Nến Thơm' },
  { id: 3,  name: 'Nến Thơm Lavender Garden',     price: 180000, cat: 'nen_thom', tag: 'Nến Thơm' },

  { id: 4,  name: 'Nến Thơm "Tết Sum Vầy"',       price: 250000, cat: 'le_hoi', tag: 'Bộ sưu tập mùa Lễ Hội' },
  { id: 5,  name: 'Nến Tạo Hình Bánh Chưng',      price: 120000, cat: 'le_hoi', tag: 'Bộ sưu tập mùa Lễ Hội' },
  { id: 6,  name: 'Nến Tạo Hình Quả Quýt Tài Lộc',price:  90000, cat: 'le_hoi', tag: 'Bộ sưu tập mùa Lễ Hội' },
  { id: 7,  name: 'Set Quà Tết "Phú Quý"',        price: 550000, cat: 'le_hoi', tag: 'Bộ sưu tập mùa Lễ Hội' },

  { id: 8,  name: 'Tinh Dầu Sả Chanh (Lemongrass)',price:  85000, cat: 'td_phong', tag: 'Tinh Dầu Thơm Phòng' },
  { id: 9,  name: 'Tinh Dầu Ngọc Lan Tây (Ylang Ylang)', price: 95000, cat: 'td_phong', tag: 'Tinh Dầu Thơm Phòng' },

  { id: 10, name: 'Nến Trụ Trơn Basic (Không mùi)', price: 50000, cat: 'nen_trang_tri', tag: 'Nến Trang Trí' },
  { id: 11, name: 'Nến Bubble Cube (Nến khối lập phương)', price: 75000, cat: 'nen_trang_tri', tag: 'Nến Trang Trí' },

  { id: 12, name: 'Bộ Que Khuếch Tán Hương Biển',  price: 220000, cat: 'que_khuech_tan', tag: 'Que Khuếch Tán Tinh Dầu' },
  { id: 13, name: 'Máy Xông Tinh Dầu Vân Gỗ',      price: 350000, cat: 'may_khuech_tan', tag: 'Máy Khuếch Tán Tinh Dầu' },
  { id: 14, name: 'Tinh Dầu Treo Xe Cà Phê',       price:  65000, cat: 'td_treo', tag: 'Tinh Dầu Treo' },

  { id: 15, name: 'Kéo Cắt Bấc Nến (Wick Trimmer)',price: 110000, cat: 'phu_kien', tag: 'Phụ kiện nến' },
  { id: 16, name: 'Đèn Đốt Nến Thơm (Candle Warmer)',price: 450000, cat: 'phu_kien', tag: 'Phụ kiện nến' },

  { id: 17, name: 'Set Quà "Self-Love" (Cho Nàng)',price: 390000, cat: 'set_qua', tag: 'Set quà tặng' },
  { id: 18, name: 'Nến Thơm Mùa Lễ Hội "Gingerbread"', price: 200000, cat: 'le_hoi', tag: 'Bộ sưu tập mùa Lễ Hội' },
];


  const fmt = (n) => n.toLocaleString('vi-VN') + 'đ';

  function matchPriceRange(price, range) {
    if (range === 'all') return true;
    if (range === 'under200') return price < 200000;
    if (range === '200to300') return price >= 200000 && price <= 300000;
    if (range === 'over300') return price > 300000;
    return true;
  }

  function getView() {
    const q = (searchEl?.value || '').trim().toLowerCase();
    const cat = filterEl?.value || 'all';
    const range = priceEl?.value || 'all';

    return products.filter(p => {
      const okCat = cat === 'all' || p.cat === cat;
      const okQ = !q || p.name.toLowerCase().includes(q);
      const okPrice = matchPriceRange(p.price, range);
      return okCat && okQ && okPrice;
    });
  }

  function render() {
    const view = getView();

    grid.innerHTML = view.map(p => `
      <article class="card product-card">
        <div class="product-img">
          <span>Ảnh sản phẩm</span>
        </div>
        <div class="product-body">
          <h3 class="product-title">${p.name}</h3>
          <div class="product-meta">
            <span class="price">${fmt(p.price)}</span>
            <span class="tag">${p.tag}</span>
          </div>
          <div class="product-actions">
            <a class="btn small" href="/thongdong/customer/product-detail.php?id=${p.id}">Xem chi tiết</a>
          <a class="btn small outline" href="/thongdong/customer/cart.php?add=${p.id}">Thêm giỏ</a>

          </div>
        </div>
      </article>
    `).join('');

    if (view.length === 0) {
      grid.innerHTML = `
        <div class="card" style="grid-column:1/-1; padding:18px;">
          <b>Không tìm thấy sản phẩm phù hợp.</b>
          <div class="muted" style="margin-top:6px;">Thử đổi bộ lọc hoặc từ khoá tìm kiếm nhé.</div>
        </div>
      `;
    }
  }

  searchEl?.addEventListener('input', render);
  filterEl?.addEventListener('change', render);
  priceEl?.addEventListener('change', render);

  render();
})();
