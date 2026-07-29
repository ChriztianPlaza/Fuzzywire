<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=presets');
    exit;
}
?>

<div class="admin-head">
  <div><h1>Bouquet Presets</h1><div class="sub">Add, edit, or remove ready-made arrangements</div></div>
  <button class="btn-admin" onclick="openBouquetModal()">+ Add Bouquet</button>
</div>
<table class="admin-table">
  <thead><tr><th>Image</th><th>Name</th><th>Price</th><th>Occasion</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($bouquets as $b): ?>
      <tr>
        <td>
          <?php if (!empty($b['image'])): ?>
            <img src="<?= htmlspecialchars($b['image']) ?>" width="50" height="50" style="object-fit:cover;border-radius:4px;">
          <?php else: ?>
            <span style="color:var(--ink-faint)">No image</span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($b['name']) ?></td>
        <td>₱<?= number_format($b['price'], 2) ?></td>
        <td><?= ucfirst(htmlspecialchars($b['occasion'])) ?></td>
        <td>
          <button class="btn-admin-ghost" onclick="openBouquetModal(<?= $b['id'] ?>)">Edit</button>
          <button class="btn-admin-danger" onclick="deleteBouquet(<?= $b['id'] ?>)">Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<script>
  window.ADMIN_BOUQUETS_DATA = <?= json_encode($bouquets) ?>;
</script>
