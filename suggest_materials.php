<?php
$type = $_GET['type'] ?? '';

$materials = [
  'Seminar' => 'Projector, Microphone, Notepads, Pens',
  'Organization Fair' => 'Booth Materials, Posters, Tarpaulin',
  'Sports' => 'Scoreboard, Jerseys, Water, First Aid Kit',
  'Religious' => 'Sound System, Chairs, Program Leaflets',
  'Workshop' => 'Laptop, Extension Cord, Toolkit, Whiteboard',
  'Others' => 'Tables, Chairs, Banners'
];

echo json_encode(['materials' => $materials[$type] ?? 'Tables, Chairs']);
