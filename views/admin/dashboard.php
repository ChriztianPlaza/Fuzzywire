<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=dashboard');
    exit;
}

// Calculate stats using the arrays already fetched in index.php
 $totalFlowers = count($flowers);
 $pendingOrders = 0;
 $totalRevenue = 0;

foreach ($orders as $o) {
    if ($o['status'] === 'pending') $pendingOrders++;
    $totalRevenue += $o['total'];
}

 $lowStock = array_filter($flowers, function($f) {
    return $f['stock_count'] < 35 && $f['in_stock'] == 1;
});
usort($lowStock, function($a, $b) {
    return $a['stock_count'] - $b['stock_count'];
});

 $bestSellers = array_filter($flowers, function($f) {
    return $f['best_seller'] == 1;
});
usort($bestSellers, function($a, $b) {
    return $b['stock_count'] - $a['stock_count'];
});
 $bestSellers = array_slice($bestSellers, 0, 5);
?>

<div class="admin-head">
  <div><h1>Studio Dashboard</h1><div class="sub">A quiet morning look at the shop</div></div>
</div>
<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Total Stems</div><div class="stat-value"><?= $totalFlowers ?></div><div class="stat-meta">in catalog</div></div>
  <div class="stat-card"><div class="stat-label">Open Orders</div><div class="stat-value"><?= $pendingOrders ?></div><div class="stat-meta">awaiting prep</div></div>
  <div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">₱<?= number_format($totalRevenue, 0) ?></div><div class="stat-meta">gross</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
  <div class="admin-section">
    <h2>Low-stock alerts</h2>
    <div class="admin-section-sub">Restock soon to keep arrangements flowing</div>
    <?php if (empty($lowStock)): ?>
      <p style="font-size:0.88rem;color:var(--ink-soft);padding:14px 0">All stems are well-stocked.</p>
    <?php else: foreach ($lowStock as $ls): ?>
      <div class="low-stock">
        <div><span class="ls-name"><?= htmlspecialchars($ls['name']) ?></span></div>
        <div class="ls-count"><?= $ls['stock_count'] ?> left</div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div class="admin-section">
    <h2>Best-selling stems</h2>
    <div class="admin-section-sub">What's leaving the studio most often</div>
    <div class="best-seller-list">
      <?php if (empty($bestSellers)): ?>
        <p style="font-size:0.88rem;color:var(--ink-soft);padding:14px 0">No best sellers marked.</p>
      <?php else: foreach ($bestSellers as $i => $bs): ?>
        <div class="best-seller-item">
          <div class="rank"><?= $i+1 ?></div>
          <div class="mini-bloom"><?= flowerSVG($bs['shape'], $bs['color'], 32) ?></div>
          <div class="bs-info">
            <div class="bs-name"><?= htmlspecialchars($bs['name']) ?></div>
            <div class="bs-count"><?= $bs['stock_count'] ?> in stock</div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
