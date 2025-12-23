<?php
function orders_data_path(): string {
  return __DIR__ . '/../../data/orders.json';
}

function read_orders(): array {
  $path = orders_data_path();
  if (!file_exists($path)) return [];
  $json = file_get_contents($path);
  $arr = json_decode($json, true);
  return is_array($arr) ? $arr : [];
}

function write_orders(array $orders): void {
  $path = orders_data_path();
  if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
  file_put_contents($path, json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function safe_text($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function fmt_vnd(int $n): string {
  return number_format($n, 0, ',', '.') . 'đ';
}
