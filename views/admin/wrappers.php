<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=wrappers');
    exit;
}
?>

<div class="admin-head">
  <div><h1>Wrappers</h1><div class="sub">Manage bouquet wrap options with photos or colors</div></div>
  <button class="btn-admin" onclick="openWrapperModal()">+ Add Wrapper</button>
</div>
<table class="admin-table">
  <thead><tr><th>Preview</th><th>Name</th><th>Style</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($wrappers as $w): ?>
      <tr>
        <td>
          <?php
            $colorOptions = parseItemColorOptions($w);
            $firstImage = $colorOptions[0]['image'] ?? '';
          ?>
          <?php if (!empty($firstImage)): ?>
            <img src="<?= htmlspecialchars($firstImage) ?>" width="40" height="40" style="object-fit:cover;border-radius:50%;">
          <?php else: ?>
            <span class="admin-color-preview" style="margin:0">
              <?php foreach ($colorOptions as $opt): ?>
                <span class="admin-color-dot" style="background: <?= htmlspecialchars($opt['color']) ?>" title="<?= htmlspecialchars($opt['name']) ?>"></span>
              <?php endforeach; ?>
            </span>
          <?php endif; ?>
        </td>
        <td><div class="name-cell"><?= htmlspecialchars($w['name']) ?></div></td>
        <td><?= ucfirst(htmlspecialchars($w['style'])) ?></td>
        <td><?= $w['in_stock'] ? 'In stock' : 'Hidden' ?></td>
        <td>
          <button class="btn-admin-ghost" onclick="openWrapperModal(<?= $w['id'] ?>)">Edit</button>
          <button class="btn-admin-danger" onclick="deleteWrapper(<?= $w['id'] ?>)">Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<script>
  window.ADMIN_WRAPPERS_DATA = <?= json_encode($wrappers) ?>;
</script>
