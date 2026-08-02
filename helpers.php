<?php
function adjustBrightness($hex, $percent) {
  $hex = ltrim($hex, '#');
  $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
  $r = max(0, min(255, $r + $r * $percent));
  $g = max(0, min(255, $g + $g * $percent));
  $b = max(0, min(255, $b + $b * $percent));
  return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function flowerSVG($shape, $color, $size = 80) {
  $dark = adjustBrightness($color, -0.30);
  $light = adjustBrightness($color, 0.20);
  switch ($shape) {
    case 'rose':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><ellipse cx='0' cy='-22' rx='18' ry='22' fill='$color' opacity='0.75'/><ellipse cx='0' cy='-22' rx='18' ry='22' fill='$color' opacity='0.75' transform='rotate(72)'/><ellipse cx='0' cy='-22' rx='18' ry='22' fill='$color' opacity='0.75' transform='rotate(144)'/><ellipse cx='0' cy='-22' rx='18' ry='22' fill='$color' opacity='0.75' transform='rotate(216)'/><ellipse cx='0' cy='-22' rx='18' ry='22' fill='$color' opacity='0.75' transform='rotate(288)'/><ellipse cx='0' cy='-14' rx='13' ry='16' fill='$light' opacity='0.95' transform='rotate(36)'/><ellipse cx='0' cy='-14' rx='13' ry='16' fill='$light' opacity='0.95' transform='rotate(108)'/><ellipse cx='0' cy='-14' rx='13' ry='16' fill='$light' opacity='0.95' transform='rotate(180)'/><ellipse cx='0' cy='-14' rx='13' ry='16' fill='$light' opacity='0.95' transform='rotate(252)'/><ellipse cx='0' cy='-14' rx='13' ry='16' fill='$light' opacity='0.95' transform='rotate(324)'/><circle r='9' fill='$dark' opacity='0.7'/><circle r='5' fill='$dark'/></g></svg>";
    case 'peony':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><circle r='38' fill='$color' opacity='0.5'/><circle r='35' fill='$color' opacity='0.7'/><ellipse cx='0' cy='-20' rx='14' ry='18' fill='$light' opacity='0.85'/><ellipse cx='0' cy='-20' rx='14' ry='18' fill='$light' opacity='0.85' transform='rotate(60)'/><ellipse cx='0' cy='-20' rx='14' ry='18' fill='$light' opacity='0.85' transform='rotate(120)'/><ellipse cx='0' cy='-20' rx='14' ry='18' fill='$light' opacity='0.85' transform='rotate(180)'/><ellipse cx='0' cy='-20' rx='14' ry='18' fill='$light' opacity='0.85' transform='rotate(240)'/><ellipse cx='0' cy='-20' rx='14' ry='18' fill='$light' opacity='0.85' transform='rotate(300)'/><circle r='14' fill='$color'/><circle r='8' fill='$dark' opacity='0.6'/></g></svg>";
    case 'tulip':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><path d='M -20 -10 Q -25 -35 0 -40 Q 25 -35 20 -10 Q 15 5 0 5 Q -15 5 -20 -10 Z' fill='$color'/><path d='M -10 -10 Q -15 -30 0 -35 Q 15 -30 10 -10 Q 5 0 0 0 Q -5 0 -10 -10 Z' fill='$light' opacity='0.8'/></g></svg>";
    case 'ranunculus':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><circle r='32' fill='$color' opacity='0.5'/><circle r='28' fill='$color' opacity='0.7'/><circle r='22' fill='$light' opacity='0.85'/><circle r='16' fill='$color'/><circle r='10' fill='$dark' opacity='0.7'/><circle r='5' fill='$dark'/></g></svg>";
    case 'eucalyptus':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><line x1='0' y1='40' x2='0' y2='-35' stroke='$dark' stroke-width='2'/><ellipse cx='-12' cy='22' rx='10' ry='16' fill='$color' transform='rotate(-30 -12 22)'/><ellipse cx='12' cy='12' rx='10' ry='16' fill='$color' transform='rotate(30 12 12)'/><ellipse cx='-12' cy='-5' rx='10' ry='16' fill='$color' transform='rotate(-30 -12 -5)'/><ellipse cx='12' cy='-22' rx='10' ry='16' fill='$color' transform='rotate(30 12 -22)'/></g></svg>";
    case 'lavender':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><line x1='0' y1='45' x2='0' y2='-15' stroke='#6B7A5A' stroke-width='2'/><ellipse cx='0' cy='-20' rx='5' ry='7' fill='$color'/><ellipse cx='-3' cy='-28' rx='5' ry='7' fill='$color'/><ellipse cx='3' cy='-32' rx='5' ry='7' fill='$color'/><ellipse cx='-2' cy='-38' rx='4' ry='6' fill='$color'/><ellipse cx='2' cy='-42' rx='4' ry='6' fill='$light'/></g></svg>";
    case 'anemone':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><ellipse cx='0' cy='-25' rx='12' ry='22' fill='$color' opacity='0.9'/><ellipse cx='0' cy='-25' rx='12' ry='22' fill='$color' opacity='0.9' transform='rotate(60)'/><ellipse cx='0' cy='-25' rx='12' ry='22' fill='$color' opacity='0.9' transform='rotate(120)'/><ellipse cx='0' cy='-25' rx='12' ry='22' fill='$color' opacity='0.9' transform='rotate(180)'/><ellipse cx='0' cy='-25' rx='12' ry='22' fill='$color' opacity='0.9' transform='rotate(240)'/><ellipse cx='0' cy='-25' rx='12' ry='22' fill='$color' opacity='0.9' transform='rotate(300)'/><circle r='14' fill='#2A2520'/></g></svg>";
    case 'dusty':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><path d='M 0 30 L -18 -20 L 0 -38 L 18 -20 Z' fill='$color' opacity='0.85'/><path d='M 0 25 L -14 -15 L 0 -30 L 14 -15 Z' fill='$light' opacity='0.7'/></g></svg>";
    case 'dahlia':
      return "<svg viewBox='0 0 100 100' width='$size' height='$size' class='flower-svg' xmlns='http://www.w3.org/2000/svg'><g transform='translate(50,50)'><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(0)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(45)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(90)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(135)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(180)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(225)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(270)'/><path d='M 0 -38 L -6 -22 L 0 -18 L 6 -22 Z' fill='$color' opacity='0.85' transform='rotate(315)'/><circle r='8' fill='$dark'/></g></svg>";
    default:
      return "<svg viewBox='0 0 100 100' width='$size' height='$size'><circle cx='50' cy='50' r='30' fill='$color'/></svg>";
  }
}

function parseColorOptions($flower) {
  $opts = json_decode($flower['color_options'] ?? '[]', true);
  if (!is_array($opts) || empty($opts)) {
    return [['name' => 'Default', 'color' => $flower['color'] ?? '#D89B9B']];
  }
  return array_values(array_filter($opts, function($opt) {
    return !empty($opt['name']) && !empty($opt['color']);
  }));
}

function parseItemColorOptions($item) {
  $opts = json_decode($item['color_options'] ?? '[]', true);
  if (!is_array($opts) || empty($opts)) {
    return [[
      'name' => 'Default',
      'color' => $item['color'] ?? '#888888',
      'image' => $item['image'] ?? ''
    ]];
  }
  $result = [];
  foreach ($opts as $opt) {
    if (empty($opt['name']) || empty($opt['color'])) continue;
    $result[] = [
      'name' => $opt['name'],
      'color' => $opt['color'],
      'image' => $opt['image'] ?? ''
    ];
  }
  if (!$result) {
    return [[
      'name' => 'Default',
      'color' => $item['color'] ?? '#888888',
      'image' => $item['image'] ?? ''
    ]];
  }
  return $result;
}

function getFlowers($db, $builderOnly = false) {
  $sql = "SELECT * FROM flowers";
  if ($builderOnly) $sql .= " WHERE in_builder = 1 AND in_stock = 1";
  return $db->query($sql)->fetchAll();
}
function getWrappers($db) { return $db->query("SELECT * FROM wrappers ORDER BY id")->fetchAll(); }
function getRibbons($db) { return $db->query("SELECT * FROM ribbons ORDER BY id")->fetchAll(); }
function getBaseSizes($db, $activeOnly = false) {
  $sql = "SELECT * FROM base_sizes";
  if ($activeOnly) $sql .= " WHERE active = 1";
  return $db->query($sql . " ORDER BY id")->fetchAll();
}
function getBouquets($db) { return $db->query("SELECT * FROM bouquets ORDER BY id")->fetchAll(); }
function getOrders($db) { return $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(); }
function getReviews($db) { return $db->query("SELECT * FROM reviews WHERE approved = 1 ORDER BY id DESC")->fetchAll(); }

function getSetting($db, $key, $default = '') {
  $stmt = $db->prepare("SELECT value FROM app_meta WHERE key=?");
  $stmt->execute([$key]);
  $value = $stmt->fetchColumn();
  return $value === false ? $default : $value;
}

function setSetting($db, $key, $value) {
  $stmt = $db->prepare("INSERT OR REPLACE INTO app_meta (key, value) VALUES (?, ?)");
  $stmt->execute([$key, $value]);
}

// Builder is on unless an admin has explicitly switched it off.
function isCustomizeEnabled($db) {
  return getSetting($db, 'customize_enabled', '1') !== '0';
}

function rateLimitAllow($db, $bucket, $maxAttempts, $windowSeconds) {
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $key = $bucket . ':' . $ip;
  $now = time();
  $stmt = $db->prepare("SELECT count, window_start FROM rate_limits WHERE rl_key=?");
  $stmt->execute([$key]);
  $row = $stmt->fetch();

  if (!$row || ($now - $row['window_start']) > $windowSeconds) {
    $db->prepare("INSERT INTO rate_limits (rl_key, count, window_start) VALUES (?, 1, ?)
      ON CONFLICT(rl_key) DO UPDATE SET count=1, window_start=excluded.window_start")
      ->execute([$key, $now]);
    return true;
  }

  if ($row['count'] >= $maxAttempts) return false;

  $db->prepare("UPDATE rate_limits SET count=count+1 WHERE rl_key=?")->execute([$key]);
  return true;
}
?>
