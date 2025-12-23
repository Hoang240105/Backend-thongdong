<?php
function products_data_path(): string {
  return __DIR__ . '/../../data/products.json';
}

function read_products(): array {
  $path = products_data_path();
  if (!file_exists($path)) return [];
  $json = file_get_contents($path);
  $arr = json_decode($json, true);
  return is_array($arr) ? $arr : [];
}

function write_products(array $products): void {
  $path = products_data_path();
  if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
  file_put_contents($path, json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function find_product_index(array $products, string $id): int {
  foreach ($products as $i => $p) {
    if (($p['id'] ?? '') === $id) return $i;
  }
  return -1;
}

function normalize_money($v): int {
  // nhận "189000" hoặc "189.000" hoặc "189,000"
  $s = (string)$v;
  $s = preg_replace('/[^\d]/', '', $s);
  return (int)$s;
}

function safe_text($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
