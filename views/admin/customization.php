<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=customization');
    exit;
}
?>

<div class="admin-head">
  <div><h1>Builder Controls</h1><div class="sub">Manage customer bouquet builder options</div></div>
</div>

<div class="admin-section">
  <div class="admin-head" style="margin-bottom:16px">
    <div><h2>Base Sizes</h2><div class="admin-section-sub">Set the minimum stem spend required for each size tier</div></div>
    <button class="btn-admin" onclick="openBaseSizeModal()">+ Add Base Size</button>
  </div>
  <table class="admin-table">
    <thead><tr><th>Name</th><th>Description</th><th>Min Spend</th><th>Icon Size</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($baseSizes as $size): ?>
        <tr>
          <td><div class="name-cell"><span class="mini-bloom"><?= flowerSVG('rose', '#D89B9B', intval($size['icon_size'] ?: 60)) ?></span><?= htmlspecialchars($size['name']) ?></div></td>
          <td><?= htmlspecialchars($size['description']) ?></td>
          <td>&#8369;<?= number_format((float)$size['price'], 2) ?></td>
          <td><?= intval($size['icon_size']) ?></td>
          <td><?= $size['active'] ? 'Yes' : 'No' ?></td>
          <td>
            <button class="btn-admin-ghost" onclick="openBaseSizeModal(<?= $size['id'] ?>)">Edit</button>
            <button class="btn-admin-danger" onclick="deleteBaseSize(<?= $size['id'] ?>)">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-section">
  <div class="admin-head" style="margin-bottom:16px">
    <div><h2>Builder Stems</h2><div class="admin-section-sub">Toggle stems and manage available color options</div></div>
  </div>
  <table class="admin-table">
    <thead><tr><th>Stem</th><th>Price</th><th>Colors</th><th>Stock</th><th>In Builder</th></tr></thead>
    <tbody>
      <?php foreach ($flowers as $f):
        $colorOptions = parseColorOptions($f);
      ?>
        <tr>
          <td><div class="name-cell"><span class="mini-bloom"><?= flowerSVG($f['shape'], $f['color'], 32) ?></span><?= htmlspecialchars($f['name']) ?></div></td>
          <td>&#8369;<?= number_format($f['price_per_stem'],2) ?></td>
          <td>
            <div class="admin-color-preview">
              <?php foreach ($colorOptions as $opt): ?>
                <span class="admin-color-dot" style="background: <?= htmlspecialchars($opt['color']) ?>" title="<?= htmlspecialchars($opt['name']) ?>"></span>
              <?php endforeach; ?>
            </div>
            <button class="btn-admin-ghost" style="margin-top:6px" onclick="openStemColorsModal(<?= $f['id'] ?>)">Edit colors</button>
          </td>
          <td><?= $f['stock_count'] ?></td>
          <td>
            <label class="toggle">
              <input type="checkbox" <?= $f['in_builder'] ? 'checked' : '' ?> onchange="toggleBuilder(<?= $f['id'] ?>)">
              <span class="slider"></span>
            </label>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  window.ADMIN_BASE_SIZES_DATA = <?= json_encode($baseSizes) ?>;
  window.ADMIN_FLOWERS_DATA = <?= json_encode(array_map(function($f) {
    $f['color_options'] = parseColorOptions($f);
    return $f;
  }, $flowers)) ?>;
</script>
