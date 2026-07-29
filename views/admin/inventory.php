<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=inventory');
    exit;
}
?>

<div class="admin-head">
  <div><h1>Flower Inventory</h1><div class="sub">Add, edit, and track every stem</div></div>
  <button class="btn-admin" onclick="openFlowerModal()">+ Add stem</button>
</div>
<table class="admin-table">
  <thead><tr><th>Image</th><th>Stem</th><th>Category</th><th>Price/stem</th><th>Stock</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($flowers as $f): ?>
      <tr>
        <td>
          <?php if (!empty($f['image'])): ?>
            <img src="<?= htmlspecialchars($f['image']) ?>" width="40" height="40" style="object-fit:cover;border-radius:4px;">
          <?php else: ?>
            <span style="color:var(--ink-faint)">No image</span>
          <?php endif; ?>
        </td>
        <td><div class="name-cell"><?= htmlspecialchars($f['name']) ?></div></td>
        <td><?= ucfirst(htmlspecialchars($f['category'])) ?></td>
        <td>₱<?= number_format($f['price_per_stem'],2) ?></td>
        <td><?= $f['stock_count'] ?></td>
        <td>
          <button class="btn-admin-ghost" onclick="openFlowerModal(<?= $f['id'] ?>)">Edit</button>
          <button class="btn-admin-danger" onclick="deleteFlower(<?= $f['id'] ?>)">Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<script>
  window.ADMIN_FLOWERS_DATA = <?= json_encode(array_map(function($f) {
    $f['color_options'] = parseColorOptions($f);
    return $f;
  }, $flowers)) ?>;
</script>
