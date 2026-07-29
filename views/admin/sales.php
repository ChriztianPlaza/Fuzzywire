<?php
if (!defined('FUZZYWIRE')) {
    header('Location: ../../index.php?page=admin&section=sales');
    exit;
}

$paidOrders = array_filter($orders, function($o) {
    return ($o['payment_status'] ?? '') === 'verified';
});
$totalSales = array_sum(array_map(function($o) { return (float)$o['total']; }, $paidOrders));
$delivered = count(array_filter($orders, function($o) { return $o['status'] === 'delivered'; }));
$pendingPayments = count(array_filter($orders, function($o) { return ($o['payment_status'] ?? '') === 'pending verification'; }));
$statusCounts = [];
foreach ($orders as $o) {
    $status = $o['status'] ?: 'pending';
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
}
?>

<div class="admin-head">
  <div><h1>Sales Report</h1><div class="sub">Revenue, payments, and fulfillment at a glance</div></div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Verified Sales</div><div class="stat-value">&#8369;<?= number_format($totalSales, 2) ?></div><div class="stat-meta"><?= count($paidOrders) ?> paid orders</div></div>
  <div class="stat-card"><div class="stat-label">Delivered</div><div class="stat-value"><?= $delivered ?></div><div class="stat-meta">completed deliveries</div></div>
  <div class="stat-card"><div class="stat-label">Payment Checks</div><div class="stat-value"><?= $pendingPayments ?></div><div class="stat-meta">awaiting verification</div></div>
</div>

<div class="admin-section">
  <h2>Order Status</h2>
  <table class="admin-table">
    <thead><tr><th>Status</th><th>Orders</th></tr></thead>
    <tbody>
      <?php foreach ($statusCounts as $status => $count): ?>
        <tr><td><?= ucfirst(htmlspecialchars($status)) ?></td><td><?= $count ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
