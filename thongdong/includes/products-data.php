<?php
// customer/includes/products-data.php

$PRODUCTS = [
  1 => ['id'=>1, 'name'=>'Nến Quế Ấm', 'price'=>189000, 'cat'=>'tet',  'tag'=>' Đỏ Vàng'],
  2 => ['id'=>2, 'name'=>'Nến Sen Nhẹ', 'price'=>209000, 'cat'=>'viet', 'tag'=>'Thuần Việt'],
  3 => ['id'=>3, 'name'=>'Nến Trà Xanh', 'price'=>199000, 'cat'=>'viet', 'tag'=>'Thuần Việt'],
  4 => ['id'=>4, 'name'=>'Set Quà “Thong Dong”', 'price'=>459000, 'cat'=>'gift', 'tag'=>'Quà tặng'],
  5 => ['id'=>5, 'name'=>'Nến Bưởi Sáng', 'price'=>219000, 'cat'=>'viet', 'tag'=>'Thuần Việt'],
  6 => ['id'=>6, 'name'=>'Nến Gừng Nồng', 'price'=>189000, 'cat'=>'tet',  'tag'=>'Tết – Đỏ Vàng'],
  7 => ['id'=>7, 'name'=>'Set Quà “Tân Niên”', 'price'=>529000, 'cat'=>'gift', 'tag'=>'Quà tặng'],
  8 => ['id'=>8, 'name'=>'Nến Gỗ Mộc', 'price'=>239000, 'cat'=>'tet',  'tag'=>'Tết – Đỏ Vàng'],
];

function td_get_product($id){
  global $PRODUCTS;
  $id = (int)$id;
  return $PRODUCTS[$id] ?? null;
}
