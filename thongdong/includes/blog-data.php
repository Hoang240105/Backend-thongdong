<?php
$BLOG_POSTS = [
  [
    'id' => 1,
    'title' => 'Thong Dong và một góc Tết trong căn phòng nhỏ',
    'date' => '17/12/2025',
    'excerpt' => 'Có những ngày mình chỉ muốn chậm lại, thắp một mùi hương ấm và nghe phố thở...',
    'content' => [
      'Tết không nhất thiết phải ồn ào. Có khi chỉ cần một góc phòng gọn gàng, một tách trà nóng và một mùi hương quen.',
      'Nến thơm trong những ngày cuối năm giống như một lời nhắc: “Mình đã đi qua một năm rồi đó.”',
      'Nếu bạn muốn vibe Tết nhẹ nhàng: quế - gừng - gỗ mộc là combo dễ thương nhất.',
      'img' => '/thongdong/assets/img/about/tet-1.jpg'
    ],
    'tag' => 'Tết Thương',
  ],
  [
    'id' => 2,
    'title' => 'Chọn mùi hương theo mood: nhẹ - ấm - sáng',
    'date' => '15/12/2025',
    'excerpt' => 'Mùi hương cũng có “tính cách”. Chọn đúng mùi là thấy người mình dịu lại liền...',
    'content' => [
      'Mood nhẹ: sen, trà xanh, bưởi - hợp buổi tối thư giãn.',
      'Mood ấm: quế, gừng, gỗ - hợp trời lạnh, hợp chuyện tâm sự.',
      'Mood sáng: bưởi, trà - hợp buổi sáng làm việc, dọn phòng.',
      'img' => '/thongdong/assets/img/about/tet-2.jpg'
    ],
    'tag' => 'Thuần Việt',
  ],
  [
    'id' => 3,
    'title' => 'Cách thắp nến đúng để thơm lâu và không bị khói',
    'date' => '10/12/2025',
    'excerpt' => 'Thắp nến cũng có “nghi thức” nhỏ xíu để hũ nến dùng được lâu và thơm đều...',
    'content' => [
      'Lần đầu thắp: để nến chảy đều mặt trên (tầm 1 đến 2 giờ) để tránh lõm.',
      'Cắt tim nến còn ~0.5cm trước mỗi lần thắp để giảm khói.',
      'Không thắp nơi gió mạnh, và luôn đặt trên bề mặt phẳng.',
      'img' => '/thongdong/assets/img/about/tet-3.png'
    ],
    'tag' => 'Mẹo dùng nến',
  ],
];

function findPostById($id, $BLOG_POSTS) {
  foreach ($BLOG_POSTS as $p) {
    if ((int)$p['id'] === (int)$id) return $p;
  }
  return null;
}
